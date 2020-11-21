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
	
	$resp = twitch_request('https://api.twitch.tv/helix/eventsub/subscriptions', array(), 'GET');
	$stmt = $db->prepare('SELECT Subscriptions.ID, TwitchID, Subscription_Types.Name as Name, Subscription_Types.`Field`, Channels.BroadcasterID as BroadcasterID, Callbacks.Slug as Slug, Callbacks.Secret as Secret FROM Subscriptions LEFT JOIN Callbacks ON (Callbacks.SubscriptionID = Subscriptions.ID) JOIN Subscription_Types ON (Subscription_Types.id = Subscriptions.SubscriptionTypeId) JOIN Channels ON (Subscriptions.ChannelID = Channels.ID) JOIN Users ON (Users.ID = Channels.UserID) WHERE Users.Active=1');
	$stmt->execute();
	$dbrows = array();
	$found = array();
	foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$dbrows[$row['ID']] = $row;
	}
	foreach($resp->data as $sub) {
		if(isset($dbrows[$sub->id])) {
			// Update dbrow if required
			$rec = $dbrows[$sub->id];
			$found[$sub->id] = $rec;
			if($sub->id != $rec['TwitchID']) {
				$stmt = $db->prepare('UPDATE Callbacks SET TwitchID=? WHERE Slug=?');
				$stmt->execute(array($sub->id, $rec['Slug']));
			}
		} else {
			// Twitch sub doesnt exist in our DB, ignore.  @TODO remove?
		}
	}
	$missing = array_diff_key($dbrows, $found);

	foreach($missing as $sub) {
		$slug = $sub['Slug'];
		$secret = $sub['Secret'];
		
		if(!$slug) $slug = uniqid($sub['ID'], true);
		if(!$secret) $secret = uniqid($sub['ID'], true);
		
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
		
		$res = twitch_request('https://api.twitch.tv/helix/eventsub/subscriptions', $request);
		if(isset($res->data[0]->id)) {
			if($sub['Slug']) {
				// Existing record, update
				$stmt = $db->prepare('UPDATE Callbacks SET TwitchID=? WHERE Slug=?');
				$stmt->execute(array($res->data[0]->id, $sub['Slug']));
			} else {
				$stmt = $db->prepare('INSERT INTO Callbacks SET Slug=?, Secret=?, TwitchID=?');
				$stmt->execute(array($slug, $secret, $res->data[0]->id));
			}
		}
	}
}

function twitch_user_to_id($user) {
	return 38533468;
	$url = 'https://api.twitch.tv/kraken/users?login=' . $user;
	$auth_headers = array(
		'Client-ID: '. $GLOBALS['clientid'],
		'Authorization: Bearer ' . $GLOBALS['_access_token']
	);
	$context = stream_context_create(array('http'=>array('header'=>$auth_headers)));
	return json_decode(file_get_contents($url, 0, $context));
}

function twitch_request($url, $args, $method='POST') {
	$auth_headers = array(
		'Client-ID: '. $GLOBALS['clientid'],
		'Authorization: Bearer ' . $GLOBALS['_access_token']
	);
	
	if($method == 'POST')
		$context = stream_context_create(array('http'=>array('method'=>'POST','content'=>json_encode($args), 'header' => array_merge($auth_headers, array('Content-type: application/json')))));
	else {
		$context = stream_context_create(array('http'=>array('method'=>$method, 'header'=>$auth_headers)));
		$url = rtrim($url, '?') . (strpos($url,'?')!==FALSE?'&':'?') . http_build_query($args);
	}
	return json_decode(file_get_contents($url, 0, $context));
}
