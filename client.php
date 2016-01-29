<?php

require_once 'form.php';

class ClientForm {
	public $config;
	public $gateway;
	public $admin;
	
	protected static function _default_date ($input, $default) {
		if (!empty($input)) {
			$date = strtotime($input);
			if ($date !== false) return $date;
		}
		return $default;
	}
	
	public static function blank_client_data () {
		return array(
			'id'           => '0',
			'name'         => '',
			'passphrase'   => '',
			'address'      => '',
			'phone'        => '',
			'next_of_kin'  => '',
			'next_of_kin_phone' => '',
			'doctors'      => '',
			'nurses'       => '',
			'notes'        => ''
		);
	}
	
	public function __construct ($admin, $config, $gateway) {
		$this->admin     = $admin;
		$this->config    = $config;
		$this->gateway   = $gateway;
	}

	public function handle_list ($get, $post, $cookie) {
		$admin = $this->admin;
	
		## TODO: $admin can view all; otherwise just view mine
		$query = "SELECT id,name,created FROM client ORDER BY id";
		$sth = $this->config->database->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array());
		
		$out = '<table class="table sortable">'."\n";
		$out .= "<thead>\n";
		$out .= "<tr>\n";
		$out .= "<th>ID</th>\n";
		$out .= "<th>Name</th>\n";
		$out .= "<th>Created</th>\n";
		$out .= "</tr>\n";
		$out .= "</thead>\n";
		$out .= "<tbody>\n";
		while ($row = $sth->fetch()) {
			$url  = htmlspecialchars($this->config->path("client/".(int)$row['id']));
			$out .= "<tr>";
			$out .= sprintf("<td><a href=\"%s\">%d</a></td>", $url, $row['id']);
			$out .= sprintf("<td><a href=\"%s\">%s</a></td>", $url, htmlspecialchars($row['name']));
			$out .= sprintf("<td data-dateformat=\"DD-MM-YYYY\">%s</td>", date('d-m-Y', $row['id']));
			$out .= "</tr>";
		}
		$out .= "</tbody>\n";
		$out .= "</table>\n";
		
