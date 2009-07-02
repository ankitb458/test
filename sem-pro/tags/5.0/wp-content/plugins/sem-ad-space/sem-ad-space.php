<?php
# obsolete file

if ( get_option('sem_ad_space_params') ) :

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

	# import ads into wsa

	$contexts = (array) $wpdb->get_results("
		SELECT	*
		FROM	$wpdb->ad_blocks
		");

	foreach ( $contexts as $context )
	{
		$id = sanitize_title($context->ad_block_name);
		$rules = array(
			array(
				'condition' => 'any',
				'parameter' => '1',
				'display' => 'true',
				)
			);
		$adcode = $context->ad_block_code;
		$comment = $context->ad_block_description;

		$wp_ozh_wsa['contexts'][$id] = compact('rules', 'adcode', 'comment');
	}

	#dump($wp_ozh_wsa);
	update_option($wp_ozh_wsa['optionname'], $wp_ozh_wsa);

	$options = get_option('sem_ad_space_params');
	$default_ad = $options['default_ad_block_name'];

	foreach ( $contexts as $context )
	{
		if ( $default_ad = $context->ad_block_name )
		{
			$default_ad = $id;
			break;
		}
	}

	#dump($default_ad);

	function ad_spaces_export_inline_ad($input)
	{
		return '<!--wsa:' . sanitize_title($input[1]) . '-->';
	}


	# convert inline ads in posts

	$posts = (array) $wpdb->get_results("
		SELECT	posts.*
		FROM	$wpdb->posts as posts
		WHERE	posts.post_content REGEXP '<!--(ad[^>]+)-->'
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

		$wpdb->query("
			UPDATE	$wpdb->posts
			SET		post_content = '" . addslashes($post->post_content) . "'
			WHERE	ID = " . intval($post->ID)
			);
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
	sort($active_plugins);
	update_option('active_plugins', $active_plugins);
} # export_ad_spaces()

add_action('init', 'export_ad_spaces');
endif;
?>