<?php
/**
 * User-Converting-Class
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */
class convert_users extends convert_prototype {
	/**
	 * @var array $my_users User Dataset in MyBB as associative Array which will be finally inserted into the MyBB-Database
	 * @var array $my_userfields Dataset in MyBB of the custom profile fields as associative Array which will be finally inserted into the MyBB-Database
	 * @var array $wow_users Thread-Database-Entry of WoWBB as associative Array
	 * @var array $cache_wow_visited_topics_pointer file-pointer of cache-file (php-file)
	 * @var array $cache_wow_visited_topics array($WoWBB-UID => array($WoWBB-Thread-ID => $Timestamp [, ...] ) [, ...] )
	 */
	public $my_users = array();
	public $my_userfields = array();
	protected $wow_users = array();
	public $cache_wow_visited_topics_pointer;
	public $cache_wow_visited_topics = array();

	/**
	 * Constructor which opens Cache-File for visited Threads and makes it executable
	 * 
	 * @param void
	 * @return void
	 */
	public function __construct() {
		$this->cache_wow_visited_topics_pointer = fopen("cache_wow_visited_topics.php","a");
		fwrite($this->cache_wow_visited_topics_pointer, '<?php if(!isset($cache_wow_visited_topics)) { $cache_wow_visited_topics = array();}');
	}

	/**
	 * Destructor which securely closes visited-Threads-Cachefile
	 * 
	 * @param void
	 * @return void
	 */
	public function __destruct() {
		if(is_resource($this->cache_wow_visited_topics_pointer)) {
			fwrite($this->cache_wow_visited_topics_pointer," ?>");
			fclose($this->cache_wow_visited_topics_pointer);
		}
	}

	public function import_cache_wow_watched_forums() {

		global $db_old;
		foreach($db_old->query("SELECT * FROM `wowbb_watched_forums`;") as $row) {
			$this->cache_wow_watched_forums[$row["user_id"]] = explode(",",$row["forum_ids"]);
		}

		return true;
	}

	public function init_my() {
		$this->wow_users = array();
		$this->my_users = array(
			//uid						//DONE
			//wowbb_user_id				//DONE
			//username					//DONE
			//password					//DONE
			"salt" => "",
			"loginkey" => "",
			//email						//DONE
			//"postnum" => 0,			//COMPLEX&DONE
			"threadnum" => 0,
			"avatar" => "",
			"avatardimensions" => "",
			"avatartype" => "",
			//usergroup					//DONE
			"additionalgroups" => "",
			"displaygroup" => 0,
			"usertitle" => "",
			//regdate					//DONE
			//lastactive				//DONE
			//lastvisit					//DONE
			//lastpost					//DONE & COMPLEX
			//website					//DONE
			//icq						//DONE
			//aim						//DONE
			"yahoo" => "",
			"skype" => "",
			"google" => "",
			//birthday					//DONE
			"birthdayprivacy" => "none",//yes, Really
			"signature" => "",
			//allownotices				//DONE
			//hideemail					//DONE
			"subscriptionmethod" => 2,
			//invisible					//DONE
			//receivepms				//DONE
			"receivefrombuddy" => 0,
			"pmnotice" => 1,
			//pmnotify					//DONE
			"buddyrequestspm" => 1,
			"buddyrequestsauto" => 0,
			"threadmode" => "linear",
			"showimages" => 1,
			"showvideos" => 1,
			"showsigs" => 1,
			"showavatars" => 1,
			"showquickreply" => 1,
			"showredirect" => 0,
			"ppp" => 0,
			"tpp" => 0,
			"daysprune" => 0,
			"dateformat" => 0,
			"timeformat" => 0,
			"timezone" => 1,
			"dst" => 1,					//AUFPASSEN!!! Ist bei Konvertierungszeitpunkt Sommerzeit?
			"dstcorrection" => 2,
			"buddylist" => "",
			"ignorelist" => "",
			"style" => 0,
			"away" => 0,
			"awaydate" => 0,
			"returndate" => 0,
			"awayreason" => 0,
			"pmfolders" => "1**Eingang$%%$2**Ausgang$%%$3**Entwürfe$%%$4**Papierkorb",
			"notepad" => "",
			"referrer" => 0,
			"referrals" => 0,
			"reputation" => 0,
			"regip" => "",
			//lastip					//COMPLEX&DONE
			"language" => "",
			"timeonline"  => 0,
			"showcodebuttons" => 1,
			//totalpms					//COMPLEX&DONE
			//unreadpms					//COMPLEX&DONE
			"warningpoints" => 0,
			"moderateposts" => 0,
			"moderationtime" => 0,
			"suspendposting" => 0,
			"suspensiontime" => 0,
			"suspendsignature" => 0,
			"suspendsigtime" => 0,
			"coppauser" => 0,
			"classicpostbit" => 1,
			"loginattempts" => 1, //Yes, 1 is the default value!?
			"usernotes" => "",
			"sourceeditor" => 1
		);

		$this->my_userfields = array(
			//ufid								//My-UID
			//fid1								//Land
			"fid3" => "will ich nicht sagen"	//Geschlecht
		);
	}

