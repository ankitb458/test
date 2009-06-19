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

$vars = array('api_key', 'type', 'check', 'packages');

switch ( sizeof($request) ) {
case 2:
	$api_key = array_pop($request);
	$type = array_pop($request);
	
	if ( preg_match("/^[0-9a-f]{32}$/i", $api_key) && in_array($type, array('core', 'plugins', 'themes', 'skins')) )
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

$to_check = array();

if ( is_array($check) ) {
	foreach ( $check as $key => $version ) {
		if ( $type == 'core' ) {
			$slug = $key;
			if ( !in_array($slug, array('sem-pro')) )
				continue;
		} elseif ( $type == 'themes' ) {
			$slug = $key;
		} elseif ( $type == 'plugins' ) {
			$slug = explode('/', trim($key, '/'));
			if ( count($slug) != 2 )
				continue;
			$slug = current($slug);
		} else {
			continue;
		}
		
		$to_check[$slug] = (object) array(
			'key' => $key,
			'version' => $version,
			);
	}
}

header('Content-Type: text/plain; Charset: UTF-8');

if ( !$to_check ) {
	echo serialize(array());
	die;
}

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
if ( $expires === false ) {
	$expired = true;
} elseif ( is_null($expires) ) {
	$expired = false;
} else {
	$expired = time() > strtotime($expires);
}

db::connect('mysql');

if ( !$to_check ) {
	$dbs = db::query("
	SELECT	slug, url, stable_version, stable_package, bleeding_version, bleeding_package
	FROM	packages
	WHERE	type = :type
	ORDER BY slug
	", array(
		'type' => $type,
	));
	
	$response = '';
	while ( $row = $dbs->get_row() ) {
		if ( !$expired ) {
		$response .= <<<EOS
$row->slug: $row->stable_version
url: $row->url
package: $row->stable_package


EOS;
		} else {
			$response .= <<<EOS
$row->slug: $row->stable_version
url: $row->url


EOS;
		}
	}
} else {
	$dbs = db::query("
	SELECT	slug, url, stable_version, stable_package, bleeding_version, bleeding_package
	FROM	packages
	WHERE	type = :type
	AND		slug IN (" . ( implode(',', array_map(array('db', 'escape'), array_keys($to_check))) ) . ")
	ORDER BY slug
	", array(
		'type' => $type,
	));

	$response = array();
	while ( $row = $dbs->get_row() ) {
		if ( version_compare($to_check[$row->slug]->version, $row->{$packages . '_version'}, '<=') ) {
			if ( !$expired ) {
				$response[$to_check[$row->slug]->key] = (object) array(
					'slug' => $row->slug,
					'new_version' => $row->{$packages . '_version'},
					'url' => $row->url,
					'package' => $row->{$packages . '_package'},
					);
			} else {
				$response[$to_check[$row->slug]->key] = (object) array(
					'slug' => $row->slug,
					'new_version' => $row->{$packages . '_version'},
					'url' => $row->url,
					);
			}
		}
	}
	
	$response = serialize($response);
}

db::disconnect();
echo $response;

die;
?>