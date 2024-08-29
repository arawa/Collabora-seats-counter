<?php
/* ----------------------------------------------------------------------------------------
* Usage:
* php cool_current_user_count.php https://my-collabora-instance.localnet username password
------------------------------------------------------------------------------------------- */

require __DIR__ . '/vendor/autoload.php';
define('ROOTPATH', __DIR__);

$database=(ROOTPATH."/cool_user_counter.sqlite");
$CollaboraWebURL = isset($argv[1]) ? $argv[1] : null;
$CollaboraAdminUser = isset($argv[2]) ? $argv[2] : null;
$CollaboraAdminPassword = isset($argv[3]) ? $argv[3] : null;

if (is_null($CollaboraWebURL) || is_null($CollaboraAdminPassword) || is_null($CollaboraAdminUser)) {
	echo "Error: missing conf";
	exit;
}

// Get the FQDN without protocol
$CollaboraFQDN = $url = preg_replace("(^https?://)", "", $CollaboraWebURL );

// First curl request to the admin page to get the jwt
$ch = curl_init('https://'.$CollaboraFQDN.'/cool/getMetrics/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_USERPWD, $CollaboraAdminUser.":".$CollaboraAdminPassword);
$result = curl_exec($ch);
preg_match_all('/jwt=(.*?); path/mi', $result, $matches);
$token = $matches[1][0];

// Then query the admin websocket with the jwt to get data about the current logged users
$client = new WebSocket\Client("wss://".$CollaboraFQDN."/cool/adminws", [
	"credentials"=> "include",
	'headers' => [
        "Accept"=> "*/*",
        "Accept-Language"=> "en-US,en;q=0.5",
        "Sec-WebSocket-Version"=> "13",
        "Sec-WebSocket-Extensions"=> "permessage-deflate",
        "Authorization"=> 'Basic ' . base64_encode($CollaboraAdminUser . ':' . $CollaboraAdminPassword),
        "Sec-Fetch-Dest"=> "empty",
        "Sec-Fetch-Mode"=> "websocket",
        "Sec-Fetch-Site"=> "same-origin",
        "Pragma"=> "no-cache",
        "Cache-Control"=> "no-cache"
	],
	'return_obj' => false,
]);

try {
	$client->text("auth jwt=".$token);
	$client->text("documents");
	$message = $client->receive();
	$documents = ($message);
} catch (\WebSocket\ConnectionException $e) {
	echo "Error: $e \n";
} finally {
	$client->close();
}

// Parsing websocket's response (removing the "documents " prefix and keep only the json object)
$prefix = 'documents ';

if (substr($documents, 0, strlen($prefix)) == $prefix) {
    $documents = substr($documents, strlen($prefix));
}

$documents=(json_decode($documents, true));

// Create the sqlite database if it doesn't exists yet
$db = new SQLite3($database, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
$db->query('CREATE TABLE IF NOT EXISTS "stats" (
    "userId" VARCHAR,
    "docKey" VARCHAR,
	"instance" VARCHAR,
    "timestamp" DATETIME,
	PRIMARY KEY (userId, docKey)
)');

// Loop through opened documents and through logged users.
$user_count = 0;
foreach ($documents["documents"] as $index => $document) {
	// echo "Document key : ". $document["docKey"];
	// echo "Time : ". date('Y-m-d H:i:s');
	// echo "User count : ". sizeof($document["views"]);
	$user_count+=sizeof($document["views"]);
	foreach($document["views"] as $user) {
		// Store in database
		$statement = $db->prepare('INSERT or IGNORE INTO "stats" ("userId", "docKey", "instance", "timestamp")
			VALUES (:uid, :dockey, :instance, :timestamp)');
		$statement->bindValue(':uid', $user["userId"]);
		$statement->bindValue(':dockey', $document["docKey"]);
		$statement->bindValue(':instance', $CollaboraFQDN);
		$statement->bindValue(':timestamp',  date('Y-m-d H:i:s'));
		$statement->execute();
	}
}

// Print out the current user count
echo $user_count;