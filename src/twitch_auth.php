<?php
$stmt = $db->prepare('SELECT AccessToken FROM Twitch_Tokens WHERE ClientID=? AND Expires > NOW()');
$stmt->execute(array($clientid));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($rows) > 0) {
	$row = $rows[0];
	$_access_token = $row['AccessToken'];
} else {
	$data = array(
		'client_id' => $clientid,
		'client_secret' => $secret,
		'grant_type' => 'client_credentials',
		'scope' => 'channel:read:subscriptions channel:read:hype_train channel:read:redemptions bits:read'
	);

	$context = stream_context_create(array('http'=>array('method'=>'POST','header'=> array('Content-type: application/x-www-form-urlencoded'),'content'=>http_build_query($data))));
	$response = file_get_contents('https://id.twitch.tv/oauth2/token', 0, $context);
	
	$data = json_decode($response);
	
	$_access_token = $data->access_token;
	$stmt = $db->prepare('INSERT INTO Twitch_Tokens (ClientID, AccessToken, Expires, Scope) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?)');
	$stmt->execute(array($clientid, $data->access_token, $data->expires_in, json_encode($data->scope)));
}

function twitch_sync_subscriptions() {
	$db = $GLOBALS['db'];
	
	$lockfile = '/tmp/twitch_synclock';
	$fp = @fopen($lockfile, 'x');
	if(!$fp) {
		if(filemtime($lockfile) < time() - 3600)
			unlink($lockfile);
		return;
	}
	$resp = twitch_request('https://api.twitch.tv/helix/eventsub/subscriptions', array(), 'GET');
	$stmt = $db->prepare('
		SELECT 
			Subscriptions.ID, 
			TwitchID, 
			Subscription_Types.Name as Name, 
			Subscription_Types.`Field`, 
			Channels.BroadcasterID as BroadcasterID, 
			Callbacks.Slug as Slug, 
			Callbacks.Secret as Secret, 
			COUNT(Actions.ID) as NumActions 
		FROM 
			Subscriptions 
			LEFT JOIN Callbacks ON (Callbacks.SubscriptionID = Subscriptions.ID) 
			JOIN Subscription_Types ON (Subscription_Types.id = Subscriptions.SubscriptionTypeId) 
			JOIN Channels ON (Subscriptions.ChannelID = Channels.ID) 
			JOIN Users ON (Users.ID = Channels.UserID) 
			LEFT JOIN Actions ON (Actions.SubscriptionID=Subscriptions.ID) 
		WHERE
			Users.Active=1
		GROUP BY
			Subscriptions.ID, 
			TwitchID, 
			Subscription_Types.Name, 
			Subscription_Types.`Field`, 
			Channels.BroadcasterID, 
			Callbacks.Slug, 
			Callbacks.Secret
		HAVING
			NumActions > 0
	');
	$stmt->execute();
	$dbrows = array();
	$found = array();
	foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$dbrows[$row['TwitchID']] = $row;
	}
	$updates = 0;
	$deletions = 0;
	$additions = 0;
	$unchanged = 0;
	foreach($resp->data as $sub) {
		if(isset($dbrows[$sub->id])) {
			// Update dbrow if required
			$rec = $dbrows[$sub->id];
			$found[$sub->id] = $rec;
			if($sub->id != $rec['TwitchID']) {
				$updates++;
				$stmt = $db->prepare('UPDATE Callbacks SET TwitchID=? WHERE Slug=?');
				$stmt->execute(array($sub->id, $rec['Slug']));
			} else {
				$unchanged++;
			}
		} else {
			$res = twitch_request('https://api.twitch.tv/helix/eventsub/subscriptions', array('id'=>$sub->id), 'DELETE');
			$stmt = $db->prepare('DELETE FROM Callbacks WHERE TwitchID=? AND Created < DATE_SUB(NOW(), INTERVAL 1 MINUTE)');
			$stmt->execute(array($sub->id));
			$deletions++;
		}
	}
	$missing = array_diff_key($dbrows, $found);

	foreach($missing as $sub) {
		$slug = $sub['Slug'];
		$secret = $sub['Secret'];
		
		if(!$slug) $slug = uniqid($sub['ID'], true);
		if(!$secret) $secret = uniqid($sub['ID'], true);
		
		$additions++;
		
		// New sub, submit to API
		$request = array(
			'type' => $sub['Field'],
			'version' => 1,
			'condition' => array(
				'broadcaster_user_id' => $sub['BroadcasterID']
			),
			'transport' => array(
				'method' => 'webhook',
				'callback' => 'https://streamerreactor.viper-7.com/callback/' . $slug,
				'secret' => $secret
			)
		);
		if(!$sub['Slug']) {
			$stmt = $db->prepare('INSERT INTO Callbacks SET Slug=?, Secret=?, SubscriptionID=?');
			$stmt->execute(array($slug, $secret, $sub['ID']));
		}
		
		var_dump($request);
		$res = twitch_request('https://api.twitch.tv/helix/eventsub/subscriptions', $request);
		if(isset($res->data[0]->id)) {
			$stmt = $db->prepare('UPDATE Callbacks SET TwitchID=? WHERE Slug=?');
			$stmt->execute(array($res->data[0]->id, $slug));
		}
	}
	
	fclose($fp);
	unlink($lockfile);
	echo "Sync complete. {$additions} additions, {$updates} updates, {$deletions} deletions, {$unchanged} unchanged.";
}
function validate() {
	$url = 'https://id.twitch.tv/oauth2/validate';
	$auth_headers = array(
		'Authorization: OAuth ' . $GLOBALS['_access_token']
	);
	$options = array('http'=>array('header'=>array_merge($auth_headers, array('Content-type: application/json'))));
	$context = stream_context_create($options);
	$res = file_get_contents($url,0,$context);
	echo '<pre>';
	var_dump($res);
	die();
}
function twitch_get_user($user) {
	$url = 'https://api.twitch.tv/helix/users?login=' . $user;
	$auth_headers = array(
		'Client-ID: '. $GLOBALS['clientid'],
		'Authorization: Bearer ' . $GLOBALS['_access_token']
	);
	$context = stream_context_create(array('http'=>array('header'=>$auth_headers)));
	$res = json_decode(file_get_contents($url, 0, $context));
	return $res->data;
}
function twitch_user_to_id($user) {
	return twitch_get_user($user)[0]->id;
}

function twitch_request($url, $args, $method='POST') {
	$auth_headers = array(
		'Client-ID: '. $GLOBALS['clientid'],
		'Authorization: Bearer ' . $GLOBALS['_access_token']
	);
	
	if($method == 'POST')
		$options = array('http'=>array('method'=>'POST','content'=>json_encode($args), 'header' => array_merge($auth_headers, array('Content-type: application/json'))));
	else {
		$options = array('http'=>array('method'=>$method, 'header'=>$auth_headers));
		$url = rtrim($url, '?') . (strpos($url,'?')!==FALSE?'&':'?') . http_build_query($args);
	}
	$context = stream_context_create($options);

	return json_decode(file_get_contents($url, 0, $context));
}
