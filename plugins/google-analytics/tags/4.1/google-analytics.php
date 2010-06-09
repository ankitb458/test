<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://www.semiologic.com/software/google-analytics/
Description: Adds <a href="http://analytics.google.com">Google analytics</a> to your blog, with various advanced tracking features enabled.
Version: 4.1
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

if ( !defined('sem_google_analytics_debug') )
	define('sem_google_analytics_debug', false);

if ( !defined('GA_DOMAIN') )
	define('GA_DOMAIN', false);


/**
 * google_analytics
 *
 * @package Google Analytics
 **/

class google_analytics {
	/**
	 * header_scripts()
	 *
	 * @return void
	 **/

	function header_scripts() {
		$uacct = google_analytics::get_options();
		
		$domain = GA_DOMAIN ? GA_DOMAIN : get_option('home');
		$domain = parse_url($domain);
		$domain = $domain['host'];
		$domain = preg_replace("/^www\./i", '', $domain);
		$domain = explode('.', $domain);
		if ( count($domain) > 2 ) {
			$_domain = $domain;
			$domain = array();
			do {
				$tail = array_pop($_domain);
				array_unshift($domain, $tail);
				if ( strlen($tail) > 4 )
					break;
			} while ( $_domain );
		}
		
		$domain = '[^/]+://[^/]*' . implode('\\.', array_map('addslashes', $domain)) . '(/|$)';
		
		echo <<<EOS

<script type="text/javascript">
window.google_analytics_uacct = "$uacct";
window.google_analytics_regexp = new RegExp("$domain", 'i');
</script>

EOS;
		
		if ( !sem_google_analytics_debug && ( !$uacct || current_user_can('publish_posts') || current_user_can('publish_pages') ) )
			return;
		
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_script('google_analytics', $folder . 'js/scripts.js', array('jquery'), '20090927', true);
		
		wp_localize_script('google_analytics', 'google_analyticsL10n', array(
			'ad_event' => __('Ad Unit', 'google-analytics'),
			'file_event' => __('File', 'google-analytics'),
			'audio_event' => __('Audio', 'google-analytics'),
			'video_event' => __('Video', 'google-analytics'),
			'signup_event' => __('Sign Up', 'google-analytics'),
			'custom_event' => __('Custom', 'google-analytics'),
			'click_event' => __('Click', 'google-analytics'),
			'download_event' => __('Download', 'google-analytics'),
			'submit_event' => __('Submit', 'google-analytics'),
			'success_event' => __('Success', 'google-analytics'),
			'l10n_print_after' => 'try{convertEntities(google_analyticsL10n);}catch(e){};'
		));
	} # header_scripts()
	
	
	/**
	 * footer_scripts()
	 *
	 * @return void
	 **/

	function footer_scripts() {
		$uacct = google_analytics::get_options();
		
		$ga_domain = ''; # experimental
		if ( GA_DOMAIN )
			$ga_domain = 'pageTracker._setDomainName(\'' . addslashes(GA_DOMAIN) . '\'); ';
		
		echo <<<EOS

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
EOS;
		if ( !sem_google_analytics_debug && !$uacct )
			return;
		
echo <<<EOS

<script type="text/javascript">
try { var pageTracker = _gat._getTracker("$uacct"); $ga_domain} catch(err) {}
</script>

EOS;
		
		do_action('google_analytics');
	} # footer_scripts()
	
	
	/**
	 * track_page()
	 *
	 * @return void
	 **/

	function track_page() {
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
			return;
		}
		
		if ( is_404() ) {
			$tracker = 'pageTracker._trackPageview("/404/?page=" + document.location.pathname + document.location.search + "&from=" + document.referrer);';
		} else {
			$tracker = 'pageTracker._trackPageview();';
		}
		
		echo <<<EOS

<script type="text/javascript">
try { $tracker } catch(err) {}
</script>

EOS;
	} # track_page()
	
	
	/**
	 * track_media()
	 *
	 * @param $args
	 * @return $args
	 **/

	function track_media($flashvars) {
		if ( GA_DOMAIN )
			return $flashvars;
		
		if ( !current_user_can('publish_posts') && !current_user_can('publish_pages') ) {
			$uacct = google_analytics::get_uacct();
			if ( $uacct ) {
				$flashvars['plugins'][] = 'gapro-1';
				$flashvars['gapro.accountid'] = $uacct;
			}
		}
		
		return $flashvars;
	} # track_media()
	
	
	/**
	 * get_uacct()
	 *
	 * @return void
	 **/

	function get_uacct() {
		return google_analytics::get_options();
	} # get_uacct()
	
	
	/**
	 * get_options()
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


if ( !is_admin() ) {
	add_action('wp_print_scripts', array('google_analytics', 'header_scripts'));
	add_action('wp_footer', array('google_analytics', 'footer_scripts'), 20);
	add_action('wp_footer', array('google_analytics', 'track_page'), 1000); // after script manager
	add_action('mediacaster_audio', array('google_analytics', 'track_media'));
	add_action('mediacaster_video', array('google_analytics', 'track_media'));
} else {
	add_action('admin_menu', array('google_analytics', 'admin_menu'));
}
?>