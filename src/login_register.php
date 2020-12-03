<!doctype html>
<html>
<head>
<title>StreamReactor</title>
<?php
if(isset($_POST['email'])) {
	$hash = hash('sha256', $_POST['password']);
	
	$stmt = $db->prepare('SELECT ID, Name, Email, Password, Created, Active FROM Users WHERE Email=? AND Active=1');
	$stmt->execute(array($_POST['email']));
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	if(count($rows) > 0) {
		if($hash == $rows[0]['Password']) {
			$user = $rows[0];
			$_SESSION['user'] = $user['ID'];
			return;
		}
		?><span class="error">Sorry, that email and password does not match.</span><?php
	} else {
		$stmt = $db->prepare('INSERT INTO Users (Email, Password) VALUES (?,?)');
		$stmt->execute(array($_POST['email'], $hash));
		$stmt = $db->prepare('SELECT ID, Name, Email, Password, Created, Active FROM Users WHERE Email=? AND Active=1');
		$stmt->execute(array($_POST['email']));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$user = $rows[0];
		$_SESSION['user'] = $user['ID'];
		return;
	}
}
?>
<style type="text/css">
	html, body {
		max-width: 800px;
		margin: 24px auto;
	}
	span.field {
		display: inline-block;
		width: 150px;
	}
</style>
</head>
<body>
<form method="post">
	<label><span class="field">Email:</span> <input type="text" name="email" size="30"></label><br>
	<label><span class="field">Password:</span> <input type="password" name="password" size="30"></label><br>
	<input type="submit" value="Login/Register">
</form>
</body>
</html>