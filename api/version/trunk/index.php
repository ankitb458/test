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
			if ( $slug != 'sem-pro' )
				continue;
		} elseif ( $type == 'themes' ) {
			$slug = $key;
		} elseif ( $type == 'plugins' ) {
			$slug = explode('/', trim($key, '/'));
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
} elseif ( $type != 'themes' ) {
	$expired = time() > strtotime($expires);
}

db::connect('mysql');

if ( !$to_check ) {
	$dbs = db::query("
		SELECT	package as slug, url, stable_version, stable_package, bleeding_version, bleeding_package
		FROM	packages
		WHERE	type = :type
		ORDER BY package
		", array(
			'type' => $type,
		));
} else {
	$dbs = db::query("
		SELECT	package as slug, url, stable_version, stable_package, bleeding_version, bleeding_package
		FROM	packages
		WHERE	type = :type
		AND		package IN (" . ( implode(',', array_map(array('db', 'escape'), array_keys($to_check))) ) . ")
		ORDER BY package
		", array(
			'type' => $type,
		));
}

db::disconnect();

if ( $type != 'core' ) {
	$response = array();
	while ( $row = $dbs->get_row() ) {
		if ( empty($row->{$packages . '_version'}) || preg_match("|^http://downloads.wordpress.org|i", $row->{$packages . '_package'}) ) {
			continue;
		} elseif ( !$to_check ) {
			$response[$row->slug] = (object) array(
				'slug' => $row->slug,
				'new_version' => $row->{$packages . '_version'},
				'url' => $row->url,
				'package' => $row->{$packages . '_package'},
				);
		} elseif ( version_compare($to_check[$row->slug]->version, $row->{$packages . '_version'}, '<') ) {
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
} else {
	$response = new stdClass;
	$response->current = null;
	$response->package = null;
	
	if ( $row = $dbs->get_row() ) {
		if ( !empty($row->{$packages . '_version'}) ) {
			if ( isset($to_check[$row->slug]) && version_compare($to_check[$row->slug]->version, $row->{$packages . '_version'}, '<') ) {
				$response->response = 'upgrade';
				$response->url = $row->url;
				if ( !$expired )
					$response->package = $row->{$packages . '_package'};
				$response->current = $row->{$packages . '_version'};
				$response->locale = 'en_US';
			} elseif ( isset($to_check[$row->slug]) && version_compare($to_check[$row->slug]->version, $row->stable_version, '>') ) {
				$response->response = 'development';
				$response->url = $row->url;
				if ( !$expired )
					$response->package = $row->bleeding_package;
				$response->current = $row->bleeding_version;
				$response->locale = 'en_US';
			} else {
				$response->response = 'latest';
				$response->url = $row->url;
				if ( !$expired )
					$response->package = $row->{$packages . '_package'};
				$response->current = $row->{$packages . '_version'};
				$response->locale = 'en_US';
			}
		}
	}
}

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	echo serialize($response);
} elseif ( $type != 'core' ) {
	if ( !$expired) {
		foreach ( $response as $key => $package ) {
			echo $key . ',' . $package->new_version . ',' . $package->package . "\n";
		}
	} else {
		foreach ( $response as $key => $package ) {
			echo $key . ',' . $package->new_version . ',' . "\n";
		}
	}
} else {
	if ( !$expired ) {
		echo 'sem-pro,' . $response->current . ',' . $response->package . "\n";
	} else {
		echo 'sem-pro,' . $response->current . ',' . "\n";
	}
}
die;
?>