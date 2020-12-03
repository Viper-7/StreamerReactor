<?php
include 'src/userauth.php';
include 'src/twitch_auth.php';

?>
<!doctype html>
<html>
<head><title>StreamerReactor</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js" integrity="sha384-qlmct0AOBiA2VPZkMY3+2WqkHtIQ9lSdAsAn5RUJD/3vA5MKDgSGcdmIv4ycVxyn" crossorigin="anonymous"></script>
<style type="text/css">
	html, body {
		max-width: 800px;
		margin: 0 auto;
	}
	div.channel .header {
		margin: 0 auto;
		margin-top: 16px;
		text-align: center;
	}
	div.channel .header h2 {
		display: inline;
		border: 1px solid #777;
		background-color: #f0f0f0;
		padding: 8px;
		margin: 0 auto;
		font-size: x-large;
		font-family: Verdana;
	}
	div.cta {
		text-align: center;
	}
	.cta a {
		margin: 0 48px;
	}
	div.cta a, div.cta a:hover, div.cta a:active, div.cta a:visited {
		color: #000;
	}
	div.event {
		background-color: #eeeeee;
	}
	div.service_target {
		display: inline;
	}
	span.field {
		display: inline-block;
		width: 150px;
	}
	div.form_target3 {
		margin: 8px;
		border: 1px solid #999;
		padding: 8px;
		display: none;
	}
	div.action {
		margin: 8px 0;
	}
	.template_help {
		
	}
	.leftdel {
		float: left;
		font-size: x-large;
		margin-left: 8px;
		margin-right: 12px;
	}
	.leftdel a, .leftdel a:hover, .leftdel a:visited, .leftdel a:active {
		text-decoration: none;
		color: #C55;
		font-family: Verdana;
	}
	.template_description {
		max-width: 600px;
		margin: 16px;
		padding: 8px;
		background-color: #fff;
		color: #000;
		border: 1px solid #444;
		white-space: pre;
		font-family: monospace;
	}
	span.note {
		color: #666;
		font-size: small;
	}
</style>
</head>
<body>
<div class="content">
<div id="channel_list"></div>
<br>
<br>
<div id="add_channel" style="display: none;">
	<form method="post" id="add_channel_form">
	<label><span class="field">URL:</span> http://twitch.tv/<input type="text" name="slug" size="15" placeholder="KappaStream"></label><br>
	<br><br>
	<input type="submit" value="Create Channel"> <input type="button" name="cancel" class="cancel" value="Cancel">
	</form>
</div>
<br><div class="cta"><a href="/manage/channels/add" id="add_channel_link">Add a channel</a></div><br>
<div id="add_event" style="display: none;">
	<form method="post" class="add_event_form">
	<label><span class="field">Type:</span> <select name="subtypeid" size="1">
	<?php
	$stmt = $db->prepare('SELECT ID, Name, NeedsAccess FROM Subscription_Types');
	$stmt->execute();
	$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$stmt = $db->prepare('SELECT Twitch_User_Tokens.ID FROM Twitch_User_Tokens JOIN Channels ON (Twitch_User_Tokens.ChannelID = Channels.ID) WHERE Channels.UserID=?');
	$stmt->execute(array($_SESSION['user']));
	$rights = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$hasrights = count($rights) == 1;
	foreach($subs as $sub) {
		if($sub['NeedsAccess'] && !$hasrights) continue;
		?>
		<option value="<?=$sub['ID']?>"><?=$sub['Name']?></option>
		<?php
	}
	?>
	</select></label><br>
	<br>
	<input type="submit" value="Create Event"> <input type="button" name="cancel" class="cancel" value="Cancel">
	</form>
