<?php

require_once 'constants.php';
require_once 'crud.php';
require_once 'client.php';

class Router {
	public $server;
	public $session;
	public $config;
	
	public function __construct(&$srv, &$cfg, &$sess) {
		$this->server  =& $srv;
		$this->config  =& $cfg;
		$this->session =& $sess;
	}
		
	public function handle_request ($get, $post, $cookie) {
		$env  =& $this->server;
		$cfg  =& $this->config;
		$sess =& $this->session;
		
		$action = empty($post['action']) ? (empty($get['action']) ? '' : $get['action']) : $post['action'];
		
		if ($action==='session-dump') {
			header("Content-Type: text/plain");
			print_r($_SESSION);
			return;
		}
		
		if ($action==='logout') {
			foreach ($sess as $k => $v) {
				unset($sess[$k]);
			}
			return $this->redirect('');
		}
		
		if (empty($sess['user_id']) || !$sess['user_id']) {
			$ok = false;
			if ($action==='login') {
				$ok = $this->handle_login($get, $post, $cookie);
			}
			if (!$ok) return $this->show_login_form($get, $post, $cookie);
		}
		
		$is_admin = $_SESSION['roles'][ROLE_ADMINISTRATOR];
		
		$parts     = explode('/', $env['PATH_INFO']);
		if (empty($parts[0])) array_shift($parts);
		$component = array_shift($parts);
		$id        = array_shift($parts);

		if (empty($component)) {
			return $this->redirect('client');
		}
		elseif ($component==='client') {
			$crud = new ClientForm($is_admin, $this->config, $this);
			if ($id && !empty($parts[0])) {
				$form_id = $parts[0];
				return $crud->handle_form($id, $form_id, $get, $post, $cookie);
			}
			elseif ($id) {
				return $crud->handle_client($id, $get, $post, $cookie);
			}		
			else {
				return $crud->handle_list($get, $post, $cookie);
			}
		}
		elseif ($component==='form') {
			$crud = new FormManager($is_admin, $this->config, $this);
			if (strlen($id)) {
				return $crud->handle_form($id, $parts, $get, $post, $cookie);
			}		
			else {
				return $crud->handle_list($parts, $get, $post, $cookie);
			}
		}
		elseif ($is_admin) {
			$crud = new CrudForm($component, $this->config, $this);
			return (
				$id
					? $crud->handle_item($id, $parts, $get, $post, $cookie)
					: $crud->handle_index($parts, $get, $post, $cookie)
			);
		}
		else {
			return $this->error_message('Permission denied. Administrator access required.');
		}
	}
	
	# HTTP 200
	public function respond ($title, $html) {
		print $this->config->template->apply_template($title, $html);
		return;
	}
	
	# HTTP 302
	public function redirect ($to) {
		header("Content-Type: text/plain");
		print_r($_SERVER);
		
		$uri = ($this->server['HTTPS'] ? 'https://' : 'http://')
			. $this->server['HTTP_HOST']
			. $this->server['SCRIPT_NAME']
			. "/$to";
		
		header("Status: 302 Found");
		header("Location: $uri");
		return;
	}
	
	# HTTP 404
	public function not_found ($msg) {
		header("HTTP/1.0 404 Not Found");
		$tmpl = $this->config->template;
		print $tmpl->apply_template('Not Found', sprintf('<h1>Not Found</h1><p>%s</p>', htmlspecialchars($msg)));
		return;
	}
	
	# HTTP 500
	public function error_message ($msg) {
		header("HTTP/1.0 500 Error");
		$tmpl = $this->config->template;
		print $tmpl->apply_template('Error', sprintf('<h1>Error</h1><p>%s</p>', htmlspecialchars($msg)));
		return;
	}

	protected function show_login_form ($get, $post, $cookie) {
		return $this->respond('Log In', '
			<form action="" method="post" class="login" style="width:16em;margin:0 auto;">
				<div class="form-group">
					<label for="username">username:</label>
					<input class="form-control" name="username" id="username" />
				</div>
				<div class="form-group">
					<label for="password">password:</label>
					<input class="form-control" name="password" id="password" type="password" />
				</div>
				<div class="form-group">
					<input type="hidden" name="action" value="login" />
					<input class="btn btn-primary" type="submit" value="login" />
				</div>
			</form>
		');
	}
	
	protected function handle_login ($get, $post, $cookie) {
		$u = $post['username'];
		$p = $post['password'];
		
		$ok = false;
		
		$sth = $this->config->database->prepare('SELECT * FROM user WHERE username=?');
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute(array($u));
		if ($row = $sth->fetch()) {			
			list($salt, $hash) = explode(':', $row['salthash']);
			if (empty($hash) && $salt===$p) {
				$ok = 1;
			}
			if (!empty($hash) && strtolower($salt)===strtolower(sha1($p))) {
				$ok = 1;
			}
			
			if ($ok) {
				$sth = $this->config->database->prepare('SELECT p.id,p.name FROM permission p INNER JOIN user_permission up ON up.permission_id=p.id WHERE up.user_id=? AND CURRENT_TIMESTAMP() BETWEEN up.start_date AND up.end_date');
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				$sth->execute(array($row['id']));
				
				$perms = array();
				while ($row2 = $sth->fetch()) {
					$perms[$row2['id']] = $row2['name'];
				}
				
				// admin gets automatically all other roles
				if ($perms[ROLE_ADMINISTRATOR]) {
					$perms[ROLE_USER]     = true;
					$perms[ROLE_TIMELORD] = true;
				}
				
				if ($perms[ROLE_USER]) {
					$this->session['user']    = $row;
					$this->session['user_id'] = $row['id'];
					$this->session['login']   = time();
					$this->session['ip']      = $_SERVER['REMOTE_ADDR'];
					$this->session['roles']   = $perms;
				}
				else {
					$ok = false;
				}
			}
		}
		
		return $ok;
	}
}