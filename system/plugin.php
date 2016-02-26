<?php
/*
    baltGro - SMS/PayPal maksājumu sistēmas gatavais risinājums
    baltGro ir aplikācija, kura saistās ar baltGro SMS/PayPal un uzturēšanas risinājumiem. Šo aplikācija drīkst izmantot tikai baltgro.lv klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un tās spraudņus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
	https://twitter.com/mrYtteroy
*/

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) OR (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != "xmlhttprequest")) die("Ajax Only!");
if(isset($_POST['plugin'])){
	require "../config.inc.php";
	if($c['sms']['debug'] === true){
		echo baltsms::alert("<center>SMS DEBUG IS TURNED ON!</center>", "warning");
	}
	if(file_exists($c['dir'] . "/plugins/" . $_POST['plugin'] . ".php")){
		include $c['dir'] . "/plugins/" . $_POST['plugin'] . ".php";
	}else{
		echo baltsms::alert(str_replace("<PLUGIN>", $_POST['plugin'] . ".php", $c['lang']['lv']['plugin_not_found']), "danger");
	}
}else{
	exit;
}