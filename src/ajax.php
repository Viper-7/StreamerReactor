<?php
include 'src/userauth.php';
include 'src/twitch_auth.php';

switch($url_parts[0]) {
	case 'getchannels':
		$stmt = $db->prepare('
			SELECT 
				Channels.ID,
				Channels.Name as Name,
				Slug,
				BroadcasterID,
				Channels.Created as Created
			FROM
				Channels
				JOIN Users ON (Channels.UserID = Users.ID)
			WHERE
				Channels.UserID = ?
		');

		$stmt->execute(array($_SESSION['user']));
		$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($channels as $channel) {
			?><div class="channel"><div class="header"><h2><?=$channel['Name']?> (http://twitch.tv/<?=$channel['Slug']?>)</h2></div>
			<?php
				$stmt = $db->prepare('SELECT ID FROM Twitch_User_Tokens WHERE ChannelID=?');
				$stmt->execute(array($channel['ID']));
				$tokens = $stmt->fetchAll();
				if(count($tokens) == 0) {
					$url = 'https://id.twitch.tv/oauth2/authorize';
					$args = array(
						'client_id' => $clientid,
						'redirect_uri' => 'https://streamerreactor.viper-7.com/oauth.php',
						'response_type' => 'token',
						'scope' => 'channel:read:subscriptions channel:read:hype_train channel:read:redemptions bits:read'
					);
					$url .= '?' . http_build_query($args);
					?><br><br><div style="text-align: center">This channel has not been linked to a twitch account. Event options are limited until you <a href="<?=htmlentities($url)?>">Link Your Account</a></div><?php
				}
			?>

<!---			<table cellspacing=0 cellpadding=0 style="margin: 0 auto; width: 600px; border: 0;">
			<tr><th width="300">Name</th><td><?=$channel['Name']?></td></tr>
			<tr><th>URL</th><td>http://twitch.tv/<?=$channel['Slug']?></td></tr>
			<tr><th>Broadcaster ID</th><td><?=$channel['BroadcasterID'] ?: twitch_user_to_id($channel['Slug'])?></td></tr>
			<tr><th>Created</th><td><?=$channel['Created']?></td></tr>
			</table>--->
			<?php
			
			$stmt2 = $db->prepare('
				SELECT
					Subscriptions.ID,
					Subscriptions.RewardID,
					Subscription_Types.ID as TypeID,
					Subscription_Types.Name,
					Subscription_Types.Field
				FROM
					Subscriptions
					JOIN Subscription_Types ON (Subscriptions.SubscriptionTypeID = Subscription_Types.ID)
					LEFT JOIN Callbacks ON (Callbacks.SubscriptionID = Subscriptions.ID)
				WHERE
					Subscriptions.ChannelID=?
			');
			$stmt2->execute(array($channel['ID']));
			$subs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
			foreach($subs as $sub) {
				?>
				<div class="event">
				<div class="header">When twitch raises a <?=$sub['Name']?> event:</div>
				<?php
				$stmt3 = $db->prepare('
					SELECT
						Actions.ID,
						Action_Services.Host,
						Action_Services.Port,
						Action_Services.Path,
						Action_Services.Username,
						Action_Services.Password,
						Actions.`Field`,
						Actions.ValueTemplate,
						Action_Service_Types.Name as TypeName,
						Action_Service_Types.Handler as Handler
					FROM
						Actions
						JOIN Action_Services ON (Actions.ActionServiceID = Action_Services.ID)
						JOIN Action_Service_Types ON (Action_Service_Types.ID = Action_Services.ServiceTypeID)
					WHERE
						Actions.SubscriptionID=?
				');
				$stmt3->execute(array($sub['ID']));
				$actions = $stmt3->fetchAll(PDO::FETCH_ASSOC);
				foreach($actions as $action) {
					?>
					<div class="action">
						<span class="leftdel"><a href="/manage/actions/<?=$action['ID']?>/delete" class="action_delete_link" data-id="<?=$action['ID']?>">X</a></span>
						<?php
						switch($action['TypeName']) {
							case 'HTTP':
								$path = ltrim($action['Path'], '/');
								echo "Make a HTTP request to http://{$action['Host']}/{$path}?{$action['Field']}=[value]<br>";
								echo "<i>{$action['ValueTemplate']}</i><br>";
								break;
							case 'IRC':
								echo "Send a message on IRC ({$action['Host']}) to channel {$action['Field']}<br>";
								echo "<i>{$action['ValueTemplate']}</i><br>";
								break;
								
						}
						?><br>
					</div>
					<?php
				}
			?>
			<br>
			<div style="display: none"><input type="hidden" name="subscriptionid" value="<?=$sub['ID']?>" class="event_subid"></div>
			<div class="form_target2"></div>
			<div class="cta"><a href="/manage/actions/add" class="add_action_link">Add an Action</a><a href="/manage/events/del/<?=$sub['ID']?>" data-id="<?=$sub['ID']?>" class="del_event_link">Delete this Event</a></div><br>
			</div>
			<?php
			}
			?>
			<br>
			<div style="display: none"><input type="hidden" name="channelid" value="<?=$channel['ID']?>" class="event_channelid"></div>
			<div class="form_target"></div>
			<div class="cta"><a href="/manage/subscriptions/add" class="add_subscription_link">Add an Event</a></div><br>
			<br><hr><br></div>
			<?php
		}
		break;
	case 'delete_action':
		$stmt = $db->prepare('SELECT Actions.ID FROM Actions JOIN Action_Services ON (Action_Services.ID = Actions.ActionServiceID) WHERE Actions.ID=? and Action_Services.UserID=?');
		$stmt->execute(array($_GET['action'], $_SESSION['user']));
		$rows = $stmt->fetchAll();
		if(count($rows) > 0) {
			$stmt = $db->prepare('DELETE FROM Actions WHERE ID=?');
			$stmt->execute(array($_GET['action']));
		}
		break;
	case 'delete_event':
		$stmt = $db->prepare('SELECT Subscriptions.ID AS ID FROM Channels JOIN Subscriptions ON (Subscriptions.ChannelID = Channels.ID) WHERE Subscriptions.ID=? and Channels.UserID=?');
		$stmt->execute(array($_GET['event'], $_SESSION['user']));
		$rows = $stmt->fetchAll();
		if(count($rows) > 0) {
			$stmt = $db->prepare('DELETE FROM Actions WHERE SubscriptionID=?');
			$stmt->execute(array($rows[0]['ID']));
			$stmt = $db->prepare('DELETE FROM Subscriptions WHERE ID=?');
			$stmt->execute(array($rows[0]['ID']));
		}
		break;		
	case 'create_channel':
		$stmt = $db->prepare('INSERT INTO Channels (Name, Slug, UserID, BroadcasterID) VALUES (?, ?, ?, ?)');
		$stmt->execute(array($_POST['name'], $_POST['slug'], $_SESSION['user'], $_POST['broadcasterid']));
		return $db->lastInsertId;

		break;
	case 'sync':
		twitch_sync_subscriptions();
		break;
	case 'create_subscription':
		if(isset($_POST['channelid'])) {
			$stmt = $db->prepare('INSERT INTO Subscriptions (ChannelID, SubscriptionTypeID) VALUES (?, ?)');
			$stmt->execute(array($_POST['channelid'], $_POST['subtypeid']));
			$id = $db->lastInsertId;
		}
		
		twitch_sync_subscriptions();
		return $id;
		
		break;
	case 'create_service':
		if(isset($_POST['typeid'])) {
			$stmt = $db->prepare('INSERT INTO Action_Services (UserID, ServiceTypeID, Host, Port, Path, Username, `Password`) VALUES (?,?,?,?,?,?,?)');
			$stmt->execute(array($_SESSION['user'], $_POST['typeid'], $_POST['host'], $_POST['port'], $_POST['path'], $_POST['username'], $_POST['password']));
			return $db->lastInsertId;
		}
		
		break;
	case 'create_action':
		if(isset($_POST['ActionServiceID'])) {
			$stmt = $db->prepare('INSERT INTO Actions (ActionServiceID, SubscriptionID, `Field`, ValueTemplate) VALUES (?, ?, ?, ?)');
			$stmt->execute(array($_POST['ActionServiceID'], $_POST['subscriptionid'], $_POST['field'], $_POST['valuetemplate']));
			return $db->lastInsertId;
		}
		
		twitch_sync_subscriptions();
		break;
	case 'services_field':
		?>
		<label><span class="field">Service:</span> <select name="ActionServiceID" size="1" class="action_service_id">
		<?php
		$stmt = $db->prepare('SELECT ID, Host, Path, Username FROM Action_Services WHERE UserID=? AND ServiceTypeID=?');
		$stmt->execute(array($_SESSION['user'], $_GET['typeid']));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($rows as $row) {
			?>
			<option value="<?=$row['ID']?>"><?=($row['Username']?($row['Username'].'@'):'').$row['Host'].$row['Path']?></option>
			<?php
		}
		?>
		</select></label>
		<?php
		break;
	case 'service_help':
		$stmt = $db->prepare('SELECT TemplateHelp FROM Subscription_Types JOIN Subscriptions ON (Subscriptions.SubscriptionTypeID = Subscription_Types.ID) WHERE Subscriptions.ID=?');
		$stmt->execute(array($_GET['subid']));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo $rows[0]['TemplateHelp'];
		break;
	case 'action_metadata':
		$stmt = $db->prepare('SELECT FieldName FROM Action_Service_Types WHERE ID=?');
		$stmt->execute(array($_GET['typeid']));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = $db->prepare('SELECT TemplateHelp FROM Subscription_Types JOIN Subscriptions ON (Subscriptions.SubscriptionTypeID = Subscription_Types.ID) WHERE Subscriptions.ID=?');
		$stmt->execute(array($_GET['subid']));
		$rows2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$data = array(
			'template_help' => $rows2[0]['TemplateHelp'],
			'field_name' => $rows[0]['FieldName']
		);
		
		echo json_encode($data);
		break;
	case 'twitch_auth':
		$stmt = $db->prepare('SELECT ID FROM Channels WHERE UserID=?');
		$stmt->execute(array($_SESSION['user']));
		$rows = $stmt->fetchAll();
		$channel = $rows[0]['ID'];
		
		parse_str($_POST['hash'], $data);
		
		$stmt = $db->prepare('INSERT INTO Twitch_User_Tokens (ChannelID, ClientID, AccessToken, Scope) VALUES (?,?,?,?)');
		$stmt->execute(array($channel, $clientid, $data['access_token'], $data['scope']));
		break;
}
