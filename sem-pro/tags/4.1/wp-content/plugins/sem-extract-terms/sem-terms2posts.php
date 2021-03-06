<?php
/*
Plugin Name: Terms2posts
Plugin URI: http://www.semiologic.com/software/terms2posts/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/terms2posts/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Requires the <a href="http://www.semiologic.com/software/extract-terms/">Extract terms plugin</a>. Returns Yahoo! terms as related posts. To use, call the_terms2posts(); where you want the related posts to appear.
Version: 2.6
Author: Denis de Bernardy
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


require_once dirname(__FILE__) . '/sem-extract-terms.php';


class sem_terms2posts
{
	#
	# Constructor
	#

	function sem_terms2posts()
	{
		global $wpdb;

		if ( !get_settings('posts_have_fulltext_index') )
		{
			$wpdb->query("ALTER TABLE `$wpdb->posts` ADD FULLTEXT ( `post_title`, `post_content` )");
			update_option('posts_have_fulltext_index', 1);
		}
	} # end sem_terms2posts()


	#
	# get_the_posts()
	#

	function get_the_posts($num_posts = 5, $post = null)
	{
		global $wpdb;

		if ( !isset($post) )
		{
			$post = $GLOBALS['post'];
		}

		$terms = get_the_post_terms($post);

		if ( $terms )
		{
			$s = "";

			#$terms = array_slice($terms, 0, 3);

			foreach ( $terms as $term )
			{
				$s .= ( $s ? " " : "" ) . $term;
			}

			$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

			$related_posts = $wpdb->get_results("
				SELECT
					posts.*,
					MATCH ( posts.post_title, posts.post_content )
						AGAINST ( '" . addslashes($s) . "' ) AS mysql_score
				FROM
					$wpdb->posts as posts
				WHERE
					posts.post_date_gmt <= '" . $now . "'
					AND posts.ID <> " . intval($post->ID)
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
					AND ( posts.post_status = 'static' OR posts.post_status = 'publish' )
				ORDER BY
					mysql_score DESC
				LIMIT " . intval($num_posts)
				);
		}

		if ( !$related_posts )
		{
			$related_posts = array();
		}

		return $related_posts;
	} # end get_the_posts()


	#
	# dispay()
	#

	function display($num_posts = 5, $post = null)
	{
		$related_posts = $this->get_the_posts($num_posts, $post);

		if ( $related_posts )
		{
			if ( function_exists('update_post_cache') )
			{
				update_post_cache($related_posts);
			}
			if ( function_exists('update_page_cache') )
			{
				update_page_cache($related_posts);
			}

			foreach ( $related_posts as $related_post )
			{
				echo "<li>"
					. "<a href=\""
						. apply_filters('the_permalink', get_permalink($related_post->ID))
						. "\">"
					. stripslashes($related_post->post_title)
					. "</a>"
					. "</li>\n";
			}
		}
	} # end display()
} # end sem_terms2posts

$sem_terms2posts =& new sem_terms2posts();


#
# Template tags
#

function the_terms2posts($num_posts = 5, $post = null)
{
	global $sem_terms2posts;

	$sem_terms2posts->display($num_posts, $post);
} # end the_terms2posts()


########################
#
# Backward compatibility
#

function sem_terms2posts($num_posts = 5, $post = null)
{
	the_terms2posts($num_posts, $post);
} # end sem_terms2posts()
?>