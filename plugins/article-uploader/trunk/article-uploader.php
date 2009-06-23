<?php
/*
Plugin Name: Article Uploader
Plugin URI: http://www.semiologic.com/software/article-uploader/
Description: Lets you upload files in place of using the WP editor when writing your entries.
Version: 2.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: article-uploader
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('article-uploader', null, dirname(__FILE__) . '/lang');


/**
 * article_uploader
 *
 * @package Article Uploader
 **/

add_action('admin_menu', array('article_uploader', 'meta_boxes'), 30);
add_action('the_post', array('article_uploader', 'the_post'));
add_action('loop_end', array('article_uploader', 'loop_end'));

class article_uploader {
	/**
	 * meta_boxes()
	 *
	 * @return void
	 **/
	
	function meta_boxes() {
		if ( current_user_can('unfiltered_html') ) {
			add_meta_box('article_uploader', __('Article Uploader', 'article-uploader'), array('article_uploader_admin', 'entry_editor'), 'post');
			add_meta_box('article_uploader', __('Article Uploader', 'article-uploader'), array('article_uploader_admin', 'entry_editor'), 'page');
			add_action('save_post', array('article_uploader_admin', 'save_entry'));
		}
	} # meta_boxes()
	
	
	/**
	 * loop_end()
	 *
	 * @return void
	 **/

	function loop_end() {
		article_uploader::restore_filters();
	} # loop_end()
	
	
	/**
	 * the_post()
	 *
	 * @return void
	 **/
	
	function the_post(&$post) {
		if ( get_post_meta($post->ID, '_kill_formatting', true) === '1' )
			article_uploader::strip_filters();
		else
			article_uploader::restore_filters();
	} # the_post()
	
	
	/**
	 * strip_filters()
	 *
	 * @return void
	 **/

	function strip_filters() {
		global $article_uploader_filter_backup;
		
		if ( !isset($article_uploader_filter_backup) )
			$article_uploader_filter_backup = array('the_content' => array(), 'the_excerpt' => array());
		
		foreach ( array(
			'wptexturize',
			'wpautop',
			'Markdown',
			) as $callback ) {
			$priority = has_filter('the_content', $callback);
			
			if ( $priority !== false ) {
				$article_uploader_filter_backup['the_content'][$priority][] = $callback;
				remove_filter('the_content', $callback, $priority);
			}
			
			$priority = has_filter('the_excerpt', $callback);
			
			if ( $priority !== false ) {
				$article_uploader_filter_backup['the_excerpt'][$priority][] = $callback;
				remove_filter('the_excerpt', $callback, $priority);
			}
		}
	} # strip_filters()
	
	
	/**
	 * restore_filters()
	 *
	 * @return void
	 **/

	function restore_filters() {
		global $article_uploader_filter_backup;
		
		foreach ( (array) $article_uploader_filter_backup as $filter => $filters )
			foreach ( (array) $filters as $priority => $callbacks )
				foreach ( (array) $callbacks as $callback )
					add_filter($filter, $callback, $priority);
	} # restore_filters()
} # article_uploader


if ( !function_exists('load_multipart_entry') ) :
function load_multipart_entry() {
	include dirname(__FILE__) . '/multipart-entry/multipart-entry.php';
}
endif;

function article_uploader_admin() {
	include dirname(__FILE__) . '/article-uploader-admin.php';
}

foreach ( array('post.php', 'post-new.php', 'page.php', 'page-new.php') as $hook ) {
	add_action("load-$hook", 'article_uploader_admin');
	add_action("load-$hook", 'load_multipart_entry');
}
?>