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
					Subscriptions.TwitchID,
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
						Action_Services.Name,
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
						<ul>
							<li><?=$action['TypeName'].':'.$action['Name'] . ' - ' . $action['Field'] . ' = ' . $action['ValueTemplate']?></li>
						</ul>
					</div>
					<?php
				}
			?>
			<br>
			<div style="display: none"><input type="hidden" name="subscriptionid" value="<?=$sub['ID']?>" class="event_subid"></div>
			<div class="form_target2"></div>
			<div class="cta"><a href="/manage/actions/add" class="add_action_link">Add an Action</a></div><br>
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
	case 'create_channel':
		$stmt = $db->prepare('INSERT INTO Channels (Name, Slug, UserID) VALUES (?, ?, ?)');
		$stmt->execute(array($_POST['name'], $_POST['slug'], $_SESSION['user']));
		break;
	case 'create_subscription':
		if(isset($_POST['channelid'])) {
			$stmt = $db->prepare('INSERT INTO Subscriptions (ChannelID, SubscriptionTypeID) VALUES (?, ?)');
			$stmt->execute(array($_POST['channelid'], $_POST['subtypeid']));
		}
		
		twitch_sync_subscriptions();
		
		break;
	case 'services_field':
		?>
		<label><span class="field">Service:</span> <select name="ActionServiceID" size="1" class="action_service_id">
		<?php
		$stmt = $db->prepare('SELECT ID, Name FROM Action_Services WHERE UserID=? AND ServiceTypeID=?');
		$stmt->execute(array($_SESSION['user'], $_GET['typeid']));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($rows as $row) {
			?>
			<option value="<?=$row['ID']?>"><?=$row['Name']?></option>
			<?php
		}
		?>
		</select></label>
		<?php
		break;
	
}
