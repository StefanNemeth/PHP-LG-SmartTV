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
			
		case 'info':
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
			
		case 'keyPress':
			if (!isset($_GET['value'])) {
				exit;
			}
			$codes = array(
				'up' => TV_CMD_UP,
				'down' => TV_CMD_DOWN,
				'left' => TV_CMD_LEFT,
				'right' => TV_CMD_RIGHT,
				'enter' => TV_CMD_OK,
				'esc' => TV_CMD_EXIT,
				'backspace' => TV_CMD_BACK,
				'h' => TV_CMD_HOME_MENU
			);
			$tv->processCommand($codes[$_GET['value']]);
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
				  CHAR = {8 : 'backspace', 27 : 'esc', 13 : 'enter', 37 : 'left', 38 : 'up', 39 : 'right', 40 : 'down', 72 : 'h' };
				if (CHAR[keyCode] !== "undefined") {
					$.get("index.php?cmd=keyPress&value=" + CHAR[keyCode]);
					e.preventDefault();
				}
			});
			function refreshImage() {
				$("#liveImage").attr("src", "index.php?cmd=screen&t="+(new Date()).getTime());
			}
			function refreshData() {
				$.get("index.php?cmd=info", function (data) {
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