		return $this->gateway->respond('Client List', $out);
	}
	
	public function handle_edit ($id, $data) {
	
		$is_new = false;
		$fields = array_keys( self::blank_client_data() );
		$query  = '';
		$values = array();
		
		if ($id === 0 || $id === '0' || $id === 'new') {
			$is_new = true;
			$query  = array();
			$places = array();
			foreach ($fields as $f) {
				if ($f === 'id') continue;
				$query  []= $f;
				$values []= $data[$f];
				$places []= '?';
			}
			$query  []= 'created';
			$places []= '?';
			$values []= date('Y-m-d H:i:s');
			$query = sprintf(
				'INSERT INTO client (%s) VALUES (%s)',
				join(', ', $query),
				join(', ', $places)
			);
		}
		
		else {
			$query = array();
			foreach ($fields as $f) {
				if ($f === 'id') continue;
				$query  []= "$f=?";
				$values []= $data[$f];
			}
			$query = sprintf(
				'UPDATE client SET %s WHERE id=?',
				join(', ', $query)
			);
			$values []= $id;
		}
		
		$sth = $this->config->database->prepare($query);
		$sth->execute($values);
		
		if ($is_new) {
			$id = $this->dbh->lastInsertId();
		}
		
		$sth = $this->config->database->prepare('DELETE FROM client_form WHERE client_id=?');
		$sth->execute(array($id));

		$sth = $this->config->database->prepare('INSERT INTO client_form (client_id, form_id) VALUES (?, ?)');
		foreach ($data as $k => $v) {
			if (!$v) continue;
			if (preg_match('/^form_(\d+)$/', $k, $matches)) {
				$sth->execute(array($id, $matches[1]));
			}
		}

		$sth = $this->config->database->prepare('DELETE FROM user_client WHERE client_id=?');
		$sth->execute(array($id));

		$sth = $this->config->database->prepare('INSERT INTO user_client (client_id, user_id, start_date, end_date, notes) VALUES (?, ?, ?, ?, ?)');
		foreach ($data as $k => $v) {
			if (!$v) continue;
			if (preg_match('/^user_(\d+)$/', $k, $matches)) {
				$user_id = $matches[1];
				$start   = self::_default_date($data["user_${user_id}_start_date"], strtotime('today'));
				$end     = self::_default_date($data["user_${user_id}_end_date"], strtotime('2038-01-01'));
				$notes   = empty($data["user_${user_id}_notes"]) ? '' : $data["user_${user_id}_notes"];
				$sth->execute(array($id, $user_id, date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end), $notes));
			}
		}

		return $id;
	}
	
	public function handle_client ($id, $get, $post, $cookie) {
		$admin = $this->admin;
		if ($get['edit'] && !$admin) return $this->gateway->error_message('Must be an administrator.');
	
		## TODO: check permission for this user and client
		
		if ($post['action']==='client') {
			$real_id = $this->handle_edit($id, $post);
			return $this->gateway->redirect("client/$real_id");
		}
		
		$query = "SELECT * FROM client WHERE id=?";
		$sth = $this->config->database->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($id));
		$client = $sth->fetch();
		
		if ($id === 'new') {
			$client = self::blank_client_data();
		}
		
		$out = '';
		$edit = false;
		if ($get['edit'] || $id=='new') {
			$out .= "<form method=\"post\" action=\"\">\n";
			$edit = true;
		}

		$out .= "<div class='row'>\n";
		$out .= "<div class='col-sm-12'>\n";
		$out .= sprintf("<h1>%s</h1>\n", htmlspecialchars($client['name']));
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class='row'>\n";
		$out .= "<div class='col-sm-3'>".$this->make_menu($id,$form_id)."</div>\n";
		$out .= "<div class='col-sm-6'>\n";
		$out .= $this->make_details_table($client, $edit);
		$out .= "</div>\n";
		$out .= "<div class='col-sm-3'>\n";
		
		$out .= "<div class=\"panel panel-info\">\n";
		$out .= "<div class=\"panel-heading\"><h3 class=\"panel-title\">Users</h3></div>\n";
		$out .= "<div class=\"panel-body\" style=\"max-height:24em;overflow-y:auto\">\n";
		$out .= $this->make_user_list($client, $edit);
		$out .= "</div>\n";
		$out .= "</div>\n";
		
		$out .= "<div class=\"panel panel-info\">\n";
		$out .= "<div class=\"panel-heading\"><h3 class=\"panel-title\">Forms</h3></div>\n";
		$out .= "<div class=\"panel-body\" style=\"max-height:24em;overflow-y:auto\">\n";
		$out .= $this->make_form_list($client, $edit);
		$out .= "</div>\n";
		$out .= "</div>\n";
		
		$out .= "</div>\n";
		$out .= "</div>\n";

		if ($edit) {
			$out .= "<input type=\"hidden\" name=\"action\" value=\"client\" >\n";
			$out .= "</form>\n";
		}

		return $this->gateway->respond($id==='new' ? 'New Client' : $client['name'], $out);
	}
	
	public function handle_form ($id, $form_id, $get, $post, $cookie) {
		if ($form_id === '0' || $form_id === '' || is_null($form_id))
			return $this->handle_client($id, $get, $post, $cookie);
		
		$admin = $this->admin;
		
		## TODO: check permission
		$query = "SELECT * FROM client WHERE id=?";
		$sth = $this->config->database->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($id));
		$client = $sth->fetch();

		$form = new Form($this->config->database, $form_id, $id);

		if ($post['action']==='note') {
			$post['user_id'] = $_SESSION['user_id'];
			$ret = $form->add_note($post);
			if ($ret) {
				return $this->gateway->error_message($ret);
			}
			return $this->gateway->redirect("client/$id/$form_id");
		}
		elseif ($post['action']==='submit') {
			$post['user_id'] = $_SESSION['user_id'];
			$ret = $form->add_submission($post);
			if ($ret) {
				return $this->gateway->error_message($ret);
			}
			return $this->gateway->redirect("client/$id/$form_id");
		}
		
		$title = $client['name']." :: ".$form->title;
			
		$out = '';
		$out .= "<div class='row'>\n";
		$out .= "<div class='col-sm-9'>\n";
		$out .= sprintf("<h1>%s</h1>\n", htmlspecialchars($client['name']));
		$out .= "</div>\n";
		$out .= "<div class='col-sm-3'>\n";
		$out .= sprintf("<p>%s</p>\n", nl2br(htmlspecialchars($client['doctors'])));
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class='row'>\n";
		$out .= "<div class='col-sm-3'>".$this->make_menu($id,$form_id)."</div>\n";
		$out .= "<div class='col-sm-9'>\n";
		$out .= sprintf("<h2>%s</h2>\n", htmlspecialchars($form->title));
		$out .= $form->render_intro();
		$out .= "<div class=\"panel panel-primary\">\n";
		$out .= "<div class=\"panel-heading\"><h3 class=\"panel-title\">Records</h3></div>\n";
		$out .= "<div class=\"panel-body\">\n";
		$out .= $form->render_submissions();
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"panel panel-info\">\n";
		$out .= "<div class=\"panel-heading\"><h3 class=\"panel-title\">Messages</h3></div>\n";
		$out .= "<div class=\"panel-body\">\n";
		$out .= $form->render_notes();
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		
		return $this->gateway->respond($title, $out);
	}
	
	protected function make_menu ($id, $form_id=0) {
		## TODO: check permission
		$query = "SELECT f.id,f.title,f.slug,f.is_checklist,f.description FROM form f INNER JOIN client_form cf ON cf.form_id=f.id WHERE cf.client_id=? ORDER BY f.sort_order,f.title";
		$sth = $this->config->database->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($id));
		
		$out = "<ul role=\"navigation\" class=\"nav nav-pills nav-stacked\">\n";
		
		$cls = '';
		if ($form_id === '0' || $form_id === '' || is_null($form_id))
			$cls = ' class="active"';
		$out .= sprintf(
			"<li%s><a href=\"%s\">Client Details</a></li>\n",
			$cls,
			htmlspecialchars($this->config->path("client/".$id))
		);
		
		while ($row = $sth->fetch()) {
			$cls = '';
			if ($row['id']==$form_id || $row['slug']==$form_id)
				$cls = ' class="active"';
			
			$out .= sprintf(
				"<li%s><a href=\"%s\" title=\"%s\">%s%s</a></li>\n",
				$cls,
				htmlspecialchars($this->config->path("client/".(int)$id."/".$row['slug'])),
				empty($row['description']) ? htmlspecialchars($row['title']) : htmlspecialchars($row['title'] . " :: " . $row['description']),
				htmlspecialchars($row['title']),
				$row['is_checklist']?'<span class="pull-right glyphicon glyphicon-list-alt"></span>':''
			);
		}
		$out .= "</ul>\n";
		
		return $out;
	}
	
	protected function make_user_list ($client_data, $editable) {
	
		if ($editable) {
			$query = "SELECT u.id,u.name,cu.* FROM user u LEFT JOIN user_client cu ON u.id=cu.user_id AND cu.client_id=? ORDER BY u.name";
			$sth = $this->config->database->prepare($query);
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			$sth->execute(array($client_data['id']));
			
			$out = "";
			while ($row = $sth->fetch()) {
				$ctrl = "user_${row['id']}";
				$out .= "<div class=\"row\">";
				$out .= "<div class=\"col-sm-12\">";
				$out .= sprintf("<h4>%s</h4>", htmlspecialchars($row['name']));
				$out .= "</div>";
				$out .= "</div>";
				$out .= "<div class=\"form-group\">";
				$out .= "<input class=\"shelf-toggle\" data-shelf=\"${ctrl}_shelf\" type=\"checkbox\" value=\"1\" id=\"${ctrl}\" name=\"${ctrl}\" ".($row['user_id'] ? 'checked="checked"' : '')." /> ";
				$out .= "<label for=\"${ctrl}\">Grant access</label>";
				$out .= "</div>";
				$out .= "<div id=\"${ctrl}_shelf\"\">";
				$out .= "<div class=\"form-group row\">";
				$out .= "<div class=\"col-sm-6\">";
				$out .= "<label for=\"${ctrl}_start_date\">Start</label>";
				$out .= "<input class=\"form-control\" data-datetime-format=\"YYYY-MM-DD\" value=\"".$row['start_date']."\" id=\"${ctrl}_start_date\" name=\"${ctrl}_start_date\" />";
				$out .= "</div>";
				$out .= "<div class=\"col-sm-6\">";
				$out .= "<label for=\"${ctrl}_end_date\">End</label>";
				$out .= "<input class=\"form-control\" data-datetime-format=\"YYYY-MM-DD\" value=\"".$row['end_date']."\" id=\"${ctrl}_end_date\" name=\"${ctrl}_end_date\" />";
				$out .= "</div>";
				$out .= "</div>";
				$out .= "<div class=\"form-group\">";
				$out .= "<label for=\"${ctrl}_notes\">Access notes</label>";
				$out .= "<input class=\"form-control\" value=\"".htmlspecialchars($row['notes'])."\" id=\"${ctrl}_notes\" name=\"${ctrl}_notes\" />";
				$out .= "</div>";
				$out .= "</div>";
			}
			$out .= "";
			
			return $out;
		}
	
		$query = "SELECT u.id,u.name FROM user_client cu INNER JOIN user u ON u.id=cu.user_id WHERE cu.client_id=? AND CURRENT_TIMESTAMP() BETWEEN cu.start_date AND cu.end_date ORDER BY cu.start_date";
		$sth = $this->config->database->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($client_data['id']));
		
		$out = "<ul class=\"list-unstyled\">\n";
		while ($row = $sth->fetch()) {
			$out .= sprintf("<li>%s</li>\n", htmlspecialchars($row['name']));
		}
		$out .= "</ul>\n";
		return $out;
	}
	
	protected function make_form_list ($client_data, $editable) {

		if ($editable) {
		
			if ($client_data['id']) {
				$query = "SELECT f.id,f.title,cf.client_id FROM form f LEFT JOIN client_form cf ON f.id=cf.form_id AND cf.client_id=? ORDER BY f.sort_order, f.title";
				$sth = $this->config->database->prepare($query);
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				$sth->execute(array($client_data['id']));
			}
			else {
				$query = "SELECT f.id,f.title,f.is_default AS client_id FROM form f ORDER BY f.sort_order, f.title";
				$sth = $this->config->database->prepare($query);
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				$sth->execute();
			}
			
			$out = "";
			while ($row = $sth->fetch()) {
				$ctrl = "form_${row['id']}";
				$chkd = '';
				if ($row['client_id']) $chkd = 'checked="checked"';
				$out .= "<div class=\"form-group\">";
				$out .= "<input type=\"checkbox\" value=\"1\" id=\"${ctrl}\" name=\"${ctrl}\" $chkd />";
				$out .= " <label style=\"font-weight:normal\" for=\"${ctrl}\">".htmlspecialchars($row['title'])."</label>";
				$out .= "</div>";
			}
			$out .= "";
			
			return $out;
		}	

		$query = "SELECT f.id,f.title FROM client_form cf INNER JOIN form f ON f.id=cf.form_id WHERE cf.client_id=? ORDER BY f.sort_order,f.title";
		$sth = $this->config->database->prepare($query);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($client_data['id']));
		
		$out = "<ul class=\"list-unstyled\">\n";
		while ($row = $sth->fetch()) {
			$out .= sprintf("<li>%s</li>\n", htmlspecialchars($row['title']));
		}
		$out .= "</ul>\n";
		return $out;
	}
	
	protected function make_details_table ($client_data, $editable) {
		## TODO: check permission
		
		$label = array(
			'id'                   => 'ID',
			'next_of_kin'          => 'Next of Kin',
			'next_of_kin_phone'    => 'Next of Kin Phone',
		);
		
		$keys = array('id','created');  # created is not really a key, but ho hum
		
		$out = "<table class=\"table\">\n";
		
		foreach ($client_data as $k => $v) {
		
			$l = empty($label[$k]) ? ucfirst($k) : $label[$k];
			
			if ($editable) {
				if (in_array($k, $keys)) {
					$out .= sprintf(
						"<tr><th>%s</th><td>%s<input type=\"hidden\" name=\"%s\" value=\"%s\" /></td></tr>\n",
						htmlspecialchars($l),
						nl2br(htmlspecialchars($v)),
						htmlspecialchars($k),
						htmlspecialchars($v)
					);
				}
				elseif (in_array($k, array('doctors', 'nurses', 'notes'))) {
					$out .= sprintf(
						"<tr><th>%s</th><td><textarea rows=\"3\" cols=\"40\" class=\"form-control\" type=\"text\" name=\"%s\">%s</textarea></td></tr>\n",
						htmlspecialchars($l),
						htmlspecialchars($k),
						htmlspecialchars($v)
					);
				}
				else {
					$out .= sprintf(
						"<tr><th>%s</th><td><input class=\"form-control\" type=\"text\" name=\"%s\" value=\"%s\" /></td></tr>\n",
						htmlspecialchars($l),
						htmlspecialchars($k),
						htmlspecialchars($v)
					);
				}
			}
			else {
				$out .= sprintf("<tr><th>%s</th><td>%s</td></tr>\n", htmlspecialchars($l), nl2br(htmlspecialchars($v)));
			}
		}
		
		$out .= "</table>\n";
		
		if ($this->admin) {
			if ($editable)  {
				$out .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Save\" />\n";
				$out .= sprintf("<a class=\"btn btn-danger\" href=\"%s\">Cancel</a>\n", $this->config->path("client/".$client_data['id']));
			}
			else {
				$out .= sprintf("<div><a class=\"btn btn-default\" href=\"%s?edit=1\">Edit details</a></div>\n", $this->config->path("client/".($client_data["id"]?$client_data["id"]:'new')));
			}
		}

		return $out;
	}
}
