<?php
/*
Plugin Name: Search Reloaded
Plugin URI: http://www.semiologic.com/software/search-reloaded/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/search-reloaded/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Enhances WordPress' default search engine.
Author: Denis de Bernardy
Version: 2.2
Author URI: http://www.semiologic.com
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

		if ( !get_settings('posts_have_fulltext_index') )
		{
			$wpdb->query("ALTER TABLE `$wpdb->posts` ADD FULLTEXT ( `post_title`, `post_content` )");
			update_option('posts_have_fulltext_index', 1);
		}

		add_filter('posts_where', array(&$this, 'bypass_search'));
		add_filter('the_posts', array(&$this, 'redo_search'));
	} # end sem_search_reloaded()


	#
	# bypass_search()
	#

	function bypass_search($where)
	{
		if ( is_search() )
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

		if ( is_search() )
		{
			$GLOBALS['sem_s'] = get_query_var('s');

			preg_match_all("/((\w|-)+)/", $GLOBALS['sem_s'], $out);
			$keywords = current($out);

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
				$posts_per_page = get_settings('posts_per_page');
			}
			$offset = ( $paged - 1 ) * $posts_per_page;

			$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

			if ( isset($GLOBALS['sem_static_front']) )
			{
				$GLOBALS['sem_static_front']->init();
			}
			if ( isset($GLOBALS['sem_sidebar_tile']) )
			{
				$GLOBALS['sem_sidebar_tile']->init();
			}

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
					. ( ( defined('sem_sidebar_tile_id') && sem_sidebar_tile_id )
						? "
					AND posts.ID <> " . intval(sem_sidebar_tile_id)
						: ""
						)
					. "
					AND ( posts.post_password = '' )
					AND ( posts.post_status = 'static' OR posts.post_status = 'publish' )
					AND ( posts.post_title REGEXP '$reg_one_present' OR posts.post_content REGEXP '$reg_one_present' )
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
					. ( ( defined('sem_sidebar_tile_id') && sem_sidebar_tile_id )
						? "
					AND posts.ID <> " . intval(sem_sidebar_tile_id)
						: ""
						)
					. "
					AND ( posts.post_password = '' )
					AND ( posts.post_status = 'static' OR posts.post_status = 'publish' )
					AND ( posts.post_title REGEXP '$reg_one_present' OR posts.post_content REGEXP '$reg_one_present' )
				GROUP BY posts.ID
				LIMIT " . intval($offset) . ", ". intval($posts_per_page);

			$the_posts = $wpdb->get_results($search_query);
			$GLOBALS['request'] = preg_replace("/(\n|\s)+/", " ", $request_query);

			if ( function_exists('update_post_cache') )
			{
				update_post_cache($the_posts);
			}
			if ( function_exists('update_page_cache') )
			{
				update_page_cache($the_posts);
			}
		}

		return $the_posts;
	} # end redo_search()
} # end sem_search_reloaded()

$sem_search_reloaded =& new sem_search_reloaded();


########################
#
# Backward compatibility
#

function sem_search_results()
{
global $wp_query;

global $sem_s;

$paged = $wp_query->get('paged');
if (!$paged)
	$paged = 1;
$posts_per_page = $wp_query->get('posts_per_page');
if ( !$posts_per_page )
	$posts_per_page = get_settings('posts_per_page');
$start = ( $paged - 1 ) * $posts_per_page + 1;
?>
<div class="header">
<h1>Search results for: <?php echo stripslashes(preg_replace("/\"/", "&quot;", $sem_s )); ?></h1>
<?php
if ( function_exists('sem_display_ad_space') )
	sem_display_ad_space();
?>
</div>
<div class="body">
<ol start="<?php echo $start; ?>">
<?php while (have_posts()) : the_post(); ?>
<li id="post-<?php the_ID(); ?>"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
<?php edit_post_link('Edit', '<span class="edit_link"> | ', '</span>'); ?></h2>
<?php the_excerpt(); ?>
</li>
<?php endwhile; ?>
</ol>
</div>
<?php
} // end sem_search_results()
?>