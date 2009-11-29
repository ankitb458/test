<?php
# obsolete plugin
$active_plugins = get_option('active_plugins', array());

foreach ( $active_plugins as $key => $plugin ) {
	if ( $plugin == 'link-widgets/link-widgets.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);
update_option('active_plugins', $active_plugins);

$sem_opt = get_option('link_widgets');
if ( $sem_opt !== false ) {
	global $wpdb;
	
	foreach ( $sem_opt as $k => $opt ) {
		if ( !is_numeric($k) )
			continue;
		if ( isset($opt['filter']) )
			$sem_opt[$k]['category'] = $opt['filter'];
	}
	update_option('widget_links', $sem_opt);
	
	$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
	
	foreach ( $sidebars_widgets as $sidebar => $widgets ) {
		if ( !is_array($widgets) ) continue;
		foreach ( $widgets as $k => $widget ) {
			if ( preg_match("/^link_widget/", $widget) ) {
				$new_widget = str_replace("link_widget", "links", $widget);
				$sidebars_widgets[$sidebar][$k] = $new_widget;
				if ( $sidebar == 'inline_widgets' ) {
					$wpdb->query("
						UPDATE	$wpdb->posts
						SET		post_content = replace(post_content, '[widget:" . $wpdb->escape($widget) . "]', '[widget id=\"" . $wpdb->escape($new_widget) . "\"/]')
						WHERE	post_content LIKE '%[widget:" . $wpdb->escape($widget) . "]%'
						");
				}
			}
		}
	}
	
	wp_set_sidebars_widgets($sidebars_widgets);
	delete_option('link_widgets');
}
?>