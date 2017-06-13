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
defined("amx_config_present") or require "../config.amx.php";
in_array($p, $c['sms']['plugins']['amx']) or die(baltsms::alert("Spraudnis nav ievadīts atļauto spraudņu sarakstā!", "danger"));
/*
-----------------------------------------------------
    Minecraft naudas spraudņa konfigurācija
-----------------------------------------------------
*/

/*
    Naudas pircēju tabulas nosaukums
*/
$c[$p]['db']['table'] = "baltsms_amx_admin";

/*
    Vai uzrādīt pircēju sarakstu - jā/nē - true/false
*/
$c[$p]['sms']['buyers'] = true;


$c[$p]['sms']['access'] = 'bcdefijumz';

/*
    Vai pēc veiksmīga pirkuma izsūtīt informatīvu paziņojumu uz serveri?
*/
$c[$p]['ingame']['notifications'] = true;
/*
    Kādu ziņu izsūtīt uz serveri?
*/
$c[$p]['ingame']['message'] = "<NICKNAME> tikko nopirka admin statusu izmantojot SMS veikalu!";


// iekš config.amx.php ieslēdz `showservers`, lai uzzinātu serveru id
$c[$p]['prices'] = array(
    1 => array(
    	80 => 5,
    	100 => 15,
    	160 => 30,
    )
);

$c['lang'][$p]['lv'] = array(
    "instructions" => "Lai iegādātos ACC uz <LENGTH> dienām par <PRICE> EUR, sūti kodu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai saņemtu atslēgas kodu!",
	# Kļūdas
    "error_empty_nickname" => "Ievadi savu spēlētāja vārdu!",
    "error_empty_server" => "Izvēlies serveri!",
    "error_empty_price" => "Izvēlies cenu!",
    "error_empty_code" => "Ievadi atslēgas kodu!",
    "error_invalid_code" => "Atslēgas kods nav pareizi sastādīts!",
    "error_price_not_listed" => "Izvēlētā cena nav atrasta priekš izvēlētā servera!",
    "money_purchased" => "ACC veiksmīgi iegādāts. Lai jauka spēlēšana!",
	# Forma
    "form_price" => "Cena",
    "form_code" => "Atslēgas kods",
    "form_player_name" => "Spēlētājs",
    "form_player_password" => "Parole",
    "form_server" => "Serveris",
    "form_select_server" => "Izvēlies serveri",
    "form_price" => "Cena",
    "form_select_price" => "Izvēlies cenu",
    "form_unlock_code" => "Atslēgas kods",
    "form_buy" => "Pirkt",
	# Tabula
    "table_nickname" => "Spēlētājs",
    "table_server" => "Serveris",
    "table_flags" => "Flagi",
    "table_date" => "Datums",
    "table_no_buyers" => "Neviens vēl nav iegādājies ACC. Varbūt vēlies būt pirmais?"
);

$c['lang'][$p]['en'] = array(
	"instructions" => "To purchase <LENGTH> days ACC for <PRICE> EUR, send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b> to receive an unclock code!",
	# Kļūdas
	"error_empty_nickname" => "Enter your nickname!",
	"error_empty_server" => "Select the server!",
	"error_empty_price" => "Select the price!",
	"error_empty_code" => "Enter the unlock code!",
	"error_invalid_code" => "The format of the unlock code is not valid!",
	"error_price_not_listed" => "The selected price has not been found for the selected server!",
	"money_purchased" => "ACC was purchased successfully. Have fun!",
	# Forma
	"form_price" => "Price",
	"form_code" => "Unlock code",
	"form_player_name" => "Player",
	"form_player_password" => "Password",
	"form_server" => "Server",
	"form_select_server" => "Select server",
	"form_price" => "Price",
	"form_select_price" => "Select price",
	"form_unlock_code" => "Unlock code",
	"form_buy" => "Buy",
	# Tabula
	"table_nickname" => "Player",
    "table_server" => "Server",
    "table_flags" => "Flags",
    "table_date" => "Date",
    "table_no_buyers" => "No one has bought ACC yet. Would you like to be the first?"
);
/*
-----------------------------------------------------
    Minecraft naudas spraudņa konfigurācija
-----------------------------------------------------
*/
$db = new db($amx['db']['host'], $amx['db']['username'], $amx['db']['password'], $amx['db']['database']);
if($db->connected === false) die(baltsms::alert("Nevar izveidot savienojumu ar MySQL serveri. Pārbaudi norādītos pieejas datus!", "danger"));
$lang[$p] = $c['lang'][$p][$c['page']['lang_personal']];

