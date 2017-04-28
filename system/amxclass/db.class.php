<?php
class db{
	private $db_connection;
	public $query_count = 0;
	private $query_ok = array();
	private $query_dead = array();
	private $settings = array();
	private  $tables = array();
	
	function __construct($host, $user, $pass, $table){
		$this->connect($host, $user, $pass, $table);
		$this->query("SET NAMES 'utf8'");
		$this->settings['db'] = $table;
		$res = $this->query("SHOW TABLES");
		while($row = mysqli_fetch_array($res)){
			$this->tables[] = $row[0];
		}
	}
	
	function select_db($db, $conn){
		mysqli_select_db($conn, $db);
	}
	
	function connect($host, $user, $pass, $table){
		$this->db_connection = mysqli_connect($host, $user, $pass, $table);
		mysqli_set_charset($this->db_connection, "utf8");
	}
	
	function query($query){
		++$this->query_count;
		$sql = mysqli_query($this->db_connection, $query);
		if($sql)
			$this->add_query($query);
		else
			$this->add_query($query, mysqli_error($this->db_connection));
		return $sql;
	}
	
	private function add_query($query, $error = false){
		if($error)
			$this->query_dead[] = [$query, $error];
		else
			$this->query_ok[] = [$query, false];
	}
	
	public  function dbformat(&$val){
		$val = '`'.$val.'`';
	}
	
	function insert_array($table, $array){
		$fields = array();
		$values = array();
		foreach($array as $field => $value){
			$fields[] = $field;
			$values[] = $this->esc($value);
		}
		array_walk($fields, array($this, 'dbformat'));
		$query = "INSERT INTO `$table` (". implode(", ", $fields) .") VALUES (".implode(", ", $values).")";
		if($this->query($query))
			return true;
		else
			return false;
	}
	
	function update_array($table, $array, $where = false){
		$where = $where ? " WHERE $where" : '';
		$update = array();
		foreach($array as $field => $value){
			$update[] = '`'.$field . '` = ' . $this->esc($value);
		}
		$query = "UPDATE `$table` SET " . implode(', ', $update) . $where;
		if($this->query($query))
			return true;
		else
			return false;
	}
	
	function table_exists($table){
		return in_array($table, $this->tables);
	}
	
	function rows($query){
		return @mysqli_num_rows($query);
	}
	
	function last_id(){
		return @mysqli_insert_id($this->db_connection);
	}
	
	function fetch($query){
		return @mysqli_fetch_array($query, MYSQLI_ASSOC);
	}
	
	function esc($value, $hsc = true){
		if($hsc)
			$value = htmlspecialchars($value);
		if (get_magic_quotes_gpc())
			$value = stripslashes($value);
		if(!is_numeric($value))
			$value = "'" . mysqli_real_escape_string($this->db_connection, $value) . "'";
		return $value;
	}
	function show_dead(){
    $return = "<a href='javascript:;' onclick=\"toogler('#query_dead')\"><strong style='font-size:11px;'>Neizdevušies kvēriji (". count($this->query_dead) .")</strong></a>
    
    <table cellspacing='0' cellpadding='0' class='list' id=\"query_dead\">";	$i = 0;
    foreach ($this->query_dead as $ok)
    {
    $i++;
    $class = $i%2==0?"even":"odd";
      $return .=  "<tr class='$class'><td>".htmlspecialchars($ok[0])  . " - <b>".$ok[1]."</b></td></tr>";
    }
    $return .= '</table>';
    if(count($this->query_dead))
    return msg($return, '');
  }
  function show_ok(){
    $return = "<a href='javascript:;' onclick=\"toogler('#query_ok')\"><strong style='font-size:11px;'>Izdevušies kvēriji (". count($this->query_ok) .")</strong></a>
    
    <table cellspacing='0' cellpadding='0' class='list' id=\"query_ok\">";
	$i = 0;
    foreach ($this->query_ok as $ok)
    {
    $i++;
    $class = $i%2==0?"even":"odd";
      $return .=  "<tr class='$class'><td>".htmlspecialchars($ok[0]) . "</td></tr>";
    }
    $return .= '</table>';
    return msg($return, '');
  }
	
	function count($table, $field, $where = false, $prefix = 'WHERE '){
		$res = $this->query("SELECT count($field) as `count` FROM `$table` " . ($where ? $prefix . $where : ''));
		$row = $this->fetch($res);
		return $row['count'];
	}
	
	
	function __destruct(){
		unset($this->db_connection);
	}
}