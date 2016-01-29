<?php

require_once 'database.php';
require_once 'template.php';

class Config {
	public $database, $template, $branding;
	
	public function path ($path) {
		return '/carerdb/index.php/' . $path;
	}
	public function asset ($path) {
		return '/carerdb/assets/'.$path;
	}
}

$config = new Config;
$config->database = new Database('mysql:host=localhost;dbname=carerdb','carerdb','penguin23');
$config->template = new Template($config);
$config->branding = 'Private Nursing Agency';
// s3cr3t