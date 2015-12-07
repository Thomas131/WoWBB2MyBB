WoWBB to MyBB Converter:
========================
This converter is a Command-Line PHP-Tool to convert a WoWBB-Forum to MyBB. It was just programmed for one-time-use and uploaded for portfolios-sake and so some values are hardcoded. In addition, the following things were neglected:
* Speed
* unused or less-used forum-functions like Attachments, Avatars

Used:
-----
* Encoding.php from https://github.com/neitanod/forceutf8 (Revised BSD License)
* functions.php of MyBB 1.8.4 (LGPL License)
* PDO (included in PHP; extended by me)
* Modified version of 2 functions of PHPBB3-DBAL

My Goals while programming were:
--------------------------------
* possibility to die everytime (for example when having unexpected values) and continue converting when restarting
* instant die() when having unwanted values; strict checks
* strict class-seperation (except the included mybb-functions)
