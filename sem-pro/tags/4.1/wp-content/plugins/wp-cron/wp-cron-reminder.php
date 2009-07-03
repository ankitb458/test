<?php
/*
Plugin Name: WP-Cron Reminder
Plugin URI: http://www.skippy.net/blog/plugins/
Description: sends an email (roughly) every 15 and 60 minutes; required WP-Cron.
Version: 1.1
Author: Scott Merrill
Author URI: http://www.skippy.net/
*/

/*
copyright (c) 2005 Scott Merrill (skippy@skippy.net)
Released under the terms of the GNU GPL
*/

add_action('wp_cron_15', 'wp_cron_bug_me_15');
add_action('wp_cron_hourly', 'wp_cron_bug_me_60');

/////////////////////////////////////////
function wp_cron_bug_me($when = 'fifteen') {
$user = get_userdata(1);
$headers = "From: " . $user->user_nicename . " <" . $user->user_email . ">\r\n";
mail($user->user_email, "$when minute reminder!", "This is your $when minute reminder!", $headers);
}

////////////////////////////
function wp_cron_bug_me_15() {
wp_cron_bug_me ('fifteen');
}

////////////////////////////
function wp_cron_bug_me_60() {
wp_cron_bug_me ('sixty');
}

?>
