<?php
class amxbans{
	protected $db;
	protected $config;
	private $path = '';
	
	function __construct($path, $md5 = false, $flags = 'a', $reloadadmins = true, $version = 6){
		global $c;
		
		if (!isset($config)) 
			$config = new stdClass();
		include $path . '/include/db.config.inc.php';
		
		include $c['dir'] . '/system/amxclass/db.class.php';
		$this->db = new amxdb($config->db_host, $config->db_user, $config->db_pass, $config->db_db);
		
		$this->dbprefix = (isset($config->db_prefix) ? $config->db_prefix . '_' : '');
		
		$this->tables = [
			'bans' => 'bans',
			'admins' => 'amxadmins',
			'servers' => 'serverinfo',
			'admins-servers' => 'admins_servers',
			'amx_banhistory' => 'banhistory',
		];
		
		$this->config = [
			'md5' => $md5,
			'flags' => $flags,
			'version' => 6,
			'reloadadmins' => $reloadadmins
		];
	}
	
	public function addAccessToPlayer($player, $password, $days, $access, $server){
		
		$res = $this->db->query("SELECT * FROM `" . $this->dbprefix . $this->tables['admins'] . "` WHERE `username` = '".$player."' AND `id` IN (SELECT `admin_id` FROM `" . $this->dbprefix . $this->tables['admins-servers'] . "` WHERE `server_id` = '".$server."')");
		if($this->db->rows($res)){
			$player = $this->db->fetch($res);
			
			if($this->config['md5']){
				$pass1 = md5($password);
				$pass2 = md5($player['password']);
			}else{
				$pass1 = $password;
				$pass2 = $player['password'];
			}
			
			if($pass1 != $pass2){
				echo 'wrong password';
			}else{
				$access_old = str_split($player['access']);
				$access_new = str_split($access);
				
				$access = array_merge($access_old, $access_new);
				$access = array_unique($access);
				
				sort($access);
				
				$access = implode('', $access);
				
				if(mb_strlen($access) > 3) {
					$access = str_replace( 'z', '', $access );
				}else{
					if(strpos($access, 'z') === false) {
						$access .= 'z';
					}
				}
				
				$this->db->query("UPDATE `".$this->dbprefix . $this->tables['admins']."` SET `access` = '".$access."', `nickname` = 'YTT[".$access."]' WHERE `id` = '".$player['id']."'");
				
				$admin = $this->db->query("SELECT `admin_id`, `server_id` FROM `" . $this->dbprefix . $this->tables['admins-servers'] . "` WHERE `admin_id` = '".$player['id']."' AND `server_id` = '".$server."'");
				if(!$this->db->rows($admin)){
					$admin_server_data = [
						'admin_id' => $player['id'], 
						'server_id' => $server
					];
					
					if($this->config['version'] == 6){
						$admin_server_data['custom_flags'] = $access;
						$admin_server_data['use_static_bantime'] = 'no';
					}
					
					$this->db->insert_array($this->dbprefix . $this->tables['admins-servers'], $admin_server_data);
				}
				
				//reload admin list
				if(isset($this->config['reloadadmins']) && $this->config['reloadadmins'] === true){
					$this->reload_admins($server);
					echo 'admins reloaded, ';
				}
				
				echo 'admin updated';
			}
		}else{
			$expires = date( 'Y-m-d H:i:s', strtotime('+' . $days . ' day' . ( $days != 1 ? 's' : '' )) );
			
			if(mb_strlen($access) > 3){
				$access = str_replace('z','',$access);
			}else{
				if(strpos($access, 'z') === false){
					$access .= 'z';
				}
			}
			
			$admin_data = [
				'username' 	=> $player,
				'password' 	=> ($this->config['md5'] === true ? md5($password) : $password),
				'access' 	=> $access,
				'flags' 	=> $this->config['flags'],
				'steamid' 	=> $player,
				'nickname' 	=> 'YTT[' . $access . ']',
				'created' 	=> time(),
				'expired' 	=> strtotime("+".$days." Days"),
				'days' 		=> $days
			];
			
			if($this->config['version'] == 6) {
				$admin_data['steamid'] 	= $player;
				$admin_data['created'] 	= $_SERVER['REQUEST_TIME'];
				$admin_data['expired'] 	= 0;
				$admin_data['ashow'] 	= 0;
				$admin_data['days'] 	= 0;
			}
			
			$this->db->insert_array($this->dbprefix . $this->tables['admins'], $admin_data);
			
			$newuserid = $this->db->query("SELECT `id` FROM `".$this->dbprefix . $this->tables['admins']."` WHERE `username` = '".$admin_data['username']."' AND `password` = '".$admin_data['password']."' ORDER BY `id` DESC");
			$newuserid = $this->db->fetch($newuserid)['id'];
			
			$admin_server_data = [
				'admin_id' => $newuserid, 
				'server_id' => $server
			];
			
			if($this->config['version'] == 6) {
				$admin_server_data['custom_flags'] 			= $access;
				$admin_server_data['use_static_bantime'] 	= 'no';
			}
			
			$this->db->insert_array($this->dbprefix . $this->tables['admins-servers'], $admin_server_data);
			
			if(isset($this->config['reloadadmins']) && $this->config['reloadadmins'] === true){
				$this->reload_admins($server);
			}
			
			return $newuserid;
		}
	}
	
