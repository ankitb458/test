<?php
/*
Plugin Name: Dealdotcom Widgets
Plugin URI: http://www.semiologic.com/software/marketing/dealdotcom/
Description: Widgets to display <a href="http://go.semiologic.com/dealdotcom">dealdotcom</a>'s deal of the day.
Version: 1.1.3
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('dealdotcom','wp-content/plugins/dealdotcom-widgets');

class dealdotcom
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('dealdotcom', 'widgetize'));
		
		if ( !is_admin() )
		{
			add_action('dealdotcom', array('dealdotcom', 'update'));
		}
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = dealdotcom::get_options();
		
		$widget_options = array('classname' => 'dealdotcom', 'description' => __( "Dealdotcom's deal of the day") );
		$control_options = array('width' => 260, 'id_base' => 'dealdotcom');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "dealdotcom-$o";
			wp_register_sidebar_widget($id, __('Dealdotcom'), array('dealdotcom', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Dealdotcom'), array('dealdotcom_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "dealdotcom-1";
			wp_register_sidebar_widget($id, __('Dealdotcom'), array('dealdotcom', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Dealdotcom'), array('dealdotcom_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args, $widget_args = 1)
	{
		if ( is_admin() ) return;
		
		$deal = get_option('dealdotcom_deal');
		$options = dealdotcom::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		if ( !$deal )
		{
			$deal = dealdotcom::update();
		}

		echo $args['before_widget'];

		if ( !$options[$number]['aff_id'] )
		{
			echo '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. __('Your <a href="http://go.semiologic.com/dealdotcom">dealdotcom</a> affiliate ID is not configured.')
				. '</div>';
		}
		else
		{
			$plugin_path = str_replace(
					str_replace('\\', '/', ABSPATH),
					trailingslashit(get_option('siteurl')),
					str_replace('\\', '/', dirname(__FILE__))
					);

			echo '<div style="'
					. 'width: 148px;'
					. 'margin: 0px auto;'
					. 'border: solid 2px orange;'
					. 'text-align: center;'
					. '">'
				. '<a href="'
					. 'http://dealdotcom.com/invite/'
						. htmlspecialchars($options[$number]['aff_id'])
						. '"'
					. ' title="' . htmlspecialchars(
							$deal['name'] . ' @ $' . $deal['price']
							) . '"'
					. ( $options[$number]['nofollow']
						? ' rel="nofollow"'
						: ''
						)
					. '>'
				. '<img src="'
					. $plugin_path
					. '/dealdotcom-top.gif'
					. '" alt="" />'
				. '<br />'
				. '<img src="'
					. htmlspecialchars($deal['image'])
					. '"'
					. ' border="0"'
					. ' alt="' . htmlspecialchars(
							$deal['name'] . ' @ $' . $deal['price']
							) . '"'
					. ' style="'
						. 'width: 148px;'
						. 'margin: 3px auto;'
						. '"'
					. '/>'
				. '<br />'
				. '<img src="'
					. $plugin_path
					. '/dealdotcom-bottom.gif'
					. '" alt="" />'
				. '</a>'
				. '</div>';
		}

		echo $args['after_widget'];
	} # display_widget()


	#
	# update()
	#

	function update()
	{
		$url = 'http://www.dealdotcom.com/wp';
		
		$deal = wp_remote_fopen($url);

		if ( $deal )
		{
			list($name, $price, $image) = split("<br>", $deal);
			$name = trim($name);
			$price = trim($price);
			$image = trim($image);
			$deal = compact('name', 'price', 'image');
		}

		update_option('dealdotcom_deal', $deal);

		if ( !wp_next_scheduled('dealdotcom') )
		{
			wp_schedule_event(time(), 'hourly', 'dealdotcom');
		}

		return $deal;
	} # update()


	#
	# get_options()
	#

	function get_options()
	{
		if ( ( $o = get_option('dealdotcom_widgets') ) === false )
		{
			if ( ( $o = get_option('dealdotcom') ) !== false )
			{
				$o = array( 1 => $o );
				
				foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k )
				{
					if ( !is_array($sidebars[$k]) )
					{
						continue;
					}

					if ( ( $key = array_search('dealdotcom', $sidebars[$k]) ) !== false )
					{
						$sidebars[$k][$key] = 'dealdotcom-1';
						update_option('sidebars_widgets', $sidebars);
						break;
					}
					elseif ( ( $key = array_search('Dealdotcom', $sidebars[$k]) ) !== false )
					{
						$sidebars[$k][$key] = 'dealdotcom-1';
						update_option('sidebars_widgets', $sidebars);
						break;
					}
				}
			}
			else
			{
				$o = array();
			}

			update_option('dealdotcom_widgets', $o);
		}

		return $o;
	} # get_options()


	#
	# default_options()
	#

	function default_options()
	{
		return array(
			'aff_id' => '',
			'nofollow' => false
			);
	} # default_options()
} # dealdotcom

dealdotcom::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/dealdotcom-widgets-admin.php';
}
?>