<?php
/*
Plugin Name: Archive Widgets
Plugin URI: http://www.semiologic.com/software/widgets/archive-widgets/
Description: Replaces WordPress' default archive widgets with advanced archive widgets
Author: Denis de Bernardy
Version: 1.0.1
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/plugins
Update Tag: archive_widgets
Update Package: http://www.semiologic.com/media/software/widgets/archive-widgets/archive-widgets.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class archive_widgets
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('archive_widgets', 'widgetize'), 0);
	} # init()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		# kill/change broken widgets
		global $wp_registered_widgets;
		global $wp_registered_widget_controls;
		
		foreach ( array('archives') as $widget_id )
		{
			unset($wp_registered_widgets[$widget_id]);
			unset($wp_registered_widget_controls[$widget_id]);
		}
		
		#dump(wp_get_sidebars_widgets());
		#delete_option('archive_widgets');
		if ( !( $options = get_option('archive_widgets') ) )
		{
			$options = array();
			
			foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k )
			{
				if ( !is_array($sidebars[$k]) )
				{
					continue;
				}

				if ( ( $key = array_search('archives', $sidebars[$k]) ) !== false )
				{
					$options = array( 1 => array() );
					$sidebars[$k][$key] = 'archive_widget-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
				elseif ( ( $key = array_search('Archives', $sidebars[$k]) ) !== false )
				{
					$options = array( 1 => array() );
					$sidebars[$k][$key] = 'archive_widget-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
			}
			
			update_option('archive_widgets', $options);
		}
		
		$widget_options = array('classname' => 'archives', 'description' => __( "Archives Widget") );
		$control_options = array('width' => 300, 'id_base' => 'archive_widget');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "archive_widget-$o";
			wp_register_sidebar_widget($id, __('Archives'), array('archive_widgets', 'widget_archives'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Archives'), array('archive_widgets_admin', 'widget_archives_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "archive_widget-1";
			wp_register_sidebar_widget($id, __('Archives'), array('archive_widgets', 'widget_archives'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Archives'), array('archive_widgets_admin', 'widget_archives_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	#
	# widget_archives()
	#
	
	function widget_archives($args, $widget_args = 1)
	{
		extract($args, EXTR_SKIP);
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('archive_widgets');
		if ( !isset($options[$number]) )
			return;
			
		$count = $options[$number]['count'] ? '1' : '0';
		$dropdown = $options[$number]['dropdown'] ? '1' : '0';
		$title = empty($options[$number]['title']) ? __('Archives') : $options[$number]['title'];

		if ( is_admin() )
		{
			echo $before_widget
				. $before_title
				. $title
				. $after_title
				. $after_widget;
			return;
		}
		
		echo $before_widget . "\n"
			. ( trim($title) !== ''
			 	? ( $before_title . $title . $after_title . "\n" )
				: ''
				);
				
		if($dropdown) 
		{
			echo '<select name="archive-dropdown" 
				onchange="document.location.href=this.options[this.selectedIndex].value;"> 
				<option value="">' . attribute_escape(__('Select Month')) . '</option> ';
			echo wp_get_archives("type=monthly&format=option&show_post_count=$count");
			echo '</select>';
		} 
		else 
		{
			echo '<ul>';
			echo wp_get_archives("type=monthly&show_post_count=$count");
			echo '</ul>';
		}
		
		echo $after_widget . "\n";
	} # widget_archives()

} # archive_widgets

archive_widgets::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/archive-widgets-admin.php';
}
?>