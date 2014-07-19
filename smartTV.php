<?php

/**
 * ----------------------------------------
 * @title PHP-LG-SmartTV
 * @desc LG SmartTV API
 * @author Steve Winfield
 * @copyright 2014 $AUTHOR$
 * @license see /LICENCE
 * ----------------------------------------
 * https://github.com/SteveWinfield/PHP-LG-SmartTV
**/

if (!extension_loaded('curl')) {
	die ('You have to install/enable curl in order to use this application.');
}

/**
 * Some constants
**/
define ('TV_CMD_POWER', 1);
define ('TV_CMD_NUMBER_0', 2);
define ('TV_CMD_NUMBER_1', 3);
define ('TV_CMD_NUMBER_2', 4);
define ('TV_CMD_NUMBER_3', 5);
define ('TV_CMD_NUMBER_4', 6);
define ('TV_CMD_NUMBER_5', 7);
define ('TV_CMD_NUMBER_6', 8);
define ('TV_CMD_NUMBER_7', 9);
define ('TV_CMD_NUMBER_8', 10);
define ('TV_CMD_NUMBER_9', 11);
define ('TV_CMD_UP', 12);
define ('TV_CMD_DOWN', 13);
define ('TV_CMD_LEFT', 14);
define ('TV_CMD_RIGHT', 15);
define ('TV_CMD_OK', 20);
define ('TV_CMD_HOME_MENU', 21);
define ('TV_CMD_BACK', 23);
define ('TV_CMD_VOLUME_UP', 24);
define ('TV_CMD_VOLUME_DOWN', 25);
define ('TV_CMD_MUTE_TOGGLE', 26);
define ('TV_CMD_CHANNEL_UP', 27);
define ('TV_CMD_CHANNEL_DOWN', 28);
define ('TV_CMD_BLUE', 29);
define ('TV_CMD_GREEN', 30);
define ('TV_CMD_RED', 31);
define ('TV_CMD_YELLOW', 32);
define ('TV_CMD_PLAY', 33);
define ('TV_CMD_PAUSE', 34);
define ('TV_CMD_STOP', 35);
define ('TV_CMD_FAST_FORWARD', 36);
define ('TV_CMD_REWIND', 37);
define ('TV_CMD_SKIP_FORWARD', 38);
define ('TV_CMD_SKIP_BACKWARD', 39);
define ('TV_CMD_RECORD', 40);
define ('TV_CMD_RECORDING_LIST', 41);
define ('TV_CMD_REPEAT', 42);
define ('TV_CMD_LIVE_TV', 43);
define ('TV_CMD_EPG', 44);
define ('TV_CMD_PROGRAM_INFORMATION', 45);
define ('TV_CMD_ASPECT_RATIO', 46);
define ('TV_CMD_EXTERNAL_INPUT', 47);
define ('TV_CMD_PIP_SECONDARY_VIDEO', 48);
define ('TV_CMD_SHOW_SUBTITLE', 49);
define ('TV_CMD_PROGRAM_LIST', 50);
define ('TV_CMD_TELE_TEXT', 51);
define ('TV_CMD_MARK', 52);
define ('TV_CMD_3D_VIDEO', 400);
define ('TV_CMD_3D_LR', 401);
define ('TV_CMD_DASH', 402);
define ('TV_CMD_PREVIOUS_CHANNEL', 403);
define ('TV_CMD_FAVORITE_CHANNEL', 404);
define ('TV_CMD_QUICK_MENU', 405);
define ('TV_CMD_TEXT_OPTION', 406);
define ('TV_CMD_AUDIO_DESCRIPTION', 407);
define ('TV_CMD_ENERGY_SAVING', 409);
define ('TV_CMD_AV_MODE', 410);
define ('TV_CMD_SIMPLINK', 411);
define ('TV_CMD_EXIT', 412);
define ('TV_CMD_RESERVATION_PROGRAM_LIST', 413);
define ('TV_CMD_PIP_CHANNEL_UP', 414);
define ('TV_CMD_PIP_CHANNEL_DOWN', 415);
define ('TV_CMD_SWITCH_VIDEO', 416);
define ('TV_CMD_APPS', 417);
define ('TV_CMD_MOUSE_MOVE', 'HandleTouchMove');
define ('TV_CMD_MOUSE_CLICK', 'HandleTouchClick');
define ('TV_CMD_TOUCH_WHEEL', 'HandleTouchWheel');
define ('TV_CMD_CHANGE_CHANNEL', 'HandleChannelChange');
define ('TV_CMD_SCROLL_UP', 'up');
define ('TV_CMD_SCROLL_DOWN', 'down');
define ('TV_INFO_CURRENT_CHANNEL', 'cur_channel');
define ('TV_INFO_CHANNEL_LIST', 'channel_list');
define ('TV_INFO_CONTEXT_UI', 'context_ui');
define ('TV_INFO_VOLUME', 'volume_info');
define ('TV_INFO_SCREEN', 'screen_image');
define ('TV_INFO_3D', 'is_3d');

