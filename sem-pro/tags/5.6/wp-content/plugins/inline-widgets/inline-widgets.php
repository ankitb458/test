<?php
/*
Plugin Name: Inline Widgets
Plugin URI: http://www.semiologic.com/software/widgets/inline-widgets/
Description: Creates a special sidebar that lets you insert widget in posts and pages. Configure these widgets under Design / Widgets, by selecting the Inline Widgets sidebar.
Author: Denis de Bernardy
Version: 1.0 RC
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: inline_widgets
Update Package: http://www.semiologic.com/media/software/widgets/inline-widgets/inline-widgets.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class inline_widgets
{
	#
	# init()
	#

	function init()
	{
		add_action('init', array('inline_widgets', 'add_panel'), 0);
		add_filter('the_content', array('inline_widgets', 'display'));
	} # init()
	
	
	#
	# autofill()
	#
	
	function autofill()
	{
		$sidebars_widgets = get_option('sidebars_widgets');
		
		if ( !$sidebars_widgets['inline_widgets'] )
		{
			if ( method_exists('newsletter_manager', 'new_widget') )
			{
				$sidebars_widgets['inline_widgets'][] = newsletter_manager::new_widget();
			}
			if ( method_exists('contact_form', 'new_widget') )
			{
				$sidebars_widgets['inline_widgets'][] = contact_form::new_widget();
			}
			if ( method_exists('silo', 'new_widget') )
			{
				$sidebars_widgets['inline_widgets'][] = 'silo_stub';
				$sidebars_widgets['inline_widgets'][] = 'silo_map';
			}
			
			update_option('sidebars_widgets', $sidebars_widgets);
		}
	} # autofill()
	
	
	#
	# add_panel()
	#
	
	function add_panel()
	{
		register_sidebar(
			array(
				'id' => 'inline_widgets',
				'name' => 'Inline Widgets (for use in entries)',
				'before_widget' => '<div>',
				'after_widget' => '</div>' . "\n",
				'before_title' => '<h3>',
				'after_title' => '</h3>' . "\n",
				)
			);
	} # add_panel()
	
	
	#
	# display()
	#
	
	function display($text)
	{
		$text = preg_replace_callback("/
			(?:<p>\s*)?				# maybe a paragraph tag
			(?:<br\s*\/>\s*)*		# and a couple br tags
			\[
			\s*widget\s*:
			(.*?)
			\]
			(?:\s*<br\s*\/>)*		# maybe a couple of br tags
			(?:<\/p>\s*)?			# and a close paragraph tag
			/ix", array('inline_widgets', 'display_callback'), $text);
		
		return $text;
	} # display()
	
	
	#
	# display_callback()
	#
	
	function display_callback($in)
	{
		global $wp_registered_widgets;
		
		$wp_registered_widgets = (array) $wp_registered_widgets;

		if ( !( $widget = $wp_registered_widgets[$in[1]])
			|| !is_callable($wp_registered_widgets[$in[1]]['callback'])
			)
		{
			return '';
		}
		
		$args = array(
			'before_widget' => '<div class="' . $wp_registered_widgets[$in[1]]['classname'] . '">' . "\n",
			'after_widget' => '</div>' . "\n",
			'before_title' => '%BEG_OF_TITLE%',
			'after_title' => '%END_OF_TITLE%'
			);
		
		$params = array($args, (array) $widget['params'][0]);

		ob_start();
		call_user_func_array($widget['callback'], $params);
		$widget = ob_get_clean();
		
		$widget = preg_replace("/%BEG_OF_TITLE%(.*?)%END_OF_TITLE%/", '', $widget);
		
		return $widget;
	} # display_callback()
} # inline_widgets

inline_widgets::init();


if ( is_admin()
	|| strpos($_SERVER['REQUEST_URI'], 'wp-includes') !== false
	)
{
	include dirname(__FILE__) . '/inline-widgets-admin.php';
}
?>