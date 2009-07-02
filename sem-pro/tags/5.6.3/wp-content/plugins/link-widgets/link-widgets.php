<?php
/*
Plugin Name: Link Widgets
Plugin URI: http://www.semiologic.com/software/widgets/link-widgets/
Description: Replaces WordPress' default link widgets with advanced link widgets
Author: Denis de Bernardy
Version: 1.1
Author URI: http://www.semiologic.com
Update Service: http://version.semiologic.com/wordpress
Update Tag: link_widgets
Update Package: http://www.semiologic.com/media/software/widgets/link-widgets/link-widgets.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class link_widgets
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('link_widgets', 'widgetize'), 0);
		
		foreach ( array(
			'add_link',
			'edit_link',
			'delete_link',
			'switch_theme',
			) as $hook )
		{
			add_action($hook, array('link_widgets', 'clear_cache'));
		}
		
		register_activation_hook(__FILE__, array('link_widgets', 'clear_cache'));
		register_deactivation_hook(__FILE__, array('link_widgets', 'clear_cache'));
	} # init()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		# kill/change broken widgets
		global $wp_registered_widgets;
		global $wp_registered_widget_controls;
		
		foreach ( array('links') as $widget_id )
		{
			unset($wp_registered_widgets[$widget_id]);
			unset($wp_registered_widget_controls[$widget_id]);
		}
		
		#dump(wp_get_sidebars_widgets());
		#delete_option('link_widgets');
		if ( !( $options = get_option('link_widgets') ) )
		{
			$options = array();
			
			foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k )
			{
				if ( !is_array($sidebars[$k]) )
				{
					continue;
				}

				if ( ( $key = array_search('links', $sidebars[$k]) ) !== false )
				{
					$options = array( 1 => array() );
					$sidebars[$k][$key] = 'link_widget-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
				elseif ( ( $key = array_search('Links', $sidebars[$k]) ) !== false )
				{
					$options = array( 1 => array() );
					$sidebars[$k][$key] = 'link_widget-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
			}
			
			update_option('link_widgets', $options);
		}
		
		$widget_options = array('classname' => 'links', 'description' => __( "Links Widget") );
		$control_options = array('width' => 300, 'id_base' => 'link_widget');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "link_widget-$o";
			wp_register_sidebar_widget($id, __('Links'), array('link_widgets', 'widget_links'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Links'), array('link_widgets_admin', 'widget_links_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "link_widget-1";
			wp_register_sidebar_widget($id, __('Links'), array('link_widgets', 'widget_links'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Links'), array('link_widgets_admin', 'widget_links_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	#
	# widget_links()
	#
	
	function widget_links($args, $widget_args = 1)
	{
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		extract($args, EXTR_SKIP);

		# front end: serve cache if available
		if ( !is_admin() )
		{
			$cache = get_option('link_widgets_cache');

			if ( isset($cache[$number]) )
			{
				echo $cache[$number];
				return;
			}
		}

		$options = get_option('link_widgets');
		if ( !isset($options[$number]) )
			return;
			
		if ( is_admin() )
		{
			$title = $options[$number]['filter'] ? get_term_field('name', $options[$number]['filter'], 'link_category') : 'All Links';
			
			echo $before_widget
				. $before_title
				. $title
				. $after_title
				. $after_widget;
			return;
		}
		
		$before_widget = preg_replace('/id="[^"]*"/','id="%id"', $before_widget);

		$o = wp_list_bookmarks(array(
			'title_before' => $before_title, 'title_after' => $after_title,
			'category_before' => $before_widget, 'category_after' => $after_widget,
			'show_images' => true, 'class' => 'linkcat widget',
			'show_description' => $options[$number]['desc'], 'between' => '<br />',
			'category' => $options[$number]['filter'],
			'echo' => false,
		));
		
		$cache[$number] = $o;
		update_option('link_widgets_cache', $cache);
		
		echo $o;
	} # widget_links()
	
	
	#
	# clear_cache()
	#
	
	function clear_cache($in = null)
	{
		update_option('link_widgets_cache', array());
	} # clear_cache()
} # link_widgets

link_widgets::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/link-widgets-admin.php';
}
?>