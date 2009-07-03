<?php
/*
Plugin Name: Ad Spaces
Plugin URI: http://www.semiologic.com/software/ad-space/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/ad-space/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Lets you easily manage advertisement real estate on your blog
Author: Denis de Bernardy
Version: 3.15
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('sem-ad-space');


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


#
# sem_ad_spaces_init()
#

function sem_ad_spaces_init()
{
	global $wpdb;
	global $table_prefix;
	global $wpmuBaseTablePrefix;
	global $sem_ad_block2tag;

	$options = function_exists('get_site_option')
		? get_site_option('sem_ad_space_params')
		: get_settings('sem_ad_space_params');

	# Step 1: Initialize DB

	$wpdb->ad_tags = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_tags";
	$wpdb->ad_blocks = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_blocks";
	$wpdb->ad_block2tag = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_block2tag";
	$wpdb->ad_distributions = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_distributions";
	$wpdb->ad_distribution2tag = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_distribution2tag";
	$wpdb->ad_distribution2post = ( isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : $table_prefix ) . "ad_distribution2post";

	# Step 2: Identify the ad distribution used

	if ( ( is_single()
			|| is_page()
			|| ( class_exists('sem_static_front') && sem_static_front::is_home() )
			)
		&& ( sizeof($GLOBALS['wp_query']->posts) == 1 )
		)
	{
		$ad_distribution_id = $wpdb->get_var("
			SELECT
				ad_distribution2post.ad_distribution_id
			FROM
				$wpdb->ad_distribution2post as ad_distribution2post
			WHERE
				ad_distribution2post.post_id = " . intval($GLOBALS['wp_query']->posts[0]->ID)
			);
	}

	if ( !isset($ad_distribution_id) )
	{
		if ( is_home() )
		{
			$ad_distribution_id = $options['default_ad_distribution']['home'];
		}
		elseif ( is_single() )
		{
			$ad_distribution_id = $options['default_ad_distribution']['post'];
		}
		elseif ( is_page() )
		{
			$ad_distribution_id = $options['default_ad_distribution']['page'];
		}
		elseif ( !is_feed() )
		{
			$ad_distribution_id = $options['default_ad_distribution']['misc'];
		}
		else
		{
			$ad_distribution_id = false;
		}
	}

	# -1 stands for a random distribution, 0 stands for none

	if ( $ad_distribution_id == -1 )
	{
		$ad_distribution_id = (int) $wpdb->get_var("
			SELECT
				ad_distribution_id
			FROM
				$wpdb->ad_distributions
			ORDER BY RAND()
			LIMIT 1
				");
	}

	#echo '<pre>';
	#var_dump($ad_distribution_id);
	#echo '</pre>';

	# Step 3: Fetch the relevant Ad names and initialize $sem_ad_blocks2tag

	$sem_ad_block2tag = array();

	if ( $ad_distribution_id )
	{
		$res = (array) $wpdb->get_results("
			SELECT	ad_distribution2tag.ad_tag_id,
					ad_distribution2tag.ad_block_id,
					ad_block.ad_block_name
			FROM	$wpdb->ad_distribution2tag as ad_distribution2tag
			LEFT JOIN $wpdb->ad_blocks as ad_block
				ON ad_block.ad_block_id = ad_distribution2tag.ad_block_id
			WHERE	ad_distribution2tag.ad_distribution_id = " . intval($ad_distribution_id)
			);

		#echo '<pre>';
		#var_dump($res);
		#echo '</pre>';

		foreach ( array_keys($res) as $key )
		{
			# -1 stands for a random ad block, 0 stands for no ad block

			if ( $res[$key]->ad_block_id == -1 )
			{
				$res[$key]->ad_block_name = (string) $wpdb->get_var("
					SELECT	ad_block_name
					FROM	$wpdb->ad_blocks as ad_block
					INNER JOIN $wpdb->ad_block2tag as ad_block2tag
						ON ad_block2tag.ad_block_id = ad_block.ad_block_id
					WHERE	ad_tag_id = '" . mysql_real_escape_string($res[$key]->ad_tag_id) . "'
					ORDER BY RAND()
					LIMIT 1
					");
			}

			if ( $res[$key]->ad_block_id == 0 )
			{
				$sem_ad_block2tag[$res[$key]->ad_tag_id] = false;
			}
			else
			{
				$sem_ad_block2tag[$res[$key]->ad_tag_id] = $res[$key]->ad_block_name;
			}
		}
	}

	#echo '<pre>';
	#var_dump($sem_ad_block2tag);
	#echo '</pre>';

	# Step 4: Complete with default ad blocks

	if ( $ad_distribution_id )
	{
		foreach ( $options['default_ad_block'] as $ad_tag => $ad_block_id )
		{
			if ( !isset($sem_ad_block2tag[$ad_tag]) )
			{
				if ( $ad_block_id == -1 )
				{
					$sem_ad_block2tag[$ad_tag] = (string) $wpdb->get_var("
						SELECT	ad_block_name
						FROM	$wpdb->ad_blocks as ad_block
						INNER JOIN $wpdb->ad_block2tag as ad_block2tag
							ON ad_block2tag.ad_block_id = ad_block.ad_block_id
						WHERE	ad_tag_id = '" . mysql_real_escape_string($ad_tag) . "'
						ORDER BY RAND()
						LIMIT 1
						");
				}
				elseif ( $ad_block_id )
				{
					$sem_ad_block2tag[$ad_tag] = (string) $wpdb->get_var("
						SELECT	ad_block_name
						FROM	$wpdb->ad_blocks
						WHERE	ad_block_id = " . intval($ad_block_id) . "
						");
				}
			}
		}
	}

	#echo '<pre>';
	#var_dump($sem_ad_block2tag);
	#echo '</pre>';

} # end sem_ad_spaces_init()

add_action('template_redirect', 'sem_ad_spaces_init', 0);


#
# sem_ad_spaces_start_ob()
#

function sem_ad_spaces_start_ob()
{
	if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
	{
		ob_start('sem_ad_spaces_ob_callback');
		remove_filter('the_content', 'sem_ad_spaces_main');
	}
} # end sem_ad_spaces_start_ob()

add_action('template_redirect', 'sem_ad_spaces_start_ob');


#
# sem_ad_spaces_ob_callback()
#

function sem_ad_spaces_ob_callback($buffer)
{
	global $sem_ad_blocks;

	$sem_ad_blocks = array();

	preg_match("/
			<\s*body
				(?:
					\s[^>]*
				)?
				\s*>
			.*
			<\s*\/\s*body\s*>
		/isx",
		$buffer,
		$body
		);

	if ( $body )
	{
		$body = $body[0];

		$new_body = sem_ad_spaces_main($body);

		$buffer = str_replace($body, $new_body, $buffer);
	}

	return $buffer;
} # end sem_ad_spaces_ob_callback()


#
# sem_ad_spaces_main()
#

function sem_ad_spaces_main($buffer)
{
	#echo '<pre>';
	#var_dump(htmlspecialchars($buffer, ENT_QUOTES));
	#echo '</pre>';

	$buffer = sem_ad_spaces_register_inline_ads($buffer);
	$buffer = sem_ad_spaces_register_ads($buffer);
	$buffer = sem_ad_spaces_replace_ads($buffer);

	return $buffer;
} # end sem_ad_spaces_main()

# for tests:
add_filter('the_content', 'sem_ad_spaces_main');


#
# sem_ad_spaces_register_inline_ads()
#

function sem_ad_spaces_register_inline_ads($buffer)
{
	#echo '<pre>';
	#var_dump(htmlspecialchars($buffer));
	#echo '</pre>';

	# Step 1: Clean-up inline ad blocks

	$buffer = preg_replace(
		"/
			<\s*p\s*(?:\s+[^>]*)?>	# start paragraph tag
			(
				<\s*!--\s*			# <!--
				ad(?:_)?			# adunit
				(?:
					unit
				|
					block
				|
					space
				|
					sense
				)
				(?:					# ad unit name
					\#
					.*
				)?
				\s*--\s*>			# -->
			)
			(?:						# optional end paragraph tag
				<\s*\/\s*p\s*>
			)?
		/isUx",
		"$1",
		$buffer
		);

	# Step 2: Register inline ad blocks

	$buffer = preg_replace_callback(
		"/
			<\s*!--\s*			# <!--
			ad(?:_)?			# adunit
			(?:
				unit
			|
				block
			|
				space
			|
				sense
			)
			(?:					# ad unit name
				\#
				(.*)
			)?
			\s*--\s*>			# -->
		/isUx",
		'sem_ad_spaces_register_inline_ads_callback',
		$buffer
		);

	return $buffer;
} # end sem_ad_spaces_register_inline_ads()


#
# sem_ad_spaces_register_inline_ads_callback()
#

function sem_ad_spaces_register_inline_ads_callback($input)
{
	$ad_block_xml = '<sem:ad_block'
		. ( isset($input[1])
			? ( ' name="' . $input[1] . '"' )
			: ''
			)
		. ' />';

	#echo '<pre>';
	#var_dump(htmlspecialchars($ad_block_xml));
	#echo '</pre>';

	return $ad_block_xml;
} # end sem_ad_spaces_register_inline_ads_callback()


#
# sem_ad_spaces_register_ads()
#

function sem_ad_spaces_register_ads($buffer)
{
	# Step 1: Register all ad blocks

	$buffer = preg_replace_callback(
		"/
			<\s*sem\s*:\s*ad_block		# sem:ad_block
			(
			(?:							# attributes
				\s+.*
			)*
			)
			\s*\/\s*>					# close xml tag
		/isUx",
		'sem_ad_spaces_register_ads_callback',
		$buffer
		);

	return $buffer;
} # end sem_ad_spaces_register_ads()


#
# sem_ad_spaces_register_ads_callback()
#

function sem_ad_spaces_register_ads_callback($input)
{
	global $sem_ad_blocks;
	global $wpdb;

	$options = function_exists('get_site_option')
		? get_site_option('sem_ad_space_params')
		: get_settings('sem_ad_space_params');

	# Parse attributes

	preg_match_all(
		"/
			([a-z_\.\-]+)
			=
			(?:
				\"([^\"]*)\"
			|
				'([^']*)'
			|
				([^\"']\S*)
			)
		/isx",
		$input[1],
		$matches,
		PREG_SET_ORDER
		);

	# Process attributes

	foreach ( (array) $matches as $match )
	{
		$attributes[$match[1]] = $match[2];
	}

	#echo '<pre>';
	#var_dump($attributes);
	#echo '</pre>';

	$attributes['name'] = isset($attributes['name'])
		? trim($attributes['name'])
		: '';

	if ( $attributes['name'] == '' )
	{
		$attributes['name'] = $options['default_ad_block_name'];
	}

	$attributes['name'] = strtolower($attributes['name']);

	$attributes['priority'] = isset($attributes['priority'])
		? intval($attributes['priority'])
		: 0;

#	echo '<pre>';
#	var_dump($attributes);
#	var_dump($sem_ad_blocks);
#	echo '</pre>';

	$ad_block_id = '--ad_block:' . md5(serialize($attributes) . sizeof($sem_ad_blocks, 1)) . '--';

	$sem_ad_blocks[$attributes['priority']][] = array(
		'id' => $ad_block_id,
		'name' => $attributes['name']
		);

	return $ad_block_id;
} # end sem_ad_spaces_register_ads_callback()


#
# sem_ad_spaces_replace_ads()
#

function sem_ad_spaces_replace_ads($buffer)
{
	global $sem_ad_blocks;
	global $wpdb;

	$ad_names = array();
	$sem_ad_blocks = (array) $sem_ad_blocks;

	foreach ( $sem_ad_blocks as $priority => $ad_blocks )
	{
		foreach ( $ad_blocks as $key => $ad_block )
		{
			if ( $ad_block['name'] )
			{
				$ad_names[] = $ad_block['name'];
			}
			elseif ( $ad_block['name'] === false )
			{
				$ad_name = (string) $wpdb->get_var("
					# " . md5(rand()) . "
					SELECT	ad_block_name
					FROM	$wpdb->ad_blocks as ad_block
					INNER JOIN $wpdb->ad_block2tag as ad_block2tag
						ON ad_block2tag.ad_block_id = ad_block.ad_block_id
					WHERE	ad_tag_id = 'inline'
					ORDER BY RAND()
					LIMIT 1
					");

				$ad_names[] = $ad_name;
				$sem_ad_blocks[$priority][$key]['name'] = $ad_name;
			}
		}
	}

	#echo '<pre>';
	#var_dump($sem_ad_blocks, $ad_names);
	#echo '</pre>';

	$ad_blocks = sem_ad_spaces_get_ad_blocks($ad_names);

	$options = function_exists('get_site_option')
		? get_site_option('sem_ad_space_params')
		: get_settings('sem_ad_space_params');

	$max_ad_blocks = isset($options['max_ad_blocks'])
		? intval($options['max_ad_blocks'])
		: 3;

	$displayed = 0;

	krsort($sem_ad_blocks);

	$find = array();
	$replace = array();

	foreach ( $sem_ad_blocks as $priority => $ad_block_list )
	{
		if ( isset($_GET['action']) && $_GET['action'] == 'print' )
		{
			foreach ( $ad_block_list as $ad_block )
			{
				$find[] = $ad_block['id'];
				$replace[] = '';
			}
		}
		else
		{
			foreach ( $ad_block_list as $ad_block )
			{
				$find[] = $ad_block['id'];

				if ( $max_ad_blocks > 0 && $displayed >= $max_ad_blocks )
				{
					$replace[] = '';
				}
				else
				{
					if ( isset($ad_blocks[$ad_block['name']]) )
					{
						if ( is_preview() )
						{
							$replace[] = '<div class="ad">Ad Unit / ' . stripslashes($ad_block['name']) . '</div>';
						}
						else
						{
							$replace[] = stripslashes($ad_blocks[$ad_block['name']]->ad_block_code);
						}
						$displayed++;
					}
					else
					{
						$replace[] = '';
					}
				}
			}
		}
	}

#	echo '<pre>';
#	var_dump($sem_ad_blocks);
#	var_dump($ad_block);
#	var_dump($ad_blocks);
#	var_dump($params);
#	var_dump($find, $replace);
#	echo '</pre>';

	$buffer = str_replace($find, $replace, $buffer);

	return $buffer;
} # end sem_ad_spaces_replace_ads()


#
# sem_ad_spaces_get_ad_blocks()
#

function sem_ad_spaces_get_ad_blocks($ad_names = array())
{
	global $wpdb;

	$ad_blocks = array();

	$ad_names = array_unique($ad_names);

	$sql_in = "";

	foreach ( $ad_names as $ad_name )
	{
		$sql_in .=
			( $sql_in
				? ', '
				: ''
				)
			. "'" . mysql_real_escape_string($ad_name) . "'";
	}

	if ( $sql_in )
	{
		$sql = "
		SELECT	*
		FROM	$wpdb->ad_blocks
		WHERE	ad_block_name IN ( $sql_in )
		";

		$res = $wpdb->get_results($sql);

		foreach ( (array) $res as $ad_block )
		{
			$ad_blocks[strtolower($ad_block->ad_block_name)] = $ad_block;
		}
	}

#	echo '<pre>';
#	var_dump($ad_names);
#	var_dump($sql);
#	var_dump($res);
#	echo '</pre>';

	return $ad_blocks;
} # end sem_ad_spaces_get_ad_blocks()


#
# sem_ad_spaces_display_ad_tag()
#

function sem_ad_spaces_display_ad_tag($ad_tag, $priority = 0)
{
	global $sem_ad_block2tag;

	#echo '<pre>';
	#var_dump($sem_ad_block2tag);
	#echo '</pre>';

	#echo '<div class="ad">' . $ad_tag . '</div>';

	if ( isset($sem_ad_block2tag[$ad_tag]) && $sem_ad_block2tag[$ad_tag] )
	{
		echo '<sem:ad_block name="' . $sem_ad_block2tag[$ad_tag] . '" priority="' . intval($priority) . '" />';
	}
} # end sem_ad_spaces_display_ad_tag()


#
# display_top_ad_tag()
#

function display_top_ad_tag()
{
	sem_ad_spaces_display_ad_tag('top', 10);
} # end display_top_ad_tag()

add_action('before_the_wrapper', 'display_top_ad_tag', 0);


#
# display_header_ad_tag()
#

function display_header_ad_tag()
{
	sem_ad_spaces_display_ad_tag('header', 10);
} # end display_header_ad_tag()

add_action('after_the_header', 'display_header_ad_tag', 30);


#
# display_above_ad_tag()
#

function display_above_ad_tag()
{
	sem_ad_spaces_display_ad_tag('above', 5);
} # end display_above_ad_tag()

add_action('before_the_entries','display_above_ad_tag');


#
# display_title_ad_tag()
#

function display_title_ad_tag()
{
	sem_ad_spaces_display_ad_tag('title');
} # end display_title_ad_tag()

add_action('display_entry_header', 'display_title_ad_tag', 20);


#
# display_below_ad_tag()
#

function display_below_ad_tag()
{
	sem_ad_spaces_display_ad_tag('below');
} # end display_below_ad_tag()

add_action('after_the_entry','display_below_ad_tag');


#
# display_footer_ad_tag()
#

function display_footer_ad_tag()
{
	sem_ad_spaces_display_ad_tag('footer');
} # end display_footer_ad_tag()

add_action('before_the_footer', 'display_footer_ad_tag', 3);


#
# display_sidebar_ad_tag()
#

function display_sidebar_ad_tag()
{
	sem_ad_spaces_display_ad_tag('sidebar', 5);
} # end display_sidebar_ad_tag()

add_action('sidebar_ad', 'display_sidebar_ad_tag', 5);


#
# sem_ad_spaces_widgetize()
#

function sem_ad_spaces_widgetize()
{
	if ( function_exists('register_sidebar_widget')
		&& ( !function_exists('get_site_option') || is_site_admin() )
		)
	{
		register_sidebar_widget('Sidebar Ad', 'sem_ad_spaces_display_widget');
	}
} # end sem_ad_spaces_widgetize()

add_action('plugins_loaded', 'sem_ad_spaces_widgetize');


#
# sem_ad_spaces_display_widget()
#

function sem_ad_spaces_display_widget($args)
{
	global $sem_ad_block2tag;

	if ( isset($sem_ad_block2tag['sidebar']) )
	{
		echo $args['before_widget'];
		do_action('sidebar_ad');
		echo $args['after_widget'];
	}
} # end sem_ad_spaces_display_widget()


#
# the_sidebar_ad()
#

function the_sidebar_ad()
{
	do_action('sidebar_ad');
}

#
# admin screens
#

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-ad-space-admin.php';
}

?>