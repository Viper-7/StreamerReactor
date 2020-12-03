<?php
ini_set('display_errors', 1);
error_reporting(-1);
include 'config.php';

$url_parts = preg_split('#[/\?]#', $_SERVER['REQUEST_URI']);
if($url_parts[1] == '') $url_parts[1] = 'manage';
array_shift($url_parts);
$slug = array_shift($url_parts);

switch($slug) {
	case 'manage':
		include 'src/Manage.php';
		break;
	case 'callback':
		include 'src/Callbacks/index.php';
		break;
	case 'ajax':
		include 'src/ajax.php';
		break;
}
