<?php
/*
    baltGro - SMS/PayPal maksājumu sistēmas gatavais risinājums
    baltGro ir aplikācija, kura saistās ar baltGro SMS/PayPal un uzturēšanas risinājumiem. Šo aplikācija drīkst izmantot tikai baltgro.lv klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un tās spraudņus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
	https://twitter.com/mrYtteroy
*/
/*
    NEAIZTIKT! AUTOMĀTISKI DEFINĒTAS VĒRTĪBAS!
*/
define("amx_config_present", true);
$amx = [];
require $c['dir'] . "/system/amxclass/amxbans.class.php";
/*
-----------------------------------------------------
Konfigurāciju rediģēt drīkst pēc šīs līnijas
-----------------------------------------------------
*/

/*
    Datubāzes servera adrese, pēc noklusējuma "localhost"
*/
$amx['db']['host'] = "localhost";

/*
    Datubāzes pieejas lietotājvārds
*/
$amx['db']['username'] = "pwslv";

/*
    Datubāzes pieejas parole
*/
$amx['db']['password'] = "HmZ5X9mTBIsz";

/*
    Datubāzes nosaukums
*/
$amx['db']['database'] = "pwslv_gecms";


$amx['showservers'] = false;

$amx['directory'] = '/home/dir/public_html/amxbans'; // pilna norāde uz amxbans direktoroju. BEZ slīpsvītras beigās. 


$amxclass = new amxbans($amx['directory']); // pieslēdzamies AMXBANS klasei, norādot direktoriju
$amx['servers'] = $amxclass->getServers(); // iegūstam serverus no AMXBANS datubāzes

foreach($amx['servers'] as $type => $data){
	if(file_exists($amx['directory'])){
		if(file_exists($c['dir'] . '/system/amxclass/rcon_hl_net.inc')){
			require_once $c['dir'] . '/system/amxclass/rcon_hl_net.inc';
			$amx['rcon'][$type] = new Rcon();
			$amx['rcon'][$type]->Connect($data['ip'], $data['port'], $data['rcon']);
			if(!$amx['rcon'][$type]->IsConnected()){
				$amx['servers'][$type]['show'] = false;
				echo baltsms::alert("Nav iespējams savienoties ar AMX serveri: <strong>" . $data['title'] . "</strong> (id:".$type."). Pārbaudi pieejas datus!", "danger");
			}
			$amx['rcon'][$type]->Disconnect();
		}
	}
}

if($amx['showservers'] === true){
	if(is_array($amx['servers'])){
		echo '<ul>';
		foreach($amx['servers'] as $id => $data){
			echo '<li>id: <b>'.$id.'</b> - <b>'.$data['title'].'</b></li>';
		}
		echo '</ul>';
	}
}