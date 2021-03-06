<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'sem-google-analytics/sem-google-analytics.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('google-analytics/google-analytics.php', $active_plugins) ) {
	$new_plugin = 'google-analytics/google-analytics.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>