<?php
/*
Plugin Name: Random Widgets
Plugin URI: http://www.semiologic.com/software/widgets/random-widgets/
Description: WordPress widgets that let you list a random number of posts, pages, links, or comments.
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: random_widgets
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('random-widgets','wp-content/plugins/random-widgets');

class random_widgets
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('random_widgets', 'widgetize'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = get_option('random_widgets');
		$number = intval($options['number']);

		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;

		$dims = array('width' => 460, 'height' => 350);
		$class = array('classname' => 'random_widgets');

		for ($i = 1; $i <= 9; $i++)
		{
			$name = sprintf(__('Random Widget %d', 'random-widgets'), $i);
			$id = "random-widget-$i";

			wp_register_sidebar_widget(
				$id,
				$name,
				$i <= $number
				? array('random_widgets', 'display_widget')
				: /* unregister */ '',
				$class,
				$i);

			wp_register_widget_control(
				$id,
				$name,
				$i <= $number
					? array('random_widgets_admin', 'widget_control')
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
		$options = get_option('random_widgets');
		$options = $options[$number];

		if ( !is_array($options) )
		{
			$options = array(
				'title' => __('Random Posts'),
				'type' => 'posts',
				'amount' => 5,
				'trim' => '',
				'exclude' => '',
				'desc' => false,
				);
		}

		switch ( $options['type'] )
		{
		case 'posts':
			$items = random_widgets::get_posts($options);
			break;

		case 'pages':
			$items = random_widgets::get_pages($options);
			break;

		case 'links':
			$items = random_widgets::get_links($options);
			break;

		case 'comments':
			$items = random_widgets::get_comments($options);
			break;

		case 'updates':
			$items = random_widgets::get_updates($options);
			break;

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
					posts.post_date as item_date
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
			. "
			ORDER BY RAND()
			LIMIT " . intval($options['amount'])
			;

		$items = (array) $wpdb->get_results($items_sql);

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
				. '</a>';
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
			SELECT	posts.*
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
			. "
			ORDER BY RAND()
			LIMIT " . intval($options['amount'])
			;

		$items = (array) $wpdb->get_results($items_sql);

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
				. '</a>';
		}

		return $items;
	} # get_pages()


	#
	# get_links()
	#

	function get_links($options)
	{
		global $wpdb;

		$items_sql = "
			SELECT	links.*
			FROM	$wpdb->links as links
			"
			. ( $options['filter']
				? ( "
			INNER JOIN $wpdb->term_relationships as term_relationships
			ON		term_relationships.object_id = links.link_id
			INNER JOIN $wpdb->term_taxonomy as term_taxonomy
			ON		term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
			AND		term_taxonomy.taxonomy = 'link_category'
			AND		term_taxonomy.term_id = " . intval($options['filter'])
			)
				: ''
				)
			. "
			WHERE	links.link_visible = 'Y'
			ORDER BY RAND()
			LIMIT " . intval($options['amount'])
			;

		$items = (array) $wpdb->get_results($items_sql);

		foreach ( array_keys($items) as $key )
		{
			$items[$key]->item_label = '<a href="'
				. htmlspecialchars($items[$key]->link_url)
				. '">'
				. ( $options['trim'] && strlen($items[$key]->link_name) > $options['trim']
					? ( substr($items[$key]->link_name, 0, $options['trim']) . '...' )
					: $items[$key]->link_name
					)
				. '</a>'
				. ( $options['desc'] && $items[$key]->link_description
					? ( '<br />' . $items[$key]->link_description )
					: ''
					);
		}

		return $items;
	} # get_links()


	#
	# get_comments()
	#

	function get_comments($options)
	{
		global $wpdb;

		$items_sql = "
			SELECT	posts.*,
					comments.*
			FROM	$wpdb->posts as posts
			INNER JOIN $wpdb->comments as comments
			ON		comments.comment_post_ID = posts.ID
			WHERE	posts.post_status = 'publish'
			AND		posts.post_type IN ('post', 'page')
			AND		posts.post_password = ''
			AND		comments.comment_approved = 1
			"
			. ( $options['exclude']
				? ( "
			AND		posts.ID NOT IN (" . $options['exclude'] . ")
			" )
				: ''
				)
			. "
			ORDER BY RAND()
			LIMIT " . intval($options['amount'])
			;

		$items = (array) $wpdb->get_results($items_sql);

		update_post_cache($items);
		update_page_cache($items);

		foreach ( array_keys($items) as $key )
		{
			$items[$key]->item_label = ( $options['trim'] && strlen($items[$key]->comment_author) > $options['trim']
					? ( substr($items[$key]->comment_author, 0, $options['trim']) . '...' )
					: $items[$key]->comment_author
					)
				. ' ' . __('on', 'random-widgets') . ' '
				. '<a href="'
				. htmlspecialchars(apply_filters('the_permalink', get_permalink($items[$key]->ID)) . '#comment-' . $items[$key]->comment_ID)
				. '">'
				. ( $options['trim'] && strlen($items[$key]->post_title) > $options['trim']
					? ( substr($items[$key]->post_title, 0, $options['trim']) . '...' )
					: $items[$key]->post_title
					)
				. '</a>';
		}

		return $items;
	} # get_comments()


	#
	# get_updates()
	#

	function get_updates($options)
	{
		global $wpdb;

		$items_sql = "
			SELECT	posts.*
			FROM	$wpdb->posts as posts
			WHERE	posts.post_status = 'publish'
			AND		posts.post_type IN ('post', 'page')
			AND		posts.post_password = ''
			AND		posts.post_modified <> posts.post_date
			"
			. ( $options['exclude']
				? ( "
			AND		posts.ID NOT IN (" . $options['exclude'] . ")
			" )
				: ''
				)
			. "
			ORDER BY RAND()
			LIMIT " . intval($options['amount'])
			;

		$items = (array) $wpdb->get_results($items_sql);

		update_post_cache($items);
		update_page_cache($items);

		foreach ( array_keys($items) as $keys )
		{
			$items[$key]->item_label = '<a href="'
				. htmlspecialchars(apply_filters('the_permalink', get_permalink($items[$key]->ID)))
				. '">'
				. ( $options['trim'] && strlen($items[$key]->post_title) > $options['trim']
					? ( substr($items[$key]->post_title, 0, $options['trim']) . '...' )
					: $items[$key]->post_title
					)
				. '</a>';
		}

		return $items;
	} # get_updates()
} # random_widgets

random_widgets::init();

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/random-widgets-admin.php';
}
?>