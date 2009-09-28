<?php
# Parse Request
define('mc_path', dirname(__FILE__));

include mc_path . '/config.php';
include mc_path . '/inc/utils.php';

# parse request
$request = str_replace(base_uri, '', $_SERVER['REQUEST_URI']);

if ( !$request ) {
	status_header(400);
	die;
}

$request = preg_replace("/\?.*/", '', $request);
$request = rtrim($request, '/');
$request = explode('/', $request);

switch ( sizeof($request) ) {
case 1:
	$api_key = array_pop($request);
	
	if ( preg_match("/^[0-9a-f]{32}$/i", $api_key) )
		break;
	
default:
	status_header(400);
	die;
}

$_GET = array();
$_POST = array();
$_REQUEST = array();


# Start MesoCMS
include_once dirname(__FILE__) . '/mc.php';


# Execute
$success = active_user::login(
	new user("
	SELECT	*
	FROM	users
	WHERE	user_key = check_banned(:user_key)
	", array('user_key' => $api_key)));

if ( !$success ) {
	status_header(400);
	die;
}

$cookies[session_name()] = session_id();

status_header(200);
header('Content-Type: text/plain; Charset: UTF-8');
if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	echo serialize($cookies);
} else {
	foreach ( $cookies as $key => $val ) {
		echo $key . '=' . $val . "\n";
	}
}
die;
?>