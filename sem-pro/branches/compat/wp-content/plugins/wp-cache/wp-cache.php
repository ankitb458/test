<?php
$wp_cache_config_file = WP_CONTENT_DIR . '/wp-cache-config.php';
$wp_cache_link = WP_CONTENT_DIR . '/advanced-cache.php';
$wp_config_file = ABSPATH . 'wp-config.php';

if ( is_writable($wp_config_file) ) {
	$lines = file($wp_config_file);
	foreach($lines as $line) {
	 	if ( preg_match("/^ *define *\( *\'WP_CACHE\' *, *true *\) *;/", $line)) {
			$found = true;
			break;
		}
	}
	if ($found) {
		$fd = fopen($wp_config_file, 'w');
		foreach($lines as $line) {
			if ( !preg_match("/^ *define *\( *\'WP_CACHE\' *, *true *\) *;/", $line))
				fputs($fd, $line);
		}
		fclose($fd);
	}
}

@unlink($wp_cache_config_file);
@unlink($wp_cache_link);

$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'wp-cache/wp-cache.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>