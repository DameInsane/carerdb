<?php

require 'routes.php';
require 'config.php';

session_start();

$router = new Router($_SERVER, $config, $_SESSION);
$router->handle_request($_GET, $_POST, $_COOKIE);
