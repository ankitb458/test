<?php
/*
Plugin Name: Dealdotcom
Plugin URI: http://www.semiologic.com/software/marketing/dealdotcom/
Description: A widget to display <a href="http://www.semiologic.com/go/dealdotcom">dealdotcom</a>'s deal of the day.
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: dealdotcom
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('dealdotcom','wp-content/plugins/dealdotcom');

class dealdotcom
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('dealdotcom', 'widgetize'));
		add_action('dealdotcom', array('dealdotcom', 'update'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		wp_register_sidebar_widget(
			"dealdotcom",
			__('Dealdotcom', 'dealdotcom'),
			array('dealdotcom', 'display_widget'),
			array('classname' => 'dealdotcom')
			);

		wp_register_widget_control(
			"dealdotcom",
			__('Dealdotcom', 'dealdotcom'),
			array('dealdotcom_admin', 'widget_control'),
			array('width' => 260, 'height' => 150)
			);
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args)
	{
		$options = get_option('dealdotcom');
		$deal = get_option('dealdotcom_deal');

		if ( !$deal )
		{
			$deal = dealdotcom::update();
		}

		echo $args['before_widget'];

		if ( !$options['aff_id'] )
		{
			echo '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. __('Your <a href="http://www.semiologic.com/go/dealdotcom">dealdotcom</a> affiliate ID is not configured.')
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
						. htmlspecialchars($options['aff_id'])
						. '"'
					. ' title="' . htmlspecialchars(
							$deal['name'] . ' @ $' . $deal['price']
							) . '"'
					. ( $options['nofollow']
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

		if ( function_exists('curl_init') )
		{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress');
			curl_setopt($ch, CURLOPT_HEADER, 0);

			$deal = @ curl_exec($ch);

			curl_close($ch);
		}
		else
		{
			require_once ABSPATH . WPINC . '/class-snoopy.php';

			$snoopy = new snoopy;
			$snoopy->agent = 'WordPress';

			@ $snoopy->fetch($url);

			$deal = $snoopy->results;
		}

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
} # dealdotcom

dealdotcom::init();

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/dealdotcom-admin.php';
}
?>