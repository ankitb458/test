<?php
header('Content-Type: text/xml; Charset: UTF-8');

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";

# check user_key
if ( !isset($_REQUEST['user_key']) || !preg_match("/[0-9a-f]{32}/", $_REQUEST['user_key']) ) {
	die('<error>Invalid Request</error>');
}

$user_key = $_REQUEST['user_key'];

# connect
try {
	$dbh = new PDO('pgsql:dbname=semiologic;host=127.0.0.1', 'semiologic', 'apjpfrjh');
} catch ( PDOException $e ) {
	die('<error>Failed to connect</error>');
}

$dbs = $dbh->prepare("
	SELECT	user_id
	FROM	users
	WHERE	user_key = :user_key
	", array(PDO::ATTR_EMULATE_PREPARES => true));

$dbs->execute(array('user_key' => $user_key));

$row = $dbs->fetch(PDO::FETCH_ASSOC);

if ( !$row ) {
	$dbh = null;
	die('<error>Invalid User</error>');
}
else {
	$user_id = $row['user_id'];
}

$profile_key = isset($_REQUEST['profile_key']) ? $_REQUEST['profile_key'] : null;

if ( $profile_key ) :

$dbs = $dbh->prepare("
SELECT  membership_expires
FROM    memberships
WHERE   user_id = :user_id
AND	profile_key = :profile_key 
", array(PDO::ATTR_EMULATE_PREPARES => true));

$dbs->execute(array('user_id' => $user_id, 'profile_key' => $profile_key));

$row = $dbs->fetch(PDO::FETCH_ASSOC);

if ( !$row ) {
        $dbh = null;
	die('<error>Invalid Membership</error>');
}
else {
	$expires = $row['membership_expires'];
	if ( $expires ) {
		$expires = date('Y-m-d', strtotime($expires));
	}
        $dbh = null;
	die('<expires>' . $expires . '</expires>');
}

else :

$dbs = $dbh->prepare("
	SELECT	profile_name,
		profile_key,
		membership_expires
	FROM	memberships
	WHERE	user_id = :user_id
	", array(PDO::ATTR_EMULATE_PREPARES => true));

$dbs->execute(array('user_id' => $user_id));

echo '<memberships>' . "\n";

while ( $row = $dbs->fetch(PDO::FETCH_ASSOC) ) {
	$name = $row['profile_name'];
	$key = $row['profile_key'];
	$expires = $row['membership_expires'];

	$name = htmlentities($name, ENT_COMPAT, 'UTF-8');
	$key = htmlentities($key, ENT_COMPAT, 'UTF-8');

	if ( !$expires ) {
		$expires = '';
	}
	else {
		$expires = date('Y-m-d', strtotime($expires));
	}

	echo '<membership>' . "\n";

	echo '<name>' . $name . '</name>' . "\n";

	echo '<key>' . $key . '</key>' . "\n";

	echo '<expires>' . $expires . '</expires>' . "\n";

	echo '</membership>' . "\n";
}

echo '</memberships>' . "\n";

$dbh = null;
die;

endif;
?>
