<?php
if(!isset($c['statistics']['enable']) OR $c['statistics']['enable'] === true){
	function getUserIP() {
		if( array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')>0) {
				$addr = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
				return trim($addr[0]);
			} else {
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		else {
			return $_SERVER['REMOTE_ADDR'];
		}
	}
	
	$replaceUrl = ['http://' => '', 'https://' => '', 'www.' => ''];
	
	$data = [];
	
	$data['user'] = $c['sms']['client_id'];
	$data['project'] = strtr($c['url'], $replaceUrl) . '/' . $c['page']['directory'];
	$data['url'] = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	$data['form'] = [];
	array_push($data['form'], $_POST);

	$data['paymentStatus'] = (isset($paymentStatus) ? 1 : 0);
	$data['errors'] = $errors;
	$data['userIP'] = getUserIP();
	$data['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

	$url = 'https://sys.airtel.lv/sendstats/?' . http_build_query($data);
	@file_get_contents($url);
}