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
    Minecraft Crate spraudņa konfigurācija
-----------------------------------------------------
*/

/*
    Vai pēc veiksmīga pirkuma izsūtīt informatīvu paziņojumu uz serveri?
*/
$c[$p]['ingame']['notifications'] = true;

/*
    Kādu ziņu izsūtīt uz serveri?
*/
$c[$p]['ingame']['message'] = "<NICKNAME> tikko iegādājās crate key izmantojot SMS veikalu!";

/*
    Crate iedošanas komanda.
*/
$c[$p]['commands']['giveCrate'] = "cratekey give <NICKNAME> <CRATE> 1";

$c[$p]['prices'] = array(
    "MyServer" => array(
    	90 => 'Item1',
     	155 => 'Item2',
    ),
);

$c['lang'][$p]['lv'] = array(
    "instructions" => "Lai iegādātos <CRATE> crate key par <PRICE> EUR, sūti kodu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai saņemtu atslēgas kodu!",
	# Kļūdas
    "error_empty_nickname" => "Ievadi savu spēlētāja vārdu!",
    "error_empty_server" => "Izvēlies serveri!",
    "error_empty_price" => "Izvēlies cenu!",
    "error_empty_code" => "Ievadi atslēgas kodu!",
    "error_invalid_code" => "Atslēgas kods nav pareizi sastādīts!",
    "error_price_not_listed" => "Izvēlētā cena nav atrasta priekš izvēlētā servera!",
    "money_purchased" => "Crate key veiksmīgi iegādāta. Lai jauka spēlēšana!",
	# Forma
    "form_price" => "Cena",
    "form_code" => "Atslēgas kods",
    "form_player_name" => "Spēlētājs",
    "form_server" => "Serveris",
    "form_select_server" => "Izvēlies serveri",
    "form_price" => "Cena",
    "form_select_price" => "Izvēlies cenu",
    "form_unlock_code" => "Atslēgas kods",
    "form_buy" => "Pirkt",
	# Tabula
    "table_nickname" => "Spēlētājs",
    "table_server" => "Serveris",
    "table_money" => "Crate key",
    "table_date" => "Datums",
    "table_no_buyers" => "Neviens vēl nav iegādājies crate key. Varbūt vēlies būt pirmais?"
);

$c['lang'][$p]['en'] = array(
	"instructions" => "To purchase <CRATE> crate key for <PRICE> EUR, send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b> to receive an unclock code!",
	# Kļūdas
	"error_empty_nickname" => "Enter your nickname!",
	"error_empty_server" => "Select the server!",
	"error_empty_price" => "Select the price!",
	"error_empty_code" => "Enter the unlock code!",
	"error_invalid_code" => "The format of the unlock code is not valid!",
	"error_price_not_listed" => "The selected price has not been found for the selected server!",
	"money_purchased" => "The crate key was purchased successfully. Have fun!",
	# Forma
	"form_price" => "Price",
	"form_code" => "Unlock code",
	"form_player_name" => "Player",
	"form_server" => "Server",
	"form_select_server" => "Select server",
	"form_price" => "Price",
	"form_select_price" => "Select price",
	"form_unlock_code" => "Unlock code",
	"form_buy" => "Buy",
	# Tabula
	"table_nickname" => "Player",
    "table_server" => "Server",
    "table_money" => "Crate key",
    "table_date" => "Date",
    "table_no_buyers" => "No one has bought any crate key yet. Would you like to be the first?"
);
/*
-----------------------------------------------------
    Minecraft Crate spraudņa konfigurācija
-----------------------------------------------------
*/
$db = new db($mc['db']['host'], $mc['db']['username'], $mc['db']['password'], $mc['db']['database']);
if($db->connected === false) die(baltsms::alert("Nevar izveidot savienojumu ar MySQL serveri. Pārbaudi norādītos pieejas datus!", "danger"));
$lang[$p] = $c['lang'][$p][$c['page']['lang_personal']];

if(isset($_POST['code'])):
	$errors = array();

	if(empty($_POST['nickname'])){
		$errors[] = $lang[$p]['error_empty_nickname'];
	}

	if(empty($_POST['server'])){
		$errors[] = $lang[$p]['error_empty_server'];
	}

	if(empty($_POST['price']) AND !empty($_POST['server'])){
		$errors[] = $lang[$p]['error_empty_price'];
	}else{
		if(!isset($c[$p]['prices'][$_POST['server']][$_POST['price']])){
			$errors[] = $lang[$p]['error_price_not_listed'];
		}
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
		$baltsms->setPrice($_POST['price']);
		$baltsms->setCode($_POST['code']);
		$baltsms->sendRequest();
		if($baltsms->getResponse() === true){
			$giveCrate = str_replace(
				array("<NICKNAME>", "<CRATE>"),
				array($_POST['nickname'], $c[$p]['prices'][$_POST['server']][$_POST['price']]),
				$c[$p]['commands']['giveCrate']
				);
			$mc['rcon'][$_POST['server']]->send_command($giveCrate);
			if($c[$p]['ingame']['notifications'] === true){
				$sendMessage = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$c[$p]['ingame']['message']
				);
				$mc['rcon'][$_POST['server']]->send_command("say " . $sendMessage);
			}
			
			$paymentStatus = 1;
			echo baltsms::alert($lang[$p]['money_purchased'], "success");
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
	<form class="form-horizontal" method="POST" name="<?php echo $p; ?>" id="<?php echo $p; ?>">
		<div id="alerts"></div>
		<div class="form-group">
			<label for="nickname" class="col-sm-2 control-label"><?php echo $lang[$p]['form_player_name']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="nickname" placeholder="<?php echo $lang[$p]['form_player_name']; ?>">
			</div>
		</div>
		<div class="form-group">
			<label for="server" class="col-sm-2 control-label"><?php echo $lang[$p]['form_server']; ?></label>
			<div class="col-sm-10">
				<select class="form-control" name="server" onChange="listPrices('none', this.value)">
					<option selected disabled><?php echo $lang[$p]['form_server']; ?></option>
					<?php foreach($c[$p]['prices'] as $server => $data): ?>
						<?php if($mc['servers'][$server]->show !== false): ?>
							<option value="<?php echo $server; ?>"><?php echo $mc['servers'][$server]->title; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="price" class="col-sm-2 control-label"><?php echo $lang[$p]['form_price']; ?></label>
			<div class="col-sm-10">
				<select class="form-control" id="prices">
					<option selected disabled><?php echo $lang[$p]['form_select_server']; ?></option>
				</select>
				<?php foreach($c[$p]['prices'] as $server => $prices): ?>
					<select class="form-control prices" name="price" id="none-<?php echo $server; ?>-prices" style="display: none;" onChange="changePrice(this); getvalue(this);" disabled>
						<option selected disabled><?php echo $lang[$p]['form_select_price']; ?></option>
						<?php foreach($prices as $price_code => $crate): ?>
							<option value="<?php echo $price_code; ?>" data-length="<?php echo $crate; ?> crate key"><?php echo $crate; ?> crate key - <?php echo baltsms::returnPrice($price_code); ?> EUR</option>
						<?php endforeach; ?>
					</select>
				<?php endforeach;  ?>
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
			<div id="baltsms-form-button">
				<button type="submit" class="btn btn-primary"><?php echo $lang[$p]['form_buy']; ?></button>
			</div>
		</div>
	</form>
<?php endif; ?>