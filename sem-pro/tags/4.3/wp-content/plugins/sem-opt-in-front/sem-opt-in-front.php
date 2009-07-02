<?php
/*
Plugin Name: Opt-in Front Page
Plugin URI: http://www.semiologic.com/software/publishing/opt-in-front/
Description: Restricts the access to your front page on an opt-in basis: Only posts within the category with a slug of 'blog' will be displayed on your front page. <a href="?action=autoinstall_opt_in_front">Autoinstall</a> (creates a Blog category and puts every post in it)
Author: Denis de Bernardy
Version: 2.9
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class sem_opt_in_front
{
	#
	# Variables
	#

	var $params = array(
			'cat_id' => false,		# integer, set to false to disable
			'cat_slug' => 'blog'	# string, set to false to disable
			);

	var $cache;


	#
	# Constructor
	#

	function sem_opt_in_front()
	{
		$params = get_settings('sem_opt_in_front_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			update_option('sem_opt_in_front_params', $this->params);
		}

		if ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
			&& ( $_SERVER['REQUEST_METHOD'] == 'POST' )
			)
		{
			$this->flush_cache();
		}
		else
		{
			$this->cache = get_settings('sem_opt_in_front_cache');
		}

		if ( isset($_GET['action']) && $_GET['action'] == 'autoinstall_opt_in_front' )
		{
			add_action('init', array(&$this, 'autoinstall'));
		}

		add_action('posts_where', array(&$this, 'change_posts_where'), 11);
		add_action('posts_join', array(&$this, 'change_posts_join'), 11);
		add_filter('category_link', array(&$this, 'change_permalink'), 10, 2);
		add_filter('list_cats_exclusions', array(&$this, 'hide_main_cat'));

		add_action('publish_post', array(&$this, 'flush_cache'), 0);
		add_action('save_post', array(&$this, 'flush_cache'), 0);
		add_action('edit_post', array(&$this, 'flush_cache'), 0);
		add_action('delete_post', array(&$this, 'flush_cache'), 0);
		add_action('publish_phone', array(&$this, 'flush_cache'), 0);
		add_action('add_category', array(&$this, 'flush_cache'), 0);
		add_action('delete_category', array(&$this, 'flush_cache'), 0);
	} # end sem_opt_in_front()


	#
	# init()
	#

	function init()
	{
		global $wpdb;
		global $sem_static_front;

		if ( isset($sem_static_front) && !defined('sem_home_page_id') )
		{
			$sem_static_front->init();
		}
		elseif ( !class_exists('sem_static_front') )
		{
			define('sem_home_page_id', false);
		}

		if ( $this->cache != '' )
		{
			if ( $this->cache['cat_id'] != 0 )
			{
				define('sem_main_cat_id', intval($this->cache['cat_id']));
			}
			else
			{
				define('sem_main_cat_id', false);
			}
		}
		elseif ( $this->params['cat_id'] )
		{
			$this->params['cat_id'] = $wpdb->get_var("
				SELECT
					categories.cat_ID
				FROM
					$wpdb->categories as categories
				WHERE
					categories.cat_ID = " . intval($this->params['cat_id']) . "
				LIMIT 1
				");
		}
		elseif ( $this->params['cat_slug'] )
		{
			$this->params['cat_id'] = $wpdb->get_var("
				SELECT
					categories.cat_ID
				FROM
					$wpdb->categories as categories
				WHERE
					categories.category_nicename = '" . addslashes($this->params['cat_slug']) . "'
				LIMIT 1
				");

			if ( !isset($this->params['cat_id']) && ( $this->params['cat_slug'] != 'blog' ) )
			{
				$this->params = array(
					'cat_id' => false,
					'cat_slug' => 'blog'
					);

				update_option('sem_opt_in_front_params', $this->params);
			}
		}

		if ( !defined('sem_main_cat_id') )
		{
			if ( isset($this->params['cat_id']) && $this->params['cat_id'] )
			{
				define('sem_main_cat_id', $this->params['cat_id']);

				$this->cache = array('cat_id' => $this->params['cat_id']);
				update_option('sem_opt_in_front_cache', $this->cache);
			}
			else
			{
				define('sem_main_cat_id', false);

				$this->cache = array('cat_id' => 0);
				update_option('sem_opt_in_front_cache', $this->cache);
			}
		}
	} # end init()


	#
	# flush_cache()
	#

	function flush_cache()
	{
		$this->cache = '';
		update_option('sem_opt_in_front_cache', $this->cache);
	} # end flush_cache()


	#
	# change_posts_where()
	#

	function change_posts_where($posts_where = '')
	{
		# Do nothing if we're not on the front page or its feed
		if ( !( is_home() || ( is_feed() && !( is_archive() || is_single() || is_page() || is_search() ) ) ) )
		{
			return $posts_where;
		}

		if ( !defined('sem_main_cat_id') )
		{
			$this->init();
		}

		if ( is_home() && sem_home_page_id )
		{
			return $posts_where;
		}

		if ( sem_main_cat_id )
		{
			$posts_where .= " AND sem_post2cat.category_id = " . intval(sem_main_cat_id) . " ";
		}

		return $posts_where;
	} # end change_posts_where()


	#
	# change_posts_join()
	#

	function change_posts_join($posts_join = '')
	{
		global $wpdb;

		# Do nothing if we're not on the front page or its feed
		if ( !( is_home() || ( is_feed() && !( is_archive() || is_single() || is_page() || is_search() ) ) ) )
		{
			return $posts_join;
		}

		if ( !defined('sem_main_cat_id') )
		{
			$this->init();
		}

		if ( is_home() && sem_home_page_id )
		{
			return $posts_join;
		}

		if ( sem_main_cat_id )
		{
			$posts_join .=  " LEFT JOIN $wpdb->post2cat AS sem_post2cat ON sem_post2cat.post_id = ID ";
		}

		return $posts_join;
	} # end change_posts_join()


	#
	# change_permalink()
	#

	function change_permalink($link = '', $id = '')
	{
		if ( $id == '' )
		{
			return $link;
		}

		if ( !defined('sem_main_cat_id') )
		{
			$this->init();
		}

		if ( ( !defined('sem_home_page_id') || !sem_home_page_id )
			&& ( sem_main_cat_id && ( $id == sem_main_cat_id ) )
			)
		{
			$link = get_settings('home');
		}

		return $link;
	} # end change_permalink()


	#
	# autoinstall()
	#

	function autoinstall()
	{
		global $wpdb;

		if ( current_user_can('administrator') )
		{
			$main_cat_id = $wpdb->get_var("SELECT cat_ID FROM $wpdb->categories WHERE category_nicename = 'blog'");

		    if ( !isset($main_cat_id) )
		    {
				$wpdb->query("INSERT INTO $wpdb->categories ( cat_name, category_nicename ) VALUES ( 'Blog', 'blog' )");

				$main_cat_id = $wpdb->insert_id;
			}

			$all_posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish'");

			if ( isset($all_posts) )
			{
				foreach ( $all_posts as $my_post )
				{
					$rel_id = $wpdb->get_var("SELECT rel_id FROM $wpdb->post2cat WHERE post_id = $my_post->ID and category_id = $main_cat_id");
					if ( !isset($rel_id) )
					{
					    $wpdb->query("INSERT INTO $wpdb->post2cat (post_id, category_id) VALUES ( $my_post->ID, $main_cat_id )");
					}
				}

				$post_nb = sizeof($all_posts);

				$wpdb->query("UPDATE $wpdb->categories SET category_count = " . intval($post_nb) . " WHERE cat_ID = " . intval($main_cat_id));
			}

			if ( function_exists('regen_theme_nav_menu_cache') )
			{
				regen_theme_nav_menu_cache();
			}

			echo "<p>Opt-in front page autoinstall successful. <a href=\"" . get_settings('siteurl') . "\">Back to the blog</a>.</p>";
		}
	} # end autoinstall()


	#
	# hide_main_cat()
	#

	function hide_main_cat($excludes)
	{
		if ( !defined('sem_main_cat_id') )
		{
			$this->init();
		}

		if ( sem_main_cat_id )
		{
			$excludes .= " AND cat_ID <> " . intval(sem_main_cat_id);
		}

		#echo '<pre>';
		#var_dump($excludes);
		#echo '</pre>';

		return $excludes;
	} # end hide_main_cat()
} # end sem_opt_in_front

$sem_opt_in_front =& new sem_opt_in_front();
?>