	public function import_wowbb_db($wow_id=Null) {

		global $db_old;
		$result = $db_old->query("SELECT `user_id`, `user_group_id`, `user_name`, `user_password`, `user_email`, `user_view_email`, `user_icq`, `user_aim`, `user_ym`, `user_msnm`, `user_homepage`, `user_city`, `user_region`, `user_country`, `user_birthday`, `user_date_format`, `user_time_format`, UNIX_TIMESTAMP( `user_joined` ) AS `user_joined`, `user_topic_notification`, `user_enable_pm`, `user_pm_notification`, `user_invisible`, `user_unread_pm`, `user_admin_emails`, `user_forum_digest`, `user_visited_topics` FROM `wowbb_users`". ((!is_null($wow_id))?" WHERE `user_id` = ".(int)$wow_id:"") ." LIMIT 0, 1;");

		if(!$result->rowCount())
			return false;
		$this->wow_users = $result->fetch(DB::FETCH_ASSOC);

		echo "\nimporting User ".$this->wow_users["user_id"];

		return true;
	}

	public function import_wowbb_visited() {
		$replace_array = array();
		if($this->wow_users["user_visited_topics"])
			foreach(unserialize($this->wow_users["user_visited_topics"]) AS $forum) {
				if(is_array($forum))
					$replace_array += $forum;
				elseif(is_bool($forum))
					echo "\n\nConverter_notice: Unread_forum is bool!!!";
				else
					die("\n\nUnread_forum isn't array!!!");
			}

		$this->wow_users["user_visited"] = $replace_array;
		fwrite($this->cache_wow_visited_topics_pointer,'$cache_wow_visited_topics['.(int)$this->wow_users["user_id"].'] = '.var_export($replace_array, true)."; ");
	}

	public function import_wowbb_last_post_and_ip() {
		global $db_old;
		$result = $db_old->query("SELECT UNIX_TIMESTAMP(`post_date_time`) AS `post_date_time`, `post_ip` FROM `wowbb_posts` WHERE `user_id` = ".(int)$this->wow_users["user_id"]." ORDER BY `post_date_time` DESC LIMIT 0 , 1;");

		if(!$result->rowCount()) {
			$this->my_users["lastpost"] = 0;
			$this->my_users["lastip"] = "";
		} else {
			$data = $result->fetch(PDO::FETCH_ASSOC);
			$this->my_users["lastpost"] = $data["post_date_time"];
			$this->my_users["lastip"] = $this->ip2my($data["post_ip"]);
		}

		return true;
	}

	public function import_wowbb_postnum() {
		global $db_old;
		$this->my_users["postnum"] = $db_old->query("SELECT COUNT(*)  FROM `wowbb_posts` WHERE `user_id` = ".(int)$this->wow_users["user_id"].";")->fetch(PDO::FETCH_NUM)[0];

		return true;
	}

	public function import_wowbb_pms() {
		global $db_old;
		$this->my_users["totalpms"] = $db_old->query("SELECT COUNT(*)  FROM `wowbb_pm` WHERE `user_id` = ".(int)$this->wow_users["user_id"]." AND `pm_folder_id` = 100")->fetch(PDO::FETCH_NUM)[0];
		$this->my_users["unreadpms"] = $db_old->query("SELECT COUNT(*)  FROM `wowbb_pm` WHERE `user_id` = ".(int)$this->wow_users["user_id"]." AND `pm_folder_id` = 100 AND `pm_status` = 0")->fetch(PDO::FETCH_NUM)[0];

		return true;
	}

