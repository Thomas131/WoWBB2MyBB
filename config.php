<?php
/**
 * Config of the Converter to comvert WoWBB-Forums to MyBB.
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */

/**
 *	@var array $db_old_data database-settings of the old database
 */
$db_old_data = array(
	"db-type" => "mysql",
	"host" => "127.0.0.1",
	"port" => 3306,
	"username" => "",
	"password" => "",
	"database" => ""
);

/**
 *	@var array $db_new_data database-settings of the new database
 */
$db_new_data = array(
	"db-type" => "mysql",
	"host" => "127.0.0.1",
	"port" => 3306,
	"username" => "",
	"password" => "",
	"database" => ""
);

/**
 * @var array $fid_convert_array Foren-ID-converter-array (for each forum: WoWBB-Foren-ID => MyBB-Foren-ID)
 */
$fid_convert_array = Array(
	1  => 13,
	2  => 10,
	5  => 12,
	7  => 17,
	8  => 14,
	9  => 16,
	13 => 9,
	16 => 8,
	17 => 15,
	18 => 11
);