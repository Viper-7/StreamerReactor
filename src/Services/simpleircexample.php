<pre>
<?php	
	// SimpleIRC Example Usage
	include('simpleirc.php');
	
	$si = new simpleirc();

	$si->connect('irc.chat.twitch.tv', 'Viper7__', 'oauth:mpck9hle8hikwbtnn2d8db4njbcr5n');
	
	$si->send('#lab424', 'hello');
	
	$si->disconnect();

?>
</pre>
