<?php
/*
Plugin Name: Dealdotcom Widgets
Plugin URI: http://www.semiologic.com/software/marketing/dealdotcom/
Description: Widgets to display <a href="http://go.semiologic.com/dealdotcom">dealdotcom</a>'s deal of the day.
Version: 1.2 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('dealdotcom', null, basename(dirname(__FILE__)) . '/lang');


/**
 * dealdotcom
 *
 * @package DealDotCom Widgets
 **/

add_action('widgets_init', array('dealdotcom', 'widgetize'));

if ( !is_admin() ) {
	add_action('dealdotcom', array('dealdotcom', 'update'));
}

class dealdotcom {
	/**
	 * widgetize()
	 *
	 * @return void
	 */

	function widgetize() {
		$options = dealdotcom::get_options();
		
		$widget_options = array('classname' => 'dealdotcom', 'description' => __( "Dealdotcom's deal of the day") );
		$control_options = array('width' => 400, 'id_base' => 'dealdotcom');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o ) {
			if ( !is_numeric($o) ) continue;
			$id = "dealdotcom-$o";
			wp_register_sidebar_widget($id, __('Dealdotcom'), array('dealdotcom', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Dealdotcom'), array('dealdotcom_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id ) {
			$id = "dealdotcom-1";
			wp_register_sidebar_widget($id, __('Dealdotcom'), array('dealdotcom', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Dealdotcom'), array('dealdotcom_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	/**
	 * display_widget()
	 *
	 * @param array $args Widget arguments
	 * @param int $widget_args Widget number
	 * @return void
	 **/

	function display_widget($args, $widget_args = 1) {
		if ( is_admin() ) return;
		
		$deal = get_option('dealdotcom_deal');
		$options = dealdotcom::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		echo $args['before_widget'];

		if ( !$options[$number]['aff_id'] ) {
			echo '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. sprintf(__('Your <a href="%s">dealdotcom</a> affiliate ID is not configured.'), 'http://go.semiologic.com/dealdotcom')
				. '</div>';
		} else {
			if ( !$deal ) {
				$deal = dealdotcom::update();
			}
			
			if ( is_ssl() ) {
				$deal['image'] = str_replace('http://', 'https://', $deal['image']);
			}
			
			$plugin_path = plugins_url() . '/' . basename(dirname(__FILE__));

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
	
	
	/**
	 * update()
	 *
	 * @return void
	 **/

	function update() {
		$url = 'http://www.dealdotcom.com/wp';
		
		$deal = wp_remote_fopen($url);
		
		if ( $deal ) {
			list($name, $price, $image) = split("<br>", $deal);
			$name = trim($name);
			$price = trim($price);
			$image = trim($image);
			$deal = compact('name', 'price', 'image');
		}

		update_option('dealdotcom_deal', $deal);

		if ( !wp_next_scheduled('dealdotcom') )
			wp_schedule_event(time(), 'hourly', 'dealdotcom');

		return $deal;
	} # update()
	
	
	/**
	 * get_options()
	 *
	 * @return void
	 **/

	function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('dealdotcom_widgets');
		
		if ( $o === false )
			$o = dealdotcom::init_options();
		
		return $o;
	} # get_options()
	
	
	/**
	 * default_options()
	 *
	 * @return void
	 **/

	function default_options() {
		return array(
			'aff_id' => '',
			'nofollow' => true
			);
	} # default_options()
	
	
	/**
	 * init_options()
	 *
	 * @return void
	 **/

	function init_options() {
		if ( ( $o = get_option('dealdotcom') ) !== false )
		{
			$o = array( 1 => $o );
			
			foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k ) {
				if ( !is_array($sidebars[$k]) )
					continue;

				if ( ( $key = array_search('dealdotcom', $sidebars[$k]) ) !== false ) {
					$sidebars[$k][$key] = 'dealdotcom-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				} elseif ( ( $key = array_search('Dealdotcom', $sidebars[$k]) ) !== false ) {
					$sidebars[$k][$key] = 'dealdotcom-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
			}
		} else {
			$o = array();
		}

		update_option('dealdotcom_widgets', $o);
		
		return $o;
	} # init_options()
} # dealdotcom

if ( is_admin() )
	include dirname(__FILE__) . '/dealdotcom-widgets-admin.php';
?>