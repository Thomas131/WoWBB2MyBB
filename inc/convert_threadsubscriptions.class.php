<?php
/**
 * Threadsubscriptions-converting-Class
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */
class convert_threadsubscriptions extends convert_prototype {
	/**
	 * @var array $my Threadsubscription Dataset in MyBB as associative Array which will be finally inserted into the MyBB-Database
	 * @var array $wow Threadsubscription-Database-Entry of WoWBB as associative Array
	 */
	public $my = array();
	protected $wow = array();

	/**
	 * Init $this->my and $this->wow
	 *
	 * (set the values in $this->my, that doesn't change)
	 *
	 * @param void
	 * @return void
	 */
	public function init_my() {
		$this->my = array(
			//sid							//Auto Incement
			//uid
			//tid
			"notification" => 1,
			//dateline
			"subscriptionkey" => "dummy"	//see http://community.mybb.com/thread-175893.html
		);

		$this->wow = array();
	}

	/*
	 * Import the WoWBB-Database-Entry into $this->my
	 *
	 * @param int|null $wow_id if set, it selects and converts exactly the Subscription with this ID, if not set, it converts a random one, probably the first unconverted one.
	 * @return bool true
	 */
	public function import_wowbb_db($wow_id=Null) {

		global $db_old;
		$result = $db_old->query("SELECT `notification_id`, `user_id`, `topic_id` FROM `wowbb_notifications`".(($wow_id != NULL)?" WHERE `notification_id` = ".(int)$wow_id:"")." LIMIT 0, 1;");

		if(!$result) {
			var_dump($db_old->errorCode(),$db_old->errorInfo());
			die("Error in selecting notification (id can be empty to select first one) (wow_id)".$wowid);
		}

		if(!$result->rowCount())
			return false;
		$this->wow = $result->fetch(DB::FETCH_ASSOC);

		echo "\nimporting Notification ".$this->wow["notification_id"];

		return true;
	}

	/**
	 * Converts the values, that are easy to convert from $this->wow to $this->my.
	 *
	 * @param void
	 * @return void
	 */
	public function import_wowbb() {
		$this->my["uid"] = $this->uid2my($this->wow["user_id"],0);
		if(!$this->my["uid"])	return false;
		$this->my["tid"] = $this->tid2my($this->wow["topic_id"],0);
		if(!$this->my["tid"])	return false;
		$this->my["dateline"] = time();

		 return true;
	}

	/**
	 * Inserts the dataset into MyBB; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function insert_my() {
		global $db_new;

		$ret = $db_new->query("INSERT INTO `de_threadsubscriptions` ".$db_new->build_array($this->my,"INSERT").";");

		if(!$ret || !$ret->rowCount()) {
			var_dump($db_new->errorCode(),$db_new->errorInfo());
			die("Error in inserting Notification (wow_id)".$this->wow["notification_id"]);
		} else
			echo "done (".$db_new->lastInsertId().") (my)";
	}

	/**
	 * Deletes the Row from WoWBB to make sure it isn't reconverted; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function del_wow() {
		global $db_old;
		if(!$res = $db_old->query("DELETE FROM `wowbb_notifications` WHERE `notification_id` = '".(int)$this->wow["notification_id"]."';")) {
			var_dump($db_old->errorCode(),$db_old->errorInfo());
			die("Error on deleting Notification (wow)".$this->wow["notification_id"]);
		}

		if($res->rowCount() != 1) {
			var_dump($res->errorCode(),$res->errorInfo());
			die("Error: No Notification deleted (wow)".$this->wow["notification_id"]);
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
			if($this->import_wowbb())
				$this->insert_my();
			$this->del_wow();

			$this->init_my();
		}
	}
}
?>