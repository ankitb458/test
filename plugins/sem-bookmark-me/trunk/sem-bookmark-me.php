<?php
/*
Plugin Name: Bookmark Me
Plugin URI: http://www.semiologic.com/software/widgets/bookmark-me/
Description: Adds widgets that lets visitors subscribe your webpages to social bookmarking sites such as del.icio.us and Digg.
Version: 4.5.1
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php


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
	# init()
	#
	
	function init()
	{
		add_action('wp_head', array('bookmark_me', 'css'));
		
		if ( !is_admin() )
		{
			add_action('wp_print_scripts', array('bookmark_me', 'js'));
		}
		
		add_action('widgets_init', array('bookmark_me', 'widgetize'));
		
		foreach ( array(
				'generate_rewrite_rules',
				'switch_theme',
				'update_option_active_plugins',
				'update_option_show_on_front',
				'update_option_page_on_front',
				'update_option_page_for_posts',
				'save_post',
				) as $hook)
		{
			add_action($hook, array('bookmark_me', 'clear_cache'));
		}
		
		register_activation_hook(__FILE__, array('bookmark_me', 'clear_cache'));
		register_deactivation_hook(__FILE__, array('bookmark_me', 'clear_cache'));
	} # init()
	
	
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
			'stumbleupon' => array(
				'name' => 'StumbleUpon',
				'url' => 'http://www.stumbleupon.com/submit?title=%title%&amp;url=%url%'
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
			'dzone' => array(
				'name' => 'DZone',
				'url' => 'http://www.dzone.com/links/add.html?title=%title%&amp;url=%url%',
				),				
			'facebook' => array(
				'name' => 'Facebook',
				 'url' => 'http://www.facebook.com/share.php?u=%url%'
				),
			'furl' => array(
				'name' => 'Furl',
				'url' => 'http://www.furl.net/storeIt.jsp?t=%title%&amp;u=%url%'
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
			'misterwong' => array(
				'name' => 'MisterWong',
				'url' => 'http://www.mister-wong.com/addurl/?bm_description=%title%&amp;plugin=soc&amp;bm_url=%url%',
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
			'reddit' => array(
				'name' => 'Reddit',
				'url' => 'http://reddit.com/submit?title=%title%&amp;url=%url%'
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
				'url' => 'http://www.sphere.com/search?q=sphereit:title=%title%&amp;url=%url%'
				),
			'sphinn' => array(
				'name' => 'Sphinn',
				'url' => 'http://sphinn.com/submit.php?title=%title%&amp;url=%url%',
				),					
			'spurl' => array(
				'name' => 'Spurl',
				'url' => 'http://www.spurl.net/spurl.php?title=%title%&amp;url=%url%'
				),
			'tailrank' => array(
				'name' => 'Tailrank',
				'url' => 'http://tailrank.com/share/?link_href=%title%&amp;title=%url%'
				),
			'technorati' => array(
		        'name' => 'Technorati',
		        'url' => 'http://www.technorati.com/faves?add=%url%'
				),
			'thisnext' => array(
				'name' => 'ThisNext',
				'url' => 'http://www.thisnext.com/pick/new/submit/sociable/?title=%title%&amp;url=%url%',
		        ),				
		    'twitter' => array(
		        'name' => 'Twitter',
				'url' => 'http://twitthis.com/twit?url=%url%',
				),				
			'windows_live' => array(
				'name' => 'Windows Live',
				'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&amp;mkt=en-us&amp;title=%title%&amp;top=1&amp;url=%url%'
				),
			'wists' => array(
				'name' => 'Wists',
				'url' => 'http://wists.com/r.php?c=&amp;title=%title%&amp;r=%url%'
				),
			'yahoomyweb' => array(
				'name' => 'YahooMyWeb',
				'url' => 'http://myweb2.search.yahoo.com/myresults/bookmarklet?title=%title%&amp;popup=true&amp;u=%url%'
				),
			
			'help' => array(
				'name' => 'Help',
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-social-bookmarking-sites/'
				)
			);
	} # get_services()


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
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>',
			);
		
		$default_options = bookmark_me::default_options();

		$args = array_merge($defaults, (array) $default_options, (array) $args);
		
		if ( is_feed() )
		{
			# override arguments
			$args['dropdown'] = false;
			$args['show_names'] = false;
		}
		
		if ( in_the_loop() )
		{
			$args['entry_title'] = trim(strip_tags(get_the_title(get_the_ID())));
			$args['entry_url'] = get_permalink(get_the_ID());
		}
		elseif ( is_singular() )
		{
			$args['entry_title'] = trim( strip_tags(get_the_title($GLOBALS['wp_query']->get_queried_object_id())));
			$args['entry_url'] = get_permalink($GLOBALS['wp_query']->get_queried_object_id());
		}
		else
		{
			$args['entry_title'] = trim(wp_title(null, false));
			$args['entry_url'] = ( $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' )
				. $_SERVER['HTTP_HOST']
				. $_SERVER['REQUEST_URI'];
		}

		$args['img_path'] = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/sem-bookmark-me/img/';
			
		$hash = md5(uniqid(rand()));
		
		
		
		# don't cache during rss feed
		$cache_id = md5(serialize($args));
		
		if ( in_the_loop() )
		{
			$object_id = get_the_ID();
			$cache = get_post_meta($object_id, '_bookmark_me_cache', true);
			
			if ( $cache === '' )
			{
				$cache = false;
			}
		}
		elseif ( is_singular() )
		{
			$object_id = $GLOBALS['wp_query']->get_queried_object_id();
			$cache = get_post_meta($object_id, '_bookmark_me_cache', true);
			
			if ( $cache === '' )
			{
				$cache = false;
			}
		}
		else
		{
			$cache = get_option('bookmark_me_cache');
		}

		# return cache if relevant
		
		if ( $o = $cache[$cache_id] )
		{
			$o = str_replace('{$hash}', $hash, $o);

			return $o;
		}

		# process output

		$as_dropdown = intval($args['dropdown']);
		$show_names = intval($args['show_names']);
		$home_url = user_trailingslashit(get_option('home'));
		$o = '';

		$o .= $args['before_widget'] . "\n"
			. ( $args['title']
				? ( $args['before_title'] . $args['title'] . $args['after_title'] . "\n" )
				: ''
				);

		if ( $as_dropdown )
		{
			$o .= '<div'
				. ' onmouseover="fade_bookmark_buttons_in(\'bookmark_me_{$hash}\');"'
				. ' onmouseout="fade_bookmark_buttons_out(\'bookmark_me_{$hash}\');"'
				. '>' . "\n";
			$o .= '<div class="bookmark_service">'
				. '<img'
					. ' src="' . $args['img_path'] . 'bookmark.gif"'
					. ' alt="' . __('Bookmark') . '"'
					. ' />'
				. '</div>' . "\n";
		}

		$o .= '<div class="bookmark_services'
				. ( $as_dropdown
					? ' bookmark_dropdown'
					: ''
					)
				. ( $as_dropdown && $show_names
					? ' bookmark_table'
					: ''
					)
				. '"'
			. ' id="bookmark_me_{$hash}"'
			. '>';

		if ( $as_dropdown )
		{
			$o .= '<div style="clear: both;"></div>';

			if ( !$show_names )
			{
				$o .= '<div class="bookmark_service">';
			}
			else
			{
				$o .= '<table>';
			}
		}
		else
		{
			$o .= '<p>';
		}

		$i = 0;

		foreach ( (array) $args['services'] as $service )
		{
			$details = bookmark_me::get_service($service);
			
			if ( !$details ) continue;

			if ( $show_names )
			{
				if ( $as_dropdown )
				{
					if ( !$i )
					{
						$o .= '<tr>';
					}
					elseif ( !( $i % 2 ) )
					{
						$o .= '</tr><tr>';
					}

					$o .= '<td class="bookmark_service">';

					$i++;
				}

				$o .= '<a'
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
						. 'padding: 2px 2px 2px 22px;'
						. ' background: url('
							. trailingslashit(get_option('siteurl'))
							. 'wp-content/plugins/sem-bookmark-me/img/'
							. $service . '.gif'
							. ') center left no-repeat;'
							. '"'
					. ' class="noicon"'
					. ( $args['add_nofollow'] && strpos($details['url'], $home_url) !== 0
						? ' rel="nofollow"'
						: ''
						)
					. '>'
					. __($details['name'])
					. '</a>'
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
					. ( $args['add_nofollow'] && strpos($details['url'], $home_url) !== 0
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

		if ( $as_dropdown )
		{
			if ( !$show_names )
			{
				$o .= '</div>';
			}
			else
			{
				while ( $i % 2 )
				{
					$o .= '<td></td>';
					$i++;
				}

				$o .= '</tr>'
					. '</table>';
			}

			$o .= '<div style="clear: both;"></div>' . "\n";
		}
		else
		{
			$o .= '</p>' . "\n";
		}

		$o .= '</div>' . "\n"; # bookmark services

		if ( $as_dropdown )
		{
			$o .= '</div>' . "\n";
		}
		
		$o .= $args['after_widget'] . "\n";


		# store output

		$cache[$cache_id] = $o;
		
		if ( in_the_loop() || is_singular() )
		{
			delete_post_meta($object_id, '_bookmark_me_cache');
			add_post_meta($object_id, '_bookmark_me_cache', $cache, true );
		}
		else
		{
			update_option('bookmark_me_cache', $cache);
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
				. 'wp-content/plugins/sem-bookmark-me/sem-bookmark-me.css?ver=4.0'
				. '"'
			. ' />' . "\n";
	} # css()


	#
	# js()
	#

	function js()
	{
		$plugin_path = plugin_basename(__FILE__);
		$plugin_path = preg_replace("/[^\/]+$/", '', $plugin_path);
		$plugin_path = '/wp-content/plugins/' . $plugin_path;
		
		wp_enqueue_script( 'bookmark_me', $plugin_path . 'sem-bookmark-me.js', false, '20080416' );
	} # js()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = bookmark_me::get_options();
		
		$widget_options = array('classname' => 'bookmark_me', 'description' => __( "Social bookmarking links") );
		$control_options = array('width' => 460, 'id_base' => 'bookmark_me');
		
		$id = false;
		
		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "bookmark_me-$o";
			wp_register_sidebar_widget($id, __('Bookmark Me'), array('bookmark_me', 'widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Bookmark Me'), array('bookmark_me_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "bookmark_me-1";
			wp_register_sidebar_widget($id, __('Bookmark Me'), array('bookmark_me', 'widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Bookmark Me'), array('bookmark_me_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()


	#
	# widget()
	#

	function widget($args, $widget_args = 1)
	{
		$options = bookmark_me::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$args = array_merge((array) $options[$number], (array) $args);
		
		if ( is_admin() )
		{
			echo $args['before_widget']
				. $args['before_title']
				. $args['title']
				. $args['after_title']
				. $args['after_widget'];
			
			return;
		}
		
		echo bookmark_me::display($args, $widget_args);
	} # widget()
	
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( ( $o = get_option('bookmark_me_widgets') ) === false )
		{
			if ( ( $o = get_option('sem_bookmark_me_params') ) !== false )
			{
				unset($o['before_widget']);
				unset($o['after_widget']);
				unset($o['before_title']);
				unset($o['after_title']);
				
				if ( !is_array($o['services']) )
				{
					$o['services'] = get_option('sem_bookmark_me_services');
					
					if ( !$o['services'] )
					{
						$defaults = bookmark_me::default_options();
						$o['services'] = $defaults['services'];
					}
				}
				
				$o = array( 1 => $o );
			}
			else
			{
				$o = array();
			}
			
			update_option('bookmark_me_widgets', $o);
		}
		
		return $o;
	} # get_options()
	
	
	#
	# new_widget()
	#
	
	function new_widget($k = null)
	{
		$o = bookmark_me::get_options();
		
		if ( !( isset($k) && isset($o[$k]) ) )
		{
			$k = time();
			do $k++; while ( isset($o[$k]) );
			$o[$k] = bookmark_me::default_options();
			
			update_option('bookmark_me_widgets', $o);
		}
		
		return 'bookmark_me-' . $k;
	} # new_widget()
	
	
	#
	# default_options()
	#
	
	function default_options()
	{
		return array(
			'title' => __('Spread the Word!'),
			'dropdown' => false,
			'show_names' => true,
			'add_nofollow' => false,
			'services' => array(
				'delicious',
				'digg',
				'stumbleupon',
				'help'
				),
			);
	} # default_options()
	
	
	#
	# clear_cache()
	#
	
	function clear_cache($in = null)
	{
		update_option('bookmark_me_cache', array());

		global $wpdb;
		
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_bookmark_me_cache%'");
		
		return $in;
	} # clear_cache()
} # bookmark_me

bookmark_me::init();


#
# the_bookmark_links()
#

function the_bookmark_links()
{
	echo bookmark_me::display();
} # the_bookmark_links()


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-bookmark-me-admin.php';
}
?>