<?php
# obsolete file
if ( !get_option('sem_ad_space_params') ) :

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'sem-ad-space/sem-ad-space.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);

elseif ( !defined('DOING_CRON') ) :

$active_plugins = get_option('active_plugins');

if ( !in_array('ad-manager/ad-manager.php', $active_plugins) ) {
	$new_plugin = 'ad-manager/ad-manager.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

if ( !in_array('inline-widgets/inline-widgets.php', $active_plugins) ) {
	$new_plugin = 'inline-widgets/inline-widgets.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);

function export_ad_spaces() {
	if ( !get_option('sem_ad_space_params') )
		return;
	
	$ignore_user_abort = ignore_user_abort(true);
	set_time_limit(600);
	
	global $wpdb;
	global $table_prefix;
	global $wp_ozh_wsa;

	$wpdb->ad_tags = $table_prefix . "ad_tags";
	$wpdb->ad_blocks = $table_prefix . "ad_blocks";
	$wpdb->ad_block2tag = $table_prefix . "ad_block2tag";
	$wpdb->ad_distributions = $table_prefix . "ad_distributions";
	$wpdb->ad_distribution2tag = $table_prefix . "ad_distribution2tag";
	$wpdb->ad_distribution2post = $table_prefix . "ad_distribution2post";

	$options = get_option('sem_ad_space_params');
	delete_option('sem_ad_space_params');
	
	# convert inline ads in posts

	$default_ad = $options['default_ad_block_name'];
	
	$posts = (array) $wpdb->get_results("
		SELECT	posts.*
		FROM	$wpdb->posts as posts
		WHERE	posts.post_content REGEXP '<!--ad[^>]+-->'
		");
	
	foreach ( $posts as $post ) {
		#dump(esc_html($post->post_content));

		$post->post_content = preg_replace("/
			<!--
			(?:
				ad
				(?:_)?
				(?:
					unit
				|
					block
				|
					space
				|
					sense
				)
			)
			-->
			/isUx",
			"<!--adunit#$default_ad-->",
			$post->post_content
			);

		$post->post_content = preg_replace_callback("/
			<!--
			(?:
				ad
				(?:_)?
				(?:
					unit
				|
					block
				|
					space
				|
					sense
				)
				\#
			)
			(.+)
			-->
			/isUx",
			'ad_spaces_export_inline_ad',
			$post->post_content
			);

		#dump(esc_html($post->post_content));
		#dump(get_option('sidebars_widgets'));

		$wpdb->query("
			UPDATE	$wpdb->posts
			SET		post_content = '" . addslashes($post->post_content) . "'
			WHERE	ID = " . intval($post->ID)
			);
	}
	
	
	# migrate location-specific ads
	foreach ( $options['default_ad_block'] as $area => $ad_block_id ) {
		if ( $area == 'top' ) continue;
		if ( $ad_block_id <= 0 ) continue;

		$ad_block = $wpdb->get_row("
			SELECT	*
			FROM	$wpdb->ad_blocks
			WHERE	ad_block_id = " . intval($ad_block_id) . "
			");

		if ( !$ad_block )
			return '';

		if ( strpos($ad_block->ad_block_code, 'src=\\"') !== false ) {
			$ad_block->ad_block_code = stripslashes($ad_block->ad_block_code);
		}
		
		$ops = array(
			'title' => trim($ad_block->ad_block_name),
			'code' => $ad_block->ad_block_code,
			);
		
		$ad_widgets = get_option('widget_ad_unit', array());
		unset($ad_widgets['_multiwidget']);
		if ( !$ad_widgets )
			$widget_id = 2;
		else
			$widget_id = max(array_keys($ad_widgets)) + 1;
		$ad_widgets[$widget_id] = $ops;
		update_option('widget_ad_unit', $ad_widgets);
		
		$widget_id = "ad_unit-$widget_id";
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			global $_wp_sidebars_widgets;
			if ( !$_wp_sidebars_widgets )
				$_wp_sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets =& $_wp_sidebars_widgets;
		}
		
		switch ( $area ) {
		case 'header':
			$sidebars_widgets['the_header'][] = $widget_id;
			break;
		case 'above':
			$sidebars_widgets['above_the_entries'][] = $widget_id;
			break;
		case 'title':
			$sidebars_widgets['the_entry'] = (array) $sidebars_widgets['the_entry'];
			array_unshift($sidebars_widgets['the_entry'], $widget_id);
			break;
		case 'below':
			$sidebars_widgets['the_entry'][] = $widget_id;
			break;
		case 'footer':
			array_unshift($sidebars_widgets['the_footer'], $widget_id);
			break;
		case 'sidebar':
			if ( isset($sidebars_widgets['sidebar-1']) ) {
				$sidebars_widgets['sidebar-1'][] = $widget_id;
			} elseif ( isset($sidebars_widgets['sidebar-2']) ) {
				$sidebars_widgets['sidebar-2'][] = $widget_id;
			} elseif ( isset($sidebars_widgets['ext_sidebar']) ) {
				$sidebars_widgets['ext_sidebar'][] = $widget_id;
			}
		}
		
		update_option('sidebars_widgets', $sidebars_widgets);
	}
	
	# drop obsolete option and tables

	foreach ( array(
		'ad_tags',
		'ad_blocks',
		'ad_block2tag',
		'ad_distributions',
		'ad_distribution2tag',
		'ad_distribution2post',
		) as $table ) {
		$wpdb->query("DROP TABLE IF EXISTS `" . $wpdb->{$table} . "`");
	}

	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_ad_distribution'");

	global $wp_widget_factory;
	if ( method_exists($wp_widget_factory->widgets['ad_manager'], '_register') )
		$wp_widget_factory->widgets['ad_manager']->_register();
	
	ignore_user_abort($ignore_user_abort);
} # export_ad_spaces()

function ad_spaces_export_inline_ad($input) {
	static $widget_ids = array();
	
	global $wpdb;
	
	$ad_name = trim($input[1]);
	
	$ad_block = $wpdb->get_row("
		SELECT	*
		FROM	$wpdb->ad_blocks
		WHERE	ad_block_name = '" . $wpdb->escape($ad_name) . "'
		");
		
	if ( !$ad_block )
		return '';
	
	if ( isset($widget_ids[$ad_name]) ) {
		$widget_id = $widget_ids[$ad_name];
	} else {
		if ( strpos($ad_block->ad_block_code, 'src=\\"') !== false ) {
			$ad_block->ad_block_code = stripslashes($ad_block->ad_block_code);
		}
		
		$ops = array(
			'title' => trim($ad_block->ad_block_name),
			'code' => $ad_block->ad_block_code,
			);
		
		$ad_widgets = get_option('widget_ad_unit', array());
		unset($ad_widgets['_multiwidget']);
		if ( !$ad_widgets )
			$widget_id = 2;
		else
			$widget_id = max(array_keys($ad_widgets)) + 1;
		$ad_widgets[$widget_id] = $ops;
		update_option('widget_ad_unit', $ad_widgets);
		
		$widget_id = "ad_unit-$widget_id";
		
		$widget_ids[$ad_name] = $widget_id;
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			global $_wp_sidebars_widgets;
			if ( !$_wp_sidebars_widgets )
				$_wp_sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets =& $_wp_sidebars_widgets;
		}
		$sidebars_widgets['inline_widgets'][] = $widget_id;
		update_option('sidebars_widgets', $sidebars_widgets);
	}
		
	return '[widget id="' . $widget_id . '"/]';
}

add_action('init', 'export_ad_spaces', 3000);

endif;
?>