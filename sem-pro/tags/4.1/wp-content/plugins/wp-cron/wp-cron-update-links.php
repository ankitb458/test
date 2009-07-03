<?php
/*
Plugin Name: WP-Cron UpdateLinks
Plugin URI: http://www.skippy.net/blog/2005/05/26/plugin-wp-cron/
Description: regularly pings your WP update-links.php; requires WP-Cron.
Version: 1.1
Author: Matt Read
Author URI: http://mattread.com/
*/

add_action('wp_cron_15', 'wp_cron_update_links');

function wp_cron_update_links() { 
global $wpdb;
if ( get_option('use_linksupdate') )
   include_once(ABSPATH . 'wp-admin/update-links.php');
}
?>
