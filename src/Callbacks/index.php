<?php
if(!isset($url_parts)) die('FATAL');
if(count($url_parts) < 1) die('Callback Handler Root');

$slug = array_shift($url_parts);
$stmt = $db->prepare('SELECT ID, SubscriptionID, Secret FROM Callbacks WHERE ID=?');
$stmt->execute(array($slug));

function trigger_service($event, $action, $subscription_id, $callback_id, $message_id, $message_timestamp, $service) use ($db) {
	extract($event);
	unset($event);
	extract($action);
	unset($action);
	if(isset($reward)) {
		$reward_title = $reward->title;
		$reward_cost = $reward->cost;
		$reward_prompt = $reward->prompt;
	}
	
	$vars = get_defined_vars();
	function rendertemplate($template) use ($vars) {
		foreach($vars as $key => $value) {
			$template = str_replace("#{$key}#", $value, $template);
		}
		return $template;
	}
	unset($vars);
	
	include 'src/trigger_service.php';
}

if($stmt->rowCount) {
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($rows as $row) {
		$signature = $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_SIGNATURE'];
		$message = $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_ID'] . $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_TIMESTAMP'];
		$test = hash_hmac('sha256', $message, $row['Secret'], true);
		if($test == $signature) {
			$body = json_decode(stream_get_contents(STDIN));
			if(isset($body->event)) {
				foreach($body->event as $event) {
					$stmt = $db->prepare('
						SELECT
							Actions.ID as action_id, 
							ActionServiceID as action_service_id, 
							`Field` as `field`, 
							ValueTemplate as value_template, 
							Action_Service_Types.Name as action_service_type, 
							Action_Service_Types.Handler as Handler as action_service_handler,
							Action_Services.Host,
							Action_Services.Port,
							Action_Services.Path,
							Action_Services.Username,
							Action_Services.Password
						FROM 
							Actions 
							JOIN Action_Services ON (Actions.ActionServiceID = Action_Services.ID) 
							JOIN Action_Service_Types ON (Action_Service_Types.ID = Action_Services.ServiceTypeID) 
						WHERE 
							Actions.SubscriptionID=?
					');
					$stmt->execute(array($row['SubscriptionID']));
					$row2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$keys = array('Host','Port','Path','Username','Password');
					$service = array_intersect_key($row, array_flip($keys));
					
					trigger_service($event, $row2, $row['SubscriptionID'], $row['ID'], $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_ID'], $_SERVER['HTTP_TWITCH_EVENTSUB_MESSAGE_TIMESTAMP'], $service);
				}
			}
			if(isset($body->challenge)) {
				echo $body->challenge;
				die();
			}
		}
	}
}
