<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://www.semiologic.com/software/google-analytics/
Description: Adds <a href="http://analytics.google.com">Google analytics</a> to your blog, with various advanced tracking features enabled.
Version: 3.2 RC
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


load_plugin_textdomain('google-analytics', null, dirname(__FILE__) . '/lang');


/**
 * google_analytics
 *
 * @package Google Analytics
 **/

if ( !defined('sem_google_analytics_debug') )
	define('sem_google_analytics_debug', false);

if ( !is_admin() ) {
	add_action('wp_head', array('google_analytics', 'header_scripts'));
	add_action('wp_footer', array('google_analytics', 'footer_scripts'));
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
		extract(google_analytics::get_options(), EXTR_SKIP);
		
		if ( !$uacct )
			return;
		
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
		extract(google_analytics::get_options(), EXTR_SKIP);

		if ( !$uacct ) {
			echo "\n" . '<!-- '
				. __('Configure Google Analytics under Settings / Google Analytics', 'google-analytics')
				. ' -->' . "\n";
			return;
		}
		
		$url = $_SERVER['REQUEST_URI'];
		$url = preg_replace("/#.*$/", '', $url);
		
		$ga_script = <<<EOS

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("$uacct");

EOS;
		if ( current_user_can('publish_posts') ) {
			echo "\n" . '<!-- '
				. __('You are a site author, editor or admin, and aren\'t tracked as a result')
				. '  -->' . "\n";
			$ga_script .= '// ';
		}

		$ga_script .= <<<EOS
pageTracker._trackPageview("$url");
} catch(err) {}
</script>

EOS;

		echo $ga_script;
		
		if ( !sem_google_analytics_debug && current_user_can('publish_posts') )
		 	return;
		
		$evt_script = <<<EOS

<script type="text/javascript">
//
// GA Automated Event Tracking
// ===========================
// (c) 2008, Mesoconcepts
// http://www.mesoconcepts.com/license/
//

var i = 0;

// this should catch stuff on the same domain
var ga_base_url_regexp = new RegExp("^.+?://" + document.domain, "i");

// this should catch anything that looks more or less like a file
var ga_file_regexp = new RegExp("\\\\.(?:phps|inc|js|css|exe|com|dll|reg|jpg|jpeg|gif|png|zip|tar\\.gz|tgz|mp3|wav|mpeg|avi|mov|swf|pdf|doc|rtf|xls|txt|csv)(?:\\\\?.*)?$", "i");

// automatically track relevant anchors
function ga_track_anchor() {
	var url = new String(this.href);
	url = url.replace(new RegExp("#.*$"), '');
	
	if ( !url.match(ga_base_url_regexp) ) {
		url = url.replace(new RegExp("^.+?://(www\\\\.)?", "i"), '/');
		url = "/outbound" + url;
		//alert(url);
		pageTracker._trackPageview(url);
	} else if ( url.match(ga_file_regexp) ) {
		url = url.replace(ga_base_url_regexp, '');
		url = '/file' + url;
		//alert(url);
		pageTracker._trackPageview(url);
	}
}

// add the above method to every anchor
for ( i = 0; i &lt; document.getElementsByTagName("a").length; i++ ) {
	document.getElementsByTagName("a")[i].ga_track = ga_track_anchor;
	
	var oldonclick = document.getElementsByTagName("a")[i].onclick;	
	if ( typeof document.getElementsByTagName("a")[i].onclick == 'function' ) {
		document.getElementsByTagName("a")[i].onclick = function() { 
			oldonclick(); 
			this.ga_track(); 
		}
	} else {
		document.getElementsByTagName("a")[i].onclick = function() { 
			this.ga_track(); 
		}
	}	
}
</script>

EOS;
		
		echo $evt_script;
	} # footer_scripts()
	
	
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
		
		if ( $o === false )
			$o = google_analytics::init_options();
		
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
			$o = array(
				'uacct' => $match[1],
				);
			delete_option('sem_google_analytics_params');
		} else {
			$o = false;
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