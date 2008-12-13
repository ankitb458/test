<?php
$wp_cache_config_file = ABSPATH . 'wp-content/wp-cache-config.php';
$wp_cache_link = ABSPATH . 'wp-content/advanced-cache.php';
$wp_config_file = ABSPATH . 'wp-config.php';

if ( is_writable($wp_config_file) )
{
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

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'wp-cache/wp-cache.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('sem-cache/sem-cache.php', $active_plugins) )
{
	$active_plugins[] = 'sem-cache/sem-cache.php';
	
	include_once ABSPATH . PLUGINDIR . '/sem-cache/sem-cache.php';
	do_action('activate_sem-cache/sem-cache.php');
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>