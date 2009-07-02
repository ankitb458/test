<?php

/*
Plugin Name: Full Text Feed
Version: 1.03
Plugin URI: http://cavemonkey50.com/code/full-feed/
Description: Prevents WordPress 2.1+ from adding a more link to your website's feed.
Author: Ronald Heft, Jr.
Author URI: http://cavemonkey50.com/
*/

function ff_restore_text ($content) {
	global $page, $pages;
	
	if ( $page > count($pages) )
		$page = count($pages);
	
	if ( is_feed() )
		$content = preg_replace('/<!--more(.+?)?-->/iU', '', $pages[$page-1]);
	
	return $content;
}

add_filter('the_content', 'ff_restore_text', -1);

?>