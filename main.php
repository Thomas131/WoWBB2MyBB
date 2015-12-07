<?php
/**
 * Main of the Converter to comvert WoWBB-Forums to MyBB.
 *
 * @author Thomas131 <github@ttmail.at.vu>
 * @license CC0
 */

/**
 * Includes
 */
require_once("inc/db.class.php");								//Database-Class; extended from PDO with 2 functions of the PHPBB3 Database-Abstraction-Layer
require_once("inc/convert.class.php");							//The Converter-prototype which is needed for for all convert_-Files
require_once("inc/convert_user.class.php");						//The User-converter-class
require_once("inc/convert_pm.class.php");						//The PM-converter-class
require_once("inc/convert_threads.class.php");					//The Threads-converter-class which needs the post-converter
require_once("inc/convert_posts.class.php");					//The Posts-converter
require_once("inc/convert_threadsubscriptions.class.php");		//The Threadsubscription-converter-class
require_once("inc/mybb_functions.php");							//The functions.php of MyBB
require_once("inc/Encoding.php");								//converts Charsets (Class not written by me)
require_once("config.php");										//Database-config

/**
 * Database-config
 */
$db_old = new db($db_old_data["db-type"], $db_old_data["host"], $db_old_data["port"], $db_old_data["username"], $db_old_data["password"], $db_old_data["database"]);
$db_new = new db($db_new_data["db-type"], $db_new_data["host"], $db_new_data["port"], $db_new_data["username"], $db_new_data["password"], $db_new_data["database"], "", array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

// Delete for safety-reasons because not necessary anymore
unset($db_old_data, $db_new_data);

/**
 * Beginningquestions
 */
echo "Bitte spiele die aktuellen Backups ein, dann drücke [Enter]!; Verwende bitte nicht die produktive Datenbank, sondern ein duplikat davon! MySql braucht für beide Datenbanken lese- und schreibrechte";
fread(STDIN, 1);
echo "Bitte lösche alle inaktiven Benutzer, dann drücke [Enter]! Gibt es neue (letzte Tage) inaktive Benutzer zum ev. manuellen aktivieren?";
fread(STDIN, 1);
echo "Bitte schaue manuell, dass alle Homepage-Adressen stimmen, dann drücke [Enter]! (mit http(s):// starten)";
fread(STDIN, 1);
echo "Bitte schaue manuell, dass alle ICQ-Adressen stimmen, dann drücke [Enter]! (fast immer nur Nummern)";
fread(STDIN, 1);

/**
 * Convert Users
 */

$converter_users = new convert_users();
$converter_users->import_cache_wow_watched_forums();
$converter_users->do_all_first_run();

/**
 * Convert Private Messages
 */

$converter_pms = new convert_pms();
$converter_pms->do_all();

/**
 * Convert Threads & Posts
 */

$converter_threads = new convert_threads();	//Includes Convert Posts
$converter_threads->do_all();

/**
 * finish converting Users
 */

$converter_users->do_all_secound_run();

/**
 * Convert to Threadsubscriptions
 */

$converter_threadsubscriptions = new convert_threadsubscriptions();
$converter_threadsubscriptions->do_all();


/**
 * destruct DB
 */

$db_old = NULL;
$db_new = NULL;

/**
 * Endabfragen
 */
echo "Bitte Avatars manuell importieren (Admin-CP), dann drücke [Enter]!";
fread(STDIN, 1);
echo "Bitte konvertiere die Attachments manuell, dann drücke [Enter]!";
fread(STDIN, 1);
echo "Bitte konvertiere die Umfragen manuell, dann drücke [Enter]!";
fread(STDIN, 1);
echo "Bitte konvertiere die Anhänge manuell, dann drücke [Enter]!";
fread(STDIN, 1);
echo "Bitte aktualisiere alle Statistiken (Admin-CP -> Tools & Verwaltung -> Neu zählen & aktualisieren; Cache Manager), dann drücke [Enter]!";
fread(STDIN, 1);
?>