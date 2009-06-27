<?php
/*
Plugin Name: Dealdotcom Widgets
Plugin URI: http://www.semiologic.com/software/dealdotcom/
Description: Widgets to display <a href="http://go.semiologic.com/dealdotcom">dealdotcom</a>'s deal of the day.
Version: 2.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: deadotcom-widgets
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('dealdotcom', null, dirname(__FILE__) . '/lang');


/**
 * dealdotcom
 *
 * @package DealDotCom Widgets
 **/

add_action('widgets_init', array('dealdotcom', 'widgets_init'));
add_action('dealdotcom_update', array('dealdotcom', 'update_deal'));

class dealdotcom extends WP_Widget {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_dealdotcom') === false ) {
			foreach ( array(
				'dealdotcom_widgets' => 'upgrade',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('dealdotcom');
	} # widgets_init()
	
	
	/**
	 * dealdotcom()
	 *
	 * @return void
	 **/

	function dealdotcom() {
		$widget_ops = array(
			'classname' => 'dealdotcom',
			'description' => __('Dealdotcom\'s deal of the day', 'dealdotcom'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->init();
		$this->WP_Widget('dealdotcom', __('Dealdotcom Widget', 'dealdotcom'), $widget_ops, $control_ops);
	} # dealdotcom()
	
	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( is_admin() )
			return;
		
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, dealdotcom::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( !$aff_id ) {
			echo $before_widget
				. '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. sprintf(__('Your <a href="%s">dealdotcom</a> affiliate ID is not configured.'), 'http://go.semiologic.com/dealdotcom')
				. '</div>'
				. $after_widget;
			return;
		}
		
		$deal = get_transient('dealdotcom_deal');
		
		if ( !$deal )
			$deal = dealdotcom::update_deal();
		
		if ( !$deal )
			return;
		
		if ( is_ssl() )
			$deal['image'] = str_replace('http://', 'https://', $deal['image']);
		
		
		$plugin_path = plugin_dir_url(__FILE__);
		
		echo $before_widget
			. '<div style="width: 148px; margin: 0px auto; border: solid 2px orange; text-align: center;">'
			. '<a href="' . esc_url('http://dealdotcom.com/invite/' . intval($aff_id)) . '"'
				. ' title="' . esc_attr($deal['name'] . ' @ $' . $deal['price']) . '"'
				. ' rel="nofollow"'
				. '>'
			. '<img src="' . esc_url($plugin_path . 'dealdotcom-top.gif') . '" alt="" />'
			. '<br />'
			. '<img src="' . esc_url($deal['image']) . '" border="0"'
				. ' alt="' . esc_attr($deal['name'] . ' @ $' . $deal['price']) . '"'
				. ' style="width: 148px; margin: 3px auto;"'
				. '/>'
			. '<br />'
			. '<img src="' . esc_url($plugin_path . 'dealdotcom-bottom.gif') . '" alt="" />'
			. '</a>'
			. '</div>'
			. $after_widget;
	} # widget()
	
	
	/**
	 * update_deal()
	 *
	 * @return void
	 **/

	function update_deal() {
		$url = 'http://www.dealdotcom.com/wp';
		
		$deal = wp_remote_fopen($url);
		
		if ( $deal ) {
			list($name, $price, $image) = split("<br>", $deal);
			$name = trim($name);
			$price = trim($price);
			$image = trim($image);
			$deal = compact('name', 'price', 'image');
		}

		set_transient('dealdotcom_deal', $deal);

		if ( !wp_next_scheduled('dealdotcom_update') )
			wp_schedule_event(time(), 'hourly', 'dealdotcom_update');

		return $deal;
	} # update_deal()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['aff_id'] = strip_tags($new_instance['aff_id']);
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance
	 * @return void
	 **/

	function form($instance) {
		extract($instance, EXTR_SKIP);
		
		echo '<p>'
			. sprintf(__('Your <a href="%s">DealDotCom</a> Affiliate ID:', 'dealdotcom'), esc_url('http://go.semiologic.com/dealdotcom'))
				. '<br />'
			. '<label>'
			. 'http://www.dealdotcom.com/invite/'
			. '<input class="widefat" style="width: 50px;"'
			. ' name="' . $this->get_field_name('aff_id') . '"'
			. ' type="text" value="' . esc_attr($aff_id) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return void
	 **/

	function defaults() {
		return array(
			'aff_id' => '',
			);
	} # defaults()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		foreach ( $ops as $k => $o ) {
			$ops[$k] = array(
				'aff_id' => $o['aff_id']
				);
			if ( isset($widget_contexts['dealdotcom-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['dealdotcom-' . $k];
			}
		}
		
		return $ops;
	} # upgrade()
} # dealdotcom
?>