<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://www.semiologic.com/software/marketing/google-analytics/
Description: Adds <a href="http://analytics.google.com">Google analytics</a> to your blog, with various advanced tracking features enabled.
Version: 3.1.3
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Update Package: https://members.semiologic.com/media/plugins/google-analytics/google-analytics.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('google-analytics');


class google_analytics
{
	#
	# init()
	#

	function init()
	{
		add_action('wp_footer', array('google_analytics', 'display_script'), 0);
	} # init()


	#
	# get_options()
	#

	function get_options()
	{
		if ( ( $o = get_option('google_analytics') ) === false )
		{
			if ( ( $o = get_option('sem_google_analytics_params') ) !== false
				&& is_string($o['script'])
				&& preg_match("/
					_uacct\s+=\s+\"([^\"]+)\";
					/ix", $o['script'], $match)
				)
			{
				$o = array(
					'uacct' => $match[1]
					);
			}
			else
			{
				$o = array(
					'uacct' => false
					);
			}
			
			update_option('google_analytics', $o);
		}
		
		return $o;
	} # get_options()


	#
	# display_script()
	#

	function display_script()
	{
		$options = google_analytics::get_options();

		if ( !$options['uacct'] )
		{
			echo __('<!-- Configure Google Analytics under Settings / Google Analytics -->') . "\n";
			$script = str_replace('{uacct}', $options['uacct'], $script);

			return;
		}

		$url = $_SERVER['REQUEST_URI'];
		$url = preg_replace("/#.*$/", '', $url);

		if ( isset($_GET['subscribed']) )
		{
			$url = '/subscription' . preg_replace("/(?:\?|&)subscribed/", "", $url);
		}
		elseif ( is_404() || ( is_singular() && !have_posts() ) )
		{
			$url = '/404' . $url;
		}
		elseif ( is_search() )
		{
			$url = "/search/" . urlencode($_REQUEST['s']);
		}
		
		$ga_script = <<<EOF

<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("{uacct}");


EOF;
		if ( current_user_can('publish_posts') )
		{
			echo "\n" . __('<!-- You are a site author, editor or admin, and aren\'t tracked as a result -->');
			$ga_script .= '// ';
		}

		$ga_script .= <<<EOF
pageTracker._trackPageview("{url}");
} catch(err) {}</script>

EOF;

		$ga_script = str_replace(array('{url}', '{uacct}'), array($url, $options['uacct']), $ga_script);

		echo $ga_script;
		
		if ( !current_user_can('publish_posts') || true )
		{
			$evt_script = <<<EOF

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
function ga_track_anchor()
{
	var url = new String(this.href);
	url = url.replace(new RegExp("#.*$"), '');
	
	if ( !url.match(ga_base_url_regexp) )
	{
		url = url.replace(new RegExp("^.+?://(www\\\\.)?", "i"), '/');
		url = "/outbound" + url;
		//alert(url);
		pageTracker._trackPageview(url);
	}
	else if ( url.match(ga_file_regexp) )
	{
		url = url.replace(ga_base_url_regexp, '');
		url = '/file' + url;
		//alert(url);
		pageTracker._trackPageview(url);
	}
}

// add the above method to every anchor
for ( i = 0; i < document.getElementsByTagName("a").length; i++ )
{
	document.getElementsByTagName("a")[i].ga_track = ga_track_anchor;
	
	var oldonclick = document.getElementsByTagName("a")[i].onclick;	
	if ( typeof document.getElementsByTagName("a")[i].onclick == 'function' )
	{
		document.getElementsByTagName("a")[i].onclick = function() { 
			oldonclick(); 
			this.ga_track(); 
		}
	}
	else
	{
		document.getElementsByTagName("a")[i].onclick = function() { 
			this.ga_track(); 
		}
	}	
}

</script>

EOF;
			echo $evt_script;
		}
	} # display_script()
} # google_analytics()

google_analytics::init();


# include admin stuff when relevant
if ( is_admin() )
{
	include dirname(__FILE__) . '/google-analytics-admin.php';
}
?>