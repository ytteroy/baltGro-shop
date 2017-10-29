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
    Minecraft frakciju peaceful spraudņa konfigurācija
-----------------------------------------------------
*/

/*
    Frakciju peaceful pircēju tabulas nosaukums
*/
$c[$p]['db']['table'] = "baltsms_mc_fpeaceful";

/*
    Vai uzrādīt pircēju sarakstu - jā/nē - true/false
*/
$c[$p]['sms']['buyers'] = true;

/*
    Vai pēc veiksmīga pirkuma izsūtīt informatīvu paziņojumu uz serveri?
*/
$c[$p]['ingame']['notifications'] = true;

/*
    Kādu ziņu izsūtīt uz serveri?
*/
$c[$p]['ingame']['message'] = "<NICKNAME> just purchased faction peaceful from our BaltSMS Shop!";

/*
    Frakcijas peaceful iedošanas komanda. Pēc noklusējuma, pievienota Essentials komanda
*/
$c[$p]['commands']['giveFPeaceful'][] = "f flag <NICKNAME> peaceful true";
$c[$p]['commands']['giveFPeacefulExpl'][] = "f flag <NICKNAME> explosions false";

/*
    Frakcijas peaceful noņemšanas komanda. Pēc noklusējuma, pievienota Essentials komanda
*/
$c[$p]['commands']['removeFPeaceful'][] = "f flag <NICKNAME> peaceful false";
$c[$p]['commands']['removeFPeacefulExpl'][] = "f flag <NICKNAME> explosions true";


$c[$p]['prices'] = array(
    "MyServer" => array(
    	50 => 3,
    	100 => 5,
    	150 => 7
    ),
);

$c['lang'][$p]['lv'] = array(
    "instructions" => "Lai iegādātos frakcijas peaceful uz <LENGTH> dienām par <PRICE> EUR, sūti kodu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai saņemtu atslēgas kodu!",
	# Kļūdas
    "error_empty_nickname" => "Ievadi savu spēlētāja vārdu!",
    "error_empty_server" => "Izvēlies serveri!",
    "error_empty_price" => "Izvēlies cenu!",
    "error_empty_code" => "Ievadi atslēgas kodu!",
    "error_invalid_code" => "Atslēgas kods nav pareizi sastādīts!",
    "error_price_not_listed" => "Izvēlētā cena nav atrasta priekš izvēlētā servera!",
    "fpeaceful_purchased" => "Frakcijas peaceful veiksmīgi iegādāts. Lai jauka spēlēšana!",
	# Forma
    "form_price" => "Cena",
    "form_code" => "Atslēgas kods",
    "form_player_name" => "Spēlētājs",
    "form_server" => "Serveris",
    "form_select_server" => "Izvēlies serveri",
    "form_price" => "Cena",
    "form_select_price" => "Izvēlies cenu",
    "form_days" => "dienas",
    "form_unlock_code" => "Atslēgas kods",
    "form_buy" => "Pirkt",
	# Tabula
    "table_nickname" => "Spēlētājs",
    "table_server" => "Serveris",
    "table_expires" => "Termiņa periods",
    "table_no_buyers" => "Neviens vēl nav iegādājies frakcijas peaceful. Varbūt vēlies būt pirmais?"
);

