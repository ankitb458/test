<?php
/*
Plugin Name: WP-Cron Gravatar Cache
Plugin URI: http://www.skippy.net/blog/plugins/
Description: Periodically refreshes the cached gravatar images
Version: 1.0
Author: Scott Merrill
Author URI: http://www.skippy.net/
*/

/// MAIN PROGRAM
add_action ('wp_cron_daily', 'wp_cron_gravcache');

/// FUNCTIONS
///////////////////////////////////
function wp_cron_gravcache () {

// use globals to hopefully speed up subsequent iterations
global $grav_options, $gravatar_expire;

$now = time();

foreach ($gravatar_expire as $who => $expire) {
	$cached = FALSE;
	// check the time stamp
	if ($expire < ($now - $grav_options['gravatar_expire']))  {
		// it's past the expiration time, so grab the latest version
		$cached = gravatar_cache(md5($who));
		if ($cached) {
			$gravatar_expire[$who] = $now;
		}
	}
}
update_option('gravatar_expire', $gravatar_expire);
} // wp_cron_gravcache

?>
