<?php
/*
Plugin Name: Silo Site
Plugin URI: http://www.semiologic.com/software/widgets/silo/
Description: <a href="http://www.seo2020.com/promo.html">Silo functionalities</a> for sites built using static pages.
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class silo
{
	#
	# init()
	#

	function init()
	{
		global $wpdb;

		add_action('plugins_loaded', array('silo', 'widgetize'));
	} # init()


	#
	# display_widget()
	#

	function display_widget($args = null)
	{
		global $wpdb;

		# default args

		$defaults = array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>'
			);

		$options = get_option('silo_options');

		if ( $options === false )
		{
			$options = array('title' => __('Browse'));
			update_option('silo_options', $options);
		}

		$args = array_merge($defaults, $options, $args);

		$exclude = '';
		foreach ( (array) $args['exclude'] as $val )
		{
			$exclude .= ( $exclude ? ', ' : '' ) . intval($val);
		}

		if ( is_page() )
		{
			$post =& $GLOBALS['posts'][0];

			$children = array();
			$next = $post;

			do
			{
				$current = $next;

				$post_parent = $current->post_parent;

				$children[$post_parent] = (array) $wpdb->get_results("
					SELECT	*
					FROM	$wpdb->posts
					WHERE	post_parent = $post_parent
					AND		post_type = 'page'
					AND		post_status = 'publish'"
					. ( $exclude
						? "
					AND		ID NOT IN ($exclude)"
						: ""
						)
					. "
					ORDER BY menu_order, post_title
					");

				if ( $post_parent != 0 )
				{
					$next = $wpdb->get_row("
						SELECT	*
						FROM	$wpdb->posts
						WHERE	ID = $post_parent
						AND		post_type = 'page'
						AND		post_status = 'publish'"
						. ( $exclude
							? "
						AND		ID NOT IN ($exclude)"
							: ""
							)
						. "
						ORDER BY menu_order, post_title
						");
				}

			} while  ( $current->post_parent != 0 );

			$children[$post->ID] = (array) $wpdb->get_results("
				SELECT	*
				FROM	$wpdb->posts
				WHERE	post_parent = $post->ID
				AND		post_type = 'page'
				AND		post_status = 'publish'"
				. ( $exclude
					? "
				AND		ID NOT IN ($exclude)"
					: ""
					)
				. "
				ORDER BY menu_order, post_title
				");
		}
		else
		{
			$children[0] = (array) $wpdb->get_results("
				SELECT	*
				FROM	$wpdb->posts
				WHERE	post_parent = 0
				AND		post_type = 'page'
				AND		post_status = 'publish'"
				. ( $exclude
					? "
				AND		ID NOT IN ($exclude)"
					: ""
					)
				. "
				ORDER BY menu_order, post_title
				");

			$post = object;
		}

		if ( $children )
		{
			foreach ( $children as $post_ID => $childs )
			{
				update_page_cache($childs);
			}

			echo $args['before_widget'];

			echo $args['before_title']
				. $args['title']
				. $args['after_title'];

			echo '<ul>' . "\n";

			silo::display_children($children, $post, 0);

			echo '</ul>' . "\n";

			echo $args['after_widget'];
		}
	} # display_widget()


	#
	# display_children()
	#

	function display_children(&$children, &$post, $parent_ID = 0)
	{
		foreach ( $children[$parent_ID] as $child )
		{
			echo '<li>';

			if ( $child->ID == $post->ID )
			{
				echo $child->post_title;
			}
			else
			{
				echo '<a'
						. ' href="' . apply_filters('the_permalink', get_permalink($child->ID)) . '"'
						. ' title="' . $child->post_title . '"'
						. '>'
					. $child->post_title
					. '</a>';
			}

			if ( isset($children[$child->ID]) )
			{
				echo "\n"
					. '<ul>' . "\n";

				silo::display_children($children, $post, $child->ID);

				echo '</ul>' . "\n";
			}

			echo '</li>' . "\n";
		}
	} # display_children()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_sidebar_widget('Silo Pages', array('silo', 'display_widget'));
		}
	} # widgetize()
} # silo

silo::init();


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/silo-admin.php';
}
?>