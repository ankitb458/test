<?php
/*
Plugin Name: Silo Web Design
Plugin URI: http://www.semiologic.com/software/widgets/silo/
Description: Silo web design tools for sites built using static pages.
Author: Denis de Bernardy
Version: 1.6
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: silo
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

		add_action('widgets_init', array('silo', 'widgetize'));

		add_action('save_post', array('silo', 'flush_cache'));
		add_action('delete_post', array('silo', 'flush_cache'));
		add_action('generate_rewrite_rules', array('silo', 'flush_cache'));

		add_action('update_option_sidebars_widgets', array('silo', 'flush_cache'));
	} # init()


	#
	# display_pages_widget()
	#

	function display_pages_widget($args = null)
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

		$cache_file = $args;
		$cache_file['url'] = $_SERVER['REQUEST_URI'];
		$cache_file = md5(serialize($_SERVER['REQUEST_URI']));

		if ( $file = glob(ABSPATH . 'wp-content/cache/silo-pages/' . $cache_file) )
		{
			$file = current($file);
			echo file_get_contents($file);
			return;
		}

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

				$post_parent = intval($current->post_parent);

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

		$o = '';

		if ( $children[0] )
		{
			foreach ( $children as $post_ID => $childs )
			{
				update_page_cache($childs);
			}

			$o .= $args['before_widget'];

			$o .= $args['before_title']
				. $args['title']
				. $args['after_title'];

			$o .= '<ul>' . "\n";

			$o .= silo::display_children($children, $post, 0);

			$o .= '</ul>' . "\n";

			$o .= $args['after_widget'];

		}

		if ( is_writable(ABSPATH . 'wp-content') )
		{
			@mkdir(ABSPATH . 'wp-content/cache');
			@mkdir(ABSPATH . 'wp-content/cache/silo-pages');

			@chmod(ABSPATH . 'wp-content/cache', 0777);
			@chmod(ABSPATH . 'wp-content/cache/silo-pages', 0777);

			$fp = @fopen(ABSPATH . 'wp-content/cache/silo-pages/' . $cache_file, "w+");
			@fwrite($fp, $o);
			@fclose($fp);

			@chmod(ABSPATH . 'wp-content/cache/silo-pages/' . $cache_file, 0666);
		}

		echo $o;
	} # display_pages_widget()


	#
	# display_children()
	#

	function display_children(&$children, &$post, $parent_ID = 0)
	{
		foreach ( $children[$parent_ID] as $child )
		{
			$o .= '<li>';

			if ( $child->ID == $post->ID )
			{
				$o .= $child->post_title;
			}
			else
			{
				$o .= '<a'
						. ' href="' . apply_filters('the_permalink', get_permalink($child->ID)) . '"'
						. ' title="' . $child->post_title . '"'
						. '>'
					. $child->post_title
					. '</a>';
			}

			if ( $children[$child->ID] )
			{
				$o .= "\n"
					. '<ul>' . "\n";

				$o .= silo::display_children($children, $post, $child->ID);

				$o .= '</ul>' . "\n";
			}

			$o .= '</li>' . "\n";
		}

		return $o;
	} # display_children()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_sidebar_widget('Silo Pages', array('silo', 'display_pages_widget'));
		}
	} # widgetize()


	#
	# flush_cache()
	#

	function flush_cache($id = null)
	{
		if ( $files = glob(ABSPATH . 'wp-content/cache/silo-pages/*') )
		{
			foreach ( $files as $file )
			{
				@unlink($file);
			}
		}

		return $id;
	} # flush_cache()
} # silo

silo::init();


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/silo-admin.php';
}
?>