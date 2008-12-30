<?php
/*
Plugin Name: Opt-in Front Page
Plugin URI: http://www.semiologic.com/software/publishing/opt-in-front/
Description: Restricts the access to your front page on an opt-in basis: Only posts within the category with a slug of 'blog' will be displayed on your front page.
Author: Denis de Bernardy
Version: 3.1.1 RC
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts  (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


class sem_opt_in_front
{
	#
	# init()
	#

	function init()
	{
		global $wpdb;

		$main_cat_id = $wpdb->get_var("
			SELECT	terms.term_id
			FROM	$wpdb->terms as terms
			INNER JOIN $wpdb->term_taxonomy as term_taxonomy
			ON		term_taxonomy.term_id = terms.term_id
			AND		term_taxonomy.taxonomy = 'category'
			INNER JOIN $wpdb->term_relationships as term_relationships
			ON		term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
			INNER JOIN $wpdb->posts as posts
			ON		posts.ID = term_relationships.object_id
			WHERE	terms.slug = 'blog'
			LIMIT 1
			");

		define('main_cat_id', $main_cat_id ? intval($main_cat_id) : false);

		if ( is_admin() ) return;
		
		if ( main_cat_id )
		{
			add_filter('posts_join', array('sem_opt_in_front', 'posts_join'), 11);
			add_filter('category_link', array('sem_opt_in_front', 'change_permalink'), 10, 2);
		}
	} # init()


	#
	# posts_join()
	#

	function posts_join($posts_join)
	{
		if ( defined('did_opt_in_front')
			|| !( is_home()
				|| is_feed() && !is_archive() && !is_singular() && !is_search()
				)
			)
		{
			return $posts_join;
		}

		global $wpdb;
		global $wp_query;

		$extra =  "
			INNER JOIN $wpdb->term_relationships AS sem_relationships
			ON sem_relationships.object_id = ID
			INNER JOIN $wpdb->term_taxonomy as sem_taxonomy
			ON sem_taxonomy.term_taxonomy_id = sem_relationships.term_taxonomy_id
			AND sem_taxonomy.taxonomy = 'category'
			AND sem_taxonomy.term_id = " . intval(main_cat_id) . "
			";

		$extra = str_replace(array("\n", "\r", "\t"), " ", $extra);

		$posts_join .= $extra;

		$wp_query->is_category = false;
		$wp_query->is_home = !is_feed();

		define('did_opt_in_front', true);

		return $posts_join;
	} # posts_join()


	#
	# change_permalink()
	#

	function change_permalink($link = '', $id = '')
	{
		if ( $id == '' )
		{
			return $link;
		}

		if ( $id == main_cat_id )
		{
			static $new_link;
			
			if ( !isset($new_link) )
			{
				if ( get_option('show_on_front') == 'page' && get_option('page_on_front') )
				{
					if ( $blog_page_id = get_option('page_for_posts') )
					{
						$new_link = get_permalink($blog_page_id);
					}
					else
					{
						$new_link = $link;
					}
				}
				else
				{
					$new_link = uesr_trailingslashit(get_option('home'));
				}
			}
			
			$link = $new_link;
		}

		return $link;
	} # change_permalink()
} # end sem_opt_in_front

sem_opt_in_front::init();
?>