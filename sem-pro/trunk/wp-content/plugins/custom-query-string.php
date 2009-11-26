<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'custom-query-string.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('custom-query-string/custom-query-string.php', $active_plugins) ) {
	$new_plugin = 'custom-query-string/custom-query-string.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>