	public function import_wowbb_simple() {
		if(!isset($this->wow_users["user_visited"])) $this->import_wowbb_visited();

		$this->my_users["wowbb_user_id"] = $this->wow_users["user_id"];
		$this->my_users["username"] = $this->wow_users["user_name"];
		$this->my_users["password"] = $this->wow_users["user_password"];
		$this->my_users["email"] = $this->wow_users["user_email"];
		$this->my_users["usergroup"] = $this->gid2my($this->wow_users["user_group_id"]);
		$this->my_users["regdate"] = $this->wow_users["user_joined"];
		$this->my_users["lastactive"] = (int)@max($this->wow_users["user_visited"]);
		$this->my_users["lastvisit"] = $this->my_users["lastactive"];
		$this->my_users["aim"] = $this->wow_users["user_aim"];
		$this->my_users["birthday"] = ($this->wow_users["user_birthday"] != "0000-00-00")?preg_replace("/^([0-9]+)-0?([1-9][0-9]*)-0?([1-9][0-9]*)$/","$3-$2-$1",$this->wow_users["user_birthday"]):"";
		$this->my_users["allownotices"] = $this->wow_users["user_admin_emails"];
		$this->my_users["hideemail"] = !$this->wow_users["user_view_email"];
		$this->my_users["invisible"] = $this->wow_users["user_invisible"];
		$this->my_users["receivepms"] = $this->wow_users["user_enable_pm"];
		$this->my_users["pmnotify"] = $this->wow_users["user_pm_notification"];
		$this->my_users["website"] = $this->wow_users["user_homepage"];
		$this->my_users["icq"] = ($this->wow_users["user_icq"] == "")?0:$this->wow_users["user_icq"];
	}

	public function import_wow_region() {
		$region = "";

		if(isset($this->wow_users["user_city"])) $region .= $this->wow_users["user_city"]." ";
		if(isset($this->wow_users["user_region"])) $region .= $this->wow_users["user_region"]." ";
		$region .= $this->wow_users["user_country"];

		$this->my_userfields["fid1"] = $region;
	}

	public function import_forum_subscribed() {
		if(isset($this->cache_wow_watched_forums[$this->wow_users["user_id"]])) {
			global $db_new;
			$prepared = $db_new->prepare("INSERT INTO `de_forumsubscriptions` (`fsid`, `fid`, `uid`) VALUES (NULL, ?, ?);");

			foreach($this->cache_wow_watched_forums[$this->wow_users["user_id"]] AS $wow_fid) {
				$prepared->execute(array($this->fid2my($wow_fid),$this->my_users["uid"]));
			}

			unset($this->cache_wow_watched_forums[$this->wow_users["user_id"]]);
		}

		return true;
	}

	public function insert_my_first_run() {
		global $db_new;
		$old_uid = $db_new->query("SELECT `uid`  FROM `de_users` WHERE `username` LIKE ".$db_new->quote($this->my_users["username"]).";");
		if($old_uid->rowCount()) {
			echo "already in db; del ...";
			$this->my_users["uid"] = $old_uid->fetch(PDO::FETCH_NUM)[0];
			echo (is_object($db_new->query("DELETE FROM `de_users` WHERE `de_users`.`uid` = ".(int)$this->my_users["uid"].";")))?"erfolgreich":("bad ...".die());
		}

		$ret = $db_new->query("INSERT INTO `de_users`".$db_new->build_array($this->my_users,"INSERT").";");

		if(!$ret || !$ret->rowCount()) {
				var_dump($db_new->errorCode(),$db_new->errorInfo());
				die("Error in inserting userfields (wow_id)".$this->my_users["wowbb_user_id"]);
		}

		/**
		 * Own Profile fields
		 */

		$this->my_userfields["ufid"] = $this->my_users["uid"] = $db_new->lastInsertId();

		if(!$db_new->query("SELECT COUNT(*)  FROM `de_userfields` WHERE `ufid` LIKE ".$db_new->quote($this->my_userfields["ufid"]).";")->fetch(PDO::FETCH_NUM)[0]) {
			$ret2 = $db_new->query("INSERT INTO `de_userfields`".$db_new->build_array($this->my_userfields,"INSERT").";");

			if(!$ret2 || !$ret2->rowCount()) {
				var_dump($db_new->errorCode(),$db_new->errorInfo());
				die("Error in inserting userfields (wow_id)".$this->my_users["wowbb_user_id"]);
			}
		} else {
			echo "userfields already in db; not overwritten";
		}

		/**
		 * Forumsubscriptions
		 */

		 $this->import_forum_subscribed();
	}

