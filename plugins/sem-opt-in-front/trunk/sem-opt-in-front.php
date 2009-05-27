<?php
/*
Plugin Name: Opt-in Front Page
Plugin URI: http://www.semiologic.com/software/opt-in-front/
Description: Restricts the access to your front page on an opt-in basis: Only posts within the category with a slug of 'blog' will be displayed on your front page.
Version: 3.2 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-opt-in-front-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts  (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


/**
 * sem_opt_in_front
 *
 * @package Opt-in Front Page
 **/

add_action('init', array('sem_opt_in_front', 'init'));
add_filter('category_link', array('sem_opt_in_front', 'category_link'), 10, 2);

if ( !is_admin() )
	add_filter('posts_join', array('sem_opt_in_front', 'posts_join'), 11);

foreach ( array('create_term', 'edit_term', 'delete_term') as $hook )
	add_action($hook, array('sem_opt_in_front', 'flush_cache'));

class sem_opt_in_front {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		$main_cat_id = sem_opt_in_front::get_main_cat_id();
		
		define('main_cat_id', $main_cat_id ? intval($main_cat_id) : false);
		
		if ( !main_cat_id ) {
			remove_filter('posts_join', array('sem_opt_in_front', 'posts_join'), 11);
			remove_filter('category_link', array('sem_opt_in_front', 'category_link'), 10);
		}
	} # init()
	
	
	/**
	 * get_main_cat_id()
	 *
	 * @return int $term_id
	 **/

	function get_main_cat_id() {
		$main_cat_id = get_transient('sem_opt_in_front');
		
		if ( $main_cat_id !== false )
			return (int) $main_cat_id;
		
		$main_cat = get_term_by('slug', 'blog', 'category');
		
		if ( $main_cat && !is_wp_error($main_cat) && $main_cat->count > 0 )
			$main_cat_id = (int) $main_cat->term_id;
		else
			$main_cat_id = 0;
		
		set_transient('sem_opt_in_front', $main_cat_id);
		
		return $main_cat_id;
	} # get_main_cat_id()
	
	
	/**
	 * posts_join()
	 *
	 * @param string $posts_join
	 * @return string $posts_join
	 **/

	function posts_join($posts_join) {
		if ( is_feed() ) {
			if ( is_archive() || is_singular() || is_search() || is_404() || is_robots() || is_trackback() )
				return $posts_join;
		} else {
			if ( !is_home() )
				return $posts_join;
		}
		
		global $wpdb;
		
		$main_cat = get_term(main_cat_id, 'category');
		
		$extra = str_replace(array("\t", "\r", "\n"), ' ', "
			INNER JOIN $wpdb->term_relationships AS sem_relationships
			ON sem_relationships.object_id = $wpdb->posts.ID
			AND sem_relationships.term_taxonomy_id = " . intval($main_cat->term_taxonomy_id) . "
			");
		
		$posts_join .= $extra;
		
		remove_filter('posts_join', array('sem_opt_in_front', 'posts_join'), 11);
		
		return $posts_join;
	} # posts_join()
	
	
	/**
	 * category_link()
	 *
	 * @param string $link
	 * @param int $id
	 * @return string $link
	 **/

	function category_link($link = '', $id = '') {
		if ( !$id || !main_cat_id || $id != main_cat_id )
			return $link;
		
		if ( get_option('show_on_front') == 'page' && get_option('page_on_front') ) {
			if ( $blog_page_id = get_option('page_for_posts') )
				return get_permalink($blog_page_id);
			else
				return $link;
		} else {
			return user_trailingslashit(get_option('home'));
		}
	} # category_link()
	
	
	/**
	 * flush_cache()
	 *
	 * @param mixed $in;
	 * @return mixed $in
	 **/

	function flush_cache($in = null) {
		delete_transient('sem_opt_in_front');
		return $in;
	} # flush_cache()
} # sem_opt_in_front
?>