if(isset($_POST['code'])):
	$errors = array();

	if(empty($_POST['nickname'])){
		$errors[] = $lang[$p]['error_empty_nickname'];
	}

	if(empty($_POST['server']) AND $_POST['server'] != 0){
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
			
			if($c[$p]['ingame']['notifications'] === true){
				$sendMessage = str_replace('<NICKNAME>', $_POST['nickname'], $c[$p]['ingame']['message']);
			}else{
				$sendMessage = false;
			}
			
			$addAdmin = $amxclass->addAccessToPlayer($_POST['nickname'], $_POST['password'], $c[$p]['prices'][$_POST['server']][$_POST['price']], $c[$p]['sms']['access'], $_POST['server']);
			$db->insert("INSERT INTO `" . $c[$p]['db']['table'] . "` (`nickname`, `player_id`, `server`, `access`, `time`, `expires`) VALUES (?, ?, ?, ?, ?, ?)", array(
				$_POST['nickname'],
				$addAdmin,
				$_POST['server'],
				$c[$p]['sms']['access'],
				time(),
				strtotime('+' . $c[$p]['prices'][$_POST['server']][$_POST['price']] . ' days', time())
			));
			
			$amxclass->send_chat($_POST['server'], $sendMessage);
			$amxclass->send_chat($_POST['server'], 'Lūdzu, ienāc pa jaunam serverī, lai ADMIN privilēģijas stātos spēkā!', 'amx_psay "' . $_POST['nickname'] . '"');
			
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

	if($db->tableExists($c[$p]['db']['table']) === false) echo baltsms::alert("Tabula netika atrasta datubāzē. Tā tika izveidota automātiski ar nosaukumu, kas norādīts konfigurācijā!", "success");
	if($db->tableExists($c[$p]['db']['table']) === false) echo baltsms::createTable($p, $c[$p]['db']['table']);
	?>
	<form class="form-horizontal" method="POST" id="<?php echo $p; ?>">
		<div class="panel panel-border panel-contrast" id="instructions" style="display:none;"><div class="panel-heading panel-heading-contrast text-center"><?php echo baltsms::instructionTemplate($lang[$p]['instructions'], array("price" => baltsms::returnPrice(0), "code" => 0, "length" => 0)); ?></div></div>
		<div id="alerts"></div>
		<div class="form-group">
			<label for="nickname" class="col-sm-2 control-label"><?php echo $lang[$p]['form_player_name']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="nickname" placeholder="<?php echo $lang[$p]['form_player_name']; ?>">
			</div>
		</div><div class="form-group">
			<label for="password" class="col-sm-2 control-label"><?php echo $lang[$p]['form_player_password']; ?></label>
			<div class="col-sm-10">
				<input type="password" class="form-control" name="password" placeholder="<?php echo $lang[$p]['form_player_password']; ?>">
			</div>
		</div>
		<div class="form-group">
			<label for="server" class="col-sm-2 control-label"><?php echo $lang[$p]['form_server']; ?></label>
			<div class="col-sm-10">
				<select class="form-control" name="server" onChange="listPrices('none', this.value);">
					<option selected disabled><?php echo $lang[$p]['form_server']; ?></option>
					<?php foreach($c[$p]['prices'] as $server => $data): ?>
						<?php if($amx['servers'][$server]['show'] !== false): ?>
							<option value="<?php echo $server; ?>"><?php echo $amx['servers'][$server]['title']; ?></option>
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
							<option value="<?php echo $price_code; ?>" data-length="<?php echo $days; ?>"><?php echo $days; ?> dienas - <?php echo baltsms::returnPrice($price_code); ?> EUR</option>
						<?php endforeach; ?>
					</select>
				<?php endforeach;  ?>
			</div>
		</div>
		<div class="form-group">
			<label for="name" class="col-sm-2 control-label"><?php echo $lang[$p]['form_unlock_code']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="code" placeholder="<?php echo $lang[$p]['form_unlock_code']; ?>" maxlength="9" autocomplete="off">
			</div>
		</div>
		<script>
		var x = 0.00;
		function getvalue(element){
			if(jQuery(element).find(":selected").attr("data-price")){
				price = jQuery(element).find(":selected").data("price");
			}else{
				price = element.value;
			}
			x = price/100;
		}
		
		function startPayment() {
			var y = document.forms["<?php echo $p; ?>"]["code"].value;
			if (y == null || y == "") {
				openWinPayPal(x);
				return false;
			}
		}
		</script>
		<div class="form-group">
			<button type="button" class="btn btn-success" style="float:left !important; margin-left: 16px;" onclick="startPayment()"><?php echo $lang['pay_with_paypal']; ?></button>
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
					<th><?php echo $lang[$p]['table_date']; ?></th>
					<th><?php echo $lang[$p]['table_flags']; ?></th>
				</thead>
				<tbody>
					<?php $buyers = $db->fetchAll("SELECT * FROM `" . $c[$p]['db']['table'] . "` ORDER BY `time` DESC"); ?>
					<?php if(empty($buyers)): ?>
						<tr>
							<td colspan="4"><?php echo $lang[$p]['table_no_buyers']; ?></td>
						</tr>
					<?php else: ?>
						<?php
						foreach($buyers as $buyer){
							if($buyer['expires'] < time()){
								if($amx['servers'][$buyer['server']]['show'] != false){
									$removeplayer = $amxclass->removeAccessToPlayer($buyer['player_id'], $buyer['access'], $buyer['server']);
									if($removeplayer == true){
										$db->delete("DELETE FROM `" . $c[$p]['db']['table'] . "` WHERE `id` = ?", array($buyer['id']));
									}
								}
							}
						?>
							<tr>
								<td><?php echo htmlspecialchars($buyer['nickname']); ?></td>
								<td><?php echo $amx['servers'][$buyer['server']]['title'] ?></td>
								<td><?php echo date("d/m/y H:i", $buyer['time']) . ($buyer['expires'] ? ' - ' . date("d/m/y H:i", $buyer['expires']) : ''); ?></td>
								<td><?php echo $buyer['access']; ?></td>
							</tr>
						<?php } ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
<?php endif; ?>