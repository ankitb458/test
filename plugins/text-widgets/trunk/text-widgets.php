<?php
/*
Plugin Name: Text Widgets
Plugin URI: http://www.semiologic.com/software/widgets/text-widgets/
Description: Replaces WordPress' default text widgets with advanced text widgets
Version: 1.0.1 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


class text_widgets
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('text_widgets', 'widgetize'), 0);
	} # init()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		# kill/change broken widgets
		global $wp_registered_widgets;
		global $wp_registered_widget_controls;

		foreach ( array_keys($wp_registered_widgets) as $widget_id )
		{
			if ( $wp_registered_widgets[$widget_id]['callback'] == 'wp_widget_text' )
			{
				$wp_registered_widgets[$widget_id]['callback'] = array('text_widgets', 'widget_text');
				$wp_registered_widget_controls[$widget_id]['callback'] = array('text_widgets_admin', 'widget_text_control');
			}
		}
		
	} # widgetize()
	
	
	#
	# widget_text()
	#
	
	function widget_text($args, $widget_args = 1)
	{
		extract( $args, EXTR_SKIP );
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('widget_text');
		if ( !isset($options[$number]) )
			return;

		$title = $options[$number]['title'];
		$text = apply_filters( 'widget_text', $options[$number]['text'] );
		$text = $options[$number]['filter'] ? wpautop($text) : $text;
		
		echo $before_widget . "\n"
			. ( trim($title) !== ''
			 	? ( $before_title . $title . $after_title . "\n" )
				: ''
				)
			. '<div class="textwidget">' . $text . '</div>'
			. $after_widget . "\n";
	} # widget_text()
} # text_widgets

text_widgets::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/text-widgets-admin.php';
}
?>