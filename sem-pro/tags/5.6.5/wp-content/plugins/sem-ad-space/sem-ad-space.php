<?php
# obsolete file
if ( get_option('sem_ad_space_params') ) :

$active_plugins = get_option('active_plugins');

if ( !class_exists('ad_manager') )
{
	include_once ABSPATH . PLUGINDIR . '/ad-manager/ad-manager.php';
	$active_plugins[] = 'ad-manager/ad-manager.php';
}

if ( !class_exists('inline_widgets') )
{
	include_once ABSPATH . PLUGINDIR . '/inline-widgets/inline-widgets.php';
	$active_plugins[] = 'inline-widgets/inline-widgets.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);


function export_ad_spaces()
{
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
	
	function ad_spaces_export_inline_ad($input)
	{
		static $widget_ids = array();
		
		global $wpdb;
		
		$ad_name = trim($input[1]);
		
		$ad_block = $wpdb->get_row("
			SELECT	*
			FROM	$wpdb->ad_blocks
			WHERE	ad_block_name = '" . $wpdb->escape($ad_name) . "'
			");
			
		if ( !$ad_block ) return '';
		
		if ( isset($widget_ids[$ad_name]) )
		{
			$widget_id = $widget_ids[$ad_name];
		}
		else
		{
			$widget_id = ad_manager::new_widget(array(
				'title' => trim($ad_block->ad_block_name),
				'code' => $ad_block->ad_block_code,
				));
			
			$widget_ids[$ad_name] = $widget_id;

			$sidebars_widgets = get_option('sidebars_widgets');
			$sidebars_widgets['inline_widgets'][] = $widget_id;
			update_option('sidebars_widgets', $sidebars_widgets);
		}
			
		return '[widget:' . $widget_id . ']';
	}


	# convert inline ads in posts

	$default_ad = $options['default_ad_block_name'];
	
	$posts = (array) $wpdb->get_results("
		SELECT	posts.*
		FROM	$wpdb->posts as posts
		WHERE	posts.post_content REGEXP '<!--ad[^>]+-->'
		");
	
	foreach ( $posts as $post )
	{
		#dump(htmlspecialchars($post->post_content));

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

		#dump(htmlspecialchars($post->post_content));
		#dump(get_option('sidebars_widgets'));

		$wpdb->query("
			UPDATE	$wpdb->posts
			SET		post_content = '" . addslashes($post->post_content) . "'
			WHERE	ID = " . intval($post->ID)
			);
	}
	
	
	# migrate location-specific ads
	
	foreach ( $options['default_ad_block'] as $area => $ad_block_id )
	{
		if ( $area == 'top' ) continue;
		if ( $ad_block_id <= 0 ) continue;

		$ad_block = $wpdb->get_row("
			SELECT	*
			FROM	$wpdb->ad_blocks
			WHERE	ad_block_id = " . intval($ad_block_id) . "
			");

		if ( !$ad_block ) return '';

		$widget_id = ad_manager::new_widget(array(
			'title' => trim($ad_block->ad_block_name),
			'code' => $ad_block->ad_block_code,
			));
		
		$sidebars_widgets = get_option('sidebars_widgets');

		switch ( $area )
		{
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
			$sidebars_widgets['the_footer'][] = $widget_id;
			break;
		case 'sidebar':
			if ( isset($sidebars_widgets['sidebar-1']) )
			{
				$sidebars_widgets['sidebar-1'][] = $widget_id;
			}
			elseif ( isset($sidebars_widgets['sidebar-ext']) )
			{
				$sidebars_widgets['sidebar-ext'][] = $widget_id;
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
		) as $table )
	{
		$wpdb->query("DROP TABLE IF EXISTS `" . $wpdb->{$table} . "`");
	}

	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_ad_distribution'");

	delete_option('sem_ad_space_params');


	# uninstall plugin

	$active_plugins = get_option('active_plugins');
	$key = array_search('sem-ad-space/sem-ad-space.php', $active_plugins);
	unset($active_plugins[$key]);
	sort($active_plugins);
	update_option('active_plugins', $active_plugins);
} # export_ad_spaces()

add_action('init', 'export_ad_spaces', 20);

else :

# uninstall plugin

$active_plugins = get_option('active_plugins');
$key = array_search('sem-ad-space/sem-ad-space.php', $active_plugins);
unset($active_plugins[$key]);
sort($active_plugins);
update_option('active_plugins', $active_plugins);

endif;
?>