class SmartTV {
	public function __construct($ipAddress, $port = 8080) {
		$this->connectionDetails = array($ipAddress, $port);
	}
	
	public function setPairingKey($pk) {
		$this->pairingKey = $pk;
	}
	
	public function displayPairingKey() {
		$this->sendXMLRequest('/roap/api/auth', self::encodeData(
			array('type' => 'AuthKeyReq'), 'auth'
		));
	}
	
	public function setSession($sess) {
		$this->session = $sess;
	}
	
	public function authenticate() {
		if ($this->pairingKey === null) {
			throw new Exception('No pairing key given.');
		}
		return ($this->session = $this->sendXMLRequest('/roap/api/auth', self::encodeData(
			array(
				'type' => 'AuthReq',
				'value' => $this->pairingKey
			),
			'auth'
		))['session']);
	}

	public function processCommand($commandName, $parameters = []) {
		if ($this->session === null) {
			throw new Exception('No session id given.');
		}
		if (is_numeric($commandName) && count($parameters) < 1) {
			$parameters['value'] = $commandName;
			$commandName = 'HandleKeyInput';
		}
		if (is_string($parameters) || is_numeric($parameters)) {
			$parameters = array('value' => $parameters);
		} elseif (is_object($parameters)) {
			$parameters = (array)$parameters;
		}
		$parameters['name'] = $commandName;
		return ($this->sendXMLRequest('/roap/api/command', 
			self::encodeData($parameters, 'command')
		));
	}
	
	public function queryData($targetId) {
		if ($this->session === null) {
			throw new Exception('No session id given.');
		}
		$var = $this->sendXMLRequest('/roap/api/data?target='.$targetId);
		return isset($var['data']) ? $var['data'] : $var;
	}
	
	private function sendXMLRequest($actionFile, $data = '') {
		curl_setopt(($ch = curl_init()), CURLOPT_URL, $this->connectionDetails[0] . ':' . $this->connectionDetails[1] . $actionFile);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/atom+xml',
			'Connection: Keep-Alive'
		));
		if (strlen($data) > 0) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$envar   = curl_exec($ch);
		$execute = (array)@simplexml_load_string($envar);
		if (isset($execute['ROAPError']) && $execute['ROAPError'] != '200') {
			throw new Exception('Error (' . $execute['ROAPError'] . '): ' . $execute['ROAPErrorDetail']);
		}
		return count($execute) < 2 ? $envar : $execute;
	}
	
	private static function encodeData($data, $actionType, $xml=null) {
		if ($xml == null) {
			$xml = simplexml_load_string("<!--?xml version=\"1.0\" encoding=\"utf-8\"?--><".$actionType." />");
		}
		foreach($data as $key => $value) {
			if (is_array($value))  {
				$node = $xml->addChild($key);
				self::encodeData($value, $actionType, $node);
			} else  {
				$xml->addChild($key, htmlentities($value));
			}
		}
		return $xml->asXML();
	}
	
	private $connectionDetails;
	private $pairingKey;
	private $session;
}