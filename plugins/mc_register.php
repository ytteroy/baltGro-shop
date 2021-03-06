<?php
/*
    baltGro - SMS/PayPal maksājumu sistēmas gatavais risinājums
    baltGro ir aplikācija, kura saistās ar baltGro SMS/PayPal un uzturēšanas risinājumiem. Šo aplikācija drīkst izmantot tikai baltgro.lv klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un tās spraudņus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
	https://twitter.com/mrYtteroy
*/

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) OR (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != "xmlhttprequest")) die("Ajax Only!");
$p = basename(__FILE__, ".php");
defined("config_present") or require "../config.inc.php";
defined("mc_config_present") or require "../config.minecraft.php";
in_array($p, $c['sms']['plugins']['mc']) or die(baltsms::alert("Spraudnis nav ievadīts atļauto spraudņu sarakstā!", "danger"));
/*
-----------------------------------------------------
    Minecraft reģistrācijas spraudņa konfigurācija
-----------------------------------------------------
*/

/*
    Vai pēc veiksmīga pirkuma izsūtīt informatīvu paziņojumu uz serveri?
*/
$c[$p]['ingame']['notifications'] = true;

/*
    Kādu ziņu izsūtīt uz serveri?
*/
$c[$p]['ingame']['message'] = "<NICKNAME> just registered on our BaltSMS Shop!";

/*
    Reģistrācijas komanda. Pārliecinies, ka tieši šī komanda sakrīt ar servera lietotāju reģistrācijas komandu!
    Šī komanda stradā ar AuthMe spraudni, bet ja Tu izmanto citu spraudni, pārbaudi tā spraudņa dokumentāciju!
*/
$c[$p]['commands']['register'] = "authme register <NICKNAME> <PASSWORD>";


$c[$p]['prices'] = array(
    "MyServer" => 25,
);

$c['lang'][$p]['lv'] = array(
    "instructions" => "Lai reģistrētos par <PRICE> EUR izvēlētajā serverī, sūti kodu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai saņemtu atslēgas kodu!",
	# Kļūdas
    "error_empty_nickname" => "Ievadi spēlētāja vārdu!",
    "error_username_taken" => "Lietotājvārds aizņemts!",
    "error_empty_password" => "Ievadi paroli!",
    "error_empty_server" => "Izvēlies serveri!",
    "error_empty_code" => "Ievadi atslēgas kodu!",
    "error_invalid_code" => "Atslēgas kods nav pareizi sastādīts!",
    "registration_successful" => "Reģistrācija veiksmīga!",
	# Forma
    "form_price" => "Cena",
    "form_code" => "Atslēgas kods",
    "form_player_name" => "Spēlētājs",
    "form_password" => "Parole",
    "form_server" => "Serveris",
    "form_unlock_code" => "Atslēgas kods",
    "form_register" => "Reģistrēties",
);

$c['lang'][$p]['en'] = array(
    "instructions" => "To register for <PRICE> EUR in the selected server, send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b> to receive an unclock code!",
	# Kļūdas
    "error_empty_nickname" => "Enter a nickname!",
    "error_username_taken" => "Nickname taken!",
    "error_empty_password" => "Enter a password!",
    "error_empty_server" => "Select the server!",
    "error_empty_code" => "Enter the unlock code!",
    "error_invalid_code" => "The format of the unlock code is not valid!",
    "registration_successful" => "Registration successful!",
	# Forma
    "form_price" => "Price",
    "form_code" => "Unlock code",
    "form_player_name" => "Player",
    "form_password" => "Password",
    "form_server" => "Server",
    "form_unlock_code" => "Unlock code",
    "form_register" => "Register",
);
/*
-----------------------------------------------------
    Minecraft reģistrācijas spraudņa konfigurācija
-----------------------------------------------------
*/
$lang[$p] = $c['lang'][$p][$c['page']['lang_personal']];

