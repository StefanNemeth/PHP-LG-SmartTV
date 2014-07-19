<?php

/**
 * ----------------------------------------
 * Practical - PHP LG SmartTV API
 * ----------------------------------------
 * https://github.com/SteveWinfield/PHP-LG-SmartTV
**/
session_start();
include 'smartTV.php';

/**
 * Create instance of TV
 * @param IP Address of TV
 * (optional) @param Port of TV (default is 8080)
**/
$tv = new SmartTV('192.168.2.103'); // new SmartTV('192.168.2.103', 8080)

/**
 * Check if session already created.
 * If so.. don't request a new one.
**/
if (!isset($_SESSION['SESSION_ID'])) {
	/**
	 * Set pairing key (if you don't know the pairing key
	 *				    execute the method ..->displayPairingKey() and it will
	 * 				    be shown on your tv)
	 * @param Key

	**/
	$tv->setPairingKey(678887); // $tv->displayPairingKey();
	
	/**
	 * Authenticate to the tv
	 * @except Login fails (wrong pairing key?)
	**/
	try {
		$_SESSION['SESSION_ID'] = $tv->authenticate();
	} catch (Exception $e) {
		die('Authentication failed, I am sorry.');
	}
} else {
	$tv->setSession($_SESSION['SESSION_ID']);
}
	
if (isset($_GET['cmd'])) {
	switch($_GET['cmd']) {
		case 'screen':
			header('Content-Type: image/jpeg');
			exit ($tv->queryData(TV_INFO_SCREEN));
			break;
			
		case 'changeChannel':
			if (!isset($_GET['value'])) {
				exit;
			}
			$channelName = strtolower($_GET['value']);
			foreach ($_SESSION['CHANNEL_LIST'] as $channel) {
				if (strtolower($channel['chname']) == $channelName) {
					$tv->processCommand(TV_CMD_CHANGE_CHANNEL, $channel);
					break;
				}
			}
			break;
			
		case 'channelInfo':
			$currentChannel = $tv->queryData(TV_INFO_CURRENT_CHANNEL);
			$text = 'You\'re watching <b>'.$currentChannel->progName.'</b> on <b>'.$currentChannel->chname.'</b>';
			
			if (!isset($_SESSION['CURRENT_CHANNEL']) || strtolower($currentChannel->chname) != strtolower($_SESSION['CURRENT_CHANNEL'])) {
				$text .= '___---___Change channel: <select id="programList">';
				if (!isset($_SESSION['CHANNEL_LIST'])) {
					$_SESSION['CHANNEL_LIST'] = array();
					foreach ($tv->queryData(TV_INFO_CHANNEL_LIST) as $channel) {
						$_SESSION['CHANNEL_LIST'][] = (array)$channel;
					}
				}
				foreach ($_SESSION['CHANNEL_LIST'] as $channel) {
					$text .= '<option value="'.$channel['chname'].'" '.(strtolower($channel['chname']) == strtolower($currentChannel->chname) ? 'selected' : '').'>'.$channel['chname'].'</option>';
				}
				$text .= '</select>';
				$_SESSION['CURRENT_CHANNEL'] = (string)$currentChannel->chname;
			}
			exit ($text);
			break;
			
		case 'arrow':
			if (!isset($_GET['value'])) {
				exit;
			}
			$code = 0;
			switch ($_GET['value']) {
				case 'up':
					$code = TV_CMD_UP;
					break;
				case 'down':
					$code = TV_CMD_DOWN;
					break;
				case 'left':
					$code = TV_CMD_LEFT;
					break;
				case 'right':
					$code = TV_CMD_RIGHT;
					break;

				case 'enter':
					$code = TV_CMD_OK;
					break;
				
				case 'esc':
					$code = TV_CMD_EXIT;
					break;
				
				case 'backspace':
					$code = TV_CMD_BACK;
					break;
				
				case 'h':
					$code = TV_CMD_HOME_MENU;
					break;
			}
			$tv->processCommand($code);
			break;
	}
	exit;
} else {
	unset ($_SESSION['CURRENT_CHANNEL']);
}

?>
<!doctype html>
<html lang="de">
	<head>
		<title>SmartTV Test</title>
	</head>
	<body>
		<p>Here you can control your TV with your arrow keys.</p>
		<img src='index.php?cmd=screen&t=12345' id='liveImage' />
		<p>
			<p id="channelInfo"></p>
			<p id="programInfo"></p>
		</p>
		<p>
			<strong>ENTER</strong> - OK<br />
			<strong>ESC</strong> - EXIT<br />
			<strong>BACKSPACE</strong> - BACK<br />
			<strong>H</strong> - HOME</br>
		</p>
		<!-- SCRIPTS !-->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<script>
		$(document).ready(function() {
			$(document).keydown(function (e) {
				var keyCode = e.keyCode || e.which,
				  arrow = {backspace: 8, esc: 27, enter: 13, left: 37, up: 38, right: 39, down: 40, h: 72 };
				var value = 'up';
				switch (keyCode) {
					case arrow.left:
					  value = 'left';
					  break;
					case arrow.up:
					  value = 'up';
					  break;
					case arrow.right:
					  value = 'right';
					  break;
					case arrow.down:
					  value = 'down';
					  break;
					case arrow.enter:
					  value = 'enter';
					  break;
					case arrow.esc:
					  value = 'esc';
					  break;
					case arrow.backspace:
					  value = 'backspace';
					  break;
					case arrow.h:
					  value = 'h';
					  break;
				}
				$.get("index.php?cmd=arrow&value=" + value);
				e.preventDefault();
			});
			function refreshImage() {
				$("#liveImage").attr("src", "index.php?cmd=screen&t="+(new Date()).getTime());
			}
			function refreshData() {
				$.get("index.php?cmd=channelInfo", function (data) {
					var lData = data.split("___---___");
					$("#channelInfo").html(lData[0]);
					if (lData.length > 1) {
						$("#programInfo").html(lData[1]);
						$("#programList").on('change', function() {
							$.get("index.php?cmd=changeChannel&value=" + this.value);
						});
					}
				});
			}
			refreshData();
			refreshImage();
			setInterval(refreshImage, 500);
			setInterval(refreshData,  1000);
		});
		</script>
		<!-- END !-->
	</body>
</html>