</div>
<div id="add_action" style="display: none;">
	<div class="form_target3"></div>
	<form method="post" class="add_action_form">
	<label><span class="field">Type:</span> <select name="servicetypeid" size="1" class="service_type_id">
	<?php
	$stmt = $db->prepare('SELECT ID, Name FROM Action_Service_Types WHERE Public=1');
	$stmt->execute();
	$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($types as $type) {
		?>
		<option value="<?=$type['ID']?>"><?=$type['Name']?></option>
		<?php
	}
	?>
	</select></label><br>
	<div class="service_target">
	<label><span class="field">Service:</span> <select name="ActionServiceID" size="1" class="action_service_id">
		<?php
		$stmt = $db->prepare('SELECT ID, Name FROM Action_Services WHERE UserID=? AND ServiceTypeID=?');
		$stmt->execute(array($_SESSION['user'], $types[0]['ID']));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($rows as $row) {
			?>
			<option value="<?=$row['ID']?>"><?=$row['Name']?></option>
			<?php
		}
		?>
		</select></label>
	</div> <a href="" class="add_service add_service_link">New Service</a><br>
	<div class="addservice_field_container">
	<label><span class="field addservice_field">Channel:</span> <input type="text" name="field" placeholder="#kappastream"></label><br>
	</div>
	<label><span class="field addservice_template">Template:</span> <input type="text" name="valuetemplate" size="60" placeholder="#user_name# just subscribed!" value="#user_name#"></label><br>
	<span style="margin-left: 20%;" class="note">#tags# will be replaced as below</span>
	<div class="template_description"></div>
	<br>
	<input type="submit" value="Create Action"> <input type="button" name="cancel" class="cancel" value="Cancel">
	</form>
</div>
<div id="add_service" style="display: none;">
	<form method="post" class="add_service_form">
	<input type="hidden" name="typeid" class="typeid">
	<div class="notmqttfields">
	<label><span class="field">Host:</span> <input type="text" name="host" placeholder="irc.chat.twitch.tv" class="irchost"></label><br>
	<label><span class="field">Port:</span> <input type="text" name="port"><span class="note"> you can leave this blank</span></label><br>
	<div class="addservice_path"><label><span class="field">Path:</span> <input type="text" name="path" placeholder="/handler.php"></label></div>
	<label><span class="field">Username:</span> <input type="text" name="newur" placeholder="KappaBot" autocomplete="chrome-off"><span class="note"> (optional)</span></label><br>
	<label><span class="field">Password:</span> <input type="password" name="newpr" placeholder="oauth:df9879dfs87g98s7df123ds3" autocomplete="new-password"><span class="note notirchelp"> (optional)</span><span class="note irchelp"> <a href="https://twitchapps.com/tmi/" target="_blank">Click Here</a> to generate a password for twitch chat</span></label><br>
	<br>
	<input type="submit" value="Create Service"> <input type="button" name="cancel" class="cancel" value="Cancel">
	</div>
	<div class="mqttfields" style="display: none;"></div>
	</form>
