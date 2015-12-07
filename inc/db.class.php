<?php
/**
 * My Database-Layer, extended from PDO; uses 2 functions of PHPBB3-DBAL
 * 
 * @author Thomas131 <github@ttmail.at.vu>
 */
class db extends PDO {
	/**
	 * Database-connection
	 * @param string Database-Type (mysql, pgsql, ...); see PDO-Documentation for a complete list of supported Database-Types
	 * @param string Adress of the SQL-Server; often localhost
	 * @param int Port of the SQL-Server; 3306 by default
	 * @param string Username on the SQL-Server
	 * @param string Password on the SQL-Server
	 * @param string Name of the used Database
	 * @param string any additional details to send to PDO::__construct; for more info see the PDO-Documentation
	 * @param array any additional driver-options; for more info see the PDO-Documentation
	 */
	public function __construct($type="mysql", $host="127.0.0.1", $port=3306, $username, $password, $dbname, $additional_dns_detail="", $driver_options=array()) {
		parent::__construct($type.":host=".$host.";port=".$port.";dbname=".$dbname.(($additional_dns_detail != "")?";".$additional_dns_detail:""), $username, $password, $driver_options);
	}

	/**
	 * Builds a part of a SQL-Query based on an array of datas
	 * @param array An associative array with the data; on Insert-Querys the collum-names and values to insert; on Update-Querys the collum-names and values to replace and on Where-Querys the collum-names with the expected values
	 * @query_method string Should the Query-Part be an Insert, a Update or a Where-part?
	 * @param int Only used with $query_method == "WHERE"; Connection-Type of the conditions (AND or OR).
	 * @return string the Query-Part
	 * @example build_array(array("Name" => "Max`", "Age" => 14), "WHERE", "AND"); => "`Name` LIKE `MAX\`` AND `Age` = 14"
	 * @source https://www.phpbb.com/downloads/: /phpBB3/phpbb/db/driver/driver.php
	 */
	public function build_array($assoc_ary = false, $query_method = "WHERE", $how = "AND") {
		if (!is_array($assoc_ary))
			return false;


		$query_method = strtoupper($query_method);

		switch($query_method) {
			case "INSERT":
				$fields = $values = array();

				foreach ($assoc_ary as $key => $var) {
					$fields[] = $key;
					$values[] = $this->validate($var);
				}

				return ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
			case "UPDATE":
			case "WHERE":
				$values = array();
				foreach ($assoc_ary as $key => $var)
					$values[] = "`$key` ".( (is_string($var) && $query_method=="WHERE") ? "LIKE" : "=" )." ".$this->validate($var);
				return implode(($query_method == 'UPDATE') ? ', ' : " ".$how." " , $values);
			default:
				return false;
		}
	}

	/**
	 * Handles strings, NULLs, bools and integers in a safe and correct way for SQL-Querys
	 * @source https://www.phpbb.com/downloads/: /phpBB3/phpbb/db/driver/driver.php
	 * @param string|bool|null|int|... the Value to make safe
	 * @return the secure version of the input
	 */
	public function validate($var) {
		if(is_null($var))
			return 'NULL';
		elseif(is_string($var)) {
			if(mb_detect_encoding($var) != 'UTF-8') $var = utf8_encode($var);
			return $this->quote($var);
		} else
			return (is_bool($var)) ? (int)$var : $var;
	}
}
?>
