<?php
if(!isset($url_parts)) die('FATAL');
if(count($url_parts) < 1) die('Callback Handler Root');

function rendertemplate($template) {
	$vars = $GLOBALS['vars'];
	$data = array();
	foreach($vars as $key => $value) {
		if(!is_object($value) && !is_resource($value) && !is_array($value)) {
			$template = str_replace("#{$key}#", $value, $template);
			$data[$key] = $value;
		}
	}
	if($data['SendAsJSON']) return json_encode($data);
	
	return $template;
}
function trigger_service($event, $action, $subscription_id, $message_id, $message_timestamp, $service) {
	$db = $GLOBALS['db'];
	extract((array)$event);
	unset($event);
	extract((array)$action);
	unset($action);
	if(isset($reward)) {
		$reward_title = $reward->title;
		$reward_cost = $reward->cost;
		$reward_prompt = $reward->prompt;
	}
	
	$vars = get_defined_vars();
	$GLOBALS['vars'] = $vars;
	unset($vars);
	
	$_script = 'src/Services/' . $action_service_handler;
	include $_script;
}


$slug = array_shift($url_parts);
$stmt = $db->prepare('SELECT SubscriptionID, Secret FROM Callbacks WHERE Slug=?');
$stmt->execute(array($slug));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$body = file_get_contents('php://input');

foreach($rows as $row) {
	$signature = $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_SIGNATURE'];
	$message = $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_ID'] . $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_TIMESTAMP'] . $body;
	$test = 'sha256=' . hash_hmac('sha256', $message, $row['Secret'], false);

	if($test == $signature) {
		$body = json_decode($body);
		if(isset($body->event)) {
			$event = $body->event;
			$stmt = $db->prepare('
				SELECT
					Actions.ID as action_id, 
					ActionServiceID as action_service_id, 
					Actions.`Field` as `field`, 
					Actions.SendAsJSON,
					ValueTemplate as value_template, 
					Action_Service_Types.Name as action_service_type, 
					Action_Service_Types.Handler as action_service_handler,
					Action_Services.Host,
					Action_Services.Port,
					Action_Services.Path,
					Action_Services.Username,
					Action_Services.Password,
					Subscription_Types.Code as event_type
				FROM 
					Actions 
					JOIN Action_Services ON (Actions.ActionServiceID = Action_Services.ID) 
					JOIN Action_Service_Types ON (Action_Service_Types.ID = Action_Services.ServiceTypeID) 
					LEFT JOIN Subscriptions ON (Actions.SubscriptionID = Subscriptions.ID)
					LEFT JOIN Subscription_Types ON (Subscription_Types.ID = Subscriptions.SubscriptionTypeID)
				WHERE 
					Actions.SubscriptionID=?
			');
			$stmt->execute(array($row['SubscriptionID']));
			$row2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(!isset($row2[0]))
				trigger_error($stmt->errorInfo(), E_USER_ERROR);
			$keys = array('Host','Port','Path','Username','Password');
			$service = array_intersect_key($row2[0], array_flip($keys));
			trigger_service($event, $row2[0], $row['SubscriptionID'], $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_ID'], $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_TIMESTAMP'], $service);
		}
		if(isset($body->challenge)) {
			echo $body->challenge;
			die();
		}
	}
}
