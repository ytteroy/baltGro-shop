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

function is_ssl_active() {
  if (isset($_SERVER['HTTP_CF_VISITOR'])) {
    $cf_visitor = json_decode($_SERVER['HTTP_CF_VISITOR']);
    if (isset($cf_visitor->scheme) && $cf_visitor->scheme == 'https') {
      return true;
    }
  } else if (isset($_SERVER['HTTPS'])) {
    return true;
  }
  return false;
}

define("config_present", true);
$c = [];
$c['dir'] = realpath(dirname(__FILE__));
$c['url'] = "http" . (is_ssl_active() ? "s" : "") . "://" . $_SERVER['SERVER_NAME'];

/*
-----------------------------------------------------
Konfigurāciju rediģēt drīkst pēc šīs līnijas
-----------------------------------------------------
*/

/*
    Šis ir Tavs klienta ID, kuru var atrast Airtel E-commerece kontroles panelī
*/
$c['sms']['client_id'] = 1;

/*
    Šis ir Tavs Airtel E-commerce kontroles paneļa reģistrētais atslēgas vārds, kurš tiks uzrādīts pie SMS sūtīšanas instrukcijām
*/
$c['sms']['keyword'] = "ART";

/*
    Šis ieslēdz SMS debug, kas ļaus izmantot zemāk norādīto kodu, lai testētu pakalpojumu pieslēgšanu pēc tās apmaksas (ieslēgt/izslēgt - true/false)
*/
$c['sms']['debug'] = false;

/*
    Šis ir SMS debug atslēgas kods, kurš pieļaus neapmaksātu pakalpojumu apstiprinājumu kamēr SMS debug būs ieslēgts
*/
$c['sms']['debug_code'] = 145696519;

/*
    Šis ir Tavs SMS sistēmas telefona numurs uz kuru tiks sūtīts atslēgas vārda pieprasījums. Nemaini, ja Airtel to nepieprasa mainīt
*/
$c['sms']['number'] = 144;

/*
    Šis ir spraudņu tips, kurš tiks ielādēts pirmais uz lapas ielādi
*/
$c['sms']['primary'] = "web";

/*
    Šis ir spraudņu saraksts, kas tiek ievadīts masīvā. Lūdzu ievadi tos spraudņus, kurus vēlies redzēt savā veikalā un tos, kuri pastāv /plugins folderī
*/
$c['sms']['plugins'] = [
	"web" => [
        "donate",
    ],
    "mc" => [
        "mc_group",
        "mc_crate",
        "mc_unban",
        "mc_money",
    ],
	"amx" => [
		"amx_vip",
		"amx_admin",
	],
];

/*
    Šī ir direktorija pēc ROOT direktorijas, kas noved uz SMS veikala failiem
	Ja pilnā direktorija uz veikalu ir /home/user/public_html/sms, tad jāievada sms.
*/
$c['page']['directory'] = "sms";

/*
    Šis ļaus rediģēt lapas nosaukumu, kas ir <title> saturā
*/
$c['page']['title'] = "Airtel veikals";

/*
    Šī ir sistēmas diagnostika, kura ieslēdz kļūdu reportēšanu. Lūdzu nesajauc šo ar SMS sistēmas debug
*/
$c['page']['debug'] = true;

/*
    Tava veikala platums - tiek izmantoti bootstrap responsīvie elementi
	
	** ja ievadi šādi: [8, 2]
	** tad kods būs šāds: col-md-8 col-md-offset-2
	
	dokumentācija: http://getbootstrap.com/css/#grid
*/
$c['page']['size'] = [8, 2];

/*
    Šis ieslēdz valodas karodziņu izvēli, kas ļaus mainīt aplikācijas valodu (ieslēgt/izslēgt - true/false)
*/
$c['page']['language'] = true;

/*
    Veikala noklusējuma valoda
*/
$c['page']['default_lang'] = "lv";

