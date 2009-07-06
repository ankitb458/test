<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'sem-recent-posts/sem-recent-posts.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

$sidebars_widgets = get_option('sidebars_widgets', array());
$changed = false;

foreach ( $sidebars_widgets as $sidebar => $widgets ) {
	if ( !is_array($widgets) )
		continue;
	
	$key = array_search('Fuzzy Posts', $widgets);
	
	if ( $key === false )
		$key = array_search('fuzzy-posts', $widgets);
	
	if ( $key !== false ) {
		$changed = true;
		$sidebars_widgets[$sidebar][$key] = 'fuzzy_widget-2';
		$ops = get_settings('sem_recent_posts_params');
		$ops = !empty($ops['title'])
			? array(2 => array(
				'title' => $ops['title'],
				))
			: array(2 => array(
				));
		update_option('widget_fuzzy_widget', $ops);
	}
}

if ( $changed ) {
	update_option('sidebars_widgets', $sidebars_widgets);
}

if ( !in_array('fuzzy-widgets/fuzzy-widgets.php', $active_plugins) ) {
	$active_plugins[] = 'fuzzy-widgets/fuzzy-widgets.php';
	include_once WP_PLUGIN_DIR . '/fuzzy-widgets/fuzzy-widgets.php';
	fuzzy_widget::activate();
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>