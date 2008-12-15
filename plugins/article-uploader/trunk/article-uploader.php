<?php
/*
Plugin Name: Article Uploader
Plugin URI: http://www.semiologic.com/software/publishing/article-uploader/
Description: Lets you upload files in place of using the WP editor when writing your entries.
Author: Denis de Bernardy
Version: 1.2
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: article_uploader
Update Package: http://www.semiologic.com/media/software/publishing/article-uploader/article-uploader.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class article_uploader
{
	#
	# init()
	#

	function init()
	{
		if ( !is_admin() )
		{
			add_action('loop_start', array('article_uploader', 'start'));
		}
	} # init()
	
	
	#
	# start()
	#
	
	function start()
	{
		static $did_backup = false;
		
		if ( $did_backup ) return;
		
		global $wp_query;
		global $the_content_filter_backup;
		
		$the_content_filter_backup = array();
		
		if ( $wp_query->post_count )
		{
			foreach ( array(
				'wptexturize',
				'wpautop',
				'Markdown'
				) as $callback )
			{
				if ( ( $priority = has_filter('the_content', $callback) ) !== false )
				{
					$the_content_filter_backup[$priority][] = $callback;
				}
			}
		
			$post_id = $wp_query->posts[0]->ID;
			
			if ( get_post_meta($post_id, '_kill_formatting', true) )
			{
				foreach ( $the_content_filter_backup as $priority => $filters )
				{
					foreach ( $filters as $filter )
					{
						remove_filter('the_content', $filter, $priority);
					}
				}
			}
		}
		
		$did_backup = true;
		add_action('the_content', array('article_uploader', 'next'));
		add_action('loop_end', array('article_uploader', 'reset'));
	} # start()
	
	
	#
	# next()
	#
	
	function next($in = '')
	{
		if ( in_the_loop() )
		{
			global $wp_query;
			global $the_content_filter_backup;
	
			$next_post = $wp_query->current_post + 1;

			if ( $next_post != $wp_query->post_count )
			{
				$post_id = $wp_query->posts[$next_post]->ID;

				if ( get_post_meta($post_id, '_kill_formatting', true) )
				{
					foreach ( $the_content_filter_backup as $priority => $filters )
					{
						foreach ( $filters as $filter )
						{
							remove_filter('the_content', $filter, $priority);
						}
					}
				}
				else
				{
					foreach ( $the_content_filter_backup as $priority => $filters )
					{
						foreach ( $filters as $filter )
						{
							add_filter('the_content', $filter, $priority);
						}
					}
				}
			}
		}
		
		return $in;
	} # next()
	
	
	#
	# reset()
	#
	
	function reset()
	{
		global $the_content_filter_backup;

		foreach ( (array) $the_content_filter_backup as $priority => $filters )
		{
			foreach ( $filters as $filter )
			{
				add_filter('the_content', $filter, $priority);
			}
		}
	} # reset()
} # article_uploader

article_uploader::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/article-uploader-admin.php';
}
?>