/*
    Valodas definīcijas
*/
$c['lang']['lv'] = [
	"instructions" => "Sūti īsziņu ar tekstu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai iegādātos atslēgas kodu. <SUBSTART>Cena ir <PRICE> EUR, kas tiks pievienota telefona rēķinam vai atrēķināta no priekšapmaksas kartes<SUBEND>",
    "code_wrong_price" => "Norādītais atslēgas kods nav derīgs priekš izvēlētās summas!",
    "code_not_found" => "Norādītais atslēgas kods nav atrasts sistēmā!",
    "code_unkown_response" => "Sazinies ar administratoru nododot sekojošo atbildi: ",
    "plugin-type-web" => "Website",
    "plugin-type-mc" => "Izvēlies",
	"plugin-donate" => "Atbalstīt projektu",
    "plugin-mc_group" => "Grupas",
    "plugin-mc_unban" => "Bana noņemšana",
    "plugin-mc_money" => "Nauda",
    "plugin-mc_exp" => "EXP",
    "plugin-mc_fpower" => "Frakcijas Spēks",
    "plugin-mc_fpower-expiry" => "Frakcijas Spēks",
    "plugin-mc_fpeaceful" => "Frakcijas Peaceful",
    "plugin-mc_unjail" => "Unjail",
    "plugin-mc_register" => "Reģistrācija",
    "plugin-mc_say" => "Čata ziņa",
    "plugin-mc_crate" => "Crate keys",
    "plugin-amx_admin" => "AMX admin",
    "plugin-amx_vip" => "AMX VIP",
	"pay_with_paypal" => "Maksāt ar Airtel Payhub",
    "plugin_not_found" => "[plugin-not-found] Spraudnis netika atrasts. Pārbaudi vai fails <strong>plugins/<PLUGIN></strong> eksistē!"
];

$c['lang']['en'] = [
	"instructions" => "Send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b>, to receive an unclock code. <SUBSTART>Price is <PRICE> EUR, that will be added to the phone bill or deducted from the pre-paid card<SUBEND>",
    "code_wrong_price" => "The specified unlock code is not associated with the price chosen!",
    "code_not_found" => "The specified unlock code has not been found in the database!",
    "code_unkown_response" => "Contact the administrator by passing on this message: ",
    "plugin-type-web" => "Website",
    "plugin-type-mc" => "Minecraft",
    "plugin-donate" => "Donate",
    "plugin-mc_group" => "Groups",
    "plugin-mc_unban" => "Ban removal",
    "plugin-mc_money" => "Money",
    "plugin-mc_exp" => "EXP",
    "plugin-mc_fpower" => "Faction Power",
    "plugin-mc_fpower-expiry" => "Faction Power",
    "plugin-mc_fpeaceful" => "Faction Peaceful",
    "plugin-mc_unjail" => "Unjail",
    "plugin-mc_register" => "Registration",
    "plugin-mc_say" => "Chat message",
    "plugin-mc_crate" => "Crate keys",
	"plugin-amx_admin" => "AMX admin",
    "plugin-amx_vip" => "AMX VIP",
	"pay_with_paypal" => "Pay with Airtel Payhub",
    "plugin_not_found" => "[plugin-not-found] Plugin was not found. Check if the file <strong>plugins/<PLUGIN></strong> exists!"
];

/*
-----------------------------------------------------
Konfigurāciju rediģēt drīkst līdz šai līnijai
-----------------------------------------------------
*/
if($c['page']['debug'] === true){
    error_reporting(E_ALL | E_STRICT);
    ini_set("display_errors", 1);
}else{
    error_reporting(0);
    ini_set("display_errors", 0);
}

$c['page']['lang_personal'] = (isset($_COOKIE['baltsms_language'])) ? $_COOKIE['baltsms_language'] : $c['page']['default_lang'];
$lang = $c['lang'][$c['page']['lang_personal']];

require $c['dir'] . '/system/functions.php';
require $c['dir'] . '/system/db.class.php';
require $c['dir'] . '/system/baltsms.class.php';