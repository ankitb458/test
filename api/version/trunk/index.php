<?php
define('path', dirname(__FILE__));

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

$vars = array('type', 'check', 'api_key', 'site_url', 'site_ip', 'php_version', 'mysql_version');

switch ( sizeof($request) ) {
case 2:
	$api_key = array_pop($request);
case 1:
	$type = array_pop($request);
	
	if ( ( !isset($api_key) || isset($api_key) && preg_match("/^[0-9a-f]{32}$/i", $api_key) )
		&& in_array($type, $types) )
		break;
	
default:
	status_header(400);
	die;
}

foreach ( $vars as $var ) {
	if ( !isset($$var) )
		$$var = isset($_POST[$var]) ? $_POST[$var] : '';
}

$check = array(
	'ad-manager/ad-manager.php' => '2.0 RC',
	'version-checker/version-checker.php' => '2.0 RC',
	);

$to_check = array();

if ( is_array($check) ) {
	foreach ( $check as $file => $version ) {
		$slug = explode('/', $file);
		
		if ( count($slug) != 2 ) {
			status_header(400);
			die;
		}
		
		$to_check[$slug[0]] = $file;
	}
}

$site_ip = $_SERVER['REMOTE_ADDR'];

if ( isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^WordPress(.*);(.*)$/", $_SERVER['HTTP_USER_AGENT'], $match) ) {
	$wp_version = $match[1];
	$site_url = $match[2];
} else {
	$wp_version = '';
	$site_url = '';
}

if ( in_array($site_ip, array('::1', '127.0.0.1')) )
	$site_ip = 'localhost';

if ( $site_ip != 'localhost' ) {
	$site_ip = filter_var($site_ip, FILTER_VALIDATE_IP);
	$site_url = filter_var($site_url, FILTER_VALIDATE_URL);
	
	foreach ( array('wp_version', 'php_version', 'mysql_version') as $var ) {
		if ( !preg_match("/^\d*\.\d+(?:\.\d+)(?: [a-z0-9]+)?$/i", $$var) ) {
			$$var = '';
		}
	}
	
	foreach ( $vars as $var ) {
		if ( !$$var ) {
			status_header(400);
			die;
		}
	}
}

header('Content-Type: text/plain; Charset: UTF-8');

db::connect();

db::query("
INSERT INTO api_logs (
	log_date,
	api_key,
	site_ip,
	site_url,
	wp_version,
	php_version,
	mysql_version
	)
VALUES (
	NOW(),
	:api_key,
	:site_ip,
	:site_url,
	:wp_version,
	:php_version,
	:mysql_version
	);
", compact(
	'api_date',
	'api_key',
	'site_ip',
	'site_url',
	'wp_version',
	'php_version',
	'mysql_version'
));

#var_dump($to_check);
if ( !$to_check ) {
	$dbs = db::query("
	SELECT	slug, version, url, package
	FROM	versions
	WHERE	type = :type
	ORDER BY slug
	", array(
		'type' => $type,
	));
	
	$response = '';
	while ( $row = $dbs->get_row() ) {
		$response .= <<<EOS
$row->slug: $row->version
url: $row->url
package: $row->package


EOS;
	}
} else {
	$dbs = db::query("
	SELECT	slug, version, url, package
	FROM	versions
	WHERE	type = :type
	AND		slug IN (" . ( implode(',', array_map(array('db', 'escape'), array_keys($to_check))) ) . ")
	ORDER BY slug
	", array(
		'type' => $type,
	));

	$response = array();
	while ( $row = $dbs->get_row() ) {
		if ( version_compare($row->version, $to_check[$row->slug], '>') ) {
			$response[$to_check[$row->slug]] = array(
				'slug' => $row->slug,
				'new_version' => $row->version,
				'url' => $row->url,
				'package' => $row->package,
				);
		}
	}
	
	$response = serialize($response);
}

db::disconnect();
echo $response;

die;
?>