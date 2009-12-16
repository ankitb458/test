<?php
/*
Plugin Name: Opt-in Front Page
Plugin URI: http://www.semiologic.com/software/opt-in-front/
Description: Restricts the access to your front page on an opt-in basis: Only posts within the category with a slug of 'blog' or 'news' will be displayed on your front page.
Version: 4.1.1 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-opt-in-front
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

class sem_opt_in_front {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		$main_cat_id = sem_opt_in_front::get_main_cat_id();
		
		define('main_cat_id', $main_cat_id ? intval($main_cat_id) : false);
		
		if ( main_cat_id ) {
			add_filter('category_link', array('sem_opt_in_front', 'category_link'), 10, 2);

			if ( !is_admin() ) {
				add_filter('posts_join', array('sem_opt_in_front', 'posts_join'), 11);
			}
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
		
		$main_cat_id = 0;
		
		foreach ( array('blog', 'news') as $slug ) {
			$main_cat = get_term_by('slug', $slug, 'category');

			if ( $main_cat && !is_wp_error($main_cat) && $main_cat->count > 0 ) {
				$main_cat_id = (int) $main_cat->term_id;
				break;
			}
		}
		
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
		
		static $done = false;
		
		if ( $done )
			return $posts_join;
		
		$main_cat = get_term(main_cat_id, 'category');
		
		$extra = str_replace(array("\t", "\r", "\n"), ' ', "
			INNER JOIN $wpdb->term_relationships AS sem_relationships
			ON sem_relationships.object_id = $wpdb->posts.ID
			AND sem_relationships.term_taxonomy_id = " . intval($main_cat->term_taxonomy_id) . "
			");
		
		$posts_join .= $extra;
		
		$done = true;
		
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
				return apply_filters('the_permalink', get_permalink($blog_page_id));
			else
				return $link;
		} else {
			return user_trailingslashit(get_option('home'));
		}
	} # category_link()
	
	
	/**
	 * pre_flush_post()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function pre_flush_post($post_id) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return;
		
		$post = get_post($post_id);
		if ( !$post || $post->post_type != 'page' || wp_is_post_revision($post_id) )
			return;
		
		$old = wp_cache_get($post_id, 'pre_flush_post');
		if ( $old === false )
			$old = array();
		
		$update = false;
		foreach ( array(
			'post_status',
			) as $field ) {
			if ( !isset($old[$field]) ) {
				$old[$field] = $post->$field;
				$update = true;
			}
		}
		
		if ( $update )
			wp_cache_set($post_id, $old, 'pre_flush_post');
	} # pre_flush_post()
	
	
	/**
	 * flush_post()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function flush_post($post_id) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return;
		
		$post = get_post($post_id);
		if ( !$post || $post->post_type != 'post' || wp_is_post_revision($post_id) )
			return;
		
		$old = wp_cache_get($post_id, 'pre_flush_post');
		
		if ( $post->post_status != 'publish' && ( !$old || $old['post_status'] != 'publish' ) )
			return;
		
		return sem_opt_in_front::flush_cache();
	} # flush_post()
	
	
	/**
	 * flush_cache()
	 *
	 * @param mixed $in;
	 * @return mixed $in
	 **/

	function flush_cache($in = null) {
		static $done = false;
		if ( $done )
			return $in;
		
		$done = true;
		delete_transient('sem_opt_in_front');
		
		return $in;
	} # flush_cache()
	
	
	/**
	 * activate()
	 *
	 * @return void
	 **/

	function activate() {
		global $pagenow;
		if ( $pagenow != 'plugins.php' || in_array('sem-opt-in-front/sem-opt-in-front.php', array_keys(get_option('recently_activated', array()))) ) {
			sem_opt_in_front::flush_cache();
			return;
		}
		
		global $wpdb;
		if ( !sem_opt_in_front::get_main_cat_id() ) {
			$main_cat = wp_create_term(__('News', 'sem-opt-in-front'), 'category');
			$main_cat_id = is_array($main_cat) ? $main_cat['term_taxonomy_id'] : $main_cat;
			$main_cat_id = intval($main_cat_id);
			if ( $main_cat_id ) {
				$wpdb->query("
					INSERT INTO $wpdb->term_relationships (
							object_id,
							term_taxonomy_id
							)
					SELECT	posts.ID,
							$main_cat_id
					FROM	$wpdb->posts as posts
					WHERE	posts.post_type = 'post'
					AND		posts.post_status IN ('publish', 'private', 'pending', 'draft')
					");
			}
		} else {
			$main_cat = get_term(sem_opt_in_front::get_main_cat_id(), 'category');
			$main_cat_id = $main_cat->term_taxonomy_id;
			
			$max_date = $wpdb->get_var("
				SELECT	MAX(posts.post_date)
				FROM	$wpdb->posts as posts
				JOIN	$wpdb->term_relationships as tr
				ON		tr.object_id = posts.ID
				AND		tr.term_taxonomy_id = $main_cat_id;
				");
			
			$wpdb->query("
				INSERT INTO $wpdb->term_relationships (
						object_id,
						term_taxonomy_id
						)
				SELECT	posts.ID,
						$main_cat_id
				FROM	$wpdb->posts as posts
				WHERE	posts.post_type = 'post'
				AND		posts.post_status IN ('publish', 'private', 'pending', 'draft')
				AND		posts.post_date > '$max_date'
				");
		}
		
		wp_update_term_count_now(array($main_cat_id), 'category');
		sem_opt_in_front::flush_cache();
	} # activate()
} # sem_opt_in_front

add_action('init', array('sem_opt_in_front', 'init'));

foreach ( array(
	'create_category',
	'edit_category',
	'delete_category',
	'flush_cache',
	'after_db_upgrade',
	) as $hook )
	add_action($hook, array('sem_opt_in_front', 'flush_cache'));

add_action('pre_post_update', array('sem_opt_in_front', 'pre_flush_post'));

foreach ( array(
		'save_post',
		'delete_post',
		) as $hook )
	add_action($hook, array('sem_opt_in_front', 'flush_post'), 1); // before _save_post_hook()

register_activation_hook(__FILE__, array('sem_opt_in_front', 'activate'));
register_deactivation_hook(__FILE__, array('sem_opt_in_front', 'flush_cache'));

wp_cache_add_non_persistent_groups(array('widget_queries', 'pre_flush_post'));
?>