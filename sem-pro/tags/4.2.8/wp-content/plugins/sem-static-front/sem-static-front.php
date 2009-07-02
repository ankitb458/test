<?php
/*
Plugin Name: Static Front Page
Plugin URI: http://www.semiologic.com/software/static-front/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/static-front/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Sticks the page with a slug of 'home' to your front page.
Author: Denis de Bernardy
Version: 3.1
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


if ( !defined('use_post_type_fixed') )
{
	define(
		'use_post_type_fixed',
			version_compare(
				'2.1',
				$GLOBALS['wp_version'], '<='
				)
			||
			function_exists('get_site_option')
		);
}

class sem_static_front
{
	#
	# init()
	#

	function init()
	{
		global $wpdb;

		# reset WP functionality
		if ( get_option('show_on_front') != 'posts' )
		{
			update_option('show_on_front', 'posts');
			update_option('page_on_front', '');
			update_option('page_for_posts', '');
		}

		$cache = array();
		$params = sem_static_front::get_params();

		$cache['home_id'] = $wpdb->get_var("
				SELECT
					posts.ID
				FROM
					$wpdb->posts as posts
				WHERE
				" . ( use_post_type_fixed
					? "posts.post_status = 'publish' AND posts.post_type = 'page'
				"
					: "posts.post_status = 'static'
				"
					)
				. "
					AND posts.post_name = '" . addslashes($params['home_slug']) . "'
				LIMIT 1
			");

		$cache['blog_id'] = $wpdb->get_var("
				SELECT
					posts.ID
				FROM
					$wpdb->posts as posts
				WHERE
				" . ( use_post_type_fixed
					? "posts.post_status = 'publish' AND posts.post_type = 'page'
				"
					: "posts.post_status = 'static'
				"
					)
				. "
					AND posts.post_name = '" . addslashes($params['blog_slug']) . "'
				LIMIT 1
			");

		#$cache['home_id'] = isset($cache['home_id']) ? intval($cache['home_id']) : false;
		#$cache['blog_id'] = isset($cache['blog_id']) ? intval($cache['blog_id']) : false;

		define('sem_home_page_id', $cache['home_id']);
		define('sem_blog_page_id', $cache['blog_id']);

		#echo '<pre>';
		#var_dump($cache);
		#echo '</pre>';

		#die();

		add_action('posts_where', array('sem_static_front', 'posts_where'));
		add_action('template_redirect', array('sem_static_front', 'disable'), -1000000);

		add_action('home_template', array('sem_static_front', 'home_template'), 100);
		add_filter('page_link', array('sem_static_front', 'home_permalink'), 10, 2);
		add_filter('wp_list_pages', array('sem_static_front', 'hide_home_page'));

		add_action('get_pages', array('sem_static_front', 'kill_wp_option_screen'));
		add_action('option_show_on_front', array('sem_static_front', 'show_posts_on_front'));

		add_action('the_posts', array('sem_static_front', 'blog_page'));
		add_action('option_page_for_posts', array('sem_static_front', 'page_for_posts'));
	} # init()


	#
	# get_params()
	#

	function get_params()
	{
		return array(
			'home_slug' => 'home',
			'blog_slug' => 'blog'
			);
	} # get_params()


	#
	# is_home()
	#

	function is_home()
	{
		if ( !defined('sem_static_front_is_home') )
		{
			$paged = get_query_var('paged');

			define('sem_static_front_is_home',
				is_home() && sem_home_page_id && !( $paged && $paged > 1 )
				);

			if ( sem_static_front_is_home && !defined('sem_static_front_is_blog') )
			{
				define('sem_static_front_is_blog', false);
			}
		}

		return sem_static_front_is_home;
	} # is_home()


	#
	# is_blog()
	#

	function is_blog()
	{
		if ( !defined('sem_static_front_is_blog') )
		{
			$params = sem_static_front::get_params();

			define('sem_static_front_is_blog',
				is_page() && sem_blog_page_id
				&& ( get_query_var('pagename') == $params['blog_slug']
					|| get_query_var('page_id') == sem_blog_page_id
					)
				);
		}

		return sem_static_front_is_blog;
	} # is_blog()


	#
	# posts_where()
	#

	function posts_where($posts_where)
	{
		if ( sem_static_front::is_home() )
		{
			$posts_where = " AND ID = " . intval(sem_home_page_id) . " ";
		}
		elseif ( sem_static_front::is_blog() )
		{
			#$posts_where = " AND 1 = 0 ";
		}

		return $posts_where;
	} # posts_where()


	#
	# disable()
	#

	function disable()
	{
		remove_action('posts_where', array('sem_static_front', 'posts_where'));
		remove_action('the_posts', array('sem_static_front', 'blog_page'));
	} # disable()


	#
	# home_template()
	#

	function home_template($template)
	{
		global $wp_query;

		if ( is_home() )
	    {
			$id = $wp_query->post->ID;

			if ( $id == sem_home_page_id )
			{
				$template = get_post_meta($id, '_wp_page_template', true);
			}

			if ( !isset($template) || 'default' == $template )
			{
				$template = '';
			}

			if ( !empty($template) && file_exists(TEMPLATEPATH . '/' . $template) )
			{
				$template = TEMPLATEPATH . '/' . $template;
			}
			elseif ( file_exists(TEMPLATEPATH .  '/home.php') && $id == sem_home_page_id )
			{
				$template = TEMPLATEPATH .  '/home.php';
			}
			elseif ( file_exists(TEMPLATEPATH .  '/blog.php') )
			{
				$template = TEMPLATEPATH .  '/blog.php';
			}
			elseif ( file_exists(TEMPLATEPATH .  '/page.php') )
			{
				$template = TEMPLATEPATH .  '/page.php';
			}
			elseif ( file_exists(TEMPLATEPATH .  '/index.php') )
			{
				$template = TEMPLATEPATH .  '/index.php';
			}
	    }

	    return $template;
	} # end home_template()


	#
	# home_permalink()
	#

	function home_permalink($link = '', $id = '')
	{
		if ( !$id )
		{
			return $link;
		}

		if ( sem_home_page_id && ( $id == sem_home_page_id ) )
		{
			$link = get_option('home');
		}

		return $link;
	} # home_permalink()


	#
	# hide_home_page()
	#

	function hide_home_page($pages)
	{
		$pages = preg_replace(
			"`
				<li
					\s+class=\"page_item\"
					>
					<a
						\s+
						(?:[^>]+\s+)?
						href=\"" . get_settings('home') . "\"
						(?:\s+[^>]+)?
						>[^<]*
					</a>
					\s*
				</li>
			`iUsx",
			"",
			$pages
			);

		return $pages;
	} # hide_home_page()


	#
	# kill_wp_option_screen()
	#

	function kill_wp_option_screen($pages)
	{
		if ( is_admin() && strpos($_SERVER['REQUEST_URI'], '/options-reading.php') )
		{
			return '';
		}
		else
		{
			return $pages;
		}
	} # kill_wp_option_screen()


	#
	# show_posts_on_front()
	#

	function show_posts_on_front($option)
	{
		return 'posts';
	} # show_posts_on_front()


	#
	# show_page_on_front()
	#

	function show_page_on_front($option)
	{
		return 'page';
	} # show_page_on_front()


	#
	# page_for_posts()
	#

	function page_for_posts($option)
	{
		return sem_blog_page_id;
	} # page_for_posts()


	#
	# blog_page()
	#

	function blog_page($posts)
	{
		global $wp_query;
		global $wp_rewrite;

		if ( sem_static_front::is_blog() )
		{
			sem_static_front::disable();

			remove_action('option_show_on_front', array('sem_static_front', 'show_posts_on_front'));
			add_action('option_show_on_front', array('sem_static_front', 'show_page_on_front'));
			add_action('permalink_redirect_skip', array('sem_static_front', 'skip_redirect'));

			$wp_query->is_singular = false;
			$wp_query->is_page = false;
			$wp_query->is_home = true;
			$wp_query->is_posts_page = true;

			$posts = query_posts('paged=' . intval(get_query_var('paged')));
		}

		return $posts;
	} # blog_page()


	#
	# skip_redirect()
	#

	function skip_redirect($no_redirect)
	{
		$skip = array(
			'/' . str_replace(
				trailingslashit(get_option('home')),
				'',
				get_permalink(sem_blog_page_id)
				)
			);

		return array_merge(
			(array) $no_redirect,
			$skip
			);
	} # skip_redirect()
} # sem_static_front

sem_static_front::init();
?>