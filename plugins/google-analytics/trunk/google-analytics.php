<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://www.semiologic.com/software/google-analytics/
Description: Adds <a href="http://analytics.google.com">Google analytics</a> to your blog, with various advanced tracking features enabled.
Version: 4.0 RC2
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: google-analytics
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('google-analytics', false, dirname(plugin_basename(__FILE__)) . '/lang');


/**
 * google_analytics
 *
 * @package Google Analytics
 **/

if ( !defined('sem_google_analytics_debug') )
	define('sem_google_analytics_debug', false);

if ( !is_admin() ) {
	add_action('wp_print_scripts', array('google_analytics', 'header_scripts'));
	add_action('wp_footer', array('google_analytics', 'footer_scripts'));
	add_action('wp_footer', array('google_analytics', 'track_page'), 1000); // after script manager
} else {
	add_action('admin_menu', array('google_analytics', 'admin_menu'));
}

class google_analytics {
	/**
	 * header_scripts()
	 *
	 * @return void
	 **/

	function header_scripts() {
		$uacct = google_analytics::get_options();
		
		if ( !$uacct )
			return;
		
		wp_enqueue_script('jquery');
		
		echo <<<EOS

<script type="text/javascript">
window.google_analytics_uacct = "$uacct";
</script>

EOS;
	} # header_scripts()
	
	
	/**
	 * footer_scripts()
	 *
	 * @return void
	 **/

	function footer_scripts() {
		$uacct = google_analytics::get_options();

		if ( !$uacct ) {
			echo "\n" . '<!-- '
				. __('Configure Google Analytics under Settings / Google Analytics', 'google-analytics')
				. ' -->' . "\n";
			return;
		} elseif ( current_user_can('publish_posts') || current_user_can('publish_pages') ) {
			echo "\n" . '<!-- '
				. __('Google Analytics Notice: Authors, Editors and Admins are not tracked', 'google-analytics')
				. ' -->' . "\n";
		}
		
		echo <<<EOS

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
	var pageTracker = _gat._getTracker("$uacct");
} catch(err) {}
</script>

EOS;

		if ( !sem_google_analytics_debug && ( current_user_can('publish_posts') || current_user_can('publish_pages') ) )
		 	return;
		
	} # footer_scripts()
	
	
	/**
	 * track_page()
	 *
	 * @return void
	 **/

	function track_page() {
		if ( !sem_google_analytics_debug && ( current_user_can('publish_posts') || current_user_can('publish_pages') ) )
			return;
		
		$uacct = google_analytics::get_options();
		
		if ( !$uacct )
			return;
		
		$ext2type = apply_filters('ext2type', array(
			'audio' => array('aac','ac3','aif','aiff','mp1','mp2','mp3','m3a','m4a','m4b','ogg','ram','wav','wma'),
			'video' => array('asf','avi','divx','dv','mov','mpg','mpeg','mp4','mpv','ogm','qt','rm','vob','wmv', 'm4v'),
			'document' => array('doc','docx','pages','odt','rtf','pdf'),
			'spreadsheet' => array('xls','xlsx','numbers','ods'),
			'interactive' => array('ppt','pptx','key','odp','swf'),
			'text' => array('txt'),
			'archive' => array('tar','bz2','gz','cab','dmg','rar','sea','sit','sqx','zip'),
			'code' => array('css','html','php','js'),
		));
		
		$ext2type['code'] = array_diff($ext2type['code'], array('css','html','php','js'));
		
		$file_regexp = array();
		foreach ( $ext2type as $exts )
			$file_regexp = array_merge($file_regexp, $exts);
		
		$file_regexp = '/\.(?:' . join('|', array_map('preg_quote', $file_regexp)) . ')(?:\?.*)?$/';
		
		$home_url = untrailingslashit(get_option('home'));
		
		echo <<<EOS

<script type="text/javascript">
/* <![CDATA[ */
//
// Advanced GA Tracking
// ====================
// (c) 2009, Mesoconcepts (http://www.mesoconcepts.com) - All rights reserved
//

try {
	pageTracker._trackPageview();
} catch(err) {}


jQuery(document).ready(function() {
	jQuery('a').each(function() {
		if ( this.href.match($file_regexp) ) {
			var fn = this.onclick;
			if ( typeof fn == 'function' ) {
				this.onclick = function(event) {
					try {
						window.pageTracker._trackPageview(this.href);
					} catch (err) {};
					return fn(event);
				}
			} else {
				this.onclick = function(event) {
					try {
						window.pageTracker._trackPageview(this.href);
					} catch (err) {};
				}
			}
		}
	});
	
	jQuery('.ad_unit').each(function() {
		var track_id = jQuery(this).find('input.track_id').val();
		
		if ( !track_id )
			return;
		
		var fn = this.onclick;
		if ( typeof fn == 'function' ) {
			this.onclick = function(event) {
				try {
					window.pageTracker._trackPageview('$home_url/ad-clicks/' + track_id);
				} catch (err) {};
				return fn(event);
			}
		} else {
			this.onclick = function(event) {
				try {
					window.pageTracker._trackPageview('$home_url/ad-clicks/' + track_id);
				} catch (err) {};
			}
		}
	});
});
/* ]]> */
</script>

EOS;
	} # track_page()
	
	
	/**
	 * get_options
	 *
	 * @return array $options
	 **/

	function get_options() {
		static $o;
		
		if ( !is_admin() && isset($o) )
			return $o;
		
		$o = get_option('google_analytics');
		
		if ( is_array($o) ) {
			$o = $o['uacct'];
			update_option('google_analytics', $o);
		} elseif ( $o === false ) {
			$o = google_analytics::init_options();
		}
		
		return $o;
	} # get_options()
	
	
	/**
	 * init_options()
	 *
	 * @return array $options
	 **/

	function init_options() {
		$o = get_option('sem_google_analytics_params');
		
		if ( $o !== false && is_string($o['script'])
			&& preg_match("/_uacct\s*=\s*\"([^\"]+)\"\s*;/", $o['script'], $match)
			) {
			$o = $match[1];
			delete_option('sem_google_analytics_params');
		} else {
			$o = '';
		}
		
		$o = wp_parse_args($o, array('uacct' => false));
		
		update_option('google_analytics', $o);
		
		return $o;
	} # init_options()
	
	
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/

	function admin_menu() {
		add_options_page(
			__('Google Analytics', 'google-analytics'),
			__('Google Analytics', 'google-analytics'),
			'manage_options',
			'google-analytics',
			array('google_analytics_admin', 'edit_options')
			);
	} # admin_menu()
} # google_analytics

function google_analytics_admin() {
	include dirname(__FILE__) . '/google-analytics-admin.php';
}

add_action('load-settings_page_google-analytics', 'google_analytics_admin');
?>