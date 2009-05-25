<?php
/*
Plugin Name: Bookmark Me
Plugin URI: http://www.semiologic.com/software/bookmark-me/
Description: Widgets that let your visitors share your webpages on social media sites such as Buzzup, del.icio.us and Digg.
Version: 5.0 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: bookmark-me-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php

Fam Fam Fam silk icons (email_go, printer, information) are copyright Mark James (http://www.famfamfam.com/lab/icons/silk/), and CC-By licensed:

http://creativecommons.org/licenses/by/2.5/

Other icons are copyright their respective holders.
**/


load_plugin_textdomain('bookmark-me', null, dirname(__FILE__) . '/lang');


/**
 * bookmark_me
 *
 * @package Bookmark Me
 **/

add_action('widgets_init', array('bookmark_me', 'widgetize'));

if ( !is_admin() ) {
	add_action('wp_print_scripts', array('bookmark_me', 'js'));
	add_action('wp_print_styles', array('bookmark_me', 'css'));
}

add_action('template_redirect', array('bookmark_me', 'template_redirect'), 5);

foreach ( array(
		'generate_rewrite_rules',
		'switch_theme',
		'update_option_active_plugins',
		'update_option_sidebars_widgets',
		) as $hook)
{
	add_action($hook, array('bookmark_me', 'clear_cache'));
}

register_activation_hook(__FILE__, array('bookmark_me', 'clear_cache'));
register_deactivation_hook(__FILE__, array('bookmark_me', 'clear_cache'));

class bookmark_me {
	/**
	 * template_redirect
	 *
	 * @return void
	 **/

	function template_redirect() {
		if ( isset($_GET['action']) && $_GET['action'] == 'print' ) {
			if ( file_exists(TEMPLATEPATH . '/print.php') ) {
				include sem_path . '/print.php';
			} else {
				include dirname(__FILE__) . '/print.php';
			}	
			die;
		}
	} # template_redirect()
	
	
	/**
	 * js()
	 *
	 * @return void
	 **/

