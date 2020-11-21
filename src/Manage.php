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
	div.cta a, div.cta a:hover, div.cta a:active, div.cta a:visited {
		color: #000;
	}
	div.event {
		background-color: #eeeeee;
	}
	div.action {
		background-color: #e0e0e0;
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
</style>
</head>
<body>
<div class="content">
<div id="channel_list"></div>
<br><br><div class="cta"><a href="/manage/channels/add" id="add_channel_link">Add a channel</a></div><br>
<br>
<div id="add_channel" style="display: none;">
	<form method="post" id="add_channel_form">
	<label><span class="field">Name:</span> <input type="text" name="name" value="" size="40"></label><br>
	<label><span class="field">URL:</span> http://twitch.tv/<input type="text" name="slug" size="15"></label><br>
	<br>
	<input type="submit" value="Create Channel">
	</form>
</div>
<div id="add_event" style="display: none;">
	<form method="post" class="add_event_form">
	<label><span class="field">Type:</span> <select name="subtypeid" size="1">
	<?php
	$stmt = $db->prepare('SELECT ID, Name FROM Subscription_Types');
	$stmt->execute();
	$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach($subs as $sub) {
		?>
		<option value="<?=$sub['ID']?>"><?=$sub['Name']?></option>
		<?php
	}
	?>
	</select></label><br>
	<br>
	<input type="submit" value="Create Event">
	</form>
</div>
<div id="add_action" style="display: none;">
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
	<div class="form_target3"></div>
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
	</div> <a href="" class="add_service add_service_link">Add Service</a><br>
	<label><span class="field">Field:</span> <input type="text" name="field"></label><br>
	<label><span class="field">Template:</span> <input type="text" name="valuetemplate"></label><br>
	<br>
	<input type="submit" value="Create Action">
</div>
<div id="add_service" style="display: none;">
	<form method="post" class="add_service_form">
	<label><span class="field">Host:</span> <input type="text" name="host"></label><br>
	<label><span class="field">Port:</span> <input type="text" name="port"></label><br>
	<label><span class="field">Path:</span> <input type="text" name="path"></label><br>
	<label><span class="field">Username:</span> <input type="text" name="username"></label><br>
	<label><span class="field">Password:</span> <input type="password" name="password"></label><br>
	<br>
	<input type="submit" value="Create Service">
</div>
<br>
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
					el.find('form').append(id.parent().html());
					el.show();
					
					$('.add_event_form').ajaxForm({url: '/ajax/create_subscription', type: 'post', beforeSubmit: function() {
						$('.add_subscription_link').hide();
					}, success: function() {
						$('.form_target').html('');
						$('.add_subscription_link').show();
						refreshChannels();
					}});
					return e.preventDefault();
				});
				
				$('.add_action_link').click(function(e) {
					var el = $(this).parent().parent().find('.form_target2');
					var id = $(this).parent().parent().find('input.event_subid');
					el.html($('#add_action').html());
					el.find('form').append(id.parent().html());
					
					$('.service_type_id').change(function(e) {
						var el2 = $(this).parent().parent().find('.service_target');
						$.get('/ajax/services_field', {typeid: $(this).val()}, function(data) {
							el2.html(data);
						}, 'text');
					});
					
					$('.add_service').click(function(e) {
						var el3 = $(this).parent().parent().find('.form_target3');
						el3.html($('#add_service').html());
						el3.show();
						
						$('.add_service_form').ajaxForm({url: '/ajax/create_service', type: 'post', beforeSubmit: function() {
							$('.add_service_link').hide();
						}, success: function() {
							$('.form_target3').html('');
							$('.form_target3').hide();
							$('.add_service_link').show();
							
							var root = $(this).parent().parent().parent();
							var el2 = root.find('.service_target');
							$.get('/ajax/services_field', {typeid: root.find('.service_type_id')[0].val()}, function(data) {
								el2.html(data);
							}, 'text');							
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
			return e.preventDefault();
		});
		
		$('#add_channel_form').ajaxForm({url: '/ajax/create_channel', type: 'post', beforeSubmit: function() {
			$('#add_channel').hide();
		}, success: function() {
			refreshChannels();
		}});
		refreshChannels();
	});
</script>
</html>