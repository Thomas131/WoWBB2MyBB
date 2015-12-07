<?php
/**
 * Converter-Prototype
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */
class convert_prototype {
	/**
	 * @var array $user_id_cache Array of WoWBB-UserIDs as Keys and Usernames as Values; static to have the same array in all extended classes->just one time to initialize
	 */
	public static $user_id_cache = array();

	/**
	 * Converts IP-Adresses to the Format used by MyBB
	 *
	 * @uses mybb_functions.php
	 * @param string $ip IP-Adress in dotted decimal writing (for example 127.0.0.1)
	 * @return string returns the IP-Adress in the format used by MyBB
	 */
	public function ip2my($ip) {
		return my_inet_pton($ip);
	}

	/**
	 * Converts Hardcoded Group-IDs from WoWBB to MyBB
	 *
	 * @param int WoWBB-Group-ID
	 * @returns int MyBB-Group-ID
	 */
	public function gid2my($gid) {
		$convert_array = Array(
			1 => 1,
			2 => 2,
			3 => 7,
			4 => 6,
			5 => 3,
			6 => 4
		);

		return $convert_array[$gid];
	}

	/**
	 * Converts Foren-IDs from WoWBB to MyBB (Convertion-Table in config.php); if not found it breaks unless $break_on_fail is false
	 *
	 * @uses $fid_convert_array from config.php
	 * @param int WoWBB-Forum-ID
	 * @param bool should the script break if the ID isn't found? default: true
	 * @returns int|false MyBB-Forum-ID, if found; false, if not found
	 */
	public function fid2my($fid, $break_on_fail=true) {
		global $fid_convert_array;

		if(!isset($fid_convert_array[$fid])) {
			if($break_on_fail)
				die("Forum ".$fid."(wow-id) not found!!!");
			else
				return false;
		}

		return $fid_convert_array[$fid];
	}

	/**
	 * Caches all MyBB-User-IDs and WoWBB-User-IDs in an array to be able to be accessed much faster; users need to be already converted
	 */
	private function cache_uid2my() {
		global $db_new;

		foreach($db_new->query("SELECT  `wowbb_user_id`,`uid` FROM  `de_users` WHERE  `wowbb_user_id` IS NOT NULL", $db_new->FETCH_ASSOC) as $row) {
			$this->user_id_cache[(int)$row["wowbb_user_id"]] = $row["uid"];
		}
	}

	/**
	 * Converts Thread-IDs from WoWBB to MyBB from the database; The Thread needs to be already converted! If the Thred-ID is not found, it breaks unless $break_on_fail is false
	 *
	 * @param int WoWBB-Thread-ID
	 * @param bool should the script break if the ID isn't found? default: true
	 * @returns int|false MyBB-Thread-ID, if found; false, if not found
	 */
	public function tid2my($tid, $break_on_fail=true) {
		global $db_new;

		$result = $db_new->prepare("SELECT `tid` FROM `de_threads` WHERE `wow_tid` = ?;");
		$result->execute(array((int)$tid));

		if(!$result->rowCount()) {
			if($break_on_fail)
				die("Thread ".$tid." (wow_id) nicht gefunden!");
			else
				return false;
		}

		return $result->fetch(PDO::FETCH_NUM)[0];
	}

	/**
	 * Converts User-IDs from WoWBB to MyBB from Cache; The Users needs to be already converted! If the User-ID is not found, it breaks unless $break_on_fail is set false
	 *
	 * @param int WoWBB-User-ID
	 * @param bool should the script break if the ID isn't found? default: true
	 * @returns int|false MyBB-User-ID, if found; false, if not found
	 */
	public function uid2my($uid, $break_on_fail=true) {
		if(!count($this->user_id_cache)) $this->cache_uid2my();

		if(!isset($this->user_id_cache[$uid])) {
			if($break_on_fail)
				die("User ".$uid."(wow-id) not found!!!");
			else
				return false;
		}

		return $this->user_id_cache[$uid];
	}

	//uid2my() without Caching:
	/*public function uid2my($uid) {
		global $db_new;
		$result = $db_new->prepare("SELECT  `uid` FROM  `de_users` WHERE  `wowbb_user_id` = ?;");
		$result->execute(array((int)$uid));

		if(!$result->rowCount())
			return false;

		return $result->fetch()["uid"];
	}/**/

	/**
	 * Converts WoWBB or MyBB User-IDs to Username from Database. The user needs to be already converted. If the User-ID is not found, it breaks unless $break_on_fail is set false
	 *
	 * @param int User-ID
	 * @param string Is the ID from WoWBB or from MyBB?
	 * @param bool should the script break if the ID isn't found? default: true
	 * @returns int|false Username, if found; false, if not found
	 */
	public function uid2name($uid, $type="wow", $break_on_fail=true) {
		global $db_new;

		$result = $db_new->prepare("SELECT  `username` FROM  `de_users` WHERE  ".(($type=="wow")?"`wowbb_user_id`":"`uid`")." = ?;");
		$result->execute(array((int)$uid));

		if(!$result->rowCount()) {
			if($break_on_fail)
				die("Benutzer ".$uid." (".$type.") nicht gefunden!");
			else
				return false;
		}

		return $result->fetch()["username"];
	}

