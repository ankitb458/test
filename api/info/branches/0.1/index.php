<?php
define('path', dirname(__FILE__));

if ( function_exists('date_default_timezone_set') )
	date_default_timezone_set('UTC');

include path . '/config.php';
include path . '/inc/utils.php';
include path . '/inc/db.php';

# parse request
$request = str_replace(base_uri, '', $_SERVER['REQUEST_URI']);

if ( !$request ) {
	status_header(400);
	die;
}

$request = preg_replace("/\?.*/", '', $request);
$request = rtrim($request, '/');
$request = explode('/', $request);

$vars = array('api_key', 'type', 'slug', 'action', 'packages', 'wp_version');

switch ( sizeof($request) ) {
case 2:
	$api_key = array_pop($request);
	$type = array_pop($request);
	
	if ( preg_match("/^[0-9a-f]{32}$/i", $api_key) && in_array($type, array('plugins', 'themes', 'skins')) )
		break;
	
default:
	status_header(400);
	die;
}

foreach ( $vars as $var ) {
	if ( !isset($$var) )
		$$var = isset($_POST[$var]) ? $_POST[$var] : '';
}

if ( !isset($packages) || !in_array($packages, array('stable', 'bleeding')) )
	$packages = 'stable';

if ( !isset($action) || !in_array($action, array('info', 'query')) )
	$action = 'query';

if ( $action == 'info' && !$slug )
	$action = 'query';

if ( isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^WordPress\/(.*); (.*)$/", $_SERVER['HTTP_USER_AGENT'], $match) ) {
	$wp_version = $match[1];
}

if ( !preg_match("/^\d*\.\d+(?:\.\d+)(?: [a-z0-9]+)?$/i", $wp_version) )
	$wp_version = '2.8';

$wp_version = preg_replace("/( |-).*$/", '', $wp_version);
$wp_version2 = explode('.', $wp_version);
$wp_version2 = array_slice($wp_version2, 0, 2);
$wp_version2 = implode('.', $wp_version2);

header('Content-Type: text/plain; Charset: UTF-8');

db::connect('pgsql');

$expires = db::get_var("
	SELECT	membership_expires
	FROM	memberships
	JOIN	users
	ON		users.user_id = memberships.user_id
	WHERE	user_key = :user_key
	AND		profile_key = 'sem_pro'
	", array('user_key' => $api_key));

db::disconnect();

$expired = false;
if ( $type != 'themes' ) {
	if ( $expires === false ) {
		$expired = true;
	} elseif ( is_null($expires) ) {
		$expired = false;
	} else {
		$expired = time() > strtotime($expires);
	}
}

db::connect('mysql');

if ( !$slug ) {
	$dbs = db::query("
		SELECT	packages.*
		FROM	packages
		WHERE	type = :type
		AND		${packages}_requires <= :wp_version
		AND		${packages}_compat >= :wp_version2
		ORDER BY package
		", array(
			'type' => $type,
			'wp_version' => $wp_version,
			'wp_version2' => $wp_version2,
		));
} else {
	$dbs = db::query("
		SELECT	packages.*
		FROM	packages
		WHERE	type = :type
		AND		package = :slug
		AND		${packages}_requires <= :wp_version
		AND		${packages}_compat >= :wp_version2
		ORDER BY package
		", array(
			'type' => $type,
			'slug' => $slug,
			'wp_version' => $wp_version,
			'wp_version2' => $wp_version2,
		));
}

db::disconnect();

$response = array();
while ( $row = $dbs->get_row() ) {
	if ( empty($row->{$packages . '_version'}) ) {
		continue;
	}
	if ( $type == 'themes' && $row->package != 'sem-reloaded' )
		continue;
	$response[$row->package] = (object) array(
		'name' => '',
		'slug' => $row->package,
		'version' => $row->{$packages . '_version'},
		'homepage' => $row->url,
		'requires' => $row->{$packages . '_requires'},
		'tested' => $row->{$packages . '_compat'},
		'compatibility' => array(),
		'rating' => 100,
		'num_ratings' => 0,
		'last_updated' => $row->{$packages . '_modified'},
		'download_link' => !$expired || preg_match("|^http://downloads.wordpress.org|i", $row->{$packages . '_package'}) ? $row->{$packages . '_package'} : '',
		'readme' => $row->{$packages . '_readme'},
		);
	if ( $type == 'themes' ) {
		$response[$row->package]->preview_url = 'http://www.semiologic.com';
		$response[$row->package]->screenshot_url = 'http://skins.semiologic.com/wp-content/themes/sem-reloaded/screenshot.png';
	}
}

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	echo serialize($slug ? current($response) : $response);
} else {
	if ( !$expired) {
		foreach ( $response as $key => $package ) {
			echo $key . ',' . $package->version . ',' . $package->download_link . "\n";
		}
	} else {
		foreach ( $response as $key => $package ) {
			echo $key . ',' . $package->version . ',' . "\n";
		}
	}
}
die;
?>