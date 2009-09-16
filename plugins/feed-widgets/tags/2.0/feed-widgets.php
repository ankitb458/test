<?php
/*
Plugin Name: Feed Widgets
Plugin URI: http://www.semiologic.com/software/feed-widgets/
Description: Creates a special sidebar that lets you insert widgets at the end of each post in your RSS feeds. Configure these widgets under <a href="widgets.php">Design / Widgets</a>, by selecting the Feed Widgets sidebar.
Version: 2.0
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: feed-widgets
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('feed-widgets', false, dirname(plugin_basename(__FILE__)) . '/lang');


/**
 * feed_widgets
 *
 * @package Feed Widgets
 **/

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

add_action('init', array('feed_widgets', 'panels'), -100);
add_filter('the_content', array('feed_widgets', 'display'), 100);
?>