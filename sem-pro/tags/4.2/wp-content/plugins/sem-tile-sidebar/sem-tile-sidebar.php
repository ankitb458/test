<?php
/*
Plugin Name: Sidebar Tile
Plugin URI: http://www.semiologic.com/software/sidebar-tile/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/sidebar-tile/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Displays contents of the page with a slug of 'sidebar' into your sidebar. To use, call the_sidebar_tile(); where you want the tile to appear.
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


load_plugin_textdomain('sem-tile-sidebar');

if ( !defined('sem_regexp_pb') )
{
	define('sem_regexp_pb', "/(\\/|\\\|\*|\?|\+|\.|\^|\\$|\(|\)|\[|\]|\||\{|\})/");
}
if ( !defined('sem_regexp_fix') )
{
	define('sem_regexp_fix', "\\\\$1");
}
if ( !defined('sem_cache_path') )
{
	define('sem_cache_path', ABSPATH . 'wp-content/cache/'); # same as wp-cache
}
if ( !defined('sem_cache_timeout') )
{
	define('sem_cache_timeout', 3600); # one hour
}


class sem_sidebar_tile
{
	#
	# Variables
	#

	var $params = array(
			'page_id' => false,			# integer, set to false to disable
			'page_slug' => 'sidebar'	# string, set to false to disable
			);

	var $cache;
	var $tile;


	#
	# Constructor
	#

	function sem_sidebar_tile()
	{
		$params = get_settings('sem_sidebar_tile_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			update_option('sem_sidebar_tile_params', $this->params);
		}

		if ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
			&& ( $_SERVER['REQUEST_METHOD'] == 'POST' )
			)
		{
			$this->flush_cache();
		}
		else
		{
			$this->cache = get_settings('sem_sidebar_tile_cache');
		}

		add_action('wp_list_pages', array(&$this, 'change_page_list'));

		add_action('publish_post', array(&$this, 'flush_cache'), 0);
		add_action('save_post', array(&$this, 'flush_cache'), 0);
		add_action('edit_post', array(&$this, 'flush_cache'), 0);
		add_action('delete_post', array(&$this, 'flush_cache'), 0);
		add_action('publish_phone', array(&$this, 'flush_cache'), 0);
		add_action('init', array(&$this, 'init'));
	} # end sem_sidebar_tile()


	#
	# init()
	#

	function init()
	{
		global $wpdb;

		if ( $this->cache != '' )
		{
			if ( $this->cache['page_id'] != 0 )
			{
				$this->tile =& $this->cache['tile'];
				define('sem_sidebar_tile_id', intval($this->cache['page_id']));
			}
			else
			{
				define('sem_sidebar_tile_id', false);
			}
		}
		elseif ( $this->params['page_id'] )
		{
			$this->tile = $wpdb->get_row("
				SELECT
					posts.*
				FROM
					$wpdb->posts as posts
				WHERE
					posts.post_status = 'static'
					AND posts.ID = " . intval($this->params['page_id']) . "
				LIMIT 1
				");
		}
		elseif ( $this->params['page_slug'] )
		{
			$this->tile = $wpdb->get_row("
				SELECT
					posts.*
				FROM
					$wpdb->posts as posts
				WHERE
					posts.post_status = 'static'
					AND posts.post_name = '" . addslashes($this->params['page_slug']) . "'
				LIMIT 1
				");

			if ( !isset($this->tile) && ( $this->params['page_slug'] != 'sidebar' ) )
			{
				$this->params = array(
					'page_id' => false,
					'page_slug' => 'sidebar'
					);

				update_option('sem_sidebar_tile_params', $this->params);
			}
		}

		if ( !defined('sem_sidebar_tile_id') )
		{
			if ( isset($this->tile) && $this->tile->ID )
			{
				$posts = array($this->tile);
				if ( function_exists('update_post_cache') )
				{
					update_post_cache($posts);
				}
				if ( function_exists('update_page_cache') )
				{
					update_page_cache($posts);
				}


				define('sem_sidebar_tile_id', $this->tile->ID);

				$this->cache = array('page_id' => sem_sidebar_tile_id,
						'tile' => &$this->tile);
				update_option('sem_sidebar_tile_cache', $this->cache);
			}
			else
			{
				define('sem_sidebar_tile_id', false);

				$this->cache = array('page_id' => 0);
				update_option('sem_sidebar_tile_cache', $this->cache);
			}
		}

		if ( sem_sidebar_tile_id )
		{
			$to_cache = array($this->tile);

			if ( function_exists('update_post_cache') )
			{
				update_post_cache($to_cache);
			}
			if ( function_exists('update_page_cache') )
			{
				update_page_cache($to_cache);
			}
		}
	} # end init()


	#
	# flush_cache()
	#

	function flush_cache()
	{
		$this->cache = '';
		if ( is_writable(sem_cache_path) )
		{
			$cache_files = glob(sem_cache_path . "*");

			if ( $cache_files )
			{
				foreach ( $cache_files as $cache_file )
				{
					if ( is_file($cache_file) && is_writable($cache_file) )
					{
						unlink( $cache_file );
					}
				}
			}
		}
		update_option('sem_sidebar_tile_cache', $this->cache);
	} # end flush_cache()


	#
	# change_page_list()
	#

	function change_page_list($page_list = '')
	{
		if ( !sem_sidebar_tile_id )
		{
			return $page_list;
		}

		$permalink = get_permalink(sem_sidebar_tile_id);
		$permalink = preg_replace(sem_regexp_pb, sem_regexp_fix, $permalink);

		$page_list = preg_replace("/<li class=\"page_item\"><a href=\"" . $permalink . "\" title=\"[^\"]*\">.*<\/a>\n?<\/li>/sU", "", $page_list);

		return $page_list;
	} # end change_page_list()


	#
	# display()
	#

	function display()
	{
		global $wpdb;
		global $user_ID;
		global $cur_post;

		$o = "";

		if ( !sem_sidebar_tile_id )
		{
			return $o;
		}

		if ( user_can_edit_post($user_ID, sem_sidebar_tile_id) )
		{
			$edit_link = "<a href=\""
							. trailingslashit(get_settings('siteurl')) . "wp-admin/post.php?action=edit&amp;post=" . sem_sidebar_tile_id
							. "\" class=\"edit_entry\">"
						. __('Edit Sidebar tile', 'sem-tile-sidebar')
						. "</a>";
		}
		else
		{
			$edit_link = false;
		}

		if ( function_exists('sem_smart_link_cache_posts') )
		{
			$to_cache = array($this->tile);
			$cur_post = $this->tile;
			sem_smart_link_cache_posts($to_cache);
		}

		$o = apply_filters('the_content', $this->tile->post_content)
			. ( $edit_link
				? (" <div class=\"actions\">"
					. $edit_link
					. "</div>\n"
					)
				: ""
				);

		return $o;
	} # end display()
} # end sem_sidebar_tile

$sem_sidebar_tile =& new sem_sidebar_tile();


#
# Template tags
#

function the_sidebar_tile()
{
	global $sem_sidebar_tile;

	echo $sem_sidebar_tile->display();
} # end the_sidebar_tile()


########################
#
# Backward compatibility
#

function sem_sidebar_tile()
{
	the_sidebar_tile();
} # end sem_sidebar_tile()
?>