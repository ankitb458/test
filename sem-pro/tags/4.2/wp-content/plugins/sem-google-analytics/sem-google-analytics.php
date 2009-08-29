<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://www.semiologic.com/software/google-analytics/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/google-analytics/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Adds <a href="http://analytics.google.com">Google analytics</a> to your blog, including outbound link tracking.
Author: Denis de Bernardy
Version: 1.8
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/

load_plugin_textdomain('sem-google-analytics');


if ( !defined('pattern_anchor') )
{
	define('pattern_anchor',
			"/
			<a					# anchor tag
				(?:\s+[^>]*)?	# optional attributes
				(?:
				\s+href=		# href attribute
					(
						\"[^\"]+\"	# double quoted link
					|
						'[^']+'		# single quoted link
					|
						\S+			# unquoted link
					)
				)
				(?:\s+[^>]*)?	# optional attributes
			>					# end anchor tag
			/mx");
}


class sem_google_analytics
{
	#
	# Variables
	#

	var $params = array(
			'script' => false		# string, false to disable
			);


	#
	# Constructor
	#

	function sem_google_analytics()
	{
		add_action('init', array(&$this, 'init'));
		add_action('wp_head', array(&$this, 'track'), 1000);
		#add_filter('the_content', array(&$this, 'change_links')); # for testing
		add_action('admin_menu', array(&$this, 'add2admin_menu'));
		add_action('wp_head', array(&$this, 'display_script'));
	} # end sem_google_analytics()


	#
	# init()
	#

	function init()
	{
		global $wpdb;
		$wpdb->hide_errors();
		$params = function_exists('get_site_option')
			? get_site_option('sem_google_analytics_params')
			: get_settings('sem_google_analytics_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			if ( function_exists('get_site_option') )
			{
				update_site_option('sem_google_analytics_params', $this->params);
			}
			else
			{
				update_option('sem_google_analytics_params', $this->params);
			}
		}
		$wpdb->show_errors();
	} # end init()


	#
	# add2admin_menu()
	#

