<?php
# obsolete plugin
$active_plugins = get_option('active_plugins', array());

foreach ( $active_plugins as $key => $plugin ) {
	if ( $plugin == 'tag-cloud-widgets/tag-cloud-widgets.php' ) {
		unset($active_plugins[$key]);
		break;
	} elseif ( $plugin == 'tag-cloud-widgets/tag-cloud.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);
update_option('active_plugins', $active_plugins);

$sem_opt = get_option('tag_cloud_widgets');
if ( $sem_opt !== false ) {
	global $wpdb;
	
	$new_opt = array();
	foreach ( $sem_opt as $k => $opt ) {
		if ( !is_numeric($k) )
			continue;
		$new_opt[$k] = array(
			'title' => $opt['title'],
			);
	}
	update_option('widget_tag_cloud', $new_opt);
	
	$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
	#echo '<pre>'; var_dump($sidebars_widgets);die;
	foreach ( $sidebars_widgets as $sidebar => $widgets ) {
		if ( !is_array($widgets) ) continue;
		foreach ( $widgets as $k => $widget ) {
			if ( preg_match("/^tag_cloud_widget/", $widget) ) {
				$new_widget = str_replace("tag_cloud_widget", "tag_cloud", $widget);
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
	#echo '<pre>'; var_dump($sidebars_widgets);die;
	wp_set_sidebars_widgets($sidebars_widgets);
	delete_option('archive_widgets');
}
?>