	function js() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_script('bookmark_me', $folder . 'js/scripts.js', array('jquery'), '5.0');
	} # js()
	
	
	/**
	 * css()
	 *
	 * @return void
	 **/

	function css() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_style('bookmark_me', $folder . 'css/styles.css', null, '5.0');
	} # css()
	
	
	/**
	 * widgetize()
	 *
	 * @return void
	 **/

	function widgetize() {
		$options = bookmark_me::get_options();
		
		$widget_options = array('classname' => 'bookmark_me', 'description' => __( "Social bookmarking links", 'bookmark-me') );
		$control_options = array('id_base' => 'bookmark_me');
		
		$id = false;
		
		# registered widgets
		foreach ( array_keys($options) as $o ) {
			if ( !is_numeric($o) ) continue;
			$id = "bookmark_me-$o";
			wp_register_sidebar_widget($id, __('Bookmark Me', 'bookmark-me'), array('bookmark_me', 'widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Bookmark Me', 'bookmark-me'), array('bookmark_me_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id ) {
			$id = "bookmark_me-1";
			wp_register_sidebar_widget($id, __('Bookmark Me', 'bookmark-me'), array('bookmark_me', 'widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Bookmark Me', 'bookmark-me'), array('bookmark_me_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @return void
	 **/

	function widget($args, $widget_args = 1) {
		if ( is_feed() || isset($_GET['action']) && $_GET['action'] == 'print' )
			return;
		
		$options = bookmark_me::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract($widget_args, EXTR_SKIP);
		
		$args = array_merge((array) $options[$number], (array) $args);
		
		extract($args, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. $before_title . $title . $after_title
				. $after_widget;
			return;
		} elseif ( in_the_loop() ) {
			$page_title = get_the_title();
			$page_url = get_permalink();
		} elseif ( is_singular() ) {
			global $wp_the_query;
			$post_id = $wp_the_query->get_queried_object_id();
			$page_title = get_the_title($post_id);
			$page_url = get_permalink($post_id);
		} else {
			$page_title = get_option('blogname');
			$page_url = user_trailingslashit(get_option('home'));
		}
		
		$page_title = html_entity_decode($page_title);
		
		if ( !in_the_loop() ) {
			$print_action = false;
		} elseif ( strpos($page_url, '?') !== false ) {
			$print_action = '&action=print';
		} else {
			# An endpoint would have been better, but:
			# http://core.trac.wordpress.org/ticket/9477
			$print_action = '?action=print';
		}
		
		if ( !( $o = wp_cache_get($widget_id, 'widget') ) ) {
			# check if the widget has a class
			if ( strpos($before_widget, 'bookmark_me') === false ) {
				if ( preg_match("/^(<[^>]+>)/", $before_widget, $tag) ) {
					if ( preg_match("/\bclass\s*=\s*(\"|')(.*?)\\1/", $tag[0], $class) ) {
						$tag[1] = str_replace($class[2], $class[2] . ' bookmark_me', $tag[1]);
					} else {
						$tag[1] = str_replace('>', ' class="bookmark_me"', $tag[1]);
					}
					$before_widget = preg_replace("/^$tag[0]/", $tag[1], $before_widget);
				} else {
					$before_widget = '<div class="bookmark_me">' . $before_widget;
					$after_widget = $after_widget . '</div>' . "\n";
				}
			}
			
			ob_start();
			
			echo $before_widget;

			if ( $title )
				echo $before_title . $title . $after_title;

			echo '<div class="bookmark_me_services' . ( !$print_action ? ' bookmark_me_sidebar' : '' ) . '">' . "\n";

			foreach ( bookmark_me::get_main_services() as $service_id =>  $service ) {
				echo '<a href="' . htmlspecialchars($service['url'])  . '" class="' . $service_id . ' no_icon"'
					. ' title="' . htmlspecialchars($service['name']) . '"'
					. ' rel="nofollow">'
					. $service['name']
					. '</a>' . "\n";
			}

			echo '</div>' . "\n";

			if ( $print_action ) {
				echo '<div class="bookmark_me_actions">' . "\n";

				echo '<a href="mailto:?subject=%email_title%&amp;body=%email_url%"'
					. ' title="' . htmlspecialchars(__('Email', 'bookmark-me')) .  '" class="email_entry no_icon">'
					. __('Email', 'bookmark-me')
					. '</a>' . "\n";

				echo '<a href="%print_url%"'
					. ' title="' . htmlspecialchars(__('Print', 'bookmark-me')) .  '" class="print_entry no_icon">'
					. __('Print', 'bookmark-me')
					. '</a>' . "\n";

				echo '</div>' . "\n";
			}

			echo '<div class="bookmark_me_spacer bookmark_me_ruler"></div>' . "\n";

			echo '<div class="bookmark_me_extra" style="display: none;">' . "\n";

			foreach ( bookmark_me::get_extra_services() as $service_id =>  $service ) {
				echo '<a href="' . htmlspecialchars($service['url'])  . '" class="' . $service_id . ' no_icon"'
					. ' title="' . htmlspecialchars($service['name']) . '"'
					. ( $service_id == 'help' && ( strpos(get_option('home'), 'semiologic.com') !== false )
						? ''
						: ' rel="nofollow"'
						)
					. '>'
					. $service['name']
					. '</a>' . "\n";
			}

			echo '<div class="bookmark_me_spacer"></div>' . "\n";

			echo '</div>' . "\n";
		
			echo $after_widget;

			$o = ob_get_clean();
			
			wp_cache_add($widget_id, $o, 'widget');
		}
		
		echo str_replace(
			array(
				'%enc_url%', '%enc_title%',
				'%email_url%', '%email_title%',
				'%print_url%',
				),
			array(
				urlencode($page_url), urlencode($page_title),
				rawurlencode($page_url), rawurlencode($page_title),
				htmlspecialchars($page_url . $print_action),
				),
			$o);
	} # widget()
	
	
	/**
	 * get_main_services()
	 *
	 * @return array $services
	 **/

	function get_main_services() {
		return array(
			'buzzup' => array(
			 	'name' => __('Buzz Up', 'bookmark-me'),
			 	'url' => 'http://buzz.yahoo.com/buzz?headline=%enc_title%&targetUrl=%enc_url%',
			 	),
			'digg' => array(
				'name' => __('Digg', 'bookmark-me'),
				'url' => 'http://digg.com/submit?phase=2&title=%enc_title%&url=%enc_url%',
				),
			'mixx' => array(
				'name' => __('Mixx', 'bookmark-me'),
				'url' => 'http://www.mixx.com/submit?page_url=%enc_url%',
				),
			'twitter' => array(
		        'name' => __('Twitter', 'bookmark-me'),
				'url' => 'http://twitter.com/timeline/home/?status=%enc_url%',
				),
			);
	} # get_main_services()
	
	
	/**
	 * get_extra_services()
	 *
	 * @return array $services
	 **/

	function get_extra_services() {
		return array(
			'current' => array(
				'name' => __('Current', 'bookmark-me'),
				'url' => 'http://current.com/clipper.htm?src=st&title=%enc_title%&url=%enc_url%',
				),
			'delicious' => array(
				'name' => __('Delicious', 'bookmark-me'),
				'url' => 'http://del.icio.us/post?title=%enc_title%&url=%enc_url%',
				),
			'diigo' => array(
				'name' => __('Diigo', 'bookmark-me'),
				'url' => 'http://secure.diigo.com/post?title=%enc_title%&url=%enc_url%',
				),
			'facebook' => array(
				'name' => __('Facebook', 'bookmark-me'),
				 'url' => 'http://www.facebook.com/share.php?t=%enc_title%&u=%enc_url%'
				),
			'fark' => array(
				'name' => __('Fark', 'bookmark-me'),
				'url' => 'http://cgi.fark.com/cgi/farkit.pl?h=%enc_title%&u=%enc_url%',
				),
			'google' => array(
				'name' => __('Google', 'bookmark-me'),
				'url' => 'http://www.google.com/bookmarks/mark?op=add&title=%enc_title%&bkmk=%enc_url%',
				),
			'linkedin' => array(
				'name' => __('LinkedIn', 'bookmark-me'),
				'url' => 'http://www.linkedin.com/shareArticle?mini=true&summary=&source=&title=%enc_title%&url=%enc_url%',
				),
			'live' => array(
				'name' => __('Live', 'bookmark-me'),
				'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&mkt=en-us&top=1&title=%enc_title%&url=%enc_url%',
				),
			'myspace' => array(
				'name' => __('MySpace', 'bookmark-me'),
				'url' => 'http://www.myspace.com/Modules/PostTo/Pages/?l=3&t=t=%enc_title%&u=%enc_url%',
				),
			'newsvine' => array(
				'name' => __('Newsvine', 'bookmark-me'),
				'url' => 'http://www.newsvine.com/_tools/seed&save?h=%enc_title%&u=%enc_url%',
				),
			'propeller' => array(
				'name' => __('Propeller', 'bookmark-me'),
				'url' => 'http://www.propeller.com/submit/?T=%enc_title%&U=%enc_url%',
				),
			'reddit' => array(
				'name' => __('Reddit', 'bookmark-me'),
				'url' => 'http://reddit.com/submit?title=%enc_title%&url=%enc_url%',
				),
			'slashdot' => array(
				'name' => __('Slashdot', 'bookmark-me'),
				'url' => 'http://slashdot.org/bookmark.pl?title=%enc_title%&url=%enc_url%',
				),
			'sphinn' => array(
				'name' => __('Sphinn', 'bookmark-me'),
				'url' => 'http://sphinn.com/submit.php?title=%enc_title%&url=%enc_url%',
				),					
			'stumbleupon' => array(
				'name' => __('StumbleUpon', 'bookmark-me'),
				'url' => 'http://www.stumbleupon.com/submit?title=%enc_title%&url=%enc_url%',
				),
		    'tipd' => array(
				'name' => __('Tip\'d', 'bookmark-me'),
				'url' => 'http://tipd.com/submit.php?url=%enc_url%',
				),
			'yahoo' => array(
				'name' => __('Yahoo!', 'bookmark-me'),
				'url' => 'http://bookmarks.yahoo.com/toolbar/savebm?opener=tb&t=%enc_title%&u=%enc_url%',
				),
			'help' => array(
				'name' => __('What\'s This?', 'bookmark-me'),
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-social-media-sites/',
				),
			);
	} # get_extra_services()
	
	
	/**
	 * get_options()
	 *
	 * @return array $options
	 **/
	
	function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('bookmark_me');
		
		if ( $o === false ) {
			$o = bookmark_me::init_options();
		}
		
		return $o;
	} # get_options()
	
	
	/**
	 * init_options()
	 *
	 * @return array $options
	 **/

	function init_options() {
		if ( ( $o = get_option('bookmark_me_widgets') ) !== false ) {
			foreach ( $o as $k => $opt ) {
				if ( is_numeric($k) ) {
					$o[$k] = array('title' => $opt['title']);
				}
			}
		} elseif ( ( $o = get_option('sem_bookmark_me_params') ) !== false ) {
			unset($o['before_widget']);
			unset($o['after_widget']);
			unset($o['before_title']);
			unset($o['after_title']);
			
			$o = array( 1 => $o );
		} else {
			$o = array();
		}
		
		delete_option('sem_bookmark_me_services');
		delete_option('sem_bookmark_me_params');
		delete_option('bookmark_me_widgets');
		
		update_option('bookmark_me', $o);
		
		return $o;
	} # init_options()
	
	
	/**
	 * default_options()
	 *
	 * @return array $widget_options
	 **/

	function default_options() {
		return array(
			'title' => '',
			);
	} # default_options()
	
	
	/**
	 * new_widget()
	 *
	 * @param int $k arbitrary widget number
	 * @return string $widget_id
	 **/

	function new_widget($k = null) {
		$o = bookmark_me::get_options();
		
		if ( !( isset($k) && isset($o[$k]) ) ) {
			$k = time();
			while ( isset($o[$k]) ) $k++;
			$o[$k] = bookmark_me::default_options();
			
			update_option('bookmark_me', $o);
		}
		
		return 'bookmark_me-' . $k;
	} # new_widget()
	
	
	/**
	 * clear_cache()
	 *
	 * @return void
	 **/

	function clear_cache($in = null) {
		$o = bookmark_me::get_options();
		
		if ( !$o ) return $in;
		
		foreach ( array_keys($o) as $widget_id ) {
			wp_cache_delete($widget_id, 'widget');
		}
		
		return $in;
	} # clear_cache()
} # bookmark_me


/**
 * the_bookmark_links()
 *
 * @return void
 **/

function the_bookmark_links($args = null) {
	if ( is_string($args) ) {
		$args = array('title' => $args);
	}
	
	$defaults = array(
		'before_widget' => '<div class="bookmark_me">' . "\n",
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		'title' => '',
		);
	
	$args = array_merge($args, $defaults);
	
	bookmark_me::widget($args);
} # the_bookmark_links()


/**
 * bookmark_me_admin()
 *
 * @return void
 **/

function bookmark_me_admin() {
	include dirname(__FILE__) . '/sem-bookmark-me-admin.php';
} # bookmark_me_admin()

add_action('load-widgets.php', 'bookmark_me_admin');
?>