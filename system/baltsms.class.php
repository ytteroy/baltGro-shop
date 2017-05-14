<?php
/*
    baltGro - SMS/PayPal maksājumu sistēmas gatavais risinājums
    baltGro ir aplikācija, kura saistās ar baltGro SMS/PayPal un uzturēšanas risinājumiem. Šo aplikācija drīkst izmantot tikai baltgro.lv klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un tās spraudņus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
	https://twitter.com/mrYtteroy
*/

class baltsms{
	# BaltSMS API Saite uz kuru tiks izsaukts pieprasījums
	protected $baltsms_api_url = "https://sys.airtel.lv/";
	# Atbilde
	public $response;
	# Cenas kods
	protected $price_code;
	# Saņemtais atslēgas kods
	protected $code;
	
	public static function alert($string, $type){
		return '<div class="alert alert-' . $type . '">' . $string . '</div>';
	}
	
	public static function createTable($plugin, $table){
		global $db;
		if($plugin == "donate"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `message` varchar(250) NOT NULL, `amount` int(5) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_group"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `mc_group` varchar(25) NOT NULL, `length` int(5) NOT NULL, `time` varchar(10) NOT NULL, `expires` varchar(10) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_money"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `amount` int(10) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_crate"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_exp"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `exp` int(10) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_fpower"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `power` int(10) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_fpower-expiry"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `power` int(10) NOT NULL, `time` varchar(10) NOT NULL, `expires` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_fpeaceful"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `length` int(5) NOT NULL, `time` varchar(10) NOT NULL, `expires` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "amx_admin"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `player_id` INT(16) NOT NULL, `server` varchar(25) NOT NULL, `access` varchar(32) NOT NULL, `time` varchar(10) NOT NULL, `expires` int(16) NOT NULL ,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "amx_vip"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `player_id` INT(16) NOT NULL, `server` varchar(25) NOT NULL, `access` varchar(32) NOT NULL, `time` varchar(10) NOT NULL, `expires` int(16) NOT NULL ,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}
	}
	
	public static function returnPrice($price_code){
		$price_code = $price_code * 0.01;
		return number_format($price_code, 2, ".", "");
	}
	
	public static function instructionTemplate($template, $data = array()){
		global $c;
		return str_replace(
			array(
				"<PRICE>",
				"<CODE>",
				"<LENGTH>",
				"<NUMBER>",
				"<KEYWORD>",
				),
			array(
				isset($data['code']) ? '<span id="price">' . $data['price'] . '</span>' : '',
				isset($data['code']) ? '<span id="code">' . $data['code'] . '</span>' : '',
				isset($data['length']) ? '<span id="length">' . $data['length'] . '</span>' : '',
				$c['sms']['number'],
				$c['sms']['keyword']
				),
			$template
			);
	}
	
	public function setPrice($price_code){
		$this->price_code = $price_code;
	}
	
	public function setCode($code){
		$this->code = $code;
	}
	
	private function getUserIP() {
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
	
	private function baltGroupCall($url){
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'baltGroupAPI/1.0'); // drošībai, bloķējam liekos pieprasījumus
		curl_setopt($curl, CURLOPT_HTTPHEADER, array( // pievienojam mazu info par apmeklētāju un serveri
			'User-Ip: ' . $this->getUserIP(),
			'Server-Ip: ' . $_SERVER['SERVER_ADDR'],
			'Server-Url: ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		));
		
		$data = curl_exec($curl);
		curl_close($curl);
		
		return $data;
	}
	
	public function sendRequest(){
		global $c;
		global $p;
		if($c['sms']['debug'] === true AND $this->code == $c['sms']['debug_code']){
			$debug = fopen("../debug.txt", "a+");
			fwrite($debug, "Debugged at: " . date("d/m/y H:i") . "; Price: " . self::returnPrice($this->price_code) . " EUR; Unlock code: " . $this->code . "; Service: " . $p);
			fwrite($debug, PHP_EOL);
			fclose($debug);
			$this->response = ['answer' => 'code_charged_ok'];
		}else{
			$this->response = $this->baltGroupCall($this->baltsms_api_url . 'charge/?code='.$this->code.'&user='.$c['sms']['client_id'].'&price=' . $this->price_code);
			if(is_array(json_decode($this->response, true))){
				$this->response = json_decode($this->response, true);
			}
		}
	}
	
	public function getResponse(){
		global $c;
		if(is_array($this->response)){
			return true;
		}else{
			return self::alert($c['lang']['lv']['code_unkown_response'] . '<b>' . $this->response . '</b>', "danger");
		}
	}
}