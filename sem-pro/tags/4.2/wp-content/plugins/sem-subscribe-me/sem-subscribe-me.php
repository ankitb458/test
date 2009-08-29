<?php
/*
Plugin Name: Subscribe me
Plugin URI: http://www.semiologic.com/software/subscribe-me/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/subscribe-me/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Displays a tile with subscribe buttons. To use, call the_subscribe_links(); where you want the tile to appear. Alternatively, do nothing and the tile will display when wp_meta(); is called.
Author: Denis de Bernardy
Version: 2.14
Author URI: http://www.semiologic.com
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

if ( !defined('sem_cache_path') )
{
	define('sem_cache_path', ABSPATH . 'wp-content/cache/'); # same as wp-cache
}
if ( !defined('sem_cache_timeout') )
{
	define('sem_cache_timeout', 3600); # one hour
}


class sem_subscribe_me
{
	#
	# Variables
	#

	var $path;
	var $cache_file;

	var $captions = array(
			'subscribe_to_feed' => 'Subscribe to %feed%',
			'syndicate' => 'Syndicate'
			);

	var $services = array(
			'local_feed',
			'bloglines',
			'google',
			'yahoo',
			'help_link'
			);

	var $service_names = array(
			'local_feed' => 'RSS Feed',
			'bloglines' => 'Bloglines',
			'google' => 'Google',
			'yahoo' => 'MyYahoo!',
			'msn' => 'MyMSN',
			'aol' => 'MyAOL',
			'feedlounge' => 'FeedLounge',
			'newsburst' => 'Newsburst',
			'newsgator' => 'Newsgator',
			'netvibes' => 'Netvibes',
			'rojo' => 'Rojo',
			'help_link' => 'Help with feeds'
			);

	var $service_icons = array(
			'local_feed' => 'rss.png',
			'bloglines' => 'addbloglines.gif',
			'google' => 'addgoogle.gif',
			'yahoo' => 'addmyyahoo.gif',
			'msn' => 'addmymsn.gif',
			'aol' => 'addmyaol.gif',
			'feedlounge' => 'addfeedlounge.gif',
			'newsburst' => 'addnewsburst.gif',
			'newsgator' => 'addnewsgator.gif',
			'netvibes' => 'addnetvibes.gif',
			'rojo' => 'addrojo.gif',
			'help_link' =>'help.gif'
			);

	var $service_subscribe_urls = array(
			'bloglines' => 'http://www.bloglines.com/sub/%feed_url%',
			'google' => 'http://fusion.google.com/add?feedurl=%feed_url%',
			'yahoo' => 'http://add.my.yahoo.com/rss?url=%feed_url%',
			'msn' => 'http://my.msn.com/addtomymsn.armx?id=rss&amp;ut=%feed_url%&amp;ru=%site_url%',
			'aol' => 'http://feeds.my.aol.com/add.jsp?url=%feed_url%',
			'feedlounge' => 'http://my.feedlounge.com/external/subscribe?url=%feed_url%',
			'newsburst' => 'http://www.newsburst.com/Source/?add=%feed_url%',
			'newsgator' => 'http://www.newsgator.com/ngs/subscriber/subext.aspx?url=%feed_url%',
			'netvibes' => 'http://www.netvibes.com/subscribe.php?url=%feed_url%',
			'rojo' => 'http://www.rojo.com/add-subscription?resource=%feed_url%'
			);

	#
	# Constructor
	#

	function sem_subscribe_me()
	{
		$this->path = str_replace(
					str_replace("\\", "/", ABSPATH),
					'',
					str_replace("\\", "/", dirname(__FILE__))
					);


		$this->cache_file = sem_cache_path . 'sem-subscribe-me';

		$services = get_settings('sem_subscribe_me_services');

		if ( $services )
		{
			$this->services =& $services;
		}
		else
		{
			update_option('sem_subscribe_me_services', $this->services);
		}

		if ( isset($_GET['action'])
			&& in_array($_GET['action'], array('flush', 'flush_cache'))
			)
		{
			$this->flush_cache();
		}

		add_action('admin_menu', array(&$this, 'add2admin_menu'));

		#add_action('generate_rewrite_rules', array(&$this, 'flush_cache'), 0);
		add_action('init', array(&$this, 'init'));

		add_action('plugins_loaded', array(&$this, 'widgetize'));

		$this->displayed = false;
		add_action('wp_meta', array(&$this, 'auto_display'));
	} # end sem_subscribe_me()


	#
	# init()
	#

	function init()
	{
		global $sem_theme_captions;

		if ( isset($sem_theme_captions) )
		{
			$this->captions = $sem_theme_captions->register($this->captions);
		}
		else
		{
			array_walk($this->captions, '__');
		}
	} # end init()


	#
	# flush_cache()
	#

	function flush_cache()
	{
		if ( is_writable(sem_cache_path) )
		{
			#$cache_files = glob($this->cache_file ."*");
			$cache_files = glob(sem_cache_path ."*");

			if ( $cache_files )
			{
				foreach ( $cache_files as $cache_file )
				{
					if ( is_file($cache_file) && is_writable($cache_file) )
					{
						unlink( $cache_file );
					}
				}
			}
		}
	} # end flush_cache()


	#
	# add2admin_menu()
	#

	function add2admin_menu()
	{
		add_options_page(
				__('Subscribe&nbsp;Me', 'sem-subscribe-me'),
				__('Subscribe&nbsp;Me', 'sem-subscribe-me'),
				8,
				str_replace("\\", "/", __FILE__),
				array(&$this, 'display_admin_page')
				);
	} # end add2admin_menu()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		$site_path = trailingslashit(get_settings('siteurl'));

		# Process updates, if any

		if ( isset($_POST['action'])
			&& ( $_POST['action'] == 'update_sem_subscribe_me' )
			)
		{
			$this->services = array();

			foreach ( $this->service_names as $service_id => $service_name )
			{
				if ( isset($_POST['sem_subscribe_me_' . $service_id]) )
				{
					$this->services[] = $service_id;
				}
			}

			update_option('sem_subscribe_me_services', $this->services);
			$this->flush_cache();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.', 'sem-subscribe-me')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		# Display admin page

		$feed_url = apply_filters(
				'bloginfo',
				get_feed_link('rss2'),
				'rss2_url'
				);

		foreach ( $this->service_names as $service_id => $service_name )
		{
			if ( !isset($this->captions[$service_id]) )
			{
				$this->captions[$service_id] = __($service_name, 'sem-subscribe-me');
			}
		}

		echo "<div class=\"wrap\">\n"
			. "<h2>" . __('Syndication Options', 'sem-subscribe-me') . "</h2>\n"
			. "<form method=\"post\" action=\"\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"update_sem_subscribe_me\" />\n";

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Active syndication services', 'sem-subscribe-me') . "</legend>\n"
			. "<p>" . __('Select your favorite syndication services.', 'sem-subscribe-me') . "</p>\n"
			. "<table>\n";

		foreach ( $this->service_names as $service_id => $service_name )
		{
			echo "<tr>";

			switch ( $service_id )
			{
			case 'local_feed':
				echo "<td style=\"width: 150px;\">"
					. "<a href=\"" . $feed_url . "\""
						. " style=\"background: url("
								. $site_path
								. $this->path
								. "/img/"
								. $this->service_icons[$service_id]
								. ")"
								. " center left no-repeat;"
								. " padding-left: 18px;\""
						. ' class="noicon"'
						. ">"
						. $this->captions[$service_id]
						. "</a>"
					. "</td>";
				break;

			case 'help_link':
				echo "<td>"
					. "<a href=\"http://www.semiologic.com/resources/help-with-feeds/\""
						. " style=\"background: url("
							. $site_path
							. $this->path
							. "/img/help.gif)"
							. " center left no-repeat;"
							. " padding-left: 18px;\""
						. ' class="noicon"'
						. ' />'
						. $this->captions[$service_id]
						. "</a>"
					. "</td>";
				break;

			default:
				echo "<td>"
					. "<a"
						. " href=\"" . str_replace(
										"%site_url%",
										$site_path,
										str_replace(
											"%feed_url%",
											$feed_url,
											$this->service_subscribe_urls[$service_id]
											)
										)
									. "\""
						. ' class="noicon"'
						. ">"
					. "<img"
						. " src=\"" . $site_path
									. $this->path
									. "/img/"
									. $this->service_icons[$service_id]
									. "\""
						. " alt=\"" . str_replace(
										"%feed%",
										$this->captions[$service_id],
										$this->captions['subscribe_to_feed']
										)
									. "\""
						. " align=\"middle\""
						. " style=\"border: none;\""
						. " />"
						. "</a>"
					. "</td>";
				break;
			}

			echo "<td>"
					. "<label for=\"sem_subscribe_me_" . $service_id . "\">"
						. "<input type=\"checkbox\""
							. " id=\"sem_subscribe_me_" . $service_id . "\""
							. " name=\"sem_subscribe_me_" . $service_id . "\""
							. ( in_array($service_id, $this->services)
								? " checked=\"checked\""
								: ""
								)
							. " /> "
						. ( $service_id != 'help_link'
							? str_replace(
								"%feed%",
								$this->captions[$service_id],
								$this->captions['subscribe_to_feed']
								)
							: $this->captions[$service_id]
							)
						. "</label>"
					. "</td>";

			echo "</tr>\n";
		}

		echo "</table>\n"
			. "</fieldset>\n"
			. "<p class=\"submit\">"
			. "<input type=\"submit\""
				. " value=\"" . __('Update Options', 'sem-subscribe-me') . "\""
				. " />"
			. "</p>\n";

		echo "</form>"
			. "</div>\n";
	} # end display_admin_page()


	#
	# display()
	#

	function display($args = null)
	{
		if ( !$this->displayed )
		{
			$this->displayed = true;
		}

		if ( !isset($args['before_widget']) )
		{
			$args['before_widget'] = '';
		}
		if ( !isset($args['after_widget']) )
		{
			$args['after_widget'] = '';
		}
		if ( !isset($args['before_title']) )
		{
			$args['before_title'] = '<h2>';
		}
		if ( !isset($args['after_title']) )
		{
			$args['after_title'] = '</h2>';
		}
		if ( !isset($args['title']) )
		{
			$options = get_settings('sem_subscribe_me_params');

			if ( isset($options['title']) && $options['title'] )
			{
				$args['title'] = $options['title'];
			}
			else
			{
				$args['title'] = $this->captions['syndicate'];
			}
		}

		$site_path = trailingslashit(get_settings('siteurl'));

		# return cache
		if ( file_exists($this->cache_file) )
		{
			if ( ( filemtime($this->cache_file) + sem_cache_timeout ) >= time() )
			{
				return file_get_contents($this->cache_file);
			}
			elseif ( is_writable(sem_cache_path) )
			{
				$this->flush_cache();
			}
		}

		# display

		$o = "";

		$feed_url = apply_filters(
				'bloginfo',
				get_feed_link('rss2'),
				'rss2_url'
				);


		if ( !empty($this->services) )
		{
			foreach ( $this->service_names as $service_id => $service_name )
			{
				if ( !isset($this->captions[$service_id]) )
				{
					$this->captions[$service_id] = __($service_name, 'sem-subscribe-me');
				}
			}

			$o = $args['before_widget']
				. '<div class="tile sem_subscribe_me">' . "\n"
				. '<div class="tile_header">'
				. $args['before_title'] . $args['title'] . $args['after_title'] . "\n"
				. '</div>'
				. '<div class="tile_body">'
				. '<ul>';

			foreach ( $this->services as $service_id )
			{
				switch ( $service_id )
				{
				case 'local_feed':
					$o .= "<li>"
						. "<a href=\"" . $feed_url . "\""
						. " style=\"background: url("
								. $site_path
								. $this->path
								. "/img/rss.png)"
								. " center left no-repeat;"
								. " padding-left: 18px;\" >"
							. $this->captions[$service_id]
							. "</a>"
						. "</li>\n";
					break;

				case 'help_link':
					$o .= "<li class=\"help_link\">"
						. "<a href=\"http://www.semiologic.com/resources/help-with-feeds/\""
							. " style=\"background: url("
								. $site_path
								. $this->path
								. "/img/help.gif)"
								. " center left no-repeat;"
								. " padding-left: 18px;\" >"
							. $this->captions[$service_id]
							. "</a>"
						. "</li>\n";
					break;

				default:
					$o .= "<li>"
						. "<a"
							. " href=\"" . str_replace(
											"%site_url%",
											$site_path,
											str_replace(
												"%feed_url%",
												$feed_url,
												$this->service_subscribe_urls[$service_id]
												)
											)
										. "\""
										. ">"
						. "<img"
							. " src=\"" . $site_path
										. $this->path
										. "/img/"
										. $this->service_icons[$service_id]
										. "\""
							. " alt=\"" . str_replace(
											"%feed%",
											$this->captions[$service_id],
											$this->captions['subscribe_to_feed']
											)
										. "\""
							. " align=\"middle\""
							. " style=\"border: none;\""
							. " />"
							. "</a>"
						. "</li>\n";
					break;
				}
			}

			$o .= '</ul>'
			    . '</div>'
			    . '</div>'
			    . $args['after_widget'];
		}

		if ( is_writable(sem_cache_path) && is_writable($this->cache_file) )
		{
			$fp = fopen($this->cache_file, "w+");
			fwrite($fp, $o);
			fclose($fp);
		}

		return $o;
	} # end display()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_sidebar_widget('Subscribe Me', array(&$this, 'display_widget'));
			register_widget_control('Subscribe Me', array(&$this, 'widget_control'));
		}
	} # end widgetize()


	#
	# display_widget()
	#

	function display_widget($args)
	{
		echo $this->display($args);
	} # end display_widget()


	#
	# widget_control()
	#

	function widget_control()
	{
		$options = get_settings('sem_subscribe_me_params');

		if ( $_POST["sem_subscribe_me_widget_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["sem_subscribe_me_widget_title"])));

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('sem_subscribe_me_params', $options);
			}
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<input type="hidden"'
				. ' id="sem_subscribe_me_widget_update"'
				. ' name="sem_subscribe_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<p>'
			. '<label for="sem_subscribe_me_widget_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="sem_subscribe_me_widget_title"'
					. ' name="sem_subscribe_me_widget_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</p>';
	} # end widget_control()


	#
	# auto_display()
	#

	function auto_display()
	{
		if ( !$this->displayed && !function_exists('register_sidebar_widget') )
		{
			echo '<li>'
				. $this->display()
				. '</li>';
		}
	} # end auto_display()
} # end sem_subscribe_me

$sem_subscribe_me =& new sem_subscribe_me();


#
# Template tags
#

function the_subscribe_links()
{
	global $sem_subscribe_me;

	echo $sem_subscribe_me->display();
} # end the_subscribe_links()


########################
#
# Backward compatibility
#

function sem_subscribe_me()
{
	the_subscribe_links();
} # end sem_subscribe_me()
?>