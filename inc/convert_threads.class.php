<?php
/**
 * Converting-class for Threads
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */
class convert_threads extends convert_prototype {
	/**
	 * @var array $my Thread Dataset in MyBB as associative Array which will be finally inserted into the MyBB-Database
	 * @var array $wow Thread-Database-Entry of WoWBB as associative Array
	 * @var object|null $postobj Postconverter-Object, to be able to use its Methods
	 */
	public $my = array();
	public $wow = array();
	public $postobj;

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
			//tid
			//fid
			//subject
			"prefix" => 0,
			"icon" => 0,
			"poll" => 0,
			//uid
			//username
			//dateline
			// //firstpost
			//lastpost
			//lastposter
			//lastposteruid
			//views
			//replies
			//closed
			//sticky
			"numratings" => 0,
			"totalratings" => 0,
			"notes" => "",
			"visible" => 1,
			"unapprovedposts" => 0,
			"deletedposts" => 0,
			"attachmentcount" => 0,
			"deletetime" => 0
		);

		$this->wow = array();
	}

	/*
	 * Import the WoWBB-Database-Entry into $this->my
	 *
	 * @param int|null $wow_id if set, it selects and converts exactly the Thread with this ID, if not set, it converts a random one, probably the first unconverted one.
	 * @return bool true
	 */
	public function import_wowbb_db($wow_id=Null) {

		global $db_old;
		$result = $db_old->query("SELECT * FROM `wowbb_topics`". ((!is_null($wow_id))?" WHERE `topic_id` = ".(int)$wow_id:"") ." LIMIT 0, 1;");

		if(!$result->rowCount())
			return false;
		$this->wow = $result->fetch(DB::FETCH_ASSOC);

		echo "\nimporting Thread ".$this->wow["topic_id"];

		return true;
	}

	/**
	 * Converts the values, that are easy to convert from $this->wow to $this->my.
	 *
	 * @param void
	 * @return void
	 */
	public function import_wowbb_simple() {
		$this->my["wow_tid"] = $this->wow["topic_id"];
		$this->my["fid"] = $this->fid2my($this->wow["forum_id"]);
		$this->my["subject"]  = $this->wow["topic_name"];
		$this->my["subject"] .= (($this->wow["topic_description"] != "")?" - ".$this->wow["topic_description"]:"");
		$this->my["views"] = $this->wow["topic_views"];
		$this->my["closed"] = ($this->wow["topic_status"])?"1":"";
		$this->my["sticky"] = $this->wow["topic_type"];
	}

	/**
	 * Imports data (Username, User-ID, Posttime) of the first and last post of the thread
	 *
	 * @param void
	 * @return void
	 */
	public function import_wowbb_first_and_last_post() {
		global $db_old;
		/**
		 * Firstpost
		 */
		$result = $db_old->query("SELECT `user_id`, `post_user_name`,  UNIX_TIMESTAMP(`post_date_time`) AS `post_date_time` FROM `wowbb_posts` WHERE `topic_id` = ".(int)$this->wow["topic_id"]." ORDER BY `post_date_time` ASC LIMIT 0,1");

		if(!$result->rowCount())
			die("Konnte keinen Post mit der Topic_ID ".$this->wow["topic_id"]."(wow) finden!");
		$data = $result->fetch(DB::FETCH_ASSOC);

		$this->import_wowbb_post_userdata($data, $this->my["dateline"], $this->my["uid"], $this->my["username"]);

		/**
		 * Lastpost
		 */
		$result = $db_old->query("SELECT `user_id`, `post_user_name`,  UNIX_TIMESTAMP(`post_date_time`) AS `post_date_time` FROM `wowbb_posts` WHERE `topic_id` = ".(int)$this->wow["topic_id"]." ORDER BY `post_date_time` DESC LIMIT 0,1");

		if(!$result->rowCount())
			die("Konnte keinen Post mit der Topic_ID ".$this->wow["topic_id"]."(wow) finden!");
		$data = $result->fetch(DB::FETCH_ASSOC);

		$this->import_wowbb_post_userdata($data, $this->my["lastpost"], $this->my["lastposteruid"], $this->my["lastposter"]);
	}

	/**
	 * Counts the number of Replies
	 *
	 * @param void
	 * @return bool true
	 */
	public function import_wowbb_replies() {
		global $db_old;
		$this->my["replies"] = $db_old->query("SELECT COUNT(*)  FROM `wowbb_posts` WHERE `topic_id` = ".(int)$this->wow["topic_id"])->fetch(PDO::FETCH_NUM)[0]-1;

		return true;
	}

	/**
	 * Inserts the current data of the thread and get the Thread-ID; `firstpost` isn't set, yet
	 *
	 * @param void
	 * @return void
	 */
	public function insert_my_first_run() {
		global $db_new;
		$old_uid = $db_new->query("SELECT `tid`  FROM `de_threads` WHERE `wow_tid` LIKE '".(int)$this->my["wow_tid"]."';");
		if($old_uid->rowCount()) {
			echo "already in db; del ...";
			$this->my["tid"] = $old_uid->fetch(PDO::FETCH_NUM)[0];
			echo (is_object($db_new->query("DELETE FROM `de_threads` WHERE `tid` = ".(int)$this->my["tid"].";")))?"successfull":("bad ...".die());
		}

		$ret = $db_new->query("INSERT INTO `de_threads`".$db_new->build_array($this->my,"INSERT").";");

		if(!$ret || !$ret->rowCount()) {
			var_dump($db_new->errorCode(),$db_new->errorInfo());
			die("Error in inserting Thread (firstrun) (wow_id)".$this->wow["topic_id"]);
		} else {
			$this->my["tid"] = $db_new->lastInsertId();
			echo "Successfully inserted Thread (firstrun) ".$this->my["tid"]." (MyBB-ID)";
		}
	}

	/**
	 * Updates MyBB-Thread-Row and sets `firstpost`
	 *
	 * @param void
	 * @return void
	 */
	public function update_my() {
		global $db_new;
		$ret = $db_new->query("UPDATE `de_threads` SET `firstpost` = ".(int)$this->my["firstpost"]." WHERE `tid` = ".(int)$this->my["tid"].";");

		if(!$ret || !$ret->rowCount()) {
			var_dump($db_new->errorCode(),$db_new->errorInfo());
			die("Error in updating Thread (firstrun) (wow_id)".$this->wow["topic_id"]);
		} else {
			$this->my["tid"] = $db_new->lastInsertId();
			echo "\nSuccessfully updated Thread (firstrun) ".(int)$this->my["tid"]." (MyBB-ID)\n";
		}
	}

	/**
	 * Deletes Dataset in WoWBB to prevent reconverting on restart
	 *
	 * @param void
	 * @return void
	 */
	public function del_wow() {
		global $db_old;
		if(!$res = $db_old->query("DELETE FROM `wowbb_topics` WHERE `topic_id` = '".(int)$this->wow["topic_id"]."';")) {
			var_dump($db_old->errorCode(),$db_old->errorInfo());
			die("Error on deleting Thread (wow)".$this->wow["topic_id"]);
		}

		if($res->rowCount() != 1) {
			var_dump($res->errorCode(),$res->errorInfo());
			die("Error: No Topic deleted (wow)".$this->wow["topic_id"]);
		}

		echo "erfolgreich gelöscht ...\n";
	}

	/**
	 * Function which does the whole process of converting and is called in main.php
	 *
	 * @param void
	 * @return void
	 */
	public function do_all() {
		$this->postobj = new convert_posts($this);

		$this->init_my();
		while($this->import_wowbb_db()) {
			$this->import_wowbb_simple();
			$this->import_wowbb_first_and_last_post();
			$this->import_wowbb_replies();
			$this->insert_my_first_run();

			$this->postobj->do_all();
			$this->update_my();
			$this->del_wow();

			$this->init_my();
		}
	}
}
?>