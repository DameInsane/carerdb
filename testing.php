<?php

require 'config.php';
require 'form.php';

$f = new Form(Config::$database, 1);

print $f->render_page();