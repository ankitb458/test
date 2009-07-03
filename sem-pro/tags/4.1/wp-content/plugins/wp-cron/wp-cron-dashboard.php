<?php
/*
Plugin Name: WP-Cron Dashboard
Plugin URI: http://www.skippy.net/blog/plugins/
Description: regularly updates the WP dashboard; requires WP-Cron.
Version: 1.1
Author: Scott Merrill
Author URI: http://www.skippy.net/

Copyright (c) 2005 Scott Merrill (skippy@skippy.net)
Released under the terms of the GNU GPL
*/

add_action('wp_cron_hourly', 'wp_cron_dashboard');

////////////////////////////
function wp_cron_dashboard() {
$rss = @fetch_rss('http://feeds.technorati.com/cosmos/rss/?url='. trailingslashit(get_option('home')) .'&partner=wordpress');
$rss = @fetch_rss('http://wordpress.org/development/feed/');
$rss = @fetch_rss('http://planet.wordpress.org/feed/');
}

?>
