<?php
/*
Plugin Name: Feed Widgets
Plugin URI: http://www.semiologic.com/software/feed-widgets/
Description: Creates a special sidebar that lets you insert widgets at the end of each post in your RSS feed. Configure these widgets under Design / Widgets, by selecting the Feed Widgets sidebar. To make the best of this plugin, be sure to configure the full text feed setting (under Settings / Reading).
Version: 1.1 beta
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: feed-widgets-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('feed-widgets', null, dirname(__FILE__) . '/lang');


/**
 * feed_widgets
 *
 * @package Feed Widgets
 **/

add_action('init', array('feed_widgets', 'panels'), 0);
add_filter('the_content', array('feed_widgets', 'display'), 100);
add_filter('the_content_rss', array('feed_widgets', 'display'), 100);

class feed_widgets {
	/**
	 * panels()
	 *
	 * @return void
	 **/
	
	function panels() {
		register_sidebar(
			array(
				'id' => 'feed_widgets',
				'name' => __('Feed Widgets (for use in feeds)', 'feed-widgets'),
				'before_widget' => '<div>',
				'after_widget' => '</div>' . "\n",
				'before_title' => '<h3>',
				'after_title' => '</h3>' . "\n",
				)
			);
	} # panels()
	
	
	/**
	 * display()
	 *
	 * @return void
	 **/
	
	function display($text) {
		if ( !is_feed() )
			return $text;
		
		ob_start();
		dynamic_sidebar('feed_widgets');
		$widgets = ob_get_clean();

		$text .= "\n\n" . $widgets;
		
		return $text;
	} # display()
} # feed_widgets
?>