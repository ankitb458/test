<?php
/*
Plugin Name: Static Front Page
Plugin URI: http://www.semiologic.com/software/static-front/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/static-front/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Sticks the page with a slug of 'home' to your front page.
Author: Denis de Bernardy
Version: 2.6
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class sem_static_front
{
	#
	# Variables
	#

	var $params = array(
			'page_id' => false,		# integer, set to false to disable
			'page_slug' => 'home'	# string, set to false to disable
			);


	#
	# Constructor
	#

	function sem_static_front()
	{
		$params = get_settings('sem_static_front_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			update_option('sem_static_front_params', $this->params);
		}

		if ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
			&& ( $_SERVER['REQUEST_METHOD'] == 'POST' )
			)
		{
			$this->flush_cache();
		}
		else
		{
			$this->cache = get_settings('sem_static_front_cache');
		}

		add_action('home_template', array(&$this, 'get_template'), 100);

		add_action('posts_where', array(&$this, 'change_posts_where'), 10);
		add_filter('page_link', array(&$this, 'change_permalink'), 10, 2);
		add_filter('wp_list_pages', array(&$this, 'hide_home_page'));

		add_action('publish_post', array(&$this, 'flush_cache'), 0);
		add_action('save_post', array(&$this, 'flush_cache'), 0);
		add_action('edit_post', array(&$this, 'flush_cache'), 0);
		add_action('delete_post', array(&$this, 'flush_cache'), 0);
		add_action('publish_phone', array(&$this, 'flush_cache'), 0);
		add_action('template_redirect', array(&$this, 'disable'));
	} # end sem_static_front()


	#
	# disable()
	#

	function disable()
	{
		remove_action('posts_where', array(&$this, 'change_posts_where'), 10);
	} # end disable()


	#
	# init()
	#

	function init()
	{
		global $wpdb;

#echo '<pre>';
#var_dump(get_settings('sem_static_front_params'));
#var_dump($this->cache, get_settings('sem_static_front_cache'));
#echo '</pre>';

		if ( $this->cache != '' )
		{
			if ( $this->cache['page_id'] != 0 )
			{
				define('sem_home_page_id', intval($this->cache['page_id']));
			}
			else
			{
				define('sem_home_page_id', false);
			}
		}
		elseif ( $this->params['page_id'] )
		{
			$sql = "
				SELECT
					posts.ID
				FROM
					$wpdb->posts as posts
				WHERE
				" . ( function_exists('get_site_option')
					? "posts.post_status = 'publish' AND posts.post_type = 'page'
				"
					: "posts.post_status = 'static'
				"
					)
				. "
					AND posts.ID = " . intval($this->params['page_id']) . "
				LIMIT 1
				";
			$this->params['page_id'] = $wpdb->get_var($sql);
		}
		elseif ( $this->params['page_slug'] )
		{
			$this->params['page_id'] = $wpdb->get_var("
				SELECT
					posts.ID
				FROM
					$wpdb->posts as posts
				WHERE
				" . ( function_exists('get_site_option')
					? "posts.post_status = 'publish' AND posts.post_type = 'page'
				"
					: "posts.post_status = 'static'
				"
					)
				. "
					AND posts.post_name = '" . addslashes($this->params['page_slug']) . "'
				LIMIT 1
				");

			if ( !isset($this->params['page_id']) && ( $this->params['page_slug'] != 'home' ) )
			{
				$this->params = array(
					'page_id' => false,
					'page_slug' => 'home'
					);

				update_option('sem_static_front_params', $this->params);
			}
		}

		if ( !defined('sem_home_page_id') )
		{
			if ( isset($this->params['page_id']) && $this->params['page_id'] )
			{
				define('sem_home_page_id', $this->params['page_id']);

				$this->cache = array('page_id' => $this->params['page_id']);
				update_option('sem_static_front_cache', $this->cache);
			}
			else
			{
				define('sem_home_page_id', false);

				$this->cache = array('page_id' => 0);
				update_option('sem_static_front_cache', $this->cache);
			}
		}
	} # end init()


	#
	# flush_cache()
	#

	function flush_cache()
	{
		$this->cache = '';
		update_option('sem_static_front_cache', $this->cache);
	} # end flush_cache()


	#
	# change_posts_where()
	#

	function change_posts_where($posts_where = '')
	{
		if ( !defined('sem_home_page_id') )
		{
			$this->init();
		}

		if ( is_home() && sem_home_page_id )
		{
			$posts_where = " AND ID = " . intval(sem_home_page_id) . " ";
		}

		return $posts_where;
	} # end change_posts_where()


	#
	# change_permalink()
	#

	function change_permalink($link = '', $id = '')
	{
		if ( $id == '' )
		{
			return $link;
		}

		if ( !defined('sem_home_page_id') )
		{
			$this->init();
		}

		if ( sem_home_page_id && ( $id == sem_home_page_id ) )
		{
			$link = get_settings('home');
		}

		return $link;
	} # end change_permalink()


	#
	# get_template()
	#

	function get_template($template)
	{
		global $wp_query;
		global $sem_theme;

		if ( is_home() )
	    {
			$id = $wp_query->post->ID;
			$template = get_post_meta($id, '_wp_page_template', true);

			if ( 'default' == $template )
			{
				$template = '';
			}

			if ( !empty($template) && file_exists(TEMPLATEPATH . '/' . $template) )
			{
				$template = TEMPLATEPATH . '/' . $template;
			}
			elseif ( file_exists(TEMPLATEPATH .  '/home.php') )
			{
				$template = TEMPLATEPATH .  '/home.php';
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
	} # end get_template()


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
	} # end hide_home_page()
} # end sem_static_front

$sem_static_front =& new sem_static_front();
?>