	function add2admin_menu()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			add_options_page(
					__('Google&nbsp;Analytics', 'sem-google-analytics'),
					__('Google&nbsp;Analytics', 'sem-google-analytics'),
					8,
					str_replace("\\", "/", __FILE__),
					array(&$this, 'display_admin_page')
					);
		}
	} # end add2admin_menu()


	#
	# update()
	#

	function update()
	{
		$this->params = array();

		#echo '<pre>';
		#var_dump($_POST);
		#echo '</pre>';

		if ( !empty($_POST['ga_script']) )
		{
			$this->params['script'] = $_POST['ga_script'];
		}
		else
		{
			$this->params['script'] = false;
		}

		if ( function_exists('get_site_option') )
		{
			update_site_option('sem_google_analytics_params', $this->params);
		}
		else
		{
			update_option('sem_google_analytics_params', $this->params);
		}
	} # end update()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		# Process updates, if any

		if ( isset($_POST['action'])
			&& ( $_POST['action'] == 'update_sem_google_analytics' )
			)
		{
			$this->update();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.', 'sem-google-analytics')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		# Display admin page

		echo "<div class=\"wrap\">\n"
			. "<h2>" . __('Google Analytics Options', 'sem-google-analytics') . "</h2>\n"
			. "<form method=\"post\" action=\"\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"update_sem_google_analytics\" />\n";

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Google analytics script', 'sem-google-analytics') . "</legend>\n";

		echo "<p style=\"padding-bottom: 6px;\">"
				. "<label for=\"ga_script\">"
				. __('Paste the <a href="http://analytics.google.com">Google analytics</a> script into the following textarea:', 'sem-google-analytics')
				. "</label></p>\n"
				. "<textarea id=\"ga_script\" name=\"ga_script\""
					. " style=\"width: 400px; height: 160px;\">"
				. ( $this->params['script']
					? stripslashes(
						str_replace(
							array("<", ">", "\""),
							array("&lt;", "&gt;", "&quot;"),
							$this->params['script']
							)
						)
					: stripslashes(
						str_replace(
							array("<", ">", "\""),
							array("&lt;", "&gt;", "&quot;"),
							"<script src=\"http://www.google-analytics.com/urchin.js\" type=\"text/javascript\">
</script>
<script type=\"text/javascript\">
_uacct = \"your_id\";
urchinTracker();
</script>\n"
							)
						)
					)
				. "</textarea>\n";

		echo "</fieldset>\n";

		echo "<p class=\"submit\">"
			. "<input type=\"submit\""
				. " value=\"" . __('Update Options', 'sem-google-analytics') . "\""
				. " />"
			. "</p>\n";

		echo "</form>"
			. "</div>\n";
	} # end display_admin_page()


	#
	# track()
	#

	function track()
	{
		global $user_ID;
		global $user_level;

		if ( !$user_ID || intval($user_level) <= 1 )
		{
			ob_start(array(&$this, 'change_links'));
		}
	} # end track()


	#
	# display_script()
	#

	function display_script()
	{
		global $user_ID;
		global $user_level;

		if ( $this->params['script'] && ( !$user_ID || intval($user_level) <= 1 ) )
		{
			echo stripslashes($this->params['script']);
		}
		elseif ( !$this->params['script'] )
		{
			echo __('<!-- You need to configure Google analytics for it to work -->');
		}
		else
		{
			echo __('<!-- Google analytics is not active when users are logged in -->');
		}
	} # end display_script()


	#
	# change_links()
	#

	function change_links($text)
	{
		if ( !is_object($GLOBALS['wp_rewrite']) )
		{
			$GLOBALS['wp_rewrite'] =& new WP_Rewrite();
		}

		if ( $this->params['script'] )
		{
			return preg_replace_callback(
				pattern_anchor,
				array(&$this, 'change_link'),
				$text);
		}
		else
		{
			return $text;
		}
	} # end change_links()


	#
	# change_link()
	#

	function change_link($input)
	{
		$site_path = trailingslashit(get_settings('home'));
		$site_host = preg_replace("/^https?:\/\//", "", $site_path);
		$site_host = preg_replace("/\/.*$/", "", $site_host);

		$link = current($input);

		#echo '<pre>';
		#var_dump(str_replace(array("<", ">"), array("&lt;", "&gt;"), $link));
		#echo '</pre>';

		if ( ( strpos($link, "http://") !== false )
			&& ( strpos($link, $site_host) === false )
			)
		{
			preg_match("/href=\"([^\"]+)\"|href='([^']+)'/", $link, $out);

			$whereto = trailingslashit(end($out));

			preg_match("/http:\/\/([^\/]+)/", $whereto, $out);

			$whereto = end($out);

			#echo '<pre>';
			#var_dump(str_replace(array("<", ">"), array("&lt;", "&gt;"), $whereto));
			#echo '</pre>';

			if ( strpos($link, "onclick=") === false )
			{
				$link = str_replace(
					">",
					( " onclick=\"javascript:urchinTracker("
						. "'/outbound/" . $whereto
						. "');\">"
						),
					$link
					);
			}
			else
			{
				$link = preg_replace(
					"/(onclick=(\"|'))/",
					( "$1"
						. "javascript:urchinTracker("
						. "'/outbound/" . $whereto
						. "'); "
						),
					$link
					);
			}
		}

		#echo '<pre>';
		#var_dump(str_replace(array("<", ">"), array("&lt;", "&gt;"), $link));
		#echo '</pre>';

		return $link;
	} # end change_link()


	#
	# kill_gzip()
	#

	function kill_gzip($val)
	{
	    return false;
	} # end kill_gzip()
} # end sem_google_analytics

$sem_google_analytics =& new sem_google_analytics();
?>