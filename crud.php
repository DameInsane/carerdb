<?php

class CrudForm {
	public $component;
	public $config;
	public $gateway;
	
	public function __construct ($component, $config, $gateway) {
		$this->component = $component;
		$this->config    = $config;
		$this->gateway   = $gateway;
	}

	public function handle_item ($id, $parts, $get, $post, $cookie) {
		$component = $this->component;
		
		if (!preg_match('/^[a-z][a-z0-9_]*$/', $component)) {
			return $this->not_found("No table called '$component'");
		}

		if (!preg_match('/^[0-9\.]+$/', $id)) {
			return $this->not_found("No item found in '$component' table with key $id");
		}

		$dbh  = $this->config->database;
		$keys = $dbh->get_pkey($component);
		
		if (!count($keys)) {
			return $this->gateway->not_found("No table called '$component'");
		}
		
		$order = join(", ", $keys);
		$where = array();
		foreach ($keys as $k) {
			$where []= "$k=?";
		}
		$where = join(" AND ", $where);
		$values = explode('.', $id);
		
		$query = "SELECT * FROM $component WHERE $where ORDER BY $order";
		$sth = $dbh->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute($values);

		$out = '';
		
		if ($get['edit'])
			$out .= sprintf("<form method=\"post\" action=\"%s\">\n", htmlspecialchars($this->config->path("$component/$id")));
		
		if ($row = $sth->fetch()) {
			if ($post['action'] === 'update') {
				$f_set     = array();
				$v_set     = array();
				$f_where   = array();
				$v_where   = array();
				foreach($row as $k => $v) {
					if (in_array($k, $keys)) {
						$f_where []= "$k=?";
						$v_where []= $v;
					}
					else {
						$f_set []= "$k=?";
						$v_set []= $post[$k];
					}
				}
				$f_set = join(',', $f_set);
				$f_where = join(' AND ', $f_where);
				
				$sth = $dbh->prepare("UPDATE $component SET $f_set WHERE $f_where");
				$sth->execute(array_merge($v_set, $v_where));
				
				return $this->gateway->redirect("$component/$id");
			}

			$out .= sprintf("<h1>%s %s</h1>\n", htmlspecialchars($component), htmlspecialchars($id));
			$out .= sprintf("<p><code>%s</code></p>\n", htmlspecialchars($query));
			$out .= "<table class=\"table\">\n";
			foreach($row as $k => $v) {
				if ($get['edit']) {
					if (in_array($k, $keys)) {
						$out .= sprintf(
							"<tr><th>%s</th><td>%s<input type=\"hidden\" name=\"%s\" value=\"%s\" /></td></tr>\n",
							htmlspecialchars($k),
							nl2br(htmlspecialchars($v)),
							htmlspecialchars($k),
							htmlspecialchars($v)
						);
					}
					else {
						$out .= sprintf(
							"<tr><th>%s</th><td><input class=\"form-control\" type=\"text\" name=\"%s\" value=\"%s\" /></td></tr>\n",
							htmlspecialchars($k),
							htmlspecialchars($k),
							htmlspecialchars($v)
						);
					}
				}
				else {
					$out .= sprintf("<tr><th>%s</th><td>%s</td></tr>\n", htmlspecialchars($k), nl2br(htmlspecialchars($v)));
				}
			}
			$out .= "</table>\n";
			
			$out .= "<div>\n";
			if ($get['edit']) {
				$out .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Save\" />\n";
				$out .= sprintf("<a class=\"btn btn-danger\" href=\"%s\">Cancel</a>\n", $this->config->path("$component/$id"));
			}
			else {
				$out .= sprintf("<a class=\"btn btn-default\" href=\"%s?edit=1\">Edit item</a>\n", $this->config->path("$component/$id"));
			}
			$out .= sprintf("<a class=\"btn btn-default\" href=\"%s\">Back to index</a>\n", $this->config->path($component));
			$out .= "</div>\n";
			
			if ($get['edit']) {
				$out .= "<input type=\"hidden\" name=\"action\" value=\"update\" />\n";
				$out .= "</form>\n";
			}
			
			return $this->gateway->respond(
				sprintf("Database row: %s %s", htmlspecialchars($component), htmlspecialchars($id)),
				$out
			);
		}
		else {
			return $this->gateway->not_found("No item found in '$component' table with key $id");
		}
	}

	public function handle_index ($parts, $get, $post, $cookie) {
		$component = $this->component;
		
		if (!preg_match('/^[a-z][a-z0-9_]*$/', $component)) {
			return $this->gateway->not_found("No table called '$component'");
		}

		$dbh  = $this->config->database;
		$keys = $dbh->get_pkey($component);
		
		if (!count($keys)) {
			return $this->gateway->not_found("No table called '$component'");
		}
		
		$order = join(", ", $keys);
		$cols  = $order;
		$extra = $dbh->get_label_cols($component);
		if ($extra) {
			$cols .= "," . join(", ", $extra);
		}
		
		$query = "SELECT $cols FROM $component ORDER BY $order";
		$sth = $dbh->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute($values);
		
		$uri = $this->config->path("$component/");
		
		$out = sprintf("<h1>%s (index)</h1>\n", htmlspecialchars($component));
		$out .= sprintf("<p><code>%s</code></p>\n", htmlspecialchars($query));
		$out .= "<ul>\n";
		while ($row = $sth->fetch()) {
			foreach($row as $k => $v) {
				$id = array();
				foreach ($keys as $x) {
					$id []= $row[$x];
				}
				$id = join(".", $id);
			}
			if ($extra) {
				$extrainfo = array();
				foreach ($extra as $k) {
					$extrainfo []= htmlspecialchars($row[$k]);
				}
				$extrainfo = join('; ', $extrainfo);
				$out .= sprintf("<li><a href=\"%s\">%s</a> [%s]</li>\n", htmlspecialchars($uri.$id), htmlspecialchars($id), $extrainfo);
			}
			else {
				$out .= sprintf("<li><a href=\"%s\">%s</a></li>\n", htmlspecialchars($uri.$id), htmlspecialchars($id));
			}
		}
		$out .= "</ul>\n";
		
		return $this->gateway->respond(
			sprintf("Database row: %s %s", htmlspecialchars($component), htmlspecialchars($id)),
			$out
		);
	}
}
