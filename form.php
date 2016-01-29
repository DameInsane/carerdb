<?php

require_once 'field.php';
require_once 'crud.php';

class FormManager {
	public $config;
	public $gateway;
	public $admin;
	
	public function __construct ($admin, $config, $gateway) {
		$this->admin     = $admin;
		$this->config    = $config;
		$this->gateway   = $gateway;
	}
	
	public function handle_list ($parts, $get, $post, $cookie) {
		$out = "<h1>Forms List</h1>\n";
		$out .= "<table class=\"table table-striped sortable\">\n";
		$out .= "<thead>\n";
		$out .= "<th>ID</th>\n";
		$out .= "<th>Slug</th>\n";
		$out .= "<th>Title</th>\n";
		$out .= "<th>Fields</th>\n";
		$out .= "<th>Checklist?</th>\n";
		$out .= "<th>Notes?</th>\n";
		$out .= "<th data-defaultsort=\"asc\">Sort Order</th>\n";
		$out .= "<th>Created</th>\n";
		$out .= "<th data-defaultsort=\"disabled\">Actions</th>\n";
		$out .= "</thead>\n";
		$out .= "<tbody>\n";
		
		$sth = $this->config->database->prepare('
			SELECT
				f.id,
				f.slug,
				f.sort_order,
				f.title,
				f.is_checklist,
				f.has_notes,
				f.created,
				(SELECT COUNT(*) FROM field fd WHERE fd.form_id=f.id) AS field_count
			FROM form f
			ORDER BY f.sort_order,f.title,f.id
		');
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute();
		while ($row = $sth->fetch()) {
			$out .= "<tr>\n";
			$out .= sprintf("<td>%d</td>\n", $row['id']);
			$out .= sprintf("<td>%s</td>\n", htmlspecialchars($row['slug']));
			$out .= sprintf("<td>%s</td>\n", htmlspecialchars($row['title']));
			$out .= sprintf("<td>%d</td>\n", $row['field_count']);
			$out .= sprintf("<td>%s</td>\n", $row['is_checklist'] ? '<span class="yes">Y</span>' : '<span class="no">N</span>');
			$out .= sprintf("<td>%s</td>\n", $row['has_notes'] ? '<span class="yes">Y</span>' : '<span class="no">N</span>');
			$out .= sprintf("<td>%d</td>\n", $row['sort_order']);
			$out .= sprintf("<td>%s</td>\n", $row['created']);
			$out .= sprintf(
				"<td><a class=\"btn btn-primary btn-xs\" href=\"%s\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;Edit</a>&nbsp;<a class=\"btn btn-primary btn-xs\" href=\"%s\"><i class=\"glyphicon glyphicon-list-alt\"></i>&nbsp;Fields</a>&nbsp;<a class=\"btn btn-default btn-xs\" href=\"%s\"><i class=\"glyphicon glyphicon-share\"></i>&nbsp;Clone</a>&nbsp;<a class=\"btn btn-danger btn-xs\" href=\"%s\"><i class=\"glyphicon glyphicon-trash\"></i>&nbsp;Delete</a></td>\n",
				htmlspecialchars($this->config->path('form/'.$row['id'].'/edit')),
				htmlspecialchars($this->config->path('form/'.$row['id'].'/field')),
				htmlspecialchars($this->config->path('form/'.$row['id'].'/clone')),
				htmlspecialchars($this->config->path('form/'.$row['id'].'/delete'))
			);
			$out .= "</tr>\n";
		}
		
		$out .= "</tbody>\n";
		$out .= "</table>\n";
		
		return $this->gateway->respond('Forms', $out);
	}
	
	public function handle_field_list ($form, $get, $post, $cookie) {
		$out = "<h1>Field List</h1>\n";
		$out .= "<table class=\"table table-striped sortable\">\n";
		$out .= "<thead>\n";
		$out .= "<th>ID</th>\n";
		$out .= "<th>Slug</th>\n";
		$out .= "<th>Label</th>\n";
		$out .= "<th>Type</th>\n";
		$out .= "<th data-defaultsort=\"asc\">Sort Order</th>\n";
		$out .= "<th data-defaultsort=\"disabled\">Actions</th>\n";
		$out .= "</thead>\n";
		$out .= "<tbody>\n";
		
		$type_names = Field::known_field_types();
		
		foreach ($form->fields as $f) {
			if ($f->id < 1) continue;
			$out .= "<tr>\n";
			$out .= sprintf("<td>%d</td>\n", $f->id);
			$out .= sprintf("<td>%s</td>\n", htmlspecialchars($f->slug));
			$out .= sprintf("<td>%s</td>\n", htmlspecialchars($f->label));
			$out .= sprintf("<td>%s</td>\n", htmlspecialchars($type_names[$f->type]));
			$out .= sprintf("<td>%d</td>\n", $f->sort_order);
			$out .= sprintf(
				"<td><a class=\"btn btn-primary btn-xs\" href=\"%s\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;Edit</a>&nbsp;<a class=\"btn btn-danger btn-xs\" href=\"%s\"><i class=\"glyphicon glyphicon-trash\"></i>&nbsp;Delete</a></td>\n",
				htmlspecialchars($this->config->path('form/'.$form->id.'/field/'.$f->id.'/edit')),
				htmlspecialchars($this->config->path('form/'.$form->id.'/field/'.$f->id.'/delete'))
			);
			$out .= "</tr>\n";
		}
		
		$out .= "</tbody>\n";
		$out .= "</table>\n";
		
		$out .= sprintf(
			"<p><a class=\"btn btn-primary\" href=\"%s\">New Field</a></p>\n",
			htmlspecialchars($this->config->path('form/'.$form->id.'/field/0/edit'))
		);
		
		return $this->gateway->respond(
			sprintf('Field Manager - %s', htmlspecialchars($form->title)),
			$out
		);
	}

	public function handle_form_editor_post ($form_id, $post) {
	
		$dbh = $this->config->database;
	
		$tablename = "submission_form_" . (int)$form->id;
		$sth = $dbh->prepare("
			CREATE TABLE $tablename (
				submission_id INTEGER NOT NULL REFERENCES submission(id),
				PRIMARY_KEY submission_id
			)
		");
		$sth->execute(array());
		
		$cols = array(
			'slug', 'title', 'sort_order', 'description', 'is_checklist',
			'is_default', 'has_notes', 'intro_html', 'notes'
		);
		
		if ($form_id > 0) {
			$f_set     = array();
			$v_set     = array();
			$f_where   = array('id=?');
			$v_where   = array($form_id);
			foreach ($cols as $k) {
				$f_set []= "$k=?";
				$v_set []= empty($post[$k]) ? null : $post[$k];
			}
			$f_set = join(',', $f_set);
			$f_where = join(' AND ', $f_where);
			
			$sth = $dbh->prepare("UPDATE form SET $f_set WHERE $f_where");
			$sth->execute(array_merge($v_set, $v_where));
			return;
		}
		
		$f_set     = array();
		$v_set     = array();
		$f_marks   = array();
		foreach ($cols as $k) {
			if (empty($post[$k])) continue;
			$f_set   []= "$k";
			$f_marks []= "?";
			$v_set   []= $post[$k];
		}
		$f_set   = join(',', $f_set);
		$f_marks = join(',', $f_marks);
		
		$sth = $dbh->prepare("INSERT INTO form ($f_set) VALUES ($f_marks)");
		$sth->execute($v_set);
	}


	public function handle_field_editor_post ($form, $field, $post) {
	
		$dbh = $this->config->database;
	
		$cols = array(
			'type', 'sort_order', 'label', 'help_text', 'default_value',
			'enumeration', 'regex', 'minimum', 'maximum', 'format',
			'minimum_length', 'maximum_length'
		);
		
		if ($field->id > 0) {
			$f_set     = array();
			$v_set     = array();
			$f_where   = array('id=?');
			$v_where   = array($field->id);
			foreach ($cols as $k) {
				$f_set []= "$k=?";
				$v_set []= empty($post[$k]) ? null : $post[$k];
			}
			$f_set = join(',', $f_set);
			$f_where = join(' AND ', $f_where);
			
			$sth = $dbh->prepare("UPDATE field SET $f_set WHERE $f_where");
			$sth->execute(array_merge($v_set, $v_where));
			return;
		}
		
		$cols[] = 'slug';
		
		$f_set     = array('id', 'form_id');
		$v_set     = array(0, $form->id);
		$f_marks   = array("?", "?");
		foreach ($cols as $k) {
			if (empty($post[$k])) continue;
			$f_set   []= "$k";
			$f_marks []= "?";
			$v_set   []= $post[$k];
		}
		$f_set   = join(',', $f_set);
		$f_marks = join(',', $f_marks);
		
		$sth = $dbh->prepare("INSERT INTO field ($f_set) VALUES ($f_marks)");
		$sth->execute($v_set);

		$tablename = "submission_form_" . (int)$form->id;
		
		$sth = $dbh->prepare("
			CREATE TABLE $tablename (
				submission_id INTEGER NOT NULL REFERENCES submission(id),
				PRIMARY_KEY submission_id
			)
		");
		$sth->execute(array());  # this might fail if table already exists, but that's OK!
		
		$sth = $dbh->prepare("ALTER TABLE $tablename ADD COLUMN ".$post['slug']." TEXT");
		$sth->execute(array());
		
		return;
	}
	
	public function handle_field_trash_post ($form, $field, $post) {
		$dbh = $this->config->database;
		$sth = $dbh->prepare("DELETE FROM field WHERE id=? AND form_id=?");
		$sth->execute(array($field->id, $form->id));
		return;
	}
	
	public function handle_field_trash ($form, $field_id, $get, $post, $cookie) {
		$field = new Field ();
		foreach ($form->fields as $f) {
			if ($f->id == $field_id) {
				$field = $f;
				break;
			}
		}
		
		if ($post['action']==='delete_field') {
			$this->handle_field_trash_post($form, $field, $post);
			return $this->gateway->redirect("form/".$form->id."/field");
		}

		$title = sprintf('Form Editor :: %s :: %s', htmlspecialchars($form->title), htmlspecialchars($field->label ? $field->label : "New Field"));

		$out = "<h1>$title</h1>";
		$out .= "<form action=\"\" method=\"post\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"checkbox\" id=\"action\" name=\"action\" value=\"delete_field\" /> ";
		$out .= "<label for=\"action\">Really delete?</label>\n";
		$out .= "<p class=\"help-block\">This field will no longer be available for people to fill in, and any existing data stored in it may become inaccessible.</p>\n";
		$out .= "</div>\n";				
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"submit\" class=\"btn btn-danger btn-lg\" value=\"Delete\" /> ";
		$out .= "<a class=\"btn btn-default btn-lg\" href=\"javascript:window.history.back()\">Back</a>";
		$out .= "</div>\n";		
		$out .= "</form>\n";
		
		return $this->gateway->respond($title,	$out);
	}
	
	public function handle_form_trash_post ($form, $post) {
		$dbh = $this->config->database;
		$tablename = "submission_form_" . (int)$form->id;
		
		$sth = $dbh->prepare("DELETE FROM field WHERE form_id=?");
		$sth->execute(array($form->id));
		$sth = $dbh->prepare("DELETE FROM client_form WHERE form_id=?");
		$sth->execute(array($form->id));
		$sth = $dbh->prepare("DELETE FROM submission WHERE form_id=?");
		$sth->execute(array($form->id));
		$sth = $dbh->prepare("DELETE FROM note WHERE form_id=?");
		$sth->execute(array($form->id));
		$sth = $dbh->prepare("DELETE FROM form WHERE id=?");
		$sth->execute(array($form->id));
		$sth = $dbh->prepare("DROP TABLE $tablename");
		$sth->execute(array());
		return;
	}

	public function handle_form_trash ($form, $get, $post, $cookie) {
		if ($post['action']==='delete_form') {
			$this->handle_form_trash_post($form, $post);
			return $this->gateway->redirect('form');
		}

		$title = sprintf('Form Editor :: %s', htmlspecialchars($form->title));

		$out = "<h1>$title</h1>";
		$out .= "<form action=\"\" method=\"post\">\n";
		$out .= "<div class=\"alert alert-danger\" role=\"alert\"><strong>Danger!</strong> This form and all data ever submitted on it will be destroyed.</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"checkbox\" id=\"action\" name=\"action\" value=\"delete_form\" /> ";
		$out .= "<label for=\"action\">Really delete?</label>\n";
		$out .= "</div>\n";				
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"submit\" class=\"btn btn-danger btn-lg\" value=\"Delete\" /> ";
		$out .= "<a class=\"btn btn-default btn-lg\" href=\"javascript:window.history.back()\">Back</a>";
		$out .= "</div>\n";		
		$out .= "</form>\n";
		
		return $this->gateway->respond($title,	$out);
	}

	public function handle_form_clone_post ($form, $post) {
		$dbh = $this->config->database;
		
		$new_id = -1;
		$sth = $dbh->prepare("SELECT MAX(id) AS id FROM form");
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array());
		if ($row = $sth->fetch()) {
			$new_id = $row['id'];
			$new_id++;
		}
		
		if ($new_id < 0) {
			return $this->gateway->error_message("Could not create ID number for new form");
		}
		
		$sth = $dbh->prepare("INSERT INTO form (id) VALUES ($new_id)");
		$sth->execute();
		
		$data = (array)$form;
		$data['id']    = $new_id;
		$data['slug']  = $post['slug'];
		$data['title'] = $post['title'];
		$this->handle_form_editor_post($new_id, $data);
		
		$sth = $dbh->prepare("
			INSERT INTO field(
				form_id,
				slug,
				type,
				sort_order,
				label,
				help_text,
				default_value,
				enumeration,
				regex,
				minimum,
				maximum,
				format,
				minimum_length,
				maximum_length
			)
			SELECT
				$new_id,
				slug,
				type,
				sort_order,
				label,
				help_text,
				default_value,
				enumeration,
				regex,
				minimum,
				maximum,
				format,
				minimum_length,
				maximum_length
			FROM field
			WHERE form_id=?
		");
		$sth->execute(array($form->id));

		$oldtablename = "submission_form_" . (int)$form->id;
		$newtablename = "submission_form_" . (int)$new_id;		
		$sth = $dbh->prepare("CREATE TABLE $newtablename SELECT * FROM $oldtablename WHERE 1=0");
		$sth->execute();

		return $new_id;
	}

	public function handle_form_clone ($form, $get, $post, $cookie) {
		if ($post['action']==='clone_form') {
			$new_id = $this->handle_form_clone_post($form, $post);
			return $this->gateway->redirect('form');
#			return $this->gateway->redirect('form/' . $new_id . '/edit');
		}

		$title = sprintf('Form Editor :: %s :: Clone', htmlspecialchars($form->title));

		$out = "<h1>$title</h1>";
		$out .= "<form action=\"\" method=\"post\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"title\">Title</label>\n";
		$out .= "<input required=\"required\" type=\"text\" class=\"form-control\" id=\"title\" name=\"title\" value=\"".htmlspecialchars($form->title)."\">";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"slug\">Slug</label>\n";
		$out .= "<input required=\"required\" type=\"text\" class=\"form-control\" id=\"slug\" name=\"slug\" value=\"".htmlspecialchars($form->slug)."\">";
		$out .= "<p class=\"help-block\">Changing this is <strong>strongly recommended</strong> because a client cannot have two forms with the same slug assigned to them.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"hidden\" name=\"action\" value=\"clone_form\" /> ";
		$out .= "<input type=\"submit\" class=\"btn btn-primary btn-lg\" value=\"Clone\" /> ";
		$out .= "<a class=\"btn btn-default btn-lg\" href=\"javascript:window.history.back()\">Back</a>";
		$out .= "</div>\n";
		$out .= "</form>\n";
		
		return $this->gateway->respond($title,	$out);
	}

	public function handle_field_editor ($form, $field_id, $get, $post, $cookie) {
		$field = new Field ();
		foreach ($form->fields as $f) {
			if ($f->id == $field_id) {
				$field = $f;
				break;
			}
		}
		
		if ($post['action']==='edit_field') {
			$this->handle_field_editor_post($form, $field, $post);
			return $this->gateway->redirect('form/'.$form->id.'/field');
		}
		
		$title = sprintf('Form Editor :: %s :: %s', htmlspecialchars($form->title), htmlspecialchars($field->label ? $field->label : "New Field"));
		
		$out = "<h1>$title</h1>";
		$out .= "<form action=\"\" method=\"post\">\n";
		$out .= "<div class=\"row\">\n";
		$out .= "<div class=\"col-sm-6\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"slug\">Slug</label>\n";
		$out .= "<input ".($field_id > 0 ? 'disabled="disabled"' : 'required="required"')." type=\"text\" class=\"form-control\" id=\"slug\" name=\"slug\" value=\"".htmlspecialchars($field->slug)."\">";
		$out .= "<p class=\"help-block\">The name of the database column to store input into.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"type\">Type</label>\n";
		$out .= "<select class=\"form-control\" id=\"type\" name=\"type\" style=\"width:auto\">";
		$type_names = Field::known_field_types();
		foreach ($type_names as $type => $label) {
			$out .= sprintf(
				"<option value=\"%s\" %s>%s</option>",
				htmlspecialchars($type),
				$type == $field->type ? 'selected' : '',
				htmlspecialchars($label)
			);
		}
		$out .= "</select>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"sort_order\">Sort Order</label>\n";
		$out .= "<input type=\"number\" class=\"form-control\" id=\"sort_order\" name=\"sort_order\" style=\"width:8em\" value=\"".htmlspecialchars($field->sort_order)."\">";
		$out .= "<p class=\"help-block\">Controls what order the field will appear in on its form. Low sort orders appear first.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"label\">Label</label>\n";
		$out .= "<input required=\"required\" type=\"text\" class=\"form-control\" id=\"label\" name=\"label\" value=\"".htmlspecialchars($field->label)."\">";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"default_value\">Default Value</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"default_value\" name=\"default_value\" value=\"".htmlspecialchars($field->default_value)."\">";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"help_text\">Help Text</label>\n";
		$out .= "<textarea rows=\"4\" cols=\"60\" class=\"form-control\" id=\"help_text\" name=\"help_text\">".htmlspecialchars($field->help_text)."</textarea>";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"col-sm-6\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"enumeration\">Enumeration</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"enumeration\" name=\"enumeration\" value=\"".htmlspecialchars($field->enumeration)."\">";
		$out .= "<p class=\"help-block\">Pipe-delimited list of valid values, such as <code>Yes|No|Maybe</code>. Mostly useful for fields where the type is \"Selection\".</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"regex\">Validation Regular Expression</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"regex\" name=\"regex\" value=\"".htmlspecialchars($field->regex)."\">";
		$out .= "<p class=\"help-block\">PHP-compatible regular expression for validating field input. Leave blank if you're not sure what this means.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"row\">\n";
		$out .= "<div class=\"form-group col-sm-6\">\n";
		$out .= "<label for=\"minimum\">Minimum</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"minimum\" name=\"minimum\" value=\"".htmlspecialchars($field->minimum)."\">";
		$out .= "<p class=\"help-block\">Minimum for numeric inputs.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group col-sm-6\">\n";
		$out .= "<label for=\"maximum\">Maximum</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"maximum\" name=\"maximum\" value=\"".htmlspecialchars($field->maximum)."\">";
		$out .= "<p class=\"help-block\">Maximum for numeric inputs.</p>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"row\">\n";
		$out .= "<div class=\"form-group col-sm-6\">\n";
		$out .= "<label for=\"minimum_length\">Minimum Length</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"minimum_length\" name=\"minimum_length\" value=\"".htmlspecialchars($field->minimum_length)."\">";
		$out .= "<p class=\"help-block\">Minimum length for text inputs.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group col-sm-6\">\n";
		$out .= "<label for=\"maximum_length\">Maximum Length</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"maximum_length\" name=\"maximum_length\" value=\"".htmlspecialchars($field->maximum_length)."\">";
		$out .= "<p class=\"help-block\">Maximum length for text inputs.</p>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"format\">Format</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"format\" name=\"format\" value=\"".htmlspecialchars($field->format)."\">";
		$out .= "<p class=\"help-block\">Formatting code for PHP <code>sprintf</code> or <code>date</code> function. Leave blank if you're not sure what this means.</p>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"row\">\n";
		$out .= "<div class=\"col-sm-12\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"hidden\" name=\"action\" value=\"edit_field\">";
		$out .= "<input type=\"submit\" class=\"btn btn-success\" value=\"Save\">";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";		
		$out .= "</form>\n";
		
		return $this->gateway->respond($title,	$out);
	}

	public function handle_form_editor ($form, $get, $post, $cookie) {
		
		if ($post['action']==='edit_form') {
			$this->handle_form_editor_post($form->id, $post);
			return $this->gateway->redirect('form');
		}
		
		$title = sprintf('Form Editor :: %s', htmlspecialchars($form->id ? $form->title : "New Field"));
		
		$out = "<h1>$title</h1>";
		$out .= "<form action=\"\" method=\"post\">\n";
		$out .= "<div class=\"row\">\n";
		$out .= "<div class=\"col-sm-6\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"slug\">Slug</label>\n";
		$out .= "<input required=\"required\" type=\"text\" class=\"form-control\" id=\"slug\" name=\"slug\" value=\"".htmlspecialchars($form->slug)."\">";
		$out .= "<p class=\"help-block\">Used to generate the URL of the form.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"sort_order\">Sort Order</label>\n";
		$out .= "<input type=\"number\" class=\"form-control\" id=\"sort_order\" name=\"sort_order\" style=\"width:8em\" value=\"".htmlspecialchars($form->sort_order)."\">";
		$out .= "<p class=\"help-block\">Controls what order the form will appear for clients. Low sort orders appear first.</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"title\">Title</label>\n";
		$out .= "<input required=\"required\" type=\"text\" class=\"form-control\" id=\"title\" name=\"title\" value=\"".htmlspecialchars($form->title)."\">";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"description\">Description</label>\n";
		$out .= "<input type=\"text\" class=\"form-control\" id=\"description\" name=\"description\" value=\"".htmlspecialchars($form->description)."\">";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"notes\">Notes</label>\n";
		$out .= "<textarea rows=\"4\" cols=\"60\" class=\"form-control\" id=\"notes\" name=\"notes\">".htmlspecialchars($form->notes)."</textarea>";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"col-sm-6\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<label for=\"intro_html\">Intro HTML</label>\n";
		$out .= "<textarea rows=\"12\" cols=\"60\" class=\"form-control\" id=\"intro_html\" name=\"intro_html\">".htmlspecialchars($form->intro_html)."</textarea>";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"checkbox\" id=\"is_checklist\" name=\"is_checklist\" value=\"1\" ".($form->is_checklist ? 'checked="checked"' : '')." /> ";
		$out .= "<label for=\"is_checklist\">Is Checklist?</label>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"checkbox\" id=\"is_default\" name=\"is_default\" value=\"1\" ".($form->is_default ? 'checked="checked"' : '')." /> ";
		$out .= "<label for=\"is_default\">Is Default?</label>\n";
		$out .= "<p class=\"help-block\">Should this form be added automatically to new clients?</p>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"checkbox\" id=\"has_notes\" name=\"has_notes\" value=\"1\" ".($form->has_notes ? 'checked="checked"' : '')." /> ";
		$out .= "<label for=\"has_notes\">Has Messages Panel?</label>\n";
		$out .= "<p class=\"help-block\">Should this form have a panel for messages at the bottom?</p>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "<div class=\"row\">\n";
		$out .= "<div class=\"col-sm-12\">\n";
		$out .= "<div class=\"form-group\">\n";
		$out .= "<input type=\"hidden\" name=\"action\" value=\"edit_form\">";
		$out .= "<input type=\"submit\" class=\"btn btn-success\" value=\"Save\">";
		$out .= "</div>\n";
		$out .= "</div>\n";
		$out .= "</div>\n";		
		$out .= "</form>\n";
		
		return $this->gateway->respond($title,	$out);
	}

	public function handle_form ($id, $parts, $get, $post, $cookie) {
		$form = new Form($this->config->database, $id, 0);
		if ($parts[0] == 'field' && !strlen($parts[1])) {
			return $this->handle_field_list($form, $get, $post, $cookie);
		}
		elseif ($parts[0] == 'field' && strlen($parts[1]) && $parts[2] == 'delete') {
			return $this->handle_field_trash($form, $parts[1], $get, $post, $cookie);
		}
		elseif ($parts[0] == 'field' && strlen($parts[1]) && $parts[2] == 'edit') {
			return $this->handle_field_editor($form, $parts[1], $get, $post, $cookie);
		}
		elseif ($parts[0] == 'delete') {
			return $this->handle_form_trash($form, $get, $post, $cookie);
		}
		elseif ($parts[0] == 'clone') {
			return $this->handle_form_clone($form, $get, $post, $cookie);
		}
		elseif ($parts[0] == 'edit') {
			return $this->handle_form_editor($form, $get, $post, $cookie);
		}
	}
	
}

class Form {
	public $dbh, $id, $client_id;
	public $slug, $title, $sort_order, $description, $is_checklist;
	public $is_default, $has_notes, $intro_html, $notes, $created;
	public $fields = array();
	
	public function __construct ($db, $id, $client_id) {
		$this->dbh        = $db;
		$this->client_id  = $client_id;
		
		if (is_numeric($id)) {
			$this->id = $id;
		}
		else {
			$this->slug = $id;
		}
		
		
		$this->_init();
	}
	
	protected function _init () {
	
		if ($this->id) {
			$sth = $this->dbh->prepare('SELECT * FROM form WHERE id=?');
			$sth->setFetchMode(PDO::FETCH_INTO, $this);
			$sth->execute(array( $this->id ));
			if (! $sth->fetch()) die('huhh?');
		}
		elseif ($this->slug) {
			$sth = $this->dbh->prepare('SELECT * FROM form WHERE slug=?');
			$sth->setFetchMode(PDO::FETCH_INTO, $this);
			$sth->execute(array( $this->slug ));
			if (! $sth->fetch()) die('huh?');
		}
		
		// custom fields for this form
		$sth = $this->dbh->prepare('SELECT * FROM field WHERE form_id=? ORDER BY sort_order,slug');
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array( $this->id ));
		while ($row = $sth->fetch()) {
			$this->fields[] = Field::from_database($this->dbh, $row);
		}
		
		// standard notes field
		$f = new Field();
		$f->slug      = 'note';
		$f->label     = 'Note';
		$this->fields[] = $f;
	}
	
	public function get_submissions ($where=NULL) {
		$sth = $this->dbh->prepare(sprintf(
			"SELECT s.*,sf.*,u.name AS user_name FROM submission s INNER JOIN user u ON u.id=s.user_id INNER JOIN submission_form_%d sf ON sf.submission_id=s.id WHERE s.client_id=? %s ORDER BY s.created DESC",
			(int)$this->id,
			( empty($where) ? "" : "AND $where" )
		));
		$sth->execute(array($this->client_id));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function get_notes ($where=NULL) {
		$sth = $this->dbh->prepare(sprintf(
			"SELECT n.*,u.name AS user_name FROM note n INNER JOIN user u ON u.id=n.user_id WHERE n.form_id=? AND n.client_id=? %s ORDER BY n.created DESC",
			( empty($where) ? "" : "AND $where" )
		));
		$sth->execute(array($this->id, $this->client_id));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function render_submissions ($where=NULL, $values=NULL) {
		if (is_null($values)) $values = array();
		$submissions = $this->get_submissions($where);
		$out = '';
		$inline_form = true;
		$cols = 0;

		$out .= "<form method=\"post\" action=\"\">\n";

		if (count($this->fields) > 5) {
			$inline_form = false;
			foreach ($this->fields as $f) {
				if (empty($values[$f->slug])) $values[$f->slug] = NULL;
				$out .= $f->control_html( $values[$f->slug] );
			}
			$out .= "<div>\n";
			$out .= "<input type=\"hidden\" name=\"form_id\" value=\"".(int)$this->id."\" />\n";
			$out .= "<input type=\"hidden\" name=\"client_id\" value=\"".(int)$values['client_id']."\" />\n";
			$out .= "<input type=\"hidden\" name=\"action\" value=\"submit\" />\n";
			$out .= "<input class=\"btn btn-primary\" type=\"submit\" />\n";
			$out .= "<div><label class=\"highlight\" title=\"Highlight\"><input type=\"checkbox\" name=\"is_highlighted\" value=\"1\" /> highlight this submisson</label></div>\n";
			$out .= "</div>\n";			
		}
		
		$out .= "<table class=\"table\">\n";
		$out .= "<thead>\n";
		$out .= "<tr>\n";
		foreach ($this->fields as $f) {
			++$cols;
			$out .= "<th scope=\"col\">";
			$out .= $f->format_label_html();
			$out .= "</th>\n";
		}
		$out .= "<th scope=\"col\">Details</th>\n"; ++$cols;
		$out .= "</tr>\n";
		$out .= "</thead>\n";

		if ($inline_form) {
			$out .= "<tbody class=\"form\">\n";
			$out .= "<tr>\n";
			foreach ($this->fields as $f) {
				$out .= "<td>";
				if (empty($values[$f->slug])) $values[$f->slug] = NULL;
				$out .= $f->control_html_compact( $values[$f->slug] );
				$out .= "</td>\n";
			}
			$out .= "<td>\n";
			$out .= "<input type=\"hidden\" name=\"form_id\" value=\"".(int)$this->id."\" />\n";
			$out .= "<input type=\"hidden\" name=\"client_id\" value=\"".(int)$values['client_id']."\" />\n";
			$out .= "<input type=\"hidden\" name=\"action\" value=\"submit\" />\n";
			$out .= "<input type=\"submit\" class=\"btn btn-primary\" />\n";
			$out .= "<label class=\"highlight\" title=\"Highlight\"><input type=\"checkbox\" name=\"is_highlighted\" value=\"1\" />HL</label>\n";
			$out .= "</td>\n";
			$out .= "</tr>\n";
			$out .= "</tbody>\n";
		}

		$out .= "<tbody>\n";
		foreach ($submissions as $s) {
			$cls = ($s['is_hidden'] ? 'hidden' : ($s['is_highlighted'] ? 'warning' : 'normal'));
			$out .= "<tr class=\"$cls\">\n";
			foreach ($this->fields as $f) {
				$out .= "<td>";
				$out .= $f->format_value_html($s[$f->slug]);
				$out .= "</td>\n";
			}
			$out .= "<td class=\"meta\">";
			$out .= sprintf("<span class=\"submitter\" title=\"user_id:%d\">%s</span> ", $s['user_id'], htmlspecialchars($s['user_name']));
			$out .= sprintf("<span class=\"submitted\" title=\"%s\">%s</span>", $s['created'], date('d/m H:i', strtotime($s['created'])));
			$out .= "</td>";
			$out .= "</tr>\n";
		}
		$out .= "</tbody>\n";
		
		$out .= "</table>\n";
		$out .= "</form>\n";
		return $out;
	}
	
	public function render_notes ($where=NULL, $values=NULL) {
		if (is_null($values)) $values = array();
		$notes = $this->get_notes($where);
		$out = '';
		
		if (!$this->has_notes) return $out;

		$out .= "<form method=\"post\" action=\"\">\n";

		$out .= "<table class=\"table\">\n";
		$out .= "<thead>\n";
		$out .= "<tr>\n";
		$out .= "<th scope=\"col\">Note</th>\n";
		$out .= "<th scope=\"col\">Details</th>\n";
		$out .= "</tr>\n";
		$out .= "</thead>\n";

		$out .= "<tbody class=\"form\">\n";
		$out .= "<tr>\n";
		$out .= "<td>\n";
		$out .= "<input class=\"form-control\" type=\"text\" name=\"note\" />\n";
		$out .= "</td>\n";
		$out .= "<td>\n";
		$out .= "<input type=\"hidden\" name=\"form_id\" value=\"".(int)$this->id."\" />\n";
		$out .= "<input type=\"hidden\" name=\"client_id\" value=\"".(int)$values['client_id']."\" />\n";
		$out .= "<input type=\"hidden\" name=\"action\" value=\"note\" />\n";
		$out .= "<input type=\"submit\" class=\"btn btn-primary\" />\n";
		$out .= "<label class=\"highlight\" title=\"Highlight\"><input type=\"checkbox\" name=\"is_highlighted\" value=\"1\" />HL</label>\n";
		$out .= "</td>\n";
		$out .= "</tr>\n";
		$out .= "</tbody>\n";

		$out .= "<tbody>\n";
		foreach ($notes as $n) {
			$cls = ($n['is_hidden'] ? 'hidden' : ($n['is_highlighted'] ? 'warning' : 'normal'));
			$out .= "<tr class=\"$cls\">\n";
			$out .= "<td>";
			$out .= htmlspecialchars($n['note']);
			$out .= "</td>\n";
			$out .= "<td class=\"meta\">";
			$out .= sprintf("<span class=\"submitter\" title=\"user_id:%d\">%s</span> ", $n['user_id'], htmlspecialchars($n['user_name']));
			$out .= sprintf("<span class=\"submitted\" title=\"%s\">%s</span>", $n['created'], date('d/m H:i', strtotime($n['created'])));
			$out .= "</td>";
			$out .= "</tr>\n";
		}
		$out .= "</tbody>\n";
		
		$out .= "</table>\n";
		$out .= "</form>\n";
		return $out;
	}
		
	public function render_intro () {
		return $this->intro_html;
	}
	
	public function add_note ($data) {
		$sth = $this->dbh->prepare('INSERT INTO note VALUES (0, ?, ?, ?, ?, 0, ?, CURRENT_TIMESTAMP())');
		$ok  = $sth->execute(array(
			$this->client_id,
			$this->id,
			$data['user_id'],
			((!empty($data['is_highlighted']) && $data['is_highlighted']) ? 1 : 0),
			$data['note']
		));
		if (!$ok) {
			$err = $this->dbh->errorInfo();
			return $err[2];
		}
		return false;
	}

	public function add_submission ($data) {
		$sth = $this->dbh->prepare('INSERT INTO submission VALUES (0, ?, ?, ?, ?, 0, ?, CURRENT_TIMESTAMP())');
		$ok = $sth->execute(array(
			$this->client_id,
			$this->id,
			$data['user_id'],
			((!empty($data['is_highlighted']) && $data['is_highlighted']) ? 1 : 0),
			$data['note']
		));
		if (!$ok) {
			$err = $this->dbh->errorInfo();
			return $err[2];
		}
		
		$line_id = $this->dbh->lastInsertId();
		
		$cols    = array('submission_id');
		$marks   = array('?');
		$values  = array($line_id);
		foreach ($this->fields as $f) {
			if ($f->slug==='note' || $f->slug==='created') continue;
			
			if ($f->validate_data($data)) {
				return $f->validate_data($data);
			}
			
			$cols   []= $f->slug;
			$marks  []= '?';
			$values []= $f->gather_data($data);
		}
		$cols    = join(', ', $cols);
		$marks   = join(', ', $marks);
		
		$query = sprintf('INSERT INTO submission_form_%d (%s) VALUES (%s)', $this->id, $cols, $marks);
		$sth   = $this->dbh->prepare($query);
		$ok    = $sth->execute($values);
		if (!$ok) {
			$err = $this->dbh->errorInfo();
			return $err[2];
		}
		
		return false;
	}
}