</div>
</div>
</body>
<script type="text/javascript">
	$(function() {
		function refreshChannels() {
			$.get('/ajax/getchannels', function(data) {
				$('#channel_list').html(data);
				
				$('.add_subscription_link').click(function(e) {
					var el = $(this).parent().parent().find('.form_target');
					var id = $(this).parent().parent().find('input.event_channelid');
					el.html($('#add_event').html());
					$('.add_subscription_link').hide();
					el.find('form').append(id.parent().html());
					el.find('input.cancel').click(function(e) {
						el.hide();
						$('.add_subscription_link').show();
						e.preventDefault();
					});
					el.show();
					
					$('.add_event_form').ajaxForm({url: '/ajax/create_subscription', type: 'post', beforeSubmit: function() {
						
					}, success: function() {
						$('.form_target').html('');
						$('.add_subscription_link').show();
						refreshChannels();
					}});
					return e.preventDefault();
				});
				
				$('.action_delete_link').click(function(e) {
					$.get('/ajax/delete_action', {action: $(this).attr('data-id')}, function() {
						refreshChannels();
					});
					e.preventDefault();
				});
				$('.del_event_link').click(function(e) {
					$.get('/ajax/delete_event', {event: $(this).attr('data-id')}, function() {
						refreshChannels();
					});
					e.preventDefault();
				})
				$('.add_action_link').click(function(e) {
					var el = $(this).parent().parent().find('.form_target2');
					var id = $(this).parent().parent().find('input.event_subid');
					var link = $(this);
					
					link.hide();
					el.html($('#add_action').html());
					el.find('form').append(id.parent().html());
					el.find('input.cancel').click(function(e) {
						el.hide();
						$('.add_action_link').show();
						e.preventDefault();
					});
					
					$.get('/ajax/action_metadata', {typeid: el.find('.service_type_id').val(), subid: id.val()}, function(data) {
						el.find('.template_description').html(data.template_help);
						el.find('.addservice_field').text(data.field_name + ':');
					}, 'json');

					
					$('.service_type_id').change(function(e) {
						var el2 = $(this).parent().parent().find('.service_target');
						
						$.get('/ajax/services_field', {typeid: $(this).val()}, function(data) {
							el2.html(data);
						}, 'text');
						$.get('/ajax/action_metadata', {typeid: $(this).val(), subid: id.val()}, function(data) {
							el2.parent().parent().find('.template_description').html(data.template_help);
							if(data.type_id == 4 && data.mqtt > 0) { 
								$('.add_service_link').hide();
							} else {
								$('.add_service_link').show();
							}
							$('.addservice_field').text(data.field_name + ':');
						}, 'json');
						
						if($(this).val() == 4) {
							$('.addservice_field_container').hide();
						} else {
							$('.addservice_field_container').show();
						}
					});
					
					var el2 = el.find('.service_target');
					$.get('/ajax/services_field', {typeid: $('.service_type_id').val()}, function(data) {
						el2.html(data);
					}, 'text');

					$('.add_action_form').ajaxForm({url: '/ajax/create_action', type: 'post', beforeSubmit: function() {
						
					}, success: function() {
						refreshChannels();
					}});
					
					$('.add_service').click(function(e) {
						var el3 = $(this).parent().parent().find('.form_target3');
						el3.html($('#add_service').html());
						el3.show();
						
						$('.add_service_link').hide();
						
						el3.find('input.cancel').click(function(e) {
							el3.hide();
							$('.add_service').show();
							e.preventDefault();
						});
						
						var typeid = el3.parent().find('.service_type_id').val();
						el3.parent().find('.typeid').val(typeid);
						if(typeid == 1) {
							el3.parent().find('.addservice_path').hide();
							el3.parent().find('.irchelp').show();
							el3.parent().find('.notirchelp').hide();
							$('.irchost').attr('placeholder', 'irc.chat.twitch.tv');
						} else if(typeid == 4) {
							$.post('/ajax/create_mqtt', {}, function(res) {
								el3.parent().find('.notmqttfields').hide();
								el3.parent().find('.mqttfields').html(res);
								el3.parent().find('.mqttfields').show();
								$('.dismissmqtt').click(function() {
									$('.form_target3').html('');
									$('.form_target3').hide();
									
									var el2 = $('.service_target');
									
									$.get('/ajax/services_field', {typeid: $(this).val()}, function(data) {
										el2.html(data);
									}, 'text');
								})
							}, 'text');
						} else {
							el3.parent().find('.addservice_path').show();
							el3.parent().find('.irchelp').hide();
							el3.parent().find('.notirchelp').show();
							$('.irchost').attr('placeholder', 'www.mywebsite.com');
						}
						
						$('.add_service_form').ajaxForm({url: '/ajax/create_service', type: 'post', beforeSubmit: function() {
							$('.form_target3').html('');
							$('.form_target3').hide();
						}, success: function() {
							link.show();
							$('.add_service_link').show();
							
							var root = el3.parent();
							var el2 = root.find('.service_target');
							$.get('/ajax/services_field', {typeid: root.find('.service_type_id').val()}, function(data) {
								el2.html(data);
							}, 'text');
							$.get('/ajax/action_metadata', {typeid: root.find('.service_type_id').val()}, function(data) {
								$(this).parent().parent().find('.template_description').html(data.template_help);
								$('.addservice_field').text(data.field_name + ':');
							}, 'json');
						}});
						
						return e.preventDefault();
					});
					el.show();
					return e.preventDefault();
				})
				
			});
			
		}
		
		$('#add_channel_link').click(function(e) {
			$('#add_channel').show();
			$('.add_channel_link').hide();
			return e.preventDefault();
		});
		
		el = $('#add_channel');
		el.find('input.cancel').click(function(e) {
			el.hide();
			$('.add_channel_link').show();
			e.preventDefault();
		});

		$('#add_channel_form').ajaxForm({url: '/ajax/create_channel', type: 'post', beforeSubmit: function() {
			$('#add_channel').hide();
		}, success: function() {
			$('.add_channel_link').show();
			refreshChannels();
		}});
		refreshChannels();
	});
</script>
</html>