	/**
	 * Converts username, date and username of a WowBB-Post-MySQL-Row as associative array
	 *
	 * @param array Associative array containing MySQL-Row of a Post in WoWBB
	 * @param int var which becomes Unix-Timestam, when the post was written
	 * @param int var which becomes MyBB-User-ID
	 * @param string var which becomes Username
	 * @return bool true
	 */
	public function import_wowbb_post_userdata(&$data, &$dateline, &$uid, &$username) {		//data is just a reference for speed-improvement
		$dateline = (int)$data["post_date_time"];

		if($data["user_id"] && $this->uid2my($data["user_id"],0) && $data["post_user_name"] != "") {
			$uid = $this->uid2my($data["user_id"]);
			$username = $data["post_user_name"];
		} elseif($data["user_id"] && $this->uid2my($data["user_id"],0)) {
			$uid = $this->uid2my($data["user_id"]);
			$username = $this->uid2name($data["user_id"]);
		} elseif($data["post_user_name"] != "") {
			$uid = 0;
			$username = $data["post_user_name"];
		} else {
			$uid = 0;
			$username = "Irgendjemand ...";
		}

		return true;
	}


	/**
	 * Converts BB-Code
	 *
	 * @param string WoWBB-BB-Code
	 * @return string MyBB-BB-Code
	 */
	public function bb2my($input) {
		/**
		 * Force UTF-8
		 * @see Encoding.php
		 */
		$input = \ForceUTF8\Encoding::fixUTF8($input);

		/**
		 * HTML to Specialchar
		 */
		$input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		/**
		 * Regexes from WowBB
		 */
		$input = preg_replace('/\[\s*(\/*)\s*(\S*)\s*(=*)\s*("?)(.*)("?)\s*\]/isU', '[\\1\\2\\3\\4\\5\\6]', $input);
		$input = preg_replace('/\[([\/=*a-z0-9]+):[a-z0-9]*(=".+")?\]/iU', '[\\1\\2]', $input);
		$input = preg_replace('/(?<!http:\/\/|\/)(www\.\S+)\b/isU', 'http://\\1', $input);
		$input = preg_replace('/(?<!\]|=|")http(s?):\/\/([^\s<\[]+)/is','[url]http\\1://\\2[/url]',$input);


		/**
		 * Own Replace
		 */
		$input = str_replace("&nbsp;"," ",$input);
		$input = str_replace("[line]",'[hr]',$input);
		$input = str_replace("[/*]","",$input);

		$input = preg_replace('/\[([^\]"]+)="([^\]]+)"\]/is','[\1=\2]',$input);
		$input = preg_replace('/\[(\/?)indent\]/i','[\1quote]',$input);
		$input = preg_replace('/\[(\/?)php\]/i','[\1code]',$input);

		/**
		 * [user=...]...[/user]-Convert
		 */
		$input = preg_replace_callback('/\[user="?([0-9]+)"?\](.*?)\[\/user\]/si',
			function ($matches) {
				$my_uid = $this->uid2my($matches[1],0);
				if(!$my_uid)
					return $matches[2];
				else
					return "[user=".$my_uid."]".$matches[2]."[/user]";
			},$input
		);

		/**
		 * Convert Sizes from HTML3-font-tag
		 */
		$input = preg_replace_callback('/\[size="?([+-]?[0-9]+)"?\]/is',
			function ($matches) {
				$ret = "[size=";
				switch($matches[1]) {
					case "-9":
					case "-8":
					case "-7":
					case "-6":
					case "-5":
					case "-4":
					case "-3":
					case "-2":
						$ret .= "62.5%"; break;
					case "-1":
						$ret .= "smaller"; break;

					case "-0":
					case "+0":
						$ret .= "100%"; break;

					case "0":
						$ret .= "xx-small"; break;
					case "1":
						$ret .= "x-small"; break;
					case "2":
						$ret .= "small"; break;
					case "3":
						$ret .= "medium"; break;
					case "4":
						$ret .= "large"; break;
					case "5":
						$ret .= "x-large"; break;
					case "6":
						$ret .= "xx-large"; break;
					case "7":
					case "8":
					case "9":
						$ret .= "48px"; break;

					case "+1":
						$ret .= "larger"; break;
					case "+2":
						$ret .= "150%"; break;
					case "+3":
						$ret .= "200%"; break;
					case "+4":
					case "+5":
					case "+6":
					case "+7":
					case "+8":
					case "+9":
						$ret .= "300%"; break;

					default:
						$ret .= (int)$matches[1]."px";
				}
				$ret .= "]";

				return $ret;
			},$input
		);

		/**
		 * Not supported => delete
		 */
		$input = preg_replace('/\[(sound|real|quicktime|media|fl|flash)[^\]]*\].*?\[\/\1\]/si',"",$input);
		$input = preg_replace('/\[\/?(?:blur|glow)\]/i',"",$input);
		$input = preg_replace('/\[\/?(?:shadow|move(?:up|[ldr])?|scroll)[^\]]*\]/i',"",$input);

		return $input;
	}
}
?>