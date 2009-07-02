<?php
/*
Plugin Name: Subscribe me
Plugin URI: http://www.semiologic.com/software/widgets/subscribe-me/
Description: Adds a widget with feed subscription buttons.
Author: Denis de Bernardy
Version: 4.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: subscribe_me
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat tips
--------

	* James Huff <http://www.macmanx.com>
	* Duke Thor <http://blog.dukethor.info>
	* Mike Koepke <http://www.mikekoepke.com>
**/


load_plugin_textdomain('sem-subscribe-me');

class subscribe_me
{
	#
	# get_services()
	#

	function get_services()
	{
		return array(
			'local_feed' => array(
				'name' => __('RSS Feed'),
				'button' => 'rss.png',
				'url' => apply_filters('bloginfo', get_feed_link('rss2'), 'rss2_url'),
				),
			'bloglines' => array(
				'name' => 'Bloglines',
				'button' => 'addbloglines.gif',
				'url' => 'http://www.bloglines.com/sub/%feed_url%',
				),
			'google' => array(
				'name' => 'Google',
				'button' => 'addgoogle.gif',
				'url' => 'http://fusion.google.com/add?feedurl=%feed_url%',
				),
			'yahoo' => array(
				'name' => 'MyYahoo!',
				'button' => 'addmyyahoo.gif',
				'url' => 'http://add.my.yahoo.com/rss?url=%feed_url%',
				),
			'msn' => array(
				'name' => 'MyMSN',
				'button' => 'addmymsn.gif',
				'url' => 'http://my.msn.com/addtomymsn.armx?id=rss&amp;ut=%feed_url&amp;ru=%site_url%',
				),
			'aol' => array(
				'name' => 'MyAOL',
				'button' => 'addmyaol.gif',
				'url' => 'http://feeds.my.aol.com/add.jsp?url=%feed_url%',
				),
			'feedlounge' => array(
				'name' => 'FeedLounge',
				'button' => 'addfeedlounge.gif',
				'url' => 'http://my.feedlounge.com/external/subscribe?url=%feed_url%',
				),
			'newsburst' => array(
				'name' => 'Newsburst',
				'button' => 'addnewsburst.gif',
				'url' => 'http://www.newsburst.com/Source/?add=%feed_url%',
				),
			'newsgator' => array(
				'name' => 'Newsgator',
				'button' => 'addnewsgator.gif',
				'url' => 'http://www.newsgator.com/ngs/subscriber/subext.aspx?url=%feed_url%',
				),
			'netvibes' => array(
				'name' => 'Netvibes',
				'button' => 'addnetvibes.gif',
				'url' => 'http://www.netvibes.com/subscribe.php?url=%feed_url%',
				),
			'rojo' => array(
				'name' => 'Rojo',
				'button' => 'addrojo.gif',
				'url' => 'http://www.rojo.com/add-subscription?resource=%feed_url%'
				),
			'pageflakes' => array(
				'name' => 'Pageflakes',
				'button' => 'addpageflakes.gif',
				'url' => 'http://www.pageflakes.com/subscribe.aspx?url=%feed_url%',
				),
			'live' => array(
				'name' => 'Windows Live',
				'button' => 'addwindowslive.gif',
				'url' => 'http://www.live.com/?add=%feed_url%',
				),
			'help_link' => array(
				'name' => __('Help'),
				'button' =>'help.gif',
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-feeds/'
				),
			);
	} # get_services()


	#
	# default_services()
	#

	function default_services()
	{
		return array(
			'local_feed',
			'bloglines',
			'help_link'
			);
	} # default_services()


	#
	# get_service()
	#

	function get_service($key = 'local_feed')
	{
		$services = subscribe_me::get_services();

		return $services[$key];
	} # get_service()


	#
	# display()
	#

