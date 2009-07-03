<?php
/*
Plugin Name: No Ping Wait
Plugin URI: http://somethingunpredictable.com/wp-no-ping-wait/
Description: Speeds up posting by moving generic pings to execute-pings.php
Author: Robert Deaton
Author URI: http://somethingunpredictable.com/
Version: 2.0
*/
remove_action('publish_post', 'generic_ping');
add_action('publish_post', 'masq_set_generic_ping');
function masq_set_generic_ping() {
	update_option('masq_generic_ping_waiting', 1);
}
if(strstr($_SERVER['REQUEST_URI'], 'execute-pings.php') !== FALSE && get_option('masq_generic_ping_waiting')) {
	update_option('masq_generic_ping_waiting', 0);
	generic_ping();
}
?>