<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'wp-db-backup.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('wp-db-backup/wp-db-backup.php', $active_plugins) ) {
	$new_plugin = 'wp-db-backup/wp-db-backup.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>