<?php
/**
 * Converting-class for Posts
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */
class convert_posts extends convert_prototype {
	/**
	 * @var array $my Post Dataset in MyBB as associative Array which will be finally inserted into the MyBB-Database
	 * @var array $wow Post-Database-Entry of WoWBB as associative Array; merged from `wowbb_posts` and `wowbb_post_texts`
	 * @var &object $threadref Reference to Threadconverter, to be able to use its Methods
	 * @var int $lastpost The ID if the last successfully converted post
	 */
	public $my = array();
	public $wow = array();
	protected $threadref;
	public $lastpost = 0;

	/**
	 * Constructer which sets the reference of the Threadconvert-object to be able to call thread-convert-methods
	 *
	 * @used convert_threads::do_all
	 * @param &object $threadref The Reference of the Threadconvert-object
	 * @return void
	 */
	public function __construct(&$threadref) {
		$this->threadref =& $threadref;
	}

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
			//pid
			//wow_pid
			//tid
			//replyto
			//fid
			//subject
			"icon" => 0,
			//uid
			//username
			//dateline
			//message
			//ipadress
			"includesig" => 0,
			"smilieoff" => 0,
			//edituid
			//edittime
			"editreason" => "",
			"visible" => 1
		);

		$this->wow = array();
	}

	/*
	 * Import the WoWBB-Database-Entry into $this->my
	 *
	 * @param int|null $wow_id if set, it selects and converts exactly the Post with this ID, if not set, it converts a random one, probably the first unconverted one.
	 * @return bool true
	 */
	public function import_wowbb_db($wow_id=Null) {
		global $db_old;
		$result = $db_old->query("SELECT `wowbb_posts`.`post_id`, `wowbb_posts`.`topic_id`, `wowbb_posts`.`user_id`, `wowbb_posts`.`post_user_name`, UNIX_TIMESTAMP(`wowbb_posts`.`post_date_time`) AS `post_date_time`, `wowbb_posts`.`post_ip`, UNIX_TIMESTAMP(`wowbb_posts`.`post_last_edited_on`) AS `post_last_edited_on`, `wowbb_posts`.`post_last_edited_by`, `wowbb_post_texts`.`post_text` FROM `wowbb_posts`, `wowbb_post_texts` WHERE `topic_id` = ".(int)$this->threadref->wow["topic_id"]." AND `wowbb_posts`.`post_id` = `wowbb_post_texts`.`post_id`". ((!is_null($wow_id))?" AND `wowbb_posts`.`post_id` = ".(int)$wow_id:"") ." ORDER BY `wowbb_posts`.`post_date_time` ASC LIMIT 0,1;");

		if(!$result->rowCount())
			return false;
		$this->wow = $result->fetch(DB::FETCH_ASSOC);

		echo "\nimporting Post ".$this->wow["post_id"];

		return true;
	}

	/**
	 * Converts the values, that are easy to convert from $this->wow to $this->my.
	 *
	 * @param void
	 * @return void
	 */
	public function import_wowbb_simple() {
		$this->my["wow_pid"] = $this->wow["post_id"];
		$this->my["tid"] = $this->threadref->my["tid"];
		$this->my["replyto"] = $this->lastpost;
		$this->my["fid"] = $this->threadref->my["fid"];
		$this->my["subject"] = $this->threadref->my["subject"];

		$this->import_wowbb_post_userdata($this->wow, $this->my["dateline"], $this->my["uid"], $this->my["username"]);

		$this->my["message"] = $this->bb2my($this->wow["post_text"]);
		$this->my["ipaddress"] = $this->ip2my($this->wow["post_ip"]);
		$this->my["edituid"] = $this->uid2my($this->wow["post_last_edited_by"],0);
		$this->my["edittime"] = $this->wow["post_last_edited_on"];
	}

	/**
	 * Inserts the dataset into MyBB; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function insert_my() {
		global $db_new;
		$old_uid = $db_new->query("SELECT `pid`  FROM `de_posts` WHERE `wow_pid` LIKE '".(int)$this->my["wow_pid"]."';");
		if($old_uid->rowCount()) {
			echo "already in db; del ...";
			$this->my["pid"] = $old_uid->fetch(PDO::FETCH_NUM)[0];
			echo (is_object($db_new->query("DELETE FROM `de_posts` WHERE `pid` = ".(int)$this->my["pid"].";")))?"erfolgreich":("bad ...".die());
		}


		$ret = $db_new->query("INSERT INTO `de_posts`".$db_new->build_array($this->my,"INSERT").";");

		if(!$ret || !$ret->rowCount()) {
			var_dump($db_new->errorCode(),$db_new->errorInfo());
			die("Error in inserting Post (wow_id)".$this->wow["post_id"]);
		} else {
			$this->my["pid"] = $db_new->lastInsertId();
			echo "Successfully inserted Post ".$this->my["pid"]." (MyBB-ID)";
		}
	}

	/**
	 * Deletes the Row from WoWBB to make sure it isn't reconverted; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function del_wow() {
		global $db_old;
		if(!$res = $db_old->query("DELETE FROM `wowbb_posts` WHERE `post_id` = '".(int)$this->wow["post_id"]."';")) {
			var_dump($db_old->errorCode(),$db_old->errorInfo());
			die("Error on deleting Post (wow)".$this->wow["post_id"]);
		}

		if($res->rowCount() != 1) {
			var_dump($res->errorCode(),$res->errorInfo());
			die("Error: No Post deleted (wow)".$this->wow["post_id"]);
		}

		echo "erfolgreich gelöscht ...";
	}

	/**
	 * Function which does the whole process of converting and is called in convert_threads::do_all; dies on Error
	 *
	 * @param void
	 * @return void
	 */
	public function do_all() {
		$this->lastpost = 0;
		$this->init_my();
		while($this->import_wowbb_db()) {
			$this->import_wowbb_simple();
			$this->insert_my();
			if(!$this->lastpost) $this->threadref->my["firstpost"] = $this->my["pid"];
			$this->lastpost = $this->my["pid"];
			$this->del_wow();
			$this->init_my();
		}
	}
}
?>