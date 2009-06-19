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

$vars = array('type', 'check', 'api_key');

switch ( sizeof($request) ) {
case 2:
	$api_key = array_pop($request);
	$type = array_pop($request);
	
	if ( preg_match("/^[0-9a-f]{32}$/i", $api_key) && in_array($type, $types) )
		break;
	
default:
	status_header(400);
	die;
}

foreach ( $vars as $var ) {
	if ( !isset($$var) )
		$$var = isset($_POST[$var]) ? $_POST[$var] : '';
}

$to_check = array();

if ( is_array($check) ) {
	foreach ( $check as $file => $version ) {
		$slug = explode('/', $file);
		
		if ( count($slug) != 2 )
			continue;
		
		$to_check[$slug[0]] = $file;
	}
}


header('Content-Type: text/plain; Charset: UTF-8');

db::connect('mysql');

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
			$response[$to_check[$row->slug]] = (object) array(
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