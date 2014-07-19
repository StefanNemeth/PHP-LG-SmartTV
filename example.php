<?php

/**
 * ----------------------------------------
 * Example - PHP LG SmartTV API
 * ----------------------------------------
 * https://github.com/SteveWinfield/PHP-LG-SmartTV
**/
include 'smartTV.php';

/**
 * Create instance of TV
 * @param IP Address of TV
 * (optional) @param Port of TV (default is 8080)
**/
$tv = new SmartTV('192.168.2.103'); // new SmartTV('192.168.2.103', 8080)

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
	$tv->authenticate();
} catch (Exception $e) {
	die('Authentication failed, I am sorry.');
}

/**
 * Set your volume up.
**/
$tv->processCommand(TV_CMD_VOLUME_UP);

/**
 * Set your volume down
**/
$tv->processCommand(TV_CMD_VOLUME_DOWN);

/**
 * Move your mouse
**/
$tv->processCommand(TV_CMD_MOUSE_MOVE, [ 'x' => 20, 'y' => 20 ]);

/**
 * Trigger a mouse click
**/
$tv->processCommand(TV_CMD_MOUSE_CLICK);

/**
 * Get current volume
**/
echo $tv->queryData(TV_INFO_VOLUME)->level;

/**
 * Get current channel name
**/
echo $tv->queryData(TV_INFO_CURRENT_CHANNEL)->chname;

/**
 * Save a screenshot
**/
file_put_contents('screen.jpeg', $tv->queryData(TV_INFO_SCREEN));

/**
 * Change channel (Channel VIVA)
**/
// Get channel list
$channels    = $tv->queryData(TV_INFO_CHANNEL_LIST);

// Channel name
$channelName = 'VIVA';

// Search for channel $channelName
foreach ($channels as $channel) {
	if ($channel->chname == $channelName) {
		// Change channel
		$tv->processCommand(TV_CMD_CHANGE_CHANNEL, $channel);
		break;
	}
}