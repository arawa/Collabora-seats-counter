<?php
/* -------------------------------------------------------------------
* Usage:
* php cool_unique_users.php https://my-collabora-instance.localnet
------------------------------------------------------------------- */


define('ROOTPATH', __DIR__);
$database=(ROOTPATH."/cool_user_counter.sqlite");
$CollaboraWebURL = isset($argv[1]) ? $argv[1] : null;

if (is_null($CollaboraWebURL)) {
	echo "Error: missing conf";
	exit;
}

// Get the FQDN without protocol
$CollaboraFQDN = $url = preg_replace("(^https?://)", "", $CollaboraWebURL );

// Query the amount of users that used Collabora in the last year
$db = new SQLite3($database, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$count = $db->querySingle("SELECT COUNT (DISTINCT userid)
			FROM stats
			WHERE instance='".$CollaboraFQDN."'
			AND timestamp >= Datetime('now', '-525960 minutes', 'localtime');
		");

//print the result
echo($count);