	function display($args = null)
	{
		# default args

		$defaults = array(
			'title' => __('Syndicate'),
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>'
			);

		$options = get_option('sem_subscribe_me_params');

		$args = array_merge($defaults, (array) $options, (array) $args);

		$args['site_path'] = trailingslashit(get_option('siteurl'));
		$args['feed_url'] = apply_filters('bloginfo', get_feed_link('rss2'), 'rss2_url');
		$args['services'] = get_option('sem_subscribe_me_services');
		$args['img_path'] = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/sem-subscribe-me/img/';

		if ( !$args['services'] )
		{
			$args['services'] = subscribe_me::default_services();
		}

		$hash = md5(uniqid(rand()));

		$cache_file = ABSPATH . 'wp-content/cache/subscribe-me-' . md5(serialize($args));

		# return cache if relevant

		if ( file_exists($cache_file) )
		{
			$o = file_get_contents($cache_file);

			$o = str_replace('{$hash}', $hash, $o);

			return $o;
		}


		# process output

		$as_dropdown = intval($options['dropdown']);

		$o = '';

		$o .= $args['before_widget'] . "\n"
			. ( $args['title']
				? ( $args['before_title'] . $args['title'] . $args['after_title'] . "\n" )
				: ''
				);

		$o .= '<div'
				. ( $as_dropdown
					? ( ' onmouseover="fade_subscribe_buttons_in(\'subscribe_me_{$hash}\');"'
						. ' onmouseout="fade_subscribe_buttons_out(\'subscribe_me_{$hash}\');"'
						)
					: ''
					)
				. '>' . "\n";

		if ( $as_dropdown )
		{
			$o .= '<div class="subscribe_service">'
				. '<a href="'
					. $args['feed_url']
					. '">'
				. '<img'
					. ' src="' . $args['img_path'] . 'subscribe.gif"'
					. ' alt="' . __('RSS Feed') . '"'
					. ' />'
				. '</a>'
				. '</div>' . "\n";
		}

		$o .= '<div'
			. ' class="subscribe_services' . ( $as_dropdown ? ' subscribe_dropdown' : '' ) . '"'
			. ' id="subscribe_me_{$hash}"'
			. '>';

		if ( $as_dropdown ) $o .= '<div style="clear: both;"></div>' . "\n";

		foreach ( (array) $args['services'] as $service )
		{
			$details = subscribe_me::get_service($service);

			if ( $details )
			{
				switch( $service )
				{
				case 'local_feed':
				case 'help_link':
					$o .= '<div class="subscribe_service">'
						. '<a'
							. ' href="' . $details['url'] . '"'
							. ' style="background: url('
								. $args['img_path'] . $details['button']
								. ')'
								. ' center left no-repeat;'
								. ' padding-left: 18px;"'
							. ( ( $options['add_nofollow'] && $service != 'local_feed' )
								? ' rel="nofollow"'
								: ''
								)
							. '>'
						. $details['name']
						. '</a>'
						. '</div>' . "\n";
					break;
				default:
					$o .= '<div class="subscribe_service">'
						. '<a'
							. ' href="'
								. str_replace(
									'%site_url%',
									urlencode($args['site_path']),
									str_replace(
										'%feed_url%',
										( strpos($details['url'], '?') !== false
											? urlencode($args['feed_url'])
											: $args['feed_url']
											),
										$details['url']
										)
									) . '"'
							. ( $options['add_nofollow']
								? ' rel="nofollow"'
								: ''
								)
							. '>'
						. '<img'
							. ' src="' . $args['img_path'] . $details['button'] . '"'
							. ' alt="' . str_replace('%feed%', $details['name'], __('Subscribe to %feed%')) . '"'
							. ' />'
						. '</a>'
						. '</div>' . "\n";
					break;
				}
			}
		}

		if ( $as_dropdown ) $o .= '<div style="clear: both;"></div>' . "\n";

		$o .= '</div>' . "\n";

		$o .= '</div>' . "\n"
			. $args['after_widget'] . "\n";


		# clean cache

		if ( is_writable(dirname($cache_file)) )
		{
			$cache_files = glob(dirname($cache_file) . '/subscribe-me*');

			foreach ( (array) $cache_files as $cache_file )
			{
				@unlink($cache_file);
			}
		}


		# store output

		if ( is_writable($cache_file) && is_writable(dirname($cache_file)) )
		{
			$fp = fopen($cache_file, "w+");
			fwrite($fp, $o);
			fclose($fp);
		}


		# return output

		$o = str_replace('{$hash}', $hash, $o);

		return $o;
	} # display()


	#
	# css()
	#

	function css()
	{
		echo '<link rel="stylesheet" type="text/css"'
			. ' href="'
				. trailingslashit(get_option('siteurl'))
				. 'wp-content/plugins/sem-subscribe-me/sem-subscribe-me.css'
				. '"'
			. ' />' . "\n";
	} # css()


	#
	# js()
	#

	function js()
	{
		echo '<script type="text/javascript"'
			. ' src="'
				. trailingslashit(get_option('siteurl'))
				. 'wp-content/plugins/sem-subscribe-me/sem-subscribe-me.js'
				. '"'
			. '></script>' . "\n";
	} # js()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_sidebar_widget('Subscribe Me', array('subscribe_me', 'display_widget'));
		}
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args)
	{
		echo subscribe_me::display($args);
	} # display_widget()
} # subscribe_me

add_action('wp_head', array('subscribe_me', 'css'));
add_action('wp_head', array('subscribe_me', 'js'));
add_action('widgets_init', array('subscribe_me', 'widgetize'));


#
# the_subscribe_links()
#

function the_subscribe_links()
{
	echo subscribe_me::display();
} # the_subscribe_links()


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-subscribe-me-admin.php';
}
?>