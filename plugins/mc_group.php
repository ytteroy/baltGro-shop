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
    Minecraft grupas spraudņa konfigurācija
-----------------------------------------------------
*/

/*
    Grupu pircēju tabulas nosaukums
*/
$c[$p]['db']['table'] = "baltsms_mc_group";

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
$c[$p]['ingame']['message'] = "<NICKNAME> tikko nopirka <GROUP> statusu izmantojot SMS veikalu!";

/*
    Grupas pievienošanas komanda. Pēc noklusējuma, pievienota PermissionsEX komanda
*/
$c[$p]['commands']['addGroup'] = "pex group <GROUP> user add <NICKNAME>";

/*
    Grupas noņemšanas komanda. Pēc noklusējuma, pievienota PermissionsEX komanda
*/
$c[$p]['commands']['removeGroup']  = "pex group <GROUP> user remove <NICKNAME>";

$c[$p]['groups'] = array(
    "MyServer" => array(
    	"Citizen" => array(
    		120 => 28
    	),
    	"Elite" => array(
    		170 => 28
    	),
    	"Knight" => array(
    		240 => 14,
    		360 => 28
    	),
    	"Lord" => array(
    		550 => 14,
    		980 => 28
    	)
    ),
);

$c['lang'][$p]['lv'] = array(
    "instructions" => "Lai iegādātos šo grupu par <PRICE> EUR uz <LENGTH> dienām, sūti kodu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai saņemtu atslēgas kodu!",
	# Kļūdas
    "error_empty_nickname" => "Ievadi savu spēlētāja vārdu!",
    "error_empty_server" => "Izvēlies serveri!",
    "error_empty_group" => "Izvēlies grupu!",
    "error_empty_price" => "Izvēlies cenu!",
    "error_empty_code" => "Ievadi atslēgas kodu!",
    "error_invalid_code" => "Atslēgas kods nav pareizi sastādīts!",
    "error_price_not_listed" => "Izvēlētā cena nav atrasta priekš izvēlētās grupas un servera!",
    "group_purchased" => "Grupa veiksmīgi iegādāta. Lai jauka spēlēšana!",
	# Forma
    "form_price" => "Cena",
    "form_code" => "Atslēgas kods",
    "form_days_for" => "dienas par",
    "form_player_name" => "Spēlētājs",
    "form_server" => "Serveris",
    "form_group" => "Grupa",
    "form_select_server" => "Izvēlies serveri",
    "form_select_group" => "Izvēlies grupu",
    "form_price" => "Cena",
    "form_select_price" => "Izvēlies cenu",
    "form_unlock_code" => "Atslēgas kods",
    "form_buy" => "Pirkt",
	# Tabula
    "table_nickname" => "Spēlētājs",
    "table_server" => "Serveris",
    "table_expires" => "Termiņa periods",
    "table_group" => "Grupa",
    "table_no_buyers" => "Neviens vēl nav iegādājies grupu. Varbūt vēlies būt pirmais?"
);