$c['lang'][$p]['en'] = array(
	"instructions" => "To purchase fraction peaceful for <LENGTH> days for <PRICE> EUR, send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b> to receive an unclock code!",
	# Kļūdas
	"error_empty_nickname" => "Enter your nickname!",
	"error_empty_server" => "Select the server!",
	"error_empty_price" => "Select the price!",
	"error_empty_code" => "Enter the unlock code!",
	"error_invalid_code" => "The format of the unlock code is not valid!",
	"error_price_not_listed" => "The selected price has not been found for the selected server!",
	"fpeaceful_purchased" => "Faction peaceful was purchased successfully. Have fun!",
	# Forma
	"form_price" => "Price",
	"form_code" => "Unlock code",
	"form_player_name" => "Player",
	"form_server" => "Server",
	"form_select_server" => "Select server",
	"form_price" => "Price",
	"form_select_price" => "Select price",
	"form_days" => "days",
	"form_unlock_code" => "Unlock code",
	"form_buy" => "Buy",
	# Tabula
	"table_nickname" => "Player",
    "table_server" => "Server",
    "table_expires" => "Expiry period",
    "table_no_buyers" => "No one has bought fraction peaceful yet. Would you like to be the first?"
);
/*
-----------------------------------------------------
    Minecraft frakcijas peaceful spraudņa konfigurācija
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
			if($db->count("SELECT `id` FROM `" . $c[$p]['db']['table'] . "` WHERE `nickname` = ? AND `server` = ?", array($_POST['nickname'], $_POST['server'])) == 0){
				$db->insert("INSERT INTO `" . $c[$p]['db']['table'] . "` (`nickname`, `server`, `length`, `time`, `expires`) VALUES (?, ?, ?, ?, ?)", array(
					$_POST['nickname'],
					$_POST['server'],
					$c[$p]['prices'][$_POST['server']][$_POST['price']],
					time(),
					strtotime("+" . $c[$p]['prices'][$_POST['server']][$_POST['price']] . " days", time())
					));
			}else{
				$player = $db->fetch("SELECT `expires` FROM `" . $c[$p]['db']['table'] . "` WHERE `nickname` = ? AND `server` = ?", array($_POST['nickname'], $_POST['server']));
				$db->update("UPDATE `" . $c[$p]['db']['table'] . "` SET `expires` = ? WHERE `nickname` = ? AND `server` = ?", array(
					strtotime("+" . $c[$p]['prices'][$_POST['server']][$_POST['price']] . " days", $player['expires']),
					$_POST['nickname'],
					$_POST['server']
					));
			}
			
			foreach($c[$p]['commands']['giveFPeaceful'] as $command){
				$giveFPeaceful = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$command
				);
				$mc['rcon'][$_POST['server']]->send_command($giveFPeaceful);
			}
			
			foreach($c[$p]['commands']['giveFPeacefulExpl'] as $command){
				$giveFPeacefulExpl = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$command
				);
				$mc['rcon'][$_POST['server']]->send_command($giveFPeaceful);
			}
			
			if($c[$p]['ingame']['notifications'] === true){
				$sendMessage = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$c[$p]['ingame']['message']
				);
				$mc['rcon'][$_POST['server']]->send_command("say " . $sendMessage);
			}
			
			$paymentStatus = 1;
			echo baltsms::alert($lang[$p]['fpeaceful_purchased'], "success");
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
	
	if($db->tableExists($c[$p]['db']['table']) === false) echo baltsms::alert("Tabula netika atrasta datubāzē. Tā tika izveidota automātiski ar nosaukumu, kas norādīts konfigurācijā!", "success");
	if($db->tableExists($c[$p]['db']['table']) === false) echo baltsms::createTable($p, $c[$p]['db']['table']);
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
						<?php foreach($prices as $price_code => $days): ?>
							<option value="<?php echo $price_code; ?>" data-length="<?php echo $days; ?>"><?php echo baltsms::returnPrice($price_code); ?> EUR - <?php echo $days; ?> <?php echo $lang[$p]['form_days']; ?></option>
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
	<?php if($c[$p]['sms']['buyers'] === true): ?>
		<div class="table-responsive">
			<table class="table table-striped table-hover">
				<thead>
					<th><?php echo $lang[$p]['table_nickname']; ?></th>
					<th><?php echo $lang[$p]['table_server']; ?></th>
					<th><?php echo $lang[$p]['table_expires']; ?></th>
				</thead>
				<tbody>
					<?php $buyers = $db->fetchAll("SELECT * FROM `" . $c[$p]['db']['table'] . "` ORDER BY `time` DESC"); ?>
					<?php if(empty($buyers)): ?>
						<tr>
							<td colspan="4"><?php echo $lang[$p]['table_no_buyers']; ?></td>
						</tr>
					<?php else: ?>
						<?php foreach($buyers as $buyer): ?>
							<tr>
								<td><?php echo $buyer['nickname']; ?></td>
								<td><?php echo $mc['servers'][$buyer['server']]->title; ?></td>
								<td><?php echo date("d/m/y H:i", $buyer['time']); ?> - <?php echo date("d/m/y H:i", $buyer['expires']); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
<?php endif; ?>
<?php
/*
    Neaiztikt!!! Frakcijas peaceful termiņa pārbaude un attiecīga dzēšana!
    Neliela informācija: frakcijas peaceful tiks dzēsta no saraksta UN servera tikai un vienīgi, ja serveris būs tiešsaistē. Uz katru ielādi, tiks veikta termiņu pārbaude un ja serveris būs sasniedzams - tā tiks dzēsta gan no servera, gan no datubāzes.
*/
$purchases = $db->fetchAll("SELECT `id`, `nickname`, `server`, `expires` FROM `" . $c[$p]['db']['table'] . "`");
foreach($purchases as $purchase){
	if($purchase['expires'] <= time()){
		if($mc['rcon'][$purchase['server']]->connect() != false){
			foreach($c[$p]['commands']['removeFPeaceful'] as $command){
				$removeFPeaceful = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$command
				);
				$mc['rcon'][$purchase['server']]->send_command($removeFPeaceful);
			}
			
			foreach($c[$p]['commands']['removeFPeacefulExpl'] as $command){
				$removeFPeacefulExpl = str_replace(
					array("<NICKNAME>"),
					array($_POST['nickname']),
					$command
				);
				$mc['rcon'][$purchase['server']]->send_command($removeFPeaceful);
			}
			
			$db->delete("DELETE FROM `" . $c[$p]['db']['table'] . "` WHERE `id` = ?", array($purchase['id']));
		}
	}
}
/*
    Neaiztikt!!! Frakcijas peaceful termiņa pārbaude un attiecīga dzēšana!
*/