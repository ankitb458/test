<?php
/*
Plugin Name: Related Entries
Plugin URI: http://www.semiologic.com/software/widgets/terms2posts/
Description: Leverages Yahoo's terms extraction web service to display related posts on your site.
Version: 2.14
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
			$wpdb->query("ALTER TABLE `$wpdb->posts` ENGINE = MYISAM");
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
					AND ( posts.post_password = '' )
					AND "
					. ( use_post_type_fixed
						? "( post_status = 'publish' )"
						: "( post_status = 'publish' OR post_status = 'static' )"
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
} # end sem_terms2posts

$sem_terms2posts =& new sem_terms2posts();


#
# Template tags
#

function the_terms2posts($num_posts = 5, $post = null)
{
	global $sem_terms2posts;

	echo $sem_terms2posts->display($num_posts, $post);
} # end the_terms2posts()


#
# display_entry_related_entries()
#

function display_entry_related_entries()
{
	$show_entry_related_entries = is_single();

	if ( function_exists('the_terms2posts')
		&& apply_filters('show_entry_related_entries', $show_entry_related_entries)
		&& get_the_post_terms()
		)
	{
		echo '<div class="entry_related_entries">'
			. '<h2>'
			. get_caption('related_entries')
			. '</h2>'
			. '<ul>';

		the_terms2posts();

		echo '</ul>'
			. '</div>';
	}
} # end display_entry_related_entries()

add_action('after_the_entry', 'display_entry_related_entries', 8);


########################
#
# Backward compatibility
#

function sem_terms2posts($num_posts = 5, $post = null)
{
	the_terms2posts($num_posts, $post);
} # end sem_terms2posts()
?>