	public function removeAccessToPlayer($playerid, $remove, $server){
		$player_admin = $this->db->query("SELECT * FROM `".$this->dbprefix . $this->tables['admins']."` WHERE `id` = '".$playerid."'");
		if($this->db->rows($player_admin)){
			$player_admin = $this->db->fetch($player_admin);
			
			$access = str_split($player_admin['access']);
			$remove = str_split($remove);
			
			$new = array_diff($access, $remove);
			
			$new = implode('', $new);
			
			if(empty($new)){
				$this->db->query("DELETE FROM `".$this->dbprefix . $this->tables['admins']."` WHERE `id` = '".$playerid."'");
				$this->db->query("DELETE FROM `".$this->dbprefix . $this->tables['admins-servers']."` WHERE `admin_id` = '".$playerid."'");
			}else{
				$nickname = str_replace( 'ITP[' . $player_admin['access'] . ']', '', $player_admin['nickname'] );
				$nickname = str_replace( "  ", " ", $nickname );
				$nickname = trim( $nickname );
				
				$this->db->query("UPDATE `" . $this->dbprefix . $this->tables['admins'] . "` SET `access` = '" . $new . "', `nickname` = '" . $nickname . "' WHERE `id` = " . $playerid);
			}
			
			$this->reload_admins($server);
			
			return true;
		}else{
			return false;
		}
	}
	
	public function getServers($gametype = 'cstrike'){
		$res = $this->db->query("SELECT `id`, `hostname`, `address`, `rcon` FROM `".$this->dbprefix . $this->tables['servers']."` WHERE `gametype` = '".$gametype."'");
		if($this->db->rows($res)){
			$allServers = [];
			while($row = $this->db->fetch($res)){
				$ipport = explode(':', $row['address']);
				$data = ['title' => $row['hostname'], 'id' => $row['id'], 'ip' => $ipport[0], 'port' => $ipport[1], 'rcon' => $row['rcon'], 'show' => true];
				$allServers[$row['id']] = ['title' => $row['hostname'], 'id' => $row['id'], 'ip' => $ipport[0], 'port' => $ipport[1], 'rcon' => $row['rcon'], 'show' => true];
			}
			return $allServers;
		}else{
			return [];
		}
	}
	
	public function reload_admins($server_id = 0, $showstatus = true){
		$answ = 'error';
		if(file_exists(realpath(dirname(__FILE__)) . '/rcon_hl_net.inc')){
			$server = $this->db->query("SELECT `address`, `rcon` FROM `".$this->dbprefix . $this->tables['servers']."` WHERE `id` = '".$server_id."'");
			if($this->db->rows($server)){
				$server = $this->db->fetch($server);
				require_once realpath(dirname(__FILE__)) . '/rcon_hl_net.inc';
				$ip_port = explode( ':', $server['address'] );
				
				$rcon = new Rcon();
				$rcon->Connect( $ip_port[0], $ip_port[1], $server['rcon']);
				if($rcon->IsConnected()){
					$rcon->RconCommand( 'amx_reloadadmins' );
					$rcon->Disconnect();
					if( $showstatus ) {
						$answ = 'admins reloaded';
					}
				}else{
					$answ = 'Cannot connect to rcon';
				}
			}
		}else{
			$answ = 'rcon_hl_net.inc not found';
		}
		
		return $answ;
	}
	
	public function send_chat($server_id, $message, $type = 'say'){
		$answ = 'error';
		if(file_exists(realpath(dirname(__FILE__)) . '/rcon_hl_net.inc')){
			$server = $this->db->query("SELECT `address`, `rcon` FROM `".$this->dbprefix . $this->tables['servers']."` WHERE `id` = '".$server_id."'");
			if($this->db->rows($server)){
				$server = $this->db->fetch($server);
				require_once realpath(dirname(__FILE__)) . '/rcon_hl_net.inc';
				$ip_port = explode( ':', $server['address'] );
				
				$rcon = new Rcon();
				$rcon->Connect( $ip_port[0], $ip_port[1], $server['rcon']);
				if($rcon->IsConnected() AND !empty($message)){
					$rcon->RconCommand($type . ' ' . $message);
					$rcon->Disconnect();
					
						$answ = 'message sent';
					
				}else{
					$answ = 'Cannot connect to rcon';
				}
			}else{
				echo 'Server not found';
			}
		}else{
			$answ = 'rcon_hl_net.inc not found';
		}
		
		return realpath(dirname(__FILE__));
	}
}