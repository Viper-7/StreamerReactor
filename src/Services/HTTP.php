<?php
/**
* Standard Properties
*
* $db					object(PDO)
* $action_id				93
* $action_service_id			32
* $action_service_type			"IRC"
* $action_service_handler		"IRC.php"
* $subscription_id			1234
* $callback_id				4567
* $message_id				e76c6bd4-55c9-4987-8304-da1588d8988b
* $message_timestamp			2019-11-16T10:11:12.123Z
* $field				"#lab424"
* $value_template			"#user_name# just subscribed!"
*
*
* Event Properties
*
* $broadcaster_user_id			12826					All
* $broadcaster_user_name		"twitch"				All
* $user_id				1337					Subscribe/Cheer/Follow/Reward
* $user_name				"awesome_user"				Subscribe/Cheer/Follow/Reward
* $is_gift				true					Subscribe
* $is_anonymous				false					Cheer
* $message				"Thanks!"				Cheer
* $bits					500					Cheer
* $user_input				"Channel Point Message"			Reward
* $status				"unfulfilled"				Reward
* $reward_title				"Some Title"				Reward
* $reward_cost				500					Reward
* $reward_prompt			"Description goes here"			Reward
* $redeemed_at				2002-10-02T15:00:00Z			Reward
* $total				400					HypeTrainBegin/HypeTrainProgress/HypeTrainEnd
* $goal					1000					HypeTrainBegin/HypeTrainProgress/HypeTrainEnd
* $top_contributions			object(top_contributions)		HypeTrainBegin/HypeTrainProgress/HypeTrainEnd
* $last_contribution			object(last_contribution)		HypeTrainBegin/HypeTrainProgress/HypeTrainEnd
* $started_at				2002-10-02T15:00:00Z			HypeTrainBegin/HypeTrainProgress/HypeTrainEnd
* $expires_at				2002-10-02T15:00:00Z			HypeTrainBegin/HypeTrainProgress
* $level				3					HypeTrainProgress/HypeTrainEnd
* $ended_at				2002-10-02T15:00:00Z			HypeTrainEnd
* $cooldown_ends_at			2002-10-02T15:00:00Z			HypeTrainEnd
* $type					"live"					StreamOnline
**/

$service['Port'] = $service['Port'] ?: 80;
$login = '';
if($service['Username']) {
	$login = $service['Username'];
	if($service['Password'])
		$login .= ':' . $service['Password'];
	$login .= '@';
}
$url = "http://{$login}{$service['Host']}:{$service['Port']}/{$service['Path']}?{$field}=" . urlencode(rendertemplate($value_template));

get_headers($url);
