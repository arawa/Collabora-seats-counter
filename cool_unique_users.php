<?php
/* -------------------------------------------------------------------
* Usage:
* php cool_unique_users.php https://my-collabora-instance.localnet [ My-whopi-host.localnet ]
------------------------------------------------------------------- */


define('ROOTPATH', __DIR__);

$configFile = ROOTPATH . '/config.php';
if (file_exists($configFile)) {
	include($configFile);
}

$CollaboraWebURL = isset($argv[1]) ? $argv[1] : null;
$WopiHost = isset($argv[2]) ? $argv[2] : null;

if (is_null($CollaboraWebURL)) {
	echo "Error: missing arguments (Collabora web path)";
	exit;
}
if (is_null($WopiHost)) {
	$WopiHostCondition = " ";
} else {
	$WopiHostCondition = " AND source ='".$WopiHost."'";
}

// Get the FQDN without protocol
$CollaboraFQDN = $url = preg_replace("(^https?://)", "", $CollaboraWebURL );

// Query the amount of users (non-guests) that used Collabora in the last year
// We also skip "LocalUsers" that might have been saved in the past
$db = new SQLite3(ROOTPATH."/".$CONFIG['database'], SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$count = $db->querySingle("SELECT COUNT (DISTINCT userid)
			FROM stats
			WHERE instance='".$CollaboraFQDN."'
			".$WopiHostCondition."
			AND timestamp >= Datetime('now', '-525960 minutes', 'localtime')
			AND userid not like 'Guest-%'
			AND userid not like 'LocalUser%';
		");
		// Todo : use the guest column instead of 'Guest-%'

//print the result
echo($count);
