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
$amx['db']['host'] = "";

/*
    Datubāzes pieejas lietotājvārds
*/
$amx['db']['username'] = "";

/*
    Datubāzes pieejas parole
*/
$amx['db']['password'] = "";

/*
    Datubāzes nosaukums, kur atrodas servera dati
*/
$amx['db']['database'] = "";

$amx['directory'] = '/home/pwslv/public_html/amxbans'; // pilna norāde uz amxbans direktoroju. BEZ slīpsvītras beigās. 

$amxclass = new amxbans($amx['directory']);

$amx['servers'] = $amxclass->getServers();

foreach($amx['servers'] as $type => $data){
	if(!empty($data->query_port) AND file_exists($amx['directory'])){
		if(file_exists($c['dir'] . '/system/amxclass/rcon_hl_net.inc')){
			require $c['dir'] . '/system/amxclass/rcon_hl_net.inc';
			
			$amx['rcon'][$type] = new Rcon();
			$amx['rcon'][$type]->Connect($data['ip'], $data['port'], $data['rcon']);
			if(!$amx['rcon'][$type]->Info()){
				$data->show = false;
				echo baltsms::alert("Nav iespējams savienoties ar AMX serveri: <strong>" . $data['title'] . "</strong>. Pārbaudi pieejas datus!", "danger");
			}
		}
	}
}