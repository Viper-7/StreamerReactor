<?php
session_start();
$user = null;

if(isset($_SESSION['user'])) {
	$stmt = $db->prepare('SELECT ID, Name, Email, Created, Active FROM Users WHERE ID=? AND Active=1');
	$stmt->execute(array($_SESSION['user']));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(count($rows) == 1) {
		$user = $rows[0];
	}
}
if(!isset($user)) {
	include 'src/login_register.php';
	
	if(!isset($user))
		die();
}