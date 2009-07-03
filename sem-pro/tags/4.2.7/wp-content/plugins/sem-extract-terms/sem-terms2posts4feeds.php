<?php
/*
Plugin Name: Related Entries for Feeds
Plugin URI: http://www.semiologic.com/software/terms2posts4feeds/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/terms2posts4feeds/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Requires the <a href="http://www.semiologic.com/software/extract-terms/">Extract terms plugin</a>. Returns Yahoo! terms as related entries in your RSS feed.
Version: 2.12
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


class sem_terms2posts4feeds
{
	#
	# Constructor
	#

	function sem_terms2posts4feeds()
	{
		global $wpdb;

		if ( !get_settings('posts_have_fulltext_index') )
		{
			$wpdb->query("ALTER TABLE `$wpdb->posts` ENGINE = MYISAM;");
			$wpdb->query("ALTER TABLE `$wpdb->posts` ADD FULLTEXT ( `post_title`, `post_content` )");
			update_option('posts_have_fulltext_index', 1);
		}

		add_filter('the_content', array(&$this, 'add2rss'), 250);
	} # end sem_terms2posts4feeds()


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
					DISTINCT posts.*,
					MATCH ( posts.post_title, posts.post_content )
						AGAINST ( '" . addslashes($s) . "' ) AS mysql_score
				FROM
					$wpdb->posts as posts
				LEFT JOIN $wpdb->postmeta as postmeta
					ON postmeta.post_id = posts.ID
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
					AND ( posts.post_password = '' )
					AND "
					. ( use_post_type_fixed
						? "( post_status = 'publish' AND ( post_type = 'post' OR ( post_type = 'page' AND postmeta.meta_value = 'article.php' ) ) )"
						: "( post_status = 'publish' OR ( post_status = 'static' AND postmeta.meta_value = 'article.php' ) )"
						)
					. "
				GROUP BY
					posts.ID
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

			$o = "";

			foreach ( $related_posts as $related_post )
			{
				$o .= "<li>"
					. "<a href=\""
						. apply_filters('the_permalink', get_permalink($related_post->ID))
						. "\">"
					. htmlspecialchars(stripslashes($related_post->post_title), ENT_QUOTES)
					. "</a>"
					. "</li>\n";
			}
		}

		return $o;
	} # end display()


	#
	# add2rss()
	#

	function add2rss($content)
	{
		if ( is_feed() )
		{
			$related_posts = $this->get_the_posts();

			if ( $related_posts )
			{
				$content .= '<div class="related_entries" style="margin-top: 1.5em;">'
					. '<p><strong>' . get_caption('related_entries') . '</strong></p>'
					. '<ul>'
					. $this->display()
					. '</ul>'
					. '</div>';
			}
		}

		return $content;
	} # end add2rss()
} # end sem_terms2posts4feeds

$sem_terms2posts4feeds =& new sem_terms2posts4feeds();
?>