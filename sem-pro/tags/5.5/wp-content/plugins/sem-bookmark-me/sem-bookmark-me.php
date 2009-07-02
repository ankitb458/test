<?php
/*
Plugin Name: Bookmark me
Plugin URI: http://www.semiologic.com/software/widgets/bookmark-me/
Description: Adds a widget that lets visitors subscribe your webpages to social bookmarking sites such as del.icio.us and Digg.
Author: Denis de Bernardy
Version: 3.1
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: bookmark_me
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


load_plugin_textdomain('sem-bookmark-me');

class bookmark_me
{
	#
	# get_services()
	#

	function get_services()
	{
		return array(
			'delicious' => array(
				'name' => 'del.icio.us',
				'url' => 'http://del.icio.us/post?title=%title%&amp;url=%url%'
				),
			'digg' => array(
				'name' => 'Digg',
				'url' => 'http://digg.com/submit?phase=2&amp;title=%title%&amp;url=%url%'
				),
			'furl' => array(
				'name' => 'Furl',
				'url' => 'http://www.furl.net/storeIt.jsp?t=%title%&amp;u=%url%'
				),
			'reddit' => array(
				'name' => 'Reddit',
				'url' => 'http://reddit.com/submit?title=%title%&amp;url=%url%'
				),
			'ask' => array(
				'name' => 'Ask',
				'url' => 'http://myjeeves.ask.com/mysearch/BookmarkIt?v=1.2&amp;t=webpages&amp;title=%title%&amp;url=%url%'
				),
			'blinklist' => array(
				'name' => 'BlinkList',
				'url' => 'http://www.blinklist.com/index.php?Action=Blink/addblink.php&amp;Title=%title%&amp;Description=&amp;Url=%url%'
				),
			'bloglines' => array(
				'name' => 'Bloglines',
				'url' => 'http://www.bloglines.com/sub/%url%'
				),
			'blogmarks' => array(
				'name' => 'blogmarks',
				'url' => 'http://blogmarks.net/my/new.php?mini=1&amp;simple=1&amp;title=%title%&amp;url=%url%'
				),
			'bumpzee' => array(
				'name' => 'BUMPzee',
				'url' => 'http://www.bumpzee.com/bump.php?u=%url%'
				),
			'buzzit' => array(
				'name' => 'Blogg-Buzz',
				'url' => 'http://www.blogg-buzz.com/submit.php?url=%url%'
				),
			'facebook' => array(
				'name' => 'Facebook',
				 'url' => 'http://www.facebook.com/share.php?u=%url%'
				),
			'google' => array(
				'name' => 'Google',
				'url' => 'http://www.google.com/bookmarks/mark?op=add&amp;title=%title%&amp;bkmk=%url%'
				),
			'magnolia' => array(
				'name' => 'Ma.gnolia',
				'url' => 'http://ma.gnolia.com/beta/bookmarklet/add?title=%title%&amp;description=%title%&amp;url=%url%'
				),
			'mixx' => array(
				'name' => 'Mixx',
				'url' => 'http://www.mixx.com/submit?page_url=%url%'
				),
			'muti' => array(
				'name' => 'muti',
				'url' => 'http://www.muti.co.za/submit?title=%title%&amp;url=%url%'
				),
			'newsvine' => array(
				'name' => 'Newsvine',
				'url' => 'http://www.newsvine.com/_tools/seed&amp;save?h=%title%&amp;u=%url%'
				),
			'plugim' => array(
				'name' => 'PlugIM',
				'url' => 'http://www.plugim.com/submit?title=%title%&amp;url=%url%'
				),
			'ppnow' => array(
				'name' => 'ppnow',
				'url' => 'http://www.ppnow.com/submit.php?url=%url%'
				),
			'propeller' => array(
				'name' => 'Propeller',
				'url' => 'http://www.propeller.com/submit/?T=%title%&amp;U=%url%'
				),
			'rojo' => array(
				'name' => 'Rojo',
				'url' => 'http://www.rojo.com/submit/?title=%title%&amp;url=%url%'
				),
			'shadows' => array(
				'name' => 'Shadows',
				'url' => 'http://www.shadows.com/features/tcr.htm?title=%title%&amp;url=%url%'
				),
			'simpy' => array(
				'name' => 'Simpy',
				'url' => 'http://www.simpy.com/simpy/LinkAdd.do?title=%title%&amp;href=%url%'
				),
			'slashdot' => array(
				'name' => 'Slashdot',
				'url' => 'http://slashdot.org/bookmark.pl?title=%title%&amp;url=%url%'
				),
			'socializer' => array(
				'name' => 'Socializer',
				'url' => 'http://ekstreme.com/socializer/?title=%title%&amp;url=%url%'
				),
			'sphere' => array(
				'name' => 'Sphere',
				'url' => 'http://www.sphere.com/search?q=sphereit:%url%'
				),
			'spurl' => array(
				'name' => 'Spurl',
				'url' => 'http://www.spurl.net/spurl.php?title=%title%&amp;url=%url%'
				),
			'stumbleupon' => array(
				'name' => 'StumbleUpon',
				'url' => 'http://www.stumbleupon.com/submit?title=%title%&amp;url=%url%'
				),
			'tailrank' => array(
				'name' => 'Tailrank',
				'url' => 'http://tailrank.com/share/?link_href=%title%&amp;title=%url%'
				),
			'technorati' => array(
		        'name' => 'Technorati',
		        'url' => 'http://www.technorati.com/faves?add=%url%'
				),
			'windows_live' => array(
				'name' => 'Windows Live',
				'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&amp;mkt=en-us&amp;title=%title%&amp;top=1&amp;url=%url%'
				),
			'wists' => array(
				'name' => 'Wists',
				'url' => 'http://wists.com/r.php?c=&amp;title=%title%&amp;r=%url%'
				),
			'yahoo' => array(
				'name' => 'Yahoo!',
				'url' => 'http://myweb2.search.yahoo.com/myresults/bookmarklet?title=%title%&amp;popup=true&amp;u=%url%'
				),
			'help' => array(
				'name' => 'Help',
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-social-bookmarking-sites/'
				)
			);
	} # get_services()


	#
	# default_services()
	#

	function default_services()
	{
		return array(
			'delicious',
			'digg',
			'furl',
			'reddit',
			'help'
			);
	} # default_services()


	#
	# get_service()
	#

	function get_service($key)
	{
		$services = bookmark_me::get_services();

		return $services[$key];
	} # get_service()


	#
	# display()
	#

	function display($args = null)
	{
		# default args

		$defaults = array(
			'title' => __('Spread the Word!'),
			'show_names' => true,
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>',
			);

		$options = get_option('sem_bookmark_me_params');

		$args = array_merge($defaults, (array) $options, (array) $args);

		$args['entry_title'] = trim(wp_title(null, false));
		$args['entry_url'] = ( $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' )
			. $_SERVER['HTTP_HOST']
			. $_SERVER['REQUEST_URI'];

		$args['services'] = get_option('sem_bookmark_me_services');
		$args['img_path'] = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/sem-bookmark-me/img/';

		if ( !$args['services'] )
		{
			$args['services'] = bookmark_me::default_services();
		}

		$hash = md5(uniqid(rand()));

		$cache_file = ABSPATH . 'wp-content/cache/bookmark-me-' . md5(serialize($args));

		# return cache if relevant

		if ( file_exists($cache_file) )
		{
			$o = file_get_contents($cache_file);

			$o = str_replace('{$hash}', $hash, $o);

			return $o;
		}


		# process output

		$as_dropdown = intval($args['dropdown']);

		$o = '';

		$o .= $args['before_widget'] . "\n"
			. ( $args['title']
				? ( $args['before_title'] . $args['title'] . $args['after_title'] . "\n" )
				: ''
				);

		$o .= '<div'
				. ( $as_dropdown
					? ( ' onmouseover="fade_bookmark_buttons_in(\'bookmark_me_{$hash}\');"'
						. ' onmouseout="fade_bookmark_buttons_out(\'bookmark_me_{$hash}\');"'
						)
					: ''
					)
				. '>' . "\n";

		if ( $as_dropdown )
		{
			$o .= '<div class="bookmark_service">'
				. '<img'
					. ' src="' . $args['img_path'] . 'bookmark.gif"'
					. ' alt="' . __('Bookmark') . '"'
					. ' />'
				. '</div>' . "\n";
		}

		$o .= '<div'
			. ' class="bookmark_services' . ( $as_dropdown ? ' bookmark_dropdown' : '' ) . ( $as_dropdown && $args['show_names'] ? ' bookmark_table' : '' ) . '"'
			. ' id="bookmark_me_{$hash}"'
			. '>';

		if ( $as_dropdown )
		{
			$o .= '<div style="clear: both;"></div>';

			if ( !$args['show_names'] )
			{
				$o .= '<div class="bookmark_service">';
			}
			else
			{
				$o .= '<table>';
			}
		}

		$i = 0;

		foreach ( (array) $args['services'] as $service )
		{
			$details = bookmark_me::get_service($service);

			if ( $details )
			{
				if ( $args['show_names'] )
				{
					if ( $as_dropdown )
					{
						if ( !$i )
						{
							$o .= '<tr>';
						}
						elseif ( !( $i % 3 ) )
						{
							$o .= '</tr><tr>';
						}

						$o .= '<td class="bookmark_service">';

						$i++;
					}

					$o .= '<span>'
						. '<a'
						. ' href="'
							. str_replace(
								'%url%',
								( strpos($details['url'], '?') !== false
									? urlencode($args['entry_url'])
									: $args['entry_url']
									),
								str_replace(
									'%title%',
									rawurlencode($args['entry_title']),
									$details['url'])
									)
							. '"'
						. ' style="'
							. 'padding-left: 22px;'
							. ' background: url('
								. trailingslashit(get_option('siteurl'))
								. 'wp-content/plugins/sem-bookmark-me/img/'
								. $service . '.gif'
								. ') center left no-repeat;'
								. '"'
						. ' class="noicon"'
						. ( $args['add_nofollow']
							? ' rel="nofollow"'
							: ''
							)
						. '>'
						. __($details['name'])
						. '</a>'
						. '</span>'
						. "\n";

					if ( $as_dropdown )
					{
						$o .= '</td>';
					}
				}
				else
				{
					$o .= '<span>'
						. '<a'
						. ' href="'
							. str_replace('%url%', $args['entry_url'], str_replace('%title%', rawurlencode($args['entry_title']), $details['url']))
							. '"'
						. ' class="noicon"'
						. ( $args['add_nofollow']
							? ' rel="nofollow"'
							: ''
							)
						. ' title="' . __($details['name']) . '"'
						. '>'
						. '<img src="'
								. trailingslashit(get_option('siteurl'))
								. 'wp-content/plugins/sem-bookmark-me/img/'
								. $service . '.gif'
								. '"'
								. ' alt="' . __($details['name']) . '"'
								. ' style="border: none; margin: 0px 1px;"'
								. ' />'
						. '</a>'
						. '</span>'
						. "\n";
				}
			}
		}

		if ( $as_dropdown )
		{
			if ( !$args['show_names'] )
			{
				$o .= '</div>';
			}
			else
			{
				while ( $i % 3 )
				{
					$o .= '<td></td>';
					$i++;
				}

				$o .= '</tr>'
					. '</table>';
			}

			$o .= '<div style="clear: both;"></div>' . "\n";

		}

		$o .= '</div>' . "\n";

		$o .= '</div>' . "\n"
			. $args['after_widget'] . "\n";


		# store output

		if ( is_writable($cache_file) && is_writable(dirname($cache_file)) )
		{
			$fp = fopen($cache_file, "w+");
			@fwrite($fp, $o);
			@fclose($fp);
			@chmod($cache_file, 0666);
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
				. 'wp-content/plugins/sem-bookmark-me/sem-bookmark-me.css'
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
				. 'wp-content/plugins/sem-bookmark-me/sem-bookmark-me.js'
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
			register_sidebar_widget('Bookmark Me', array('bookmark_me', 'display_widget'));
		}
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args)
	{
		echo bookmark_me::display($args);
	} # display_widget()
} # bookmark_me

add_action('wp_head', array('bookmark_me', 'css'));
add_action('wp_head', array('bookmark_me', 'js'));
add_action('widgets_init', array('bookmark_me', 'widgetize'));


#
# the_bookmark_links()
#

function the_bookmark_links()
{
	echo bookmark_me::display();
} # the_bookmark_links()


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-bookmark-me-admin.php';
}
?>