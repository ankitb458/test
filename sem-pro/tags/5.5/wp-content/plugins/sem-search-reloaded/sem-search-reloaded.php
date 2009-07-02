<?php
/*
Plugin Name: Search Reloaded
Plugin URI: http://www.semiologic.com/software/wp-fixes/search-reloaded/
Description: Enhances WordPress' default search engine functionality.
Author: Denis de Bernardy
Version: 2.10
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: search_reloaded
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/

class sem_search_reloaded
{
	#
	# Variables
	#


	#
	# Constructor
	#

	function sem_search_reloaded()
	{
		global $wpdb;

		if ( !get_option('posts_have_fulltext_index') )
		{
			$wpdb->query("ALTER TABLE `$wpdb->posts` ENGINE = MYISAM");
			$wpdb->query("ALTER TABLE `$wpdb->posts` ADD FULLTEXT ( `post_title`, `post_content` )");
			update_option('posts_have_fulltext_index', 1);
		}

		if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
		{
			add_filter('posts_where', array('sem_search_reloaded', 'bypass_search'));
			add_filter('the_posts', array('sem_search_reloaded', 'redo_search'));
		}
	} # end sem_search_reloaded()


	#
	# bypass_search()
	#

	function bypass_search($where)
	{
		if ( is_search() && !defined('did_search') )
		{
			$where = " AND 1 = 0 ";
		}

		return $where;
	} # end bypass_search()


	#
	# redo_search()
	#

	function redo_search($the_posts = array())
	{
		global $wpdb;
		global $wp_query;

		if ( is_search() && !defined('did_search') )
		{
			$GLOBALS['sem_s'] = get_query_var('s');

			preg_match_all("/((\w|-)+)/", $GLOBALS['sem_s'], $out);
			$keywords = current($out);

			if ( empty($keywords) )
			{
				return array();
			}

			$query_string = "";
			$keyword_filter = "";

			foreach ( $keywords as $keyword )
			{
				$query_string .= ( $query_string ? " " : "" ) . $keyword;
				$reg_one_present .= ( $reg_one_present ? "|" : "" ) . $keyword;
			}

			$paged = $wp_query->get('paged');
			if (!$paged)
			{
				$paged = 1;
			}
			$posts_per_page = $wp_query->get('posts_per_page');
			if ( !$posts_per_page )
			{
				$posts_per_page = get_option('posts_per_page');
			}
			$offset = ( $paged - 1 ) * $posts_per_page;

			$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

			$search_query = "
				SELECT
					posts.*,
					CASE
						WHEN posts.post_title REGEXP '$reg_one_present'
							THEN 1
							ELSE 0
						END AS keyword_in_title,
					MATCH ( posts.post_title, posts.post_content )
						AGAINST ( '" . addslashes($query_string) . "' ) AS mysql_score
				FROM
					$wpdb->posts as posts
				WHERE
					posts.post_date_gmt <= '" . $now . "'"
					. ( ( defined('sem_home_page_id') && sem_home_page_id )
						? "
					AND posts.ID <> " . intval(sem_home_page_id)
						: ""
						)
					. "
					AND posts.post_password = ''
					AND post_status = 'publish'
					AND ( posts.post_title REGEXP '$reg_one_present' OR posts.post_content REGEXP '$reg_one_present' )
				GROUP BY
					posts.ID
				ORDER BY
					keyword_in_title DESC, mysql_score DESC, posts.post_date DESC
				LIMIT " . intval($offset) . ", ". intval($posts_per_page);

			$request_query = "
				SELECT
					posts.*
				FROM
					$wpdb->posts as posts
				WHERE
					posts.post_date_gmt <= '" . $now . "'"
					. ( ( defined('sem_home_page_id') && sem_home_page_id )
						? "
					AND posts.ID <> " . intval(sem_home_page_id)
						: ""
						)
					. "
					AND posts.post_password = ''
					AND post_status = 'publish'
					AND ( posts.post_title REGEXP '$reg_one_present' OR posts.post_content REGEXP '$reg_one_present' )
				GROUP BY
					posts.ID
				LIMIT " . intval($offset) . ", ". intval($posts_per_page);

			$the_posts = $wpdb->get_results($search_query);
			$GLOBALS['request'] = ' ' . preg_replace("/[\n\r\s]+/", " ", $request_query) . ' ';

			update_post_cache($the_posts);
			update_page_cache($the_posts);

			define('did_search', true);
		}

		return $the_posts;
	} # end redo_search()
} # end sem_search_reloaded()


########################
#
# Backward compatibility
#

function sem_search_results()
{
} // end sem_search_results()
?>