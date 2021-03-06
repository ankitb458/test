<?php
/*
Plugin Name: Search Reloaded
Plugin URI: http://www.semiologic.com/software/wp-tweaks/search-reloaded/
Description: Replaces the default WordPress search engine with a rudimentary one that orders posts by relevance.
Author: Denis de Bernardy
Version: 3.0 RC
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: search_reloaded
Update Package: http://www.semiologic.com/media/software/wp-tweaks/search-reloaded/search-reloaded.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class search_reloaded
{
	#
	# init()
	#
	
	function init()
	{	
		add_filter('sem_api_key_protected', array('search_reloaded', 'sem_api_key_protected'));

		if ( !get_option('search_reloaded_installed') )
		{
			search_reloaded::install();
		}
		
		add_action('save_post', array('search_reloaded', 'index_post'));
		
		if ( !is_admin() )
		{
			if ( !get_option('search_reloaded_indexed') )
			{
				add_action('shutdown', array('search_reloaded', 'index_posts'));
			}

			#add_filter('posts_request', array('search_reloaded', 'posts_request'));
			
			add_filter('posts_fields', array('search_reloaded', 'posts_fields'));
			add_filter('posts_where', array('search_reloaded', 'posts_where'));
			add_filter('posts_orderby', array('search_reloaded', 'posts_orderby'));
		}
	} # init()


	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/wp-tweaks/search-reloaded/search-reloaded.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# posts_request()
	#
	
	function posts_request($str)
	{
		global $wpdb;
		
		dump($str);
		dump($wpdb->get_results($str));
		
		return $str;
	} # posts_request()
	
	
	#
	# posts_fields()
	#
	
	function posts_fields($str)
	{
		if ( !is_search() ) return $str;
		
		global $wpdb;
		global $wp_query;
		
		$qs = implode(' ', $wp_query->query_vars['search_terms']);

		$str = " $wpdb->posts.*,"
			. " ( "
			. " IF ( MATCH ($wpdb->posts.search_title, $wpdb->posts.search_keywords)"
				. " AGAINST ('" . $wpdb->escape($qs) . "'),"
				. " MATCH ($wpdb->posts.search_title, $wpdb->posts.search_keywords)"
				. " AGAINST ('" . $wpdb->escape($qs) . "'),"
				. " 0 )"
			. " + IF ( MATCH ($wpdb->posts.search_title, $wpdb->posts.search_keywords, $wpdb->posts.search_content)"
				. " AGAINST ('" . $wpdb->escape($qs) . "'),"
				. " MATCH ($wpdb->posts.search_title, $wpdb->posts.search_keywords, $wpdb->posts.search_content)"
				. " AGAINST ('" . $wpdb->escape($qs) . "'),"
				. " 0 )"
			. " ) "
			. " * IF ( $wpdb->posts.post_type = 'page',"
				. " 1.5,"
				. " 1 ) as search_score";
		
		return $str;
	} # posts_fields()
	
	
	#
	# posts_where()
	#
	
	function posts_where($str)
	{
		if ( !is_search() ) return $str;
		
		global $wp_query;
		global $wpdb;
		
		$qs = implode(' ', $wp_query->query_vars['search_terms']);
		
		$str = " AND"
			. " MATCH ($wpdb->posts.search_title, $wpdb->posts.search_keywords, $wpdb->posts.search_content)"
			. " AGAINST ('" . $wpdb->escape($qs) . "')"
			. " AND (wp_posts.post_status = 'publish' OR wp_posts.post_author = 1 AND wp_posts.post_status = 'private')";
		
		return $str;
	} # posts_where()
	
	
	#
	# posts_orderby()
	#
	
	function posts_orderby($str)
	{
		if ( !is_search() ) return $str;
		
		global $wpdb;
		
		$str = 'search_score DESC';
		
		return $str;
	} # posts_orderby()
	
	
	#
	# install()
	#
	
	function install()
	{
		global $wpdb;
		
		# enforce MyISAM
		$wpdb->query("ALTER TABLE `$wpdb->posts` ENGINE = MYISAM");
		
		# add three columns
		$wpdb->query("
			ALTER TABLE $wpdb->posts ADD COLUMN `search_title` text NOT NULL DEFAULT '';
			");

		$wpdb->query("
			ALTER TABLE $wpdb->posts ADD COLUMN `search_keywords` text NOT NULL DEFAULT '';
			");

		$wpdb->query("
			ALTER TABLE $wpdb->posts ADD COLUMN `search_content` longtext NOT NULL DEFAULT '';
			");
		
		# and two full text indexes
		$wpdb->query("
			ALTER TABLE $wpdb->posts ADD FULLTEXT `search_title` ( `search_title`, `search_keywords`);
			");

		$wpdb->query("
			ALTER TABLE $wpdb->posts ADD FULLTEXT `search_content` ( `search_title`, `search_keywords`, `search_content`);
			");
		
		update_option('search_reloaded_installed', 1);
	} # install()
	
	
	#
	# index_post()
	#
	
	function index_post($post_id)
	{
		$post_id = intval($post_id);
		
		if ( $post_id <= 0 ) return;
		
		$post = get_post($post_id);
		
		if ( !$post
			|| $post->post_status != 'publish'
			|| !in_array($post->post_type, array('post', 'page', 'attachment'))
			)
		{
			return;
		}
		
		global $wpdb;
		
		if ( is_admin() )
		{
			# some plugins purposly skip outputting anything in the admin area
			
			$wpdb->query("
				UPDATE	$wpdb->posts
				SET		search_title = '',
						search_keywords = '',
						search_content = ''
				WHERE	ID = $post_id
				");
			
			update_option('search_reloaded_indexed', 0);
			
			return;
		}
		
		global $wp_query;

		setup_postdata($post);
		$wp_query->in_the_loop = true;
		
		$title = $post->post_title;
		$keywords = implode(', ', search_reloaded::get_keywords($post_id, $post->post_type == 'post'));
		
		$content = trim($post->post_content)
			? apply_filters('the_content', $post->post_content)
			: apply_filters('the_content', $post->post_excerpt);
		
		foreach ( array('title', 'keywords', 'content') as $var )
		{
			foreach ( array('script', 'style') as $junk )
			{
				$$var = preg_replace("/
					<\s*$junk\b
					.*
					<\s*\/\s*$junk\s*>
					/isUx", '', $$var);
			}
			
			$$var = strip_tags($$var);
			$$var = html_entity_decode($$var, ENT_NOQUOTES);
			$$var = str_replace("\r", "\n", $$var);
			$$var = trim($$var);
		}
		
		#dump($content);
		
		$wp_query->in_the_loop = false;
		
		$wpdb->query("
			UPDATE	$wpdb->posts
			SET		search_title = '" . $wpdb->escape($title) . "',
					search_keywords = '" . $wpdb->escape($keywords) . "',
					search_content = '" . $wpdb->escape($content) . "'
			WHERE	ID = $post_id
			");
	} # index_post()
	
	
	#
	# get_keywords()
	#
	
	function get_keywords($post_id = null, $get_categories = false)
	{
		if ( !defined('highlights_cat_id') )
		{
			global $wpdb;
			
			$highlights_cat_id = $wpdb->get_var("
				SELECT
					term_id
				FROM
					$wpdb->terms
				WHERE
					slug = 'highlights'
				");

			define('highlights_cat_id', $highlights_cat_id ? intval($highlights_cat_id) : false);
		}
		
		$keywords = array();
		$exclude = array();
		
		if ( defined('main_cat_id') && main_cat_id )
		{
			$exclude[] = main_cat_id;
		}
		
		if ( defined('highlights_cat_id') && highlights_cat_id )
		{
			$exclude[] = highlights_cat_id;
		}
		
		if ( $get_categories
			&& ( $cats = get_the_category($post_id) )
			)
		{
			foreach ( $cats as $cat )
			{
				if ( !in_array($cat->term_id, $exclude) )
				{
					$keywords[] = $cat->name;
				}
			}
		}

		if ( $tags = get_the_tags($post_id) )
		{
			foreach ( $tags as $tag )
			{
				$keywords[] = $tag->name;
			}
		}
		
		$keywords = array_map('strtolower', $keywords);
		$keywords = array_unique($keywords);

		sort($keywords);
		
		return $keywords;
	} # get_keywords()
	
	
	#
	# index_posts()
	#
	
	function index_posts()
	{
		if ( !is_home() || is_singular() || is_archive() ) return;
		
		global $wpdb;
		
		$post_ids = (array) $wpdb->get_col("
			SELECT	ID
			FROM	$wpdb->posts
			WHERE	post_status = 'publish'
			AND		post_type IN ('post', 'page', 'attachment')
			AND		search_title = ''
			LIMIT 50
			;");
		
		if ( $post_ids )
		{
			foreach ( $post_ids as $post_id )
			{
				#dump($post_id);
				search_reloaded::index_post($post_id);
			}
			
			update_option('search_reloaded_indexed', 0);
		}
		else
		{
			update_option('search_reloaded_indexed', 1);
		}
	} # index_posts()
} # search_reloaded

search_reloaded::init();
?>