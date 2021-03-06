<?php
/*
Plugin Name: HitsLink
Plugin URI: http://www.semiologic.com/software/marketing/hitslink/
Description: Adds <a href="http://www.semiologic.com/go/hitslink">HitsLink</a> to your blog, with all sorts of advanced tracking toys enabled.
Author: Denis de Bernardy
Version: 1.2
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


# include admin stuff when relevant
if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/hitslink-admin.php';
}


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


load_plugin_textdomain('hitslink');


class hitslink
{
	#
	# init()
	#

	function init()
	{
		add_action('wp_head', array('hitslink', 'display_script'));

		# for testing
		#add_action('the_content', array('hitslink', 'track_links'));
	} # init()


	#
	# get_options()
	#

	function get_options()
	{
		if ( function_exists('get_site_option') )
		{
			$options = get_site_option('hitslink_params');
		}
		else
		{
			$options = get_option('hitslink_params');
		}

		return $options;
	} # get_options()


	#
	# display_script()
	#

	function display_script()
	{
		if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
		{
			return ;
		}

		$options = hitslink::get_options();

		if ( !$options['script'] )
		{
			echo __('<!-- You need to configure the HitsLink plugin under Options / HitsLink -->') . "\n";
		}
		elseif ( current_user_can('publish_posts') )
		{
			echo __('<!-- The HitsLink plugin does not track site authors, editors and admins when they are logged in -->') . "\n";
		}
		else
		{
			$script = $options['script'];
			$track = false;

			if ( isset($_GET['subscribed']) )
			{
				$track = "subscription";
				$data = preg_replace("/(?:\?|&)subscribed/", "", $_SERVER['REQUEST_URI']);
				$ref = '';
			}
			elseif ( is_404() || ( ( is_single() || is_page() ) && !have_posts() ) )
			{
				$track = "404";
				$data = $_SERVER['REQUEST_URI'];
				$ref = $_SERVER['HTTP_REFERER'];
			}
			elseif ( is_search() )
			{
				$track = "search";
				$data = $_REQUEST['s'];
				$ref = $_SERVER['HTTP_REFERER'];
			}
			else
			{
				$data = $_SERVER['REQUEST_URI'];
				$ref = $_SERVER['HTTP_REFERER'];
			}

			$site_url = ( $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];

			#echo '<pre>';
			#var_dump($data, $ref);
			#echo '</pre>';

			foreach ( array('data', 'ref') as $var )
			{
				if ( strpos(strtolower($$var), strtolower($site_url)) === 0 )
				{
					$$var = substr($$var, strlen($site_url));
				}
			}

			$data = preg_replace("/^https?:\/\/|^\/+/i", "", $data);

			foreach ( array('data', 'ref') as $var )
			{
				$$var = preg_replace("/[^a-z0-9\.\/+\?=-]+/i", "_", $$var);
				$$var = preg_replace("/^_+|_+$/i", "", $$var);
			}

			#echo '<pre>';
			#var_dump($url, $ref);
			#echo '</pre>';

			$script = str_replace(
				"wa_pageName=location.pathname;",
				"wa_pageName='"
					. $_SERVER['HTTP_HOST'] . "/"
					. ( $track ? $track . "/" : '' )
					. $data
					. ( $ref ? ( strpos($data, '?') === false ? '?' : '&' ) . "ref=" . $ref : '' )
					. "';",
				$script
				);

			echo $script;
		}
	} # display_script()
} # hitslink()

hitslink::init();
?>