$c['lang'][$p]['en'] = array(
	"instructions" => "To purchase this group for <PRICE> EUR for <LENGTH> days, send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b> to receive an unclock code!",
	# Kļūdas
	"error_empty_nickname" => "Enter your nickname!",
	"error_empty_server" => "Select the server!",
	"error_empty_group" => "Select the group!",
	"error_empty_price" => "Select the price!",
	"error_empty_code" => "Enter the unlock code!",
	"error_invalid_code" => "The format of the unlock code is not valid!",
	"error_price_not_listed" => "The selected price has not been found for the selected group and server!",
	"group_purchased" => "The group was purchased successfully. Have fun!",
	# Forma
	"form_price" => "Price",
	"form_code" => "Unlock code",
	"form_days_for" => "days for",
	"form_player_name" => "Player",
	"form_server" => "Server",
	"form_group" => "Group",
	"form_select_server" => "Select server",
	"form_select_group" => "Select group",
	"form_price" => "Price",
	"form_select_price" => "Select price",
	"form_unlock_code" => "Unlock code",
	"form_buy" => "Buy",
	# Tabula
	"table_nickname" => "Player",
    "table_server" => "Server",
    "table_expires" => "Expiry period",
    "table_group" => "Group",
    "table_no_buyers" => "No one has bought a group yet. Would you like to be the first?"
);
/*
-----------------------------------------------------
    Minecraft grupas spraudņa konfigurācija
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
	}else{
		$server = true;
	}

	if(empty($_POST['group']) AND !empty($_POST['server'])){
		$errors[] = $lang[$p]['error_empty_group'];
	}else{
		$group = true;
	}

	if(empty($_POST['price']) AND !empty($_POST['server']) AND !empty($_POST['group'])){
		$errors[] = $lang[$p]['error_empty_price'];
	}else{
		$price = true;
	}

	if(isset($server) AND isset($group) AND isset($price)){
		if(!isset($c[$p]['groups'][$_POST['server']][$_POST['group']][$_POST['price']])){
			$errors[] = $lang[$p]['error_price_not_listed'];
		}
	}

	if(empty($_POST['code'])){
		$errors[] = $lang[$p]['error_empty_code'];
	}else{
		if(strlen($_POST['code']) != 9 OR is_numeric($_POST['code']) === false){
			$errors[] = $lang[$p]['error_invalid_code'];
		}else{
			$code = true;
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
			$db->insert("INSERT INTO `" . $c[$p]['db']['table'] . "` (`nickname`, `server`, `mc_group`, `length`, `time`, `expires`) VALUES (?, ?, ?, ?, ?, ?)", array(
				$_POST['nickname'],
				$_POST['server'],
				$_POST['group'],
				$c[$p]['groups'][$_POST['server']][$_POST['group']][$_POST['price']],
				time(),
				strtotime("+" . $c[$p]['groups'][$_POST['server']][$_POST['group']][$_POST['price']] . " days", time())
				));

			$addGroup = str_replace(
				array("<NICKNAME>", "<GROUP>"),
				array($_POST['nickname'], $_POST['group']),
				$c[$p]['commands']['addGroup']
				);
			$mc['rcon'][$_POST['server']]->send_command($addGroup);
			if($c[$p]['ingame']['notifications'] === true){
				$sendMessage = str_replace(
					array("<NICKNAME>", "<GROUP>"),
					array($_POST['nickname'], $_POST['group']),
					$c[$p]['ingame']['message']
				);
				$mc['rcon'][$_POST['server']]->send_command("say " . $sendMessage);
			}
			
			$paymentStatus = 1;
			echo baltsms::alert($lang[$p]['group_purchased'], "success");
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
		<div class="group">      
			<input class="inputMaterial" name="nickname" type="text" required>
			<span class="highlight"></span>
			<span class="bar"></span>
			<label><?php echo $lang[$p]['form_player_name']; ?></label>
		</div>
		<div class="group">      
			<select class="inputMaterial" name="server" onChange="listGroups(this.value);" required>
				<option selected disabled><?php echo $lang[$p]['form_server']; ?></option>
				<?php foreach($c[$p]['groups'] as $server => $data): ?>
					<?php if($mc['servers'][$server]->show !== false): ?>
						<option value="<?php echo $server; ?>"><?php echo $mc['servers'][$server]->title; ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			<span class="highlight"></span>
			<span class="bar"></span>
			<label><?php echo $lang[$p]['form_server']; ?></label>
		</div>
		<div class="group">      
			<select class="inputMaterial" id="groups">
				<option selected disabled><?php echo $lang[$p]['form_select_server']; ?></option>
			</select>
			<?php foreach($c[$p]['groups'] as $server => $groups): ?>
				<select class="inputMaterial groups" name="group" id="<?php echo $server; ?>-groups" style="display: none;" required onChange="listPrices(this.value, '<?php echo $server; ?>')" disabled>
					<option selected disabled><?php echo $lang[$p]['form_select_group']; ?></option>
					<?php foreach($groups as $group => $prices): ?>
						<option value="<?php echo $group; ?>"><?php echo $group; ?></option>
					<?php endforeach; ?>
				</select>
			<?php endforeach;  ?>
			<span class="highlight"></span>
			<span class="bar"></span>
			<label><?php echo $lang[$p]['form_group']; ?></label>
		</div>
		<div class="group">      
			<select class="inputMaterial" id="prices">
				<option selected disabled><?php echo $lang[$p]['form_select_group']; ?></option>
			</select>
			<?php foreach($c[$p]['groups'] as $server => $groups): ?>
				<?php foreach($groups as $group => $prices): ?>
					<select class="inputMaterial prices" name="price" id="<?php echo $group . "-" . $server; ?>-prices" required style="display: none;" onChange="changePrice(this); getvalue(this);" disabled>
						<option selected disabled><?php echo $lang[$p]['form_select_price']; ?></option>
						<?php foreach($prices as $price_code => $days): ?>
							<option value="<?php echo $price_code; ?>" data-length="<?php echo $days; ?>"><?php echo $days; ?> <?php echo $lang[$p]['form_days_for']; ?> <?php echo baltsms::returnPrice($price_code); ?> EUR</option>
						<?php endforeach; ?>
				</select>
					<?php endforeach; ?>
			<?php endforeach;  ?>
			<span class="highlight"></span>
			<span class="bar"></span>
			<label><?php echo $lang[$p]['form_price']; ?></label>
		</div>
		<div id="buycode"></div>
		<div class="group">      
			<input type="text" class="inputMaterial" name="code" maxlength="9" autocomplete="off" required>
			<span class="highlight"></span>
			<span class="bar"></span>
			<label><?php echo $lang[$p]['form_unlock_code']; ?></label>
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
					<th><?php echo $lang[$p]['table_group']; ?></th>
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
							/*
								Neaiztikt!!! Grupas termiņa pārbaude un attiecīga dzēšana!
								Neliela informācija: grupa tiks dzēsta no saraksta UN servera tikai un vienīgi, ja serveris būs tiešsaistē. Uz katru ielādi, tiks veikta termiņu pārbaude un ja serveris būs sasniedzams - tā tiks dzēsta gan no servera, gan no datubāzes.
							*/
							if($buyer['expires'] < time()){
								if($mc['rcon'][$buyer['server']]->connect() != false){
									$removeGroup = str_replace(
										array("<NICKNAME>", "<GROUP>"),
										array($buyer['nickname'], $buyer['mc_group']),
										$c[$p]['commands']['removeGroup']
									);
									$mc['rcon'][$buyer['server']]->send_command($removeGroup);
									$db->delete("DELETE FROM `" . $c[$p]['db']['table'] . "` WHERE `id` = ?", array($buyer['id']));
								}
							}
							/*
								Neaiztikt!!! Grupas termiņa pārbauda un attiecīga dzēšana!
							*/
						?>
							<tr>
								<td><?php echo htmlspecialchars($buyer['nickname']); ?></td>
								<td><?php echo $mc['servers'][$buyer['server']]->title; ?></td>
								<td><?php echo date("d/m/y H:i", $buyer['time']); ?> - <?php echo date("d/m/y H:i", $buyer['expires']); ?></td>
								<td><?php echo $buyer['mc_group']; ?></td>
							</tr>
						<?php } ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
<?php endif; ?>