<?php require "config.inc.php"; ?>
<!DOCTYPE html>
<html lang="lv">
<head>
	<meta charset="utf-8">
	<meta name="author" content="baltGro, SIA">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title><?php echo $c['page']['title']; ?></title>
	<link rel="stylesheet" type="text/css" href="//cdn.airtel.lv/beagle/css/style.css">
	<link rel="stylesheet" type="text/css" href="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/css/baltgro.css?<?php echo time(); ?>">
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css"/>
</head>
<body class="baltsms">
	<div class="col-md-<?php echo $c['page']['size'][0]; ?> col-md-offset-<?php echo $c['page']['size'][1]; ?>">
		<div class="progress" id="baltsms-loader">
			<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
				<span class="sr-only"></span>
			</div>
		</div>
		<ul id="tab-nav" class="nav nav-tabs" role="tablist">
			<?php
			if($c['page']['language'] === true){
			?>
				<div id="baltsms-flags">
					<?php foreach($c['lang'] as $language_key => $data){ ?>
						<img src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/images/flags/<?php echo $language_key; ?>.gif" onClick="setLanguage('<?php echo $language_key; ?>')">
					<?php } ?>
				</div>
			<?php
			}
			
			if($c['page']['dropdown'] == true){
				foreach($c['sms']['plugins'] as $type => $plugins){
				?>
				<li role="presentation" class="dropdown <?php echo ($c['sms']['primary'] == $type) ? "active" : ""; ?>">
					<a href="#" id="plugins-<?php echo $type; ?>" class="dropdown-toggle" data-toggle="dropdown" aria-controls="plugins-<?php echo $type; ?>-contents"><?php echo (isset($lang['plugin-type-' . $type])) ? $lang['plugin-type-' . $type] : "<span style='color: red'>language not found</span>"; ?> <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="plugins-<?php echo $type; ?>" id="plugins-<?php echo $type; ?>-contents">
						<?php foreach($plugins as $index => $plugin): ?>
							<li role="presentation" class="<?php echo ($c['sms']['primary'] == $type AND $index == 0) ? "active" : ""; ?>" onClick="loadPlugin('<?php echo $plugin; ?>')"><a href="#<?php echo $plugin; ?>" aria-controls="<?php echo $plugin; ?>" role="tab" style="color:black; border-bottom:initial;" data-toggle="tab"><?php echo (isset($lang['plugin-' . $plugin])) ? $lang['plugin-' . $plugin] : "<span style='color: red'>language not found</span>"; ?></a></li>
						<?php endforeach; ?>
					</ul>
				</li>
				<?php
				}
			}else{
				foreach($c['sms']['plugins'] as $type => $plugins){
					foreach($plugins as $index => $plugin){
					?>
					<li role="presentation" class="<?php echo ($c['sms']['primary'] == $type AND $index == 0) ? "active" : ""; ?>" onClick="loadPlugin('<?php echo $plugin; ?>')"><a href="#<?php echo $plugin; ?>" aria-controls="<?php echo $plugin; ?>" role="tab" data-toggle="tab"><?php echo (isset($lang['plugin-' . $plugin])) ? $lang['plugin-' . $plugin] : "<span style='color: red'>language not found</span>"; ?></a></li>
					<?php
					}
				}
			}
			?>
		</ul>
		<div id="baltsms-page">
			<div id="baltsms-content">
				<div role="tabpanel">
					<div class="tab-content">
						<div role="tabpanel" class="tab-pane" id="error"></div>
						<?php foreach($c['sms']['plugins'] as $type => $plugins): ?>
							<?php foreach($plugins as $index => $plugin): ?>
								<div role="tabpanel" class="tab-pane <?php echo ($c['sms']['primary'] == $type AND $index == 0) ? "active" : ""; ?>" id="<?php echo $plugin; ?>"></div>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="row"><a href="https://airtel.lv" target="_blank" class="pull-right" title="Payments by Airtel.lv"><img src="http://cdn.airtel.lv/art.png" class="img-responsive" alt="Payments by Airtel.lv" /></a></div>
			</div>
		</div>
	</div>
	<script src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/js/jquery.min.js"></script>
	<script src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/js/bootstrap.min.js"></script>
	<script src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/js/jquery.stickytabs.js"></script>
	<script src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/js/jquery.cookie.js"></script>
	<script src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/js/iframe.contentWindow.js"></script>
	<script type="text/javascript">
		var baltsms_url = "<?php echo $c['url'] . '/' . $c['page']['directory']; ?>";
	</script>
	<script src="<?php echo $c['url'] . '/' . $c['page']['directory']; ?>/assets/js/baltgro.js?<?php echo time(); ?>"></script>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			loadPlugin("<?php echo $c['sms']['plugins'][$c['sms']['primary']][0]; ?>");
			if(window.location.hash){
				loadPlugin(window.location.hash.substring(1));
			}
			jQuery(".nav-tabs").stickyTabs();
		});
	</script>
</body>
</html>