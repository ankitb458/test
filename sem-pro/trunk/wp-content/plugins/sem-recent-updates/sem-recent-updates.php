<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'sem-recent-updates/sem-recent-updates.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('fuzzy-widgets/fuzzy-widgets.php', $active_plugins) ) {
	$active_plugins[] = 'fuzzy-widgets/fuzzy-widgets.php';
	include_once WP_PLUGIN_DIR . '/fuzzy-widgets/fuzzy-widgets.php';
	fuzzy_widget::activate();
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>