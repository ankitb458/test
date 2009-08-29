<?php
/*
Plugin Name: WP-Cron Future Pings
Plugin URI: http://www.skippy.net/blog/plugins/
Description: pings updated services forfuture-dated posts.  Requires WP-Cron.
Version: 1.4 (fork)
Author: Scott Merrill
Author URI: http://www.skippy.net/

copyright (c) 2005 Scott Merrill (skippy@skippy.net)
Released under the terms of the GNU GPL

With thanks to Lionfire (http://the.lostrealm.com/) for troubleshooting
*/

add_action('publish_post', 'future_ping', 1);
add_action('delete_post', 'future_ping_delete');
add_action('wp_cron_15', 'wp_cron_future_ping');

/////////////////////////////
function future_ping($the_ID) {
global $cache_settings, $post_pingback, $wpdb;
// is this post future-dated?

// we can't use the $post_cache because it will tell us this is a draft post
// dated right now.
$query = "SELECT post_date FROM $wpdb->posts WHERE ID=$the_ID";
$post_date = $wpdb->get_var($query);
if ( $post_date < current_time('mysql') )
{
	// nope; carry on with regular post processing
	return $the_ID;
}

// we got this far, so the post is future-dated
// cache the list of generic ping sites
$ping_sites = get_settings('ping_sites');

// empty the list of generic ping sites
$cache_settings->ping_sites = '';
if (0 !== get_option('default_pingback_flag'))
{
	// disable automatic pingbacks
	$cache_settings->default_pingback_flag = 0;
}

// disable pingbacks for this post
$post_pingback = 0;

// get all the URLs to ping
// we hit the DB directly to copy the values from `to_ping` to `pinged`
$to_ping = $wpdb->get_var("SELECT to_ping FROM $wpdb->posts WHERE ID=$the_ID");

// now blank the list of sites to ping
$result = $wpdb->query("UPDATE $wpdb->posts SET to_ping = '' WHERE ID=$the_ID");

$to_ping = trim($to_ping);
$to_ping = preg_split('/\s/', $to_ping);
$pinged = implode("\n", $to_ping);

// stuff all the sites to ping into the `pinged` field
$result = $wpdb->query("UPDATE $wpdb->posts SET pinged = '$pinged' WHERE ID=$the_ID");

$future_pings = get_option('future_pings');
if (FALSE === $future_pings)
{
	add_option('future_pings', '', 'Array of future post IDs and dates', 'no');
	$future_pings = array();
}
$future_pings[$the_ID] = $post_date;
update_option('future_pings', $future_pings);

return $the_ID;
}

////////////////////////////////////
function future_ping_delete($the_ID) {
if ( !$the_ID ) return $the_ID;
$future_pings = (array) get_option('future_pings');
if ( (count($future_pings) == 0) || (!array_key_exists($the_ID, $future_pings)) )
{
	return $the_ID;
}

unset($future_pings[$the_ID]);
update_option('future_pings', $future_pings);
return $the_ID;
}

//////////////////////////////
function wp_cron_future_ping() {
$future_pings = get_option('future_pings');
if (FALSE === $future_pings)
{
	// for some reason our option isn't set, so let's set it
	add_option('future_pings', '', 'Array of post IDs and dates.', 'no');
	// and since it wasn't set, we can assume we have no future-dated
	// post to worry about, so carry on with normal processing
	return;
}

// do we have any future-dated posts?
if (0 == count($future_pings)) return;

global $wpdb;
// lets do pings for any future-dated posts which are now visible
$counter = 0; // to determine if anything changed
foreach ( (array) $future_pings as $ID => $date)
{
	if ($date < current_time('mysql'))
	{
		$counter++;
		$ID = intval($ID);
		$post = get_post($ID);
		if (! $post)
		{
			// post isn't there.  Let's remove it from our list
			unset($future_pings[$ID]);
			continue;
		}
		$pings = $wpdb->get_var("SELECT pinged FROM $wpdb->posts WHERE ID=$ID");
		$pings = trim($pings);
		$pings = preg_split('/\s/', $pings, -1, PREG_SPLIT_NO_EMPTY);
		$new_pings = implode("\n", $pings);

		// set this post's trackback URIs back to the to_ping field
		$result = $wpdb->query("UPDATE $wpdb->posts SET pinged='' WHERE ID=$ID");
		$result = $wpdb->query("UPDATE $wpdb->posts SET to_ping = '$new_pings' WHERE ID=$ID");
		generic_ping($ID);
		if (0 !== get_option('default_pingback_flag'))
		{
			pingback($post->content, $ID);
		}
		do_trackbacks($ID);
		if (isset($future_pings[$ID])) unset ($future_pings[$ID]);
	}
}
if (0 != $counter)
{
	update_option('future_pings', $future_pings);
}
} // end wp_cron_future_ping

?>