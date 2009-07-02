<?php
/*
Plugin Name: Gravatars2 WP-Cron
Plugin URI: http://zenpax.com/gravatars2/
Description: Refreshes the cached gravatar images using a pseudo-cron implementation -- Requires WP-Cron (http://skippy.net/blog/2005/10/09/wp-cron-14/) & Gravatars2 (http://zenpax.com/gravatars2/)
Version: 1.1
Author: Kip Bond
Author URI: http://zenpax.com/gravatars2/

based upon Scott Merill's WP-Cron-Gravcache plugin:
http://www.skippy.net/blog/2005/10/09/wp-cron-14/
*/

/// MAIN PROGRAM

add_action('template_redirect', 'gravatar_cron_set_refresh', 0);

/// FUNCTIONS
//////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////
function gravatar_cron_times() {

$gravatar_cron_times = array();

//$gravatar_cron_times['5s'] = "5 seconds";
//$gravatar_cron_times['30s'] = "30 seconds";
$gravatar_cron_times['15'] = "15 minutes";
$gravatar_cron_times['hourly'] = "1 hour";
$gravatar_cron_times['daily'] = "1 day";

return $gravatar_cron_times;

} // gravatar_cron_times



//////////////////////////////////////////////////////////////////////
function gravatar_cron_options() {
	// load options, set if empty
global $gravatar_cron_options;

$gravatar_cron_options = get_option('gravatar_cron_options');

if (empty($gravatar_cron_options['refresh_rate'])) {
	gravatar_cron_set_refresh('daily');
}

update_option('gravatar_cron_options', $gravatar_cron_options);

return $gravatar_cron_options;
} // gravatar_cron_options



//////////////////////////////////////////////////////////////////////
function gravatar_cron_set_refresh($rate='') {
global $gravatar_cron_options;

if (!isset($gravatar_cron_options))
$gravatar_cron_options = gravatar_cron_options();

if (!isset($gravatar_cron_times))
$gravatar_cron_times = gravatar_cron_times();

if (!isset($gravatar_cron_timekeys))
$gravatar_cron_timekeys = array_keys($gravatar_cron_times);

if (empty($rate)) {
	$rate = $gravatar_cron_options['refresh_rate'];
}

if (!in_array($rate, $gravatar_cron_timekeys)) {
	$rate = 'daily';
}

foreach ($gravatar_cron_timekeys as $timekey) {
	$action = 'wp_cron_' . $timekey;
	remove_action($action, 'gravatar_cron_run');
}

$action = 'wp_cron_' . $rate;
add_action($action, 'gravatar_cron_run');

$gravatar_cron_options['refresh_rate'] = $rate;
update_option('gravatar_cron_options', $gravatar_cron_options);
} // gravatar_cron_set_refresh



//////////////////////////////////////////////////////////////////////
function gravatar_cron_run() {
global $gravatar_cron_options;

if (!isset($gravatar_cron_options))
$gravatar_cron_options = gravatar_cron_options();

if (gravatar_cache_refresh()) {
	$gravatar_cron_options['last_run'] = time();
}

update_option('gravatar_cron_options', $gravatar_cron_options);

} // gravatar_cron_run

?>