if(isset($_POST['code'])):
	$errors = array();

	if(empty($_POST['nickname'])){
		$errors[] = $lang[$p]['error_empty_nickname'];
	}else{
		$register = str_replace(
			array("<NICKNAME>", "<PASSWORD>"),
			array($_POST['nickname'], $_POST['password']),
			$c[$p]['commands']['register']
		);
		if(strpos($mc['rcon'][$_POST['server']]->send_command($register), "Username already registered") !== false){
			$errors[] = $lang[$p]['error_username_taken'];
		}
	}

	if(empty($_POST['password'])){
		$errors[] = $lang[$p]['error_empty_password'];
	}

	if(empty($_POST['server'])){
		$errors[] = $lang[$p]['error_empty_server'];
	}

	if(empty($_POST['code'])){
		$errors[] = $lang[$p]['error_empty_code'];
	}else{
		if(strlen($_POST['code']) != 9 OR is_numeric($_POST['code']) === false){
			$errors[] = $lang[$p]['error_invalid_code'];
		}
	}

	if(count($errors) > 0){
		foreach($errors as $error){
			echo baltsms::alert($error, "danger");
		}
	}else{
		$baltsms = new baltsms();
		$baltsms->setPrice($c[$p]['prices'][$_POST['server']]);
		$baltsms->setCode($_POST['code']);
		$baltsms->sendRequest();
		if($baltsms->getResponse() === true){
			$mc['rcon'][$_POST['server']]->send_command($register);
			if($c[$p]['ingame']['notifications'] === true){
				$sendMessage = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$c[$p]['ingame']['message']
				);
				$mc['rcon'][$_POST['server']]->send_command("say " . $sendMessage);
			}
			
			$paymentStatus = 1;
			echo baltsms::alert($lang[$p]['registration_successful'], "success");
			?>
			<script type="text/javascript">
				setTimeout(function(){
					loadPlugin('<?php echo $p; ?>');
				}, 3000);
			</script>
			<?php
		}else{
			echo $baltsms->getResponse();
		}
	}
	
	include '../system/sendstats.php';
	
	else:
?>
	<form class="form-horizontal" method="POST" id="<?php echo $p; ?>">
		<div id="alerts"></div>
		<div class="form-group">
			<label for="nickname" class="col-sm-2 control-label"><?php echo $lang[$p]['form_player_name']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="nickname" placeholder="<?php echo $lang[$p]['form_player_name']; ?>">
			</div>
		</div>
		<div class="form-group">
			<label for="password" class="col-sm-2 control-label"><?php echo $lang[$p]['form_password']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="password" placeholder="<?php echo $lang[$p]['form_password']; ?>">
			</div>
		</div>
		<div class="form-group">
			<label for="server" class="col-sm-2 control-label"><?php echo $lang[$p]['form_server']; ?></label>
			<div class="col-sm-10">
				<select class="form-control" name="server" onChange="changePrice(this); getvalue(this);">
					<?php foreach($c[$p]['prices'] as $server => $price): ?>
						<?php if($mc['servers'][$server]->show !== false): ?>
							<option value="<?php echo $server; ?>" data-price="<?php echo $price; ?>"><?php echo $mc['servers'][$server]->title; ?> - <?php echo baltsms::returnPrice($price); ?> EUR</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div id="buycode"></div>
		<div class="form-group">
			<label for="name" class="col-sm-2 control-label"><?php echo $lang[$p]['form_unlock_code']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="code" placeholder="<?php echo $lang[$p]['form_unlock_code']; ?>" maxlength="9" autocomplete="off">
			</div>
		</div>
		<script>
		var default_price = <?php echo (count($c[$p]['prices']) == count($c[$p]['prices'], COUNT_RECURSIVE) ? reset($c[$p]['prices']) : 0); ?>;
		function getvalue(element, overwrite = 0){
			if(jQuery(element).find(":selected").attr("data-price")){
				price = jQuery(element).find(":selected").data("price");
			}else{
				if(overwrite == 0){
					price = element.value;
				}else{
					price = default_price;
				}
			}
			
			jQuery('#buycode').html('<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw text-center" style="font-size:50px; margin-bottom:10px;"></i>');
			jQuery('#buycode').load('https://sys.airtel.lv/buycode/' + price, function(){
				jQuery('#buycode').fadeIn('fast');
			});
		}
		
		jQuery(document).ready(function(){
			if(default_price <= 0){
				jQuery('#instructions').hide();
			}else{
				getvalue(jQuery('select[name=price]'), default_price);
			}
		});
		</script>

		<div class="form-group">
			<button type="button" class="btn btn-success" style="float:left !important; margin-left: 16px;" onclick="startPayment()"><?php echo $lang['pay_with_paypal']; ?></button>
			<div id="baltsms-form-button">
				<button type="submit" class="btn btn-primary"><?php echo $lang[$p]['form_register']; ?></button>
			</div>
		</div>
	</form>
<?php endif; ?>