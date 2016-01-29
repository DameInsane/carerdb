<?php

class Template {

	public $config;
	
	public function __construct (Config $c) {
		$this->config = $c;
	}

	public function apply_template ($title, $html) {
		return "<!DOCTYPE html>\n"
			. "<html>\n"
			. "<head>\n"
			. "<title>".htmlspecialchars($this->config->branding) . " :: " . htmlspecialchars($title)."</title>\n"
			. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->config->asset('css/bootstrap.min.css')."\" />\n"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->config->asset('css/bootstrap-theme.min.css')."\" />\n"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->config->asset('css/bootstrap-sortable.css')."\" />\n"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->config->asset('css/bootstrap-datetimepicker.css')."\" />\n"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->config->asset('css/carerdb.css')."\" />\n"
			. "</head>\n"
			. "<body>\n"
			. $this->navbar()
			. "<article role=\"main\" class=\"container\">\n"
			. $html
			. "</article>\n"
			. "<script type=\"text/javascript\" src=\"".$this->config->asset('js/jquery-1.11.2.min.js')."\"></script>\n"
			. "<script type=\"text/javascript\" src=\"".$this->config->asset('js/bootstrap.min.js')."\"></script>\n"
			. "<script type=\"text/javascript\" src=\"".$this->config->asset('js/moment-with-locales.js')."\"></script>\n"
			. "<script type=\"text/javascript\" src=\"".$this->config->asset('js/bootstrap-sortable.js')."\"></script>\n"
			. "<script type=\"text/javascript\" src=\"".$this->config->asset('js/bootstrap-datetimepicker.js')."\"></script>\n"
			. "<script type=\"text/javascript\" src=\"".$this->config->asset('js/carerdb.js')."\"></script>\n"
			. "</body>\n"
			. "</html>\n\n";
	}
	
	public function adminmenu () {
		if ($_SESSION['roles'][ROLE_ADMINISTRATOR]) {
			return '
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Admin Menu <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="'.$this->config->path('user/new').'">New user <span class="glyphicon glyphicon-plus pull-right"></span></a></li>
                  <li><a href="'.$this->config->path('user').'">Users</a></li>
                  <li><a href="'.$this->config->path('permission').'">Permissions</a></li>
                  <li><a href="'.$this->config->path('user_permission').'">Permission to user assignment</a></li>
						<li class="divider"></li>
                  <li><a href="'.$this->config->path('client/new').'">New client <span class="glyphicon glyphicon-plus pull-right"></span></a></li>
                  <li><a href="'.$this->config->path('client').'">Clients</a></li>
                  <li><a href="'.$this->config->path('user_client').'">Client to user assignment</a></li>
						<li class="divider"></li>
                  <li><a href="'.$this->config->path('form').'">Forms <span class="glyphicon glyphicon-cog pull-right"></span></a></li>
						<li class="divider"></li>
                  <li><a href="'.$this->config->path('submission').'">Submissions</a></li>
                  <li><a href="'.$this->config->path('note').'">Notes</a></li>
                </ul>
              </li>
			';
		}
		return '';
	}
	
	public function navbar () {
		return '
      <nav class="navbar navbar-default" role="navigation">
        <header class="container" role="header">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="'.$this->config->path('').'">'.htmlspecialchars($this->config->branding).'</a>
          </div>
          <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Menu <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="'.$this->config->path('client').'">Clients</a></li>
						<li class="divider"></li>
						<li><a href="'.$this->config->asset('help.html').'">Help</a></li>
						<li class="divider"></li>
                  <li><a href="'.$this->config->path('?action=logout').'">Log out <span class="glyphicon glyphicon-off pull-right"></span></a></li>
                </ul>
              </li>'.$this->adminmenu().'
            </ul>
          </div><!--/.nav-collapse -->
        </header>
      </nav>
		';
	}
}
