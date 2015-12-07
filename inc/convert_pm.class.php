<?php
/**
 * Converting-class for Private Messages
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */
class convert_pms extends convert_prototype {
	/**
	 * @var array $my_pm Private-Message-dataset in MyBB as associative Array which will be finally inserted into the MyBB-Database
	 * @var array $wow_pm Private-Message Databse-Entry of WoWBB as associative Array; merged from `wowbb_pm` and `wowbb_pm_texts`
	 */
	public $my_pm = array();
	protected $wow_pm = array();

	/**
	 * Converts Folder-Number from WoWBB to MyBB by hardcoded Array
	 *
	 * @param int $fid WoWBB-Folder-ID
	 * @return int MyBB-Folder-ID
	 */
	public function folder_convert($fid) {
		$convert_array = array(
			100 => 1,	//Inbox
			101 => 2,	//Sent
			102 => 4	//Trash
		);

		return $convert_array[$fid];
	}

	/**
	 * Init $this->my_pm and $this->wow_pm
	 *
	 * (set the values, that doesn't change)
	 *
	 * @param void
	 * @return void
	 */
	public function init_my() {
		$this->my_pm = array(
			//pmid
			//uid
			//toid
			//fromid
			//recipients
			//folder
			//subject
			"icon" => 0,
			//message
			//dateline
			"deletetime" => 0,
			//status
			"statustime" => 0,
			"includesig" => 0,
			"smilieoff" => 0,
			//receipt
			"readtime" => 0,
			"ipaddress" => ""
		);

		$this->wow_pm = array();
	}

	/**
	 * Import the WoWBB-Database-Entry into $this->my_pm
	 *
	 * @param int|null $wow_id if set, it selects and converts exactly the PM with this ID, if not set, it converts a random one, probably the first unconverted one.
	 * @return bool true
	 */
	public function import_wowbb_db($wow_id=Null) {

		global $db_old;
		$result = $db_old->query("SELECT `wowbb_pm`.`pm_id`, `wowbb_pm`.`user_id`, `wowbb_pm`.`pm_folder_id`, `wowbb_pm`.`pm_from`, `wowbb_pm`.`pm_to`, `wowbb_pm`.`pm_cc`, `wowbb_pm`.`pm_subject`, UNIX_TIMESTAMP( `wowbb_pm`.`pm_date_time` ) AS `pm_date_time`, `wowbb_pm`.`pm_status`, `wowbb_pm_texts`.`pm_text` FROM `wowbb_pm`, `wowbb_pm_texts` WHERE `wowbb_pm`.`pm_id` = `wowbb_pm_texts`.`pm_id`". ((!is_null($wow_id))?" AND `wowbb_pm`.`pm_id` = ".(int)$wow_id:"") ." LIMIT 0, 1;");

		if(!$result->rowCount())
			return false;
		$this->wow_pm = $result->fetch(DB::FETCH_ASSOC);

		echo "\nimporting PM ".$this->wow_pm["pm_id"];

		return true;
	}

	/**
	 * Converts the values, that are easy to convert from $this->wow_pm to $this->my_pm.
	 *
	 * @param void
	 * @return void
	 */
	public function import_wowbb_simple() {
		 $this->my_pm["uid"] = $this->uid2my($this->wow_pm["user_id"]);
		 $this->my_pm["toid"] = $this->uid2my($this->wow_pm["pm_to"],0);
		 $this->my_pm["fromid"] = $this->uid2my($this->wow_pm["pm_from"],0);
		 $this->my_pm["subject"] = $this->wow_pm["pm_subject"];
		 $this->my_pm["message"] = $this->bb2my($this->wow_pm["pm_text"]);
		 $this->my_pm["dateline"] = $this->wow_pm["pm_date_time"];
		 $this->my_pm["status"] = $this->wow_pm["pm_status"];
		 $this->my_pm["receipt"] = !$this->wow_pm["pm_status"];
		 $this->my_pm["folder"] = $this->folder_convert($this->wow_pm["pm_folder_id"]);
	}

	/**
	 * Converts the recipents of this Private Message
	 *
	 * @param void
	 * @return void
	 */
	public function import_wow_recipents() {
		$unserialized = array(
			"to" => array($this->uid2my($this->wow_pm["pm_to"],0))
			//bbc will be added later in this function, if needed
		);

		if($this->wow_pm["pm_cc"] != "") {
			$bbcs = explode(",",$this->wow_pm["pm_cc"]);
			$bbcs = array_diff($bbcs,array($this->wow_pm["pm_to"]));

			foreach($bbcs as &$bbc) {
				$bbc = $this->uid2my($bbc,0);
			}

			$unserialized["bbc"] = $bbcs;
		}

		$this->my_pm["recipients"] = serialize($unserialized);
	}

	/**
	 * Inserts the dataset into MyBB; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function insert_my() {
		global $db_new;

		$ret = $db_new->query("INSERT INTO `de_privatemessages`".$db_new->build_array($this->my_pm,"INSERT").";");

		if(!$ret || !$ret->rowCount()) {
			var_dump($db_new->errorCode(),$db_new->errorInfo());
			die("Error in inserting pm (wow_id)".$this->wow_pm["pm_id"]);
		} else
			echo "Successfully inserted PM ".$db_new->lastInsertId()." (MyBB-ID)";
	}

	/**
	 * Deletes the Row from WoWBB to make sure it isn't reconverted; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function del_wow() {
		global $db_old;
		if(!$res = $db_old->query("DELETE FROM `wowbb_pm` WHERE `pm_id` = '".(int)$this->wow_pm["pm_id"]."';")) {
			var_dump($db_old->errorCode(),$db_old->errorInfo());
			die("Error on deleting PM (wow)".$this->wow_pm["pm_id"]);
		}

		if($res->rowCount() != 1) {
			var_dump($res->errorCode(),$res->errorInfo());
			die("Error: No PM deleted (wow)".$this->wow_pm["pm_id"]);
		}

		echo "erfolgreich gelöscht ...";
	}

	/**
	 * Function which does the whole process of converting and is called in main.php; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function do_all() {
		$this->init_my();
		while($this->import_wowbb_db()) {
			$this->import_wowbb_simple();
			$this->import_wow_recipents();
			$this->insert_my();
			$this->del_wow();

			$this->init_my();
		}
	}
}
?>