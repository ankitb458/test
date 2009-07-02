<?php
/*
Plugin Name: Fuzzy Widgets
Plugin URI: http://www.semiologic.com/software/widgets/fuzzy-widgets/
Description: WordPress widgets that let you list fuzzy numbers of posts, pages, links, or comments.
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: fuzzy_widgets
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('fuzzy-widgets','wp-content/plugins/fuzzy-widgets');

class fuzzy_widgets
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('fuzzy_widgets', 'widgetize'));

		add_action('save_post', array('fuzzy_widgets', 'clear_entry_cache'));
		add_action('add_link', array('fuzzy_widgets', 'clear_link_cache'));
		add_action('edit_link', array('fuzzy_widgets', 'clear_link_cache'));
		add_action('edit_comment', array('fuzzy_widgets', 'clear_comment_cache'));
		add_action('comment_post', array('fuzzy_widgets', 'clear_comment_cache'));
		add_action('wp_set_comment_status', array('fuzzy_widgets', 'clear_comment_cache'));

		add_action('generate_rewrite_rules', array('fuzzy_widgets', 'clear_entry_cache'));
		add_action('generate_rewrite_rules', array('fuzzy_widgets', 'clear_link_cache'));
		add_action('generate_rewrite_rules', array('fuzzy_widgets', 'clear_comment_cache'));

		add_action('update_option_sidebars_widgets', array('fuzzy_widgets', 'clear_entry_cache'));
		add_action('update_option_sidebars_widgets', array('fuzzy_widgets', 'clear_link_cache'));
		add_action('update_option_sidebars_widgets', array('fuzzy_widgets', 'clear_comment_cache'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = get_option('fuzzy_widgets');
		$number = intval($options['number']);

		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;

		$dims = array('width' => 460, 'height' => 350);
		$class = array('classname' => 'fuzzy_widgets');

		for ($i = 1; $i <= 9; $i++)
		{
			$name = sprintf(__('Fuzzy Widget %d', 'fuzzy-widgets'), $i);
			$id = "fuzzy-widget-$i";

			wp_register_sidebar_widget(
				$id,
				$name,
				$i <= $number
				? array('fuzzy_widgets', 'display_widget')
				: /* unregister */ '',
				$class,
				$i);

			wp_register_widget_control(
				$id,
				$name,
				$i <= $number
					? array('fuzzy_widgets_admin', 'widget_control')
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
				. $args['before_title'] . 'Fuzzy Widget' . $args['after_title']
				. '<b>Your MySQL version is lower than 4.1.</b> It\'s time to <a href="http://www.semiologic.com/resources/wp-basics/wordpress-server-requirements/">change hosts</a> if yours doesn\'t want to upgrade.'
				. $args['after_widget'];

			return;
		}

		$options = get_option('fuzzy_widgets');
		$options = $options[$number];

		if ( !is_array($options) )
		{
			$options = array(
				'title' => __('Recent Posts'),
				'type' => 'posts',
				'amount' => 5,
				'fuzziness' => 'days',
				'trim' => '',
				'exclude' => '',
				'date' => true,
				'desc' => false,
				);
		}

		$cache = get_option('fuzzy_widgets_cache');

		#$cache = array();
		#update_option('fuzzy_widgets_cache', $cache);

		$cache_id = md5(serialize($options));

		if ( isset($cache[$options['type']][$cache_id]) )
		{
			echo $cache[$options['type']][$cache_id];
			return;
		}

		switch ( $options['type'] )
		{
		case 'posts':
			$items = fuzzy_widgets::get_posts($options);
			break;

		case 'old_posts':
			$items = fuzzy_widgets::get_old_posts($options);
			break;

		case 'pages':
			$items = fuzzy_widgets::get_pages($options);
			break;

		case 'links':
			$items = fuzzy_widgets::get_links($options);
			break;

		case 'comments':
			$items = fuzzy_widgets::get_comments($options);
			break;

		case 'updates':
			$items = fuzzy_widgets::get_updates($options);
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

			if ( !$options['date'] )
			{
				$o .= '<ul>';
			}

			foreach ( $items as $item )
			{
				if ( $options['date'] )
				{
					$cur_date = mysql2date(get_option('date_format'), $item->item_date);

					if ( !isset($prev_date) )
					{
						$o .= '<h3>' . $cur_date . '</h3>'
							. '<ul>';
					}
					elseif ( $cur_date != $prev_date )
					{
						$o .= '</ul>'
							. '<h3>' . $cur_date . '</h3>'
							. '<ul>';
					}

					$prev_date = $cur_date;
				}

				$o .= '<li>'
					. $item->item_label
					. '</li>';
			}

			$o .= '</ul>';

			$o .= $args['after_widget'];
		}

		$cache[$options['type']][$cache_id] = $o;

		update_option('fuzzy_widgets_cache', $cache);

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
			;

		$items = fuzzy_widgets::get_items($items_sql, $options);

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
	# get_old_posts()
	#

	function get_old_posts($options)
	{
		global $wpdb;

		$items_sql = "
			SELECT	posts.*,
					posts.post_date as item_date
			FROM	$wpdb->posts as posts
			WHERE	posts.post_status = 'publish'
			AND		posts.post_type = 'post'
			AND		posts.post_password = ''
			AND		posts.post_date <= now() - interval 1 year
			"
			. ( $options['exclude']
				? ( "
			AND		posts.ID NOT IN (" . $options['exclude'] . ")
			" )
				: ''
				)
			;

		$items = fuzzy_widgets::get_items($items_sql, $options);

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
	} # get_old_posts()


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
					posts.post_date as item_date
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

		$items = fuzzy_widgets::get_items($items_sql, $options);

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
			SELECT	links.*,
					links.link_added as item_date
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
			"
			;

		$items = fuzzy_widgets::get_items($items_sql, $options);

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

		$min_comment_date_sql = "
				SELECT	comment_post_ID,
						max(comment_date) as min_comment_date
				FROM	$wpdb->comments
				WHERE	comment_ID NOT IN (
					SELECT	invalid_comments.comment_ID
					FROM	$wpdb->comments as invalid_comments
					INNER JOIN (
						SELECT	comment_post_ID,
								max(comment_date) as max_comment_date
						FROM	$wpdb->comments
						GROUP BY comment_post_ID
						HAVING	count(comment_ID) > 1
						) as latest_comments
					ON		latest_comments.comment_post_ID = invalid_comments.comment_post_ID
					WHERE	invalid_comments.comment_date = latest_comments.max_comment_date
					)
				GROUP BY comment_post_ID
				";

		$items_sql = "
			SELECT	posts.*,
					comments.*,
					comments.comment_date as item_date
			FROM	$wpdb->posts as posts
			INNER JOIN $wpdb->comments as comments
			ON		comments.comment_post_ID = posts.ID
			INNER JOIN ( $min_comment_date_sql ) as valid_comments
			ON		valid_comments.comment_post_ID = comments.comment_post_ID
			WHERE	posts.post_status = 'publish'
			AND		posts.post_type IN ('post', 'page')
			AND		posts.post_password = ''
			AND		comments.comment_approved = 1
			AND		comments.comment_date >= valid_comments.min_comment_date
			"
			. ( $options['exclude']
				? ( "
			AND		posts.ID NOT IN (" . $options['exclude'] . ")
			" )
				: ''
				)
			;

		$items = fuzzy_widgets::get_items($items_sql, $options);

		update_post_cache($items);
		update_page_cache($items);

		foreach ( array_keys($items) as $key )
		{
			$items[$key]->item_label = ( $options['trim'] && strlen($items[$key]->comment_author) > $options['trim']
					? ( substr($items[$key]->comment_author, 0, $options['trim']) . '...' )
					: $items[$key]->comment_author
					)
				. ' ' . __('on', 'fuzzy-widgets') . ' '
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
			SELECT	posts.*,
					posts.post_modified as item_date
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
			;

		$items = fuzzy_widgets::get_items($items_sql, $options);

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
	} # get_updates()


	#
	# get_items()
	#

	function get_items($items_sql, $options)
	{
		global $wpdb;

		switch ( $options['fuzziness'] )
		{
		case 'days':
			$min_item_date_sql = "
				SELECT	MIN(min_item_date) as min_item_date
				FROM (
					SELECT	DISTINCT DATE_FORMAT( item_date, '%Y-%m-%d 00:00:00' ) as min_item_date
					FROM	( $items_sql ) as items
					ORDER BY min_item_date DESC
					LIMIT " . intval($options['amount']) . "
					) as min_item_dates
				";

			$items = (array) $wpdb->get_results("
				SELECT	items.*
				FROM	( $items_sql ) as items
				WHERE	items.item_date >= ( $min_item_date_sql )
				ORDER BY items.item_date DESC"
				);
			break;

		case 'days_ago':
			$items = (array) $wpdb->get_results("
				SELECT	items.*
				FROM	( $items_sql ) as items
				WHERE	items.item_date >= now() - interval " . intval($options['amount']) . " day
				ORDER BY items.item_date DESC"
				);
			break;

		case 'items':
			$items = (array) $wpdb->get_results("
				SELECT	items.*
				FROM	( $items_sql ) as items
				ORDER BY items.item_date DESC
				LIMIT " . intval($options['amount'])
				);
			break;

		default:
			return array();
		}

		return $items;
	} # get_items()


	#
	# clear_entry_cache()
	#

	function clear_entry_cache($in = null)
	{
		$cache = get_option('fuzzy_widgets_cache');

		unset($cache['posts']);
		unset($cache['pages']);
		unset($cache['updates']);

		update_option('fuzzy_widgets_cache', $cache);

		return $in;
	} # clear_entry_cache()


	#
	# clear_link_cache()
	#

	function clear_link_cache($in = null)
	{
		$cache = get_option('fuzzy_widgets_cache');

		unset($cache['links']);

		update_option('fuzzy_widgets_cache', $cache);

		return $in;
	} # clear_link_cache()


	#
	# clear_comment_cache()
	#

	function clear_comment_cache($in = null)
	{
		$cache = get_option('fuzzy_widgets_cache');

		unset($cache['comments']);

		update_option('fuzzy_widgets_cache', $cache);

		return $in;
	} # clear_comment_cache()
} # fuzzy_widgets

fuzzy_widgets::init();

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/fuzzy-widgets-admin.php';
}
?>