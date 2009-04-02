<?php
/*
Plugin Name: Feed Widgets
Plugin URI: http://www.semiologic.com/software/feed-widgets/
Description: Creates a special sidebar that lets you insert widgets at the end of each post in your RSS feed. Configure these widgets under Design / Widgets, by selecting the Feed Widgets sidebar. To make the best of this plugin, be sure to configure the full text feed setting (under Settings / Reading).
Version: 1.0.2 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class feed_widgets
{
	#
	# init()
	#

	function init()
	{
		add_action('init', array('feed_widgets', 'panels'), 0);
		add_filter('the_content', array('feed_widgets', 'display'), 100);
		add_filter('the_content_rss', array('feed_widgets', 'display'), 100);
	} # init()
	
	
	#
	# autofill()
	#
	
	function autofill()
	{
		$sidebars_widgets = get_option('sidebars_widgets');
		
		if ( !$sidebars_widgets['feed_widgets'] )
		{
			if ( method_exists('bookmark_me', 'new_widget') )
			{
				$sidebars_widgets['feed_widgets'][] = bookmark_me::new_widget();
			}
			
			update_option('sidebars_widgets', $sidebars_widgets);
		}
	} # autofill()
	
	
	#
	# panels()
	#
	
	function panels()
	{
		register_sidebar(
			array(
				'id' => 'feed_widgets',
				'name' => 'Feed Widgets (for use in feeds)',
				'before_widget' => '<div>',
				'after_widget' => '</div>' . "\n",
				'before_title' => '<h3>',
				'after_title' => '</h3>' . "\n",
				)
			);
	} # panels()
	
	
	#
	# display()
	#
	
	function display($text)
	{
		if ( !is_feed() ) return $text;
		
		ob_start();
		dynamic_sidebar('feed_widgets');
		$widgets = ob_get_clean();

		$text .= $widgets;
		
		return $text;
	} # display()
} # feed_widgets

feed_widgets::init();
?>