<?php
/*
Plugin Name: Related Widgets
Plugin URI: http://www.semiologic.com/software/widgets/related-widgets/
Description: WordPress widgets that let you list related posts or pages. Requires that you tag your posts and pages.
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: related_widgets
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('related-widgets','wp-content/plugins/related-widgets');

class related_widgets
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('related_widgets', 'widgetize'));

		add_action('save_post', array('related_widgets', 'clear_entry_cache'));

		add_action('generate_rewrite_rules', array('related_widgets', 'clear_entry_cache'));

		add_action('update_option_sidebars_widgets', array('related_widgets', 'clear_entry_cache'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = get_option('related_widgets');
		$number = intval($options['number']);

		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;

		$dims = array('width' => 460, 'height' => 350);
		$class = array('classname' => 'related_widgets');

		for ($i = 1; $i <= 9; $i++)
		{
			$name = sprintf(__('Related Widget %d', 'related-widgets'), $i);
			$id = "related-widget-$i";

			wp_register_sidebar_widget(
				$id,
				$name,
				$i <= $number
				? array('related_widgets', 'display_widget')
				: /* unregister */ '',
				$class,
				$i);

			wp_register_widget_control(
				$id,
				$name,
				$i <= $number
					? array('related_widgets_admin', 'widget_control')
					: /* unregister */ '',
				$dims,
				$i);
		}
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args, $number = 1)
	{
		$mysql_version = preg_replace('|[^0-9\.]|', '', @mysql_get_server_info());

		if ( version_compare($mysql_version, '4.1', '<') )
		{
			echo $args['before_widget']
				. $args['before_title'] . 'Related Widget' . $args['after_title']
				. '<b>Your MySQL version is lower than 4.1.</b> It\'s time to <a href="http://www.semiologic.com/resources/wp-basics/wordpress-server-requirements/">change hosts</a> if yours doesn\'t want to upgrade.'
				. $args['after_widget'];

			return;
		}

		$options = get_option('related_widgets');
		$options = $options[$number];

		if ( !is_array($options) )
		{
			$options = array(
				'title' => __('Related Posts'),
				'type' => 'posts',
				'amount' => 5,
				'trim' => '',
				'exclude' => '',
				'score' => false,
				);
		}

		if ( in_the_loop() )
		{
			$options['object_id'] = get_the_ID();
		}
		elseif ( is_singular() )
		{
			$options['object_id'] = $GLOBALS['wp_query']->get_queried_object_id();
		}
		else
		{
			return ;
		}

		$options['exclude'] .= ( $options['exclude'] ? ', ' : '' ) . intval($options['object_id']);

		$cache = get_option('related_widgets_cache');

		#$cache = array();
		#update_option('related_widgets_cache', $cache);

		$cache_id = md5(serialize($options));

		if ( isset($cache[$options['type']][$cache_id]) )
		{
			echo $cache[$options['type']][$cache_id];
			return;
		}

		switch ( $options['type'] )
		{
		case 'posts':
			$items = related_widgets::get_posts($options);
			break;

		case 'pages':
			$items = related_widgets::get_pages($options);
			break;

		#case 'tags':
		#	$items = related_widgets::get_tags($options);
		#	break;

		default:
			return;
		}

		$o = '';

		if ( !empty($items) )
		{
			$o .= $args['before_widget'];

			if ( $options['title'] )
			{
				$o .= $args['before_title'] . $options['title'] . $args['after_title'];
			}

			$o .= '<ul>';

			foreach ( $items as $item )
			{
				$o .= '<li>'
					. $item->item_label
					. '</li>';
			}

			$o .= '</ul>';

			$o .= $args['after_widget'];
		}

		$cache[$options['type']][$cache_id] = $o;

		update_option('related_widgets_cache', $cache);

		echo $o;
	} # display_widget()


	#
	# get_posts()
	#

	function get_posts($options)
	{
		global $wpdb;

		$items_sql = "
			SELECT	posts.*,
					posts.ID as item_id,
					lower( post_title ) as item_name
			FROM	$wpdb->posts as posts
			"
			. ( $options['filter']
				? ( "
			INNER JOIN $wpdb->term_relationships as term_relationships
			ON		term_relationships.object_id = posts.ID
			INNER JOIN $wpdb->term_taxonomy as term_taxonomy
			ON		term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
			AND		term_taxonomy.taxonomy = 'category'
			AND		term_taxonomy.term_id = " . intval($options['filter'])
			)
				: ''
				)
			. "
			WHERE	posts.post_status = 'publish'
			AND		posts.post_type = 'post'
			AND		posts.post_password = ''
			"
			. ( $options['exclude']
				? ( "
			AND		posts.ID NOT IN (" . $options['exclude'] . ")
			" )
				: ''
				)
			;

		$items = related_widgets::get_items($items_sql, $options);

		update_post_cache($items);
		update_page_cache($items);

		foreach ( array_keys($items) as $key )
		{
			$items[$key]->item_label = '<a href="'
				. htmlspecialchars(apply_filters('the_permalink', get_permalink($items[$key]->ID)))
				. '">'
				. ( $options['trim'] && strlen($items[$key]->post_title) > $options['trim']
					? ( substr($items[$key]->post_title, 0, $options['trim']) . '...' )
					: $items[$key]->post_title
					)
				. '</a>'
				. ( $options['score']
					? ( ' (' . $items[$key]->item_score . '%)' )
					: ''
					);
		}

		return $items;
	} # get_posts()


	#
	# get_pages()
	#

	function get_pages($options)
	{
		global $wpdb;
		global $page_filters;

		if ( $options['filter'] )
		{
			if ( isset($page_filters[$options['filter']]) )
			{
				$parent_sql = $page_filters[$options['filter']];
			}
			else
			{
				$parents = array($options['filter']);

				do
				{
					$old_parents = $parents;

					$parents_sql = '';

					foreach ( $parents as $parent )
					{
						$parents_sql .= ( $parents_sql ? ', ' : '' ) . $parent;
					}

					$parents = (array) $wpdb->get_col("
						SELECT	posts.ID
						FROM	$wpdb->posts as posts
						WHERE	posts.post_status = 'publish'
						AND		posts.post_type = 'page'
						AND		posts.post_password = ''
						AND		( posts.ID IN ( $parents_sql ) OR posts.post_parent IN ( $parents_sql ) )
						AND		EXISTS (
								SELECT	1
								FROM	$wpdb->posts as children
								WHERE	children.post_status = 'publish'
								AND		children.post_parent = posts.ID
								AND		children.post_type = 'page'
								AND		children.post_password = ''
								)
						ORDER BY posts.ID
						");

				} while ( $parents != $old_parents );

				$page_filters[$options['filter']] = $parent_sql;
			}
		}

		$items_sql = "
			SELECT	posts.*,
					posts.ID as item_id,
					lower( post_title ) as item_name
			FROM	$wpdb->posts as posts
			WHERE	posts.post_status = 'publish'
			AND		posts.post_type = 'page'
			AND		posts.post_password = ''
			"
			. ( $options['filter']
				? ( "
			AND		posts.post_parent IN ( $parents_sql )
			" )
				: ''
				)
			. ( $options['exclude']
				? ( "
			AND		posts.ID NOT IN (" . $options['exclude'] . ")
			" )
				: ''
				)
			;

		$items = related_widgets::get_items($items_sql, $options);

		update_post_cache($items);
		update_page_cache($items);

		foreach ( array_keys($items) as $key )
		{
			$items[$key]->item_label = '<a href="'
				. htmlspecialchars(apply_filters('the_permalink', get_permalink($items[$key]->ID)))
				. '">'
				. ( $options['trim'] && strlen($items[$key]->post_title) > $options['trim']
					? ( substr($items[$key]->post_title, 0, $options['trim']) . '...' )
					: $items[$key]->post_title
					)
				. '</a>'
				. ( $options['score']
					? ( ' (' . $items[$key]->item_score . '%)' )
					: ''
					);
		}

		return $items;
	} # get_pages()


	#
	# get_items()
	#

	function get_items($items_sql, $options)
	{
		global $wpdb;

		$post_title = get_the_title($options['object_id']);
		$post_keywords = get_post_meta($options['object_id'], '_keywords', true);

		$extra_keywords = $post_title . '-' . $post_keywords;
		$extra_keywords = sanitize_title($extra_keywords);

		$extra_keywords = explode("-", $extra_keywords);
		$extra_keywords = array_unique($extra_keywords);
		$extra_keywords = array_map(create_function('$in', 'return "\'" . addslashes($in) . "\'";'), $extra_keywords);
		$extra_keywords = implode(', ', $extra_keywords);

		#dump($extra_keywords);

		$term_scores_sql = "
			SELECT	term_relationships.object_id,
					term_relationships.term_taxonomy_id,
					CASE
					WHEN
						term_taxonomy.taxonomy = 'post_tag'
					THEN
						100
					ELSE
						75
					END as taxonomy_score,
					CASE
					WHEN
						term_relationships.object_id = " . intval($options['object_id']) . "
					THEN
						100
					WHEN
						term_relationships.object_id = object_relationships.object_id
					THEN
						90
					ELSE
						80
					END as relationship_score
			FROM	$wpdb->term_relationships as object_relationships
			INNER JOIN $wpdb->term_taxonomy as object_taxonomy
			ON		object_taxonomy.taxonomy IN ( 'post_tag', 'yahoo_terms' )
			AND		object_taxonomy.term_taxonomy_id = object_relationships.term_taxonomy_id
			INNER JOIN $wpdb->terms as object_terms
			ON		object_terms.term_id = object_taxonomy.term_id
			INNER JOIN $wpdb->term_relationships as related_relationships
			ON		related_relationships.term_taxonomy_id = object_relationships.term_taxonomy_id
			INNER JOIN $wpdb->term_relationships as term_relationships
			ON		term_relationships.object_id = related_relationships.object_id
			INNER JOIN $wpdb->term_taxonomy as term_taxonomy
			ON		term_taxonomy.taxonomy IN ( 'post_tag', 'yahoo_terms' )
			AND		term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
			WHERE	object_relationships.object_id = " . intval($options['object_id']) . "
			OR		object_terms.slug IN ( $extra_keywords )
			";

		#dump($term_scores_sql);
		#dump($wpdb->get_results($term_scores_sql));

		$term_weights_sql = "
			SELECT	object_id,
					term_taxonomy_id,
					MAX( ( taxonomy_score * relationship_score ) ) as term_weight
			FROM	( $term_scores_sql ) as term_scores
			GROUP BY object_id, term_taxonomy_id
			";

		#dump($term_weights_sql);
		#dump($wpdb->get_results($term_weights_sql));



		$object_weights_sql = "
			SELECT	object_id,
					SUM( term_weight ) as object_weight
			FROM	( $term_weights_sql ) as term_weights
			GROUP BY object_id
			";

		#dump($wpdb->get_results($object_weights_sql));


		$object_scores = "
			SELECT	object_id,
					object_weight,
					MAX( max_weight ) as max_weight
			FROM	( $object_weights_sql ) as object_weights,
					(
					SELECT	object_weight as max_weight
					FROM	( $object_weights_sql ) as max_weights
					) as max_weights
			GROUP BY object_id
			";

		#dump($wpdb->get_results($object_scores));


		$items = (array) $wpdb->get_results("
			SELECT	items.*,
					floor( 100 * exp( ( object_weight + max_weight ) / ( 2 * max_weight ) ) / exp( 1 ) ) as item_score
			FROM	( $items_sql ) as items
			INNER JOIN ( $object_scores ) as related_objects
			ON		related_objects.object_id = items.item_id
			ORDER BY item_score DESC, lower(items.item_name)
			LIMIT " . intval($options['amount'])
			);

		#dump($items);

		return $items;
	} # get_items()


	#
	# clear_entry_cache()
	#

	function clear_entry_cache($in = null)
	{
		$cache = get_option('related_widgets_cache');

		unset($cache['posts']);
		unset($cache['pages']);

		update_option('related_widgets_cache', $cache);

		return $in;
	} # clear_entry_cache()
} # related_widgets

related_widgets::init();

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/related-widgets-admin.php';
}
?>