	public function insert_my_forumsread() {
		if($this->wow_users["user_visited_topics"] == "") return true;

		global $db_new;
		foreach(unserialize($this->wow_users["user_visited_topics"]) AS $wow_fid => $wow_times) {

			$my_fid = $this->fid2my($wow_fid,0);
			if(!$my_fid || is_bool($wow_times)) continue;
			$time = max($wow_times);

			if($db_new->query("SELECT COUNT(*)  FROM `de_forumsread` WHERE `fid` = ".(int)$my_fid." AND `uid` = ".(int)$this->my_users["uid"].";")->fetch(PDO::FETCH_NUM)[0]) {
				echo "\nforumsview already in db; del ...";
				echo (is_object($db_new->query("DELETE FROM `de_forumsread` WHERE `fid` = ".(int)$my_fid." AND `uid` = ".(int)$this->my_users["uid"].";")))?"erfolgreich":("bad ...".die());
			}

			$ret = $db_new->query("INSERT INTO `de_forumsread` ".$db_new->build_array(array("fid" => $my_fid, "uid" => $this->my_users["uid"], "dateline" => $time),"INSERT").";");

			if(!$ret || !$ret->rowCount()) {
					var_dump($db_new->errorCode(),$db_new->errorInfo());
					die("Error in inserting in forumsread fid => ".$my_fid.", uid => ".$this->my_users["uid"].", dateline => ".$time);
			}
		}
	}

	public function del_wow() {
		global $db_old;
		if(!$res = $db_old->query("DELETE FROM `wowbb_users` WHERE `user_id` = '".(int)$this->wow_users["user_id"]."';")) {
			var_dump($db_old->errorCode(),$db_old->errorInfo());
			die("Error on deleting User (wow)".$this->wow_users["user_id"]." SQL: DELETE FROM `wowbb_users` WHERE `user_id` = ".$this->wow_users["user_id"]." LIMIT 0, 1;");
		}

		if($res->rowCount() != 1)	die("Error: No User deleted (wow)".$this->wow_users["user_id"].$res->errorCode().$res->errorInfo());

		echo "erfolgreich gelöscht ...";
	}

	public function do_all_first_run() {
		$this->import_cache_wow_watched_forums();
		$this->init_my();
		while($this->import_wowbb_db()) {
			$this->import_wowbb_visited();
			$this->import_wowbb_simple();
			$this->import_wowbb_postnum();
			$this->import_wowbb_last_post_and_ip();
			$this->import_wowbb_pms();
			$this->import_wow_region();
			$this->insert_my_first_run();
			$this->insert_my_forumsread();
			$this->del_wow();

			$this->init_my();
		}
	}

	public function import_cache_wow_visited_topics() {
		include("cache_wow_visited_topics.php");
		$this->cache_wow_visited_topics =& $cache_wow_visited_topics;
	}

	public function insert_my_threadsread() {
		global $db_new;

		echo "\n\nConverting Threadsread\n";
		foreach($this->cache_wow_visited_topics AS $uid => $threads_info) {
			foreach($threads_info AS $wow_tid => $wow_time) {

				$my_tid = $this->tid2my($wow_tid,0);
				if(!$my_tid || is_bool($wow_time)) continue;
				$time = $wow_time;

				if($db_new->query("SELECT COUNT(*)  FROM `de_threadsread` WHERE `tid` = ".(int)$my_tid." AND `uid` = ".(int)$uid.";")->fetch(PDO::FETCH_NUM)[0]) {
					echo "\nforumsview already in db; del ...";
					echo (is_object($db_new->query("DELETE FROM `de_threadsread` WHERE `tid` = ".(int)$my_tid." AND `uid` = ".(int)$uid.";")))?"erfolgreich":("bad ...".die());
				}

				$ret = $db_new->query("INSERT INTO `de_threadsread` ".$db_new->build_array(array("tid" => $my_tid, "uid" => $uid, "dateline" => $time),"INSERT").";");

				if(!$ret || !$ret->rowCount()) {
						var_dump($db_new->errorCode(),$db_new->errorInfo());
						die("Error in inserting in threadsread tid => ".$my_tid.", uid => ".$uid.", dateline => ".$time);
				}
			}
		}
		fclose($this->cache_wow_visited_topics_pointer);
		unlink("cache_wow_visited_topics.php");
	}

	public function do_all_secound_run() {
		$this->import_cache_wow_visited_topics();
		$this->insert_my_threadsread();
	}
}
?>