<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://www.semiologic.com/software/marketing/google-analytics/
Description: Adds <a href="http://analytics.google.com">Google analytics</a> to your blog, with various advanced tracking features enabled.
Author: Denis de Bernardy
Version: 2.5
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: google_analytics
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
	include_once dirname(__FILE__) . '/sem-google-analytics-admin.php';
}


load_plugin_textdomain('sem-google-analytics');


class sem_google_analytics
{
	#
	# init()
	#

	function init()
	{
		add_action('wp_head', array('sem_google_analytics', 'display_script'));

		# for testing
		#add_action('the_content', array('sem_google_analytics', 'track_links'));
	} # init()


	#
	# get_options()
	#

	function get_options()
	{
		if ( function_exists('get_site_option') )
		{
			$options = get_site_option('sem_google_analytics_params');
		}
		else
		{
			$options = get_option('sem_google_analytics_params');
		}

		if ( strpos($options['script'], '\\') !== false )
		{
			$options['script'] = stripslashes($options['script']);
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

		$options = sem_google_analytics::get_options();

		if ( !$options['script'] )
		{
			echo __('<!-- You need to configure the Google Analytics plugin under Options / Google Analytics -->') . "\n";
		}
		elseif ( current_user_can('publish_posts') )
		{
			echo __('<!-- The Google Analytics plugin does not track site authors, editors and admins when they are logged in -->') . "\n";
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
			elseif ( is_404() || ( is_singular() && !have_posts() ) )
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

			if ( $track )
			{
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
					"urchinTracker()",
					"urchinTracker('"
						. "/"
						. ( $track ? $track . "/" : '' )
						. $data
						. ( $ref ? ( strpos($data, '?') === false ? '?' : '&' ) . "ref=" . $ref : '' )
						. "')",
					$script
					);
			}

			echo $script;

			ob_start(array('sem_google_analytics', 'track_links'));
		}
	} # display_script()


	#
	# track_links()
	#

	function track_links($buffer)
	{
		if ( is_feed() )
		{
			return $buffer;
		}

		# escape head
		$buffer = preg_replace_callback(
			"/
			<\s*head				# head tag
				(?:\s[^>]*)?		# optional attributes
				>
			.*						# head code
			<\s*\/\s*head\s*>		# end of head tag
			/isUx",
			array('sem_google_analytics', 'escape'),
			$buffer
			);

		# escape scripts
		$buffer = preg_replace_callback(
			"/
			<\s*script				# script tag
				(?:\s[^>]*)?		# optional attributes
				>
			.*						# script code
			<\s*\/\s*script\s*>		# end of script tag
			/isUx",
			array('sem_google_analytics', 'escape'),
			$buffer
			);

		# escape objects
		$buffer = preg_replace_callback(
			"/
			<\s*object				# object tag
				(?:\s[^>]*)?		# optional attributes
				>
			.*						# object code
			<\s*\/\s*object\s*>		# end of object tag
			/isUx",
			array('sem_google_analytics', 'escape'),
			$buffer
			);

		$buffer = preg_replace_callback("/
			<\s*a					# ancher tag
				(?:\s[^>]*)?		# optional attributes
				\s*href\s*=\s*		# href=...
				(?:
					\"([^\"]*)\"	# double quoted link
				|
					'([^']*)'		# single quoted link
				|
					([^'\"]\S*)		# non-quoted link
				)
				(?:\s[^>]*)?		# optional attributes
				\s*>
			/isUx",
			array('sem_google_analytics', 'track_link'),
			$buffer
			);

		# unescape
		$buffer = sem_google_analytics::unescape($buffer);

		return $buffer;
	} # track_links()


	#
	# escape()
	#

	function escape($input)
	{
		global $sem_google_analytics_escape;

		$tag_id = '--google_analytics_escape:' . md5($input[0]) . '--';
		$sem_google_analytics_escape[$tag_id] = $input[0];

		return $tag_id;
	} # escape()


	#
	# unescape()
	#

	function unescape($input)
	{
		global $sem_google_analytics_escape;

		$find = array();
		$replace = array();

		foreach ( (array) $sem_google_analytics_escape as $key => $val )
		{
			$find[] = $key;
			$replace[] = $val;
		}

		return str_replace($find, $replace, $input);
	} # unescape()


	#
	# track_link()
	#

	function track_link($match)
	{
		$anchor = $match[0];
		$link = end($match);
		$site_url = ( $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];

		$onclick = "";

		#echo '<pre>';
		#var_dump(
		#	htmlspecialchars($anchor, ENT_QUOTES),
		#	$link
		#	preg_match("/^https?:\/\//i", $link)
		#	strpos($link, get_option('siteurl'))
		#	);
		#echo '</pre>';

		if ( preg_match("/^https?:\/\//i", $link) && ( strpos(strtolower($link), strtolower($site_url)) !== 0 ) )
		{
			$onclick = "outbound";
		}
		elseif ( preg_match("/
			\.
			(
				phps|inc|js|css
			|
				exe|com|dll|reg
			|
				jpe?g|gif|png
			|
				zip|tar\.gz|tgz
			|
				mp3|wav
			|
				mpeg|avi|mov|swf
			|
				pdf|doc|rtf|xls
			|
				txt|csv
			)
			(
				$
			|
				\?.*
			)
			/imx",
			$link
			) )
		{
			$onclick = "file";
		}

		#echo '<pre>';
		#var_dump($onclick);
		#echo '</pre>';

		if ( $onclick )
		{
			$url = $link;
			$ref = $_SERVER['HTTP_REFERER'];

			#echo '<pre>';
			#var_dump($url, $ref);
			#echo '</pre>';

			foreach ( array('url', 'ref') as $var )
			{
				if ( strpos(strtolower($$var), strtolower($site_url)) === 0 )
				{
					$$var = substr($$var, strlen($site_url));
				}
			}

			$url = preg_replace("/^https?:\/\/|^\/+/i", "", $url);

			foreach ( array('url', 'ref') as $var )
			{
				$$var = preg_replace("/[^a-z0-9\.\/+\?=-]+/i", "_", $$var);
				$$var = preg_replace("/^_+|_+$/i", "", $$var);
			}

			#echo '<pre>';
			#var_dump($url, $ref);
			#echo '</pre>';

			$onclick = "javascript:urchinTracker('"
				. "/" . $onclick
				. "/" . $url
				. ( $ref ? ( strpos($data, '?') === false ? '?' : '&' ) . "ref=" . $ref : '' )
				. "');";

			if ( preg_match("/
					onclick\s*=\s*
						(?:
							(\")([^\"]*)\"
						|
							(')([^']*)'
						)
					/iUx",
					$anchor,
					$match
					)
				)
			{
				if ( $match[3] == "'" )
				{
					$onclick = addslashes($onclick);
				}

				$old_onclick = $match[0];
				$new_onclick = str_replace(end($match), $onclick . ' ' . end($match), $old_onclick);

				#echo '<pre>';
				#var_dump($new_onclick);
				#echo '</pre>';

				$anchor = str_replace($old_onclick, $new_onclick, $anchor);
			}
			else
			{
				$anchor = str_replace('>', ' onclick="' . $onclick . '">', $anchor);
			}
		}

		return $anchor;
	} # track_link()
} # sem_google_analytics()

sem_google_analytics::init();
?>