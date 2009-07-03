<?php
/*
Plugin Name: WP-Cron Moderation
Plugin URI: http://www.skippy.net/blog/plugins/
Description: Sends hourly emails notification of pending moderation requests
Author: Scott Merrill
Version: 1.2
Author URI: http://www.skippy.net/
*/

// change "daily" to "hourly" if you want hourly notifications
add_action ('wp_cron_hourly', 'wp_cron_moderation');

///////////////////////////
function wp_cron_moderation () {
global $wpdb;

$old_post_ID = 0;

// get all the unmoderated comments, organized by post
$queue = $wpdb->get_results("SELECT comment_ID, comment_post_ID FROM $wpdb->comments WHERE comment_approved = '0' ORDER BY comment_post_ID ASC", OBJECT);

$comment_count = count($queue);
if (0 == $comment_count) return;

if (count($queue) > 1) {
	$comment_count_posts = $wpdb->get_var("SELECT count(distinct(comment_post_ID)) FROM $wpdb->comments WHERE comment_approved = '0'");
} else {
	// no sense making a SQL query if only one comment
	$comment_count_posts = 1;
}

// now check if any of these are new comments
// no sense sending a reminder every hour if nothing's new
$last_known_comment_id = get_option('moderation_last_known');
if ($last_known_comment_id == $queue[$comment_count -1]->comment_ID) return;

$notify_message = "$comment_count comment";
if (1 < $comment_count) {
	$notify_message .= "s";
	$is_are = "are";
} else {
	$is_are = "is";
}
$notify_message .= " on $comment_count_posts post";
if (1 < $comment_count_posts) {
	$notify_message .= "s";
}

$notify_message .= " $is_are currently awaiting moderation:\r\n";
$notify_message .= get_settings('siteurl') . "/wp-admin/moderation.php\r\n\r\n";

// loop over them
foreach ($queue as $q) {
	if ($q->comment_post_ID != $old_post_ID) {
		$old_post_ID = $q->comment_post_ID;
		// we're on a new post now, so get the post title
		$post_title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID='$q->comment_post_ID' LIMIT 1");

		$notify_message .= "==========\r\n";
		$notify_message .= sprintf( __('post #%1$s: "%2$s" '), $q->comment_post_ID, $post_title ) . "\r\n";
        	$notify_message .= get_permalink($q->comment_post_ID) . "\r\n==========\r\n";
	}

	$comment = $wpdb->get_row("SELECT comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_content FROM $wpdb->comments WHERE comment_ID='$q->comment_ID' LIMIT 1");
	$comment_author_domain = gethostbyaddr($comment->comment_author_IP);

	$notify_message .= sprintf( __('Author : %1$s '), $comment->comment_author) . "\r\n";
	$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
	$notify_message .= sprintf( __('   URI : %s'), $comment->comment_author_url ) . "\r\n";
	$notify_message .= sprintf( __('    IP : %s'), $comment->comment_author_IP ) . "\r\n";
	$notify_message .= __('Comment: ') . $comment->comment_content. "\r\n\r\n";
}

$notify_message .= "=-=-=-=-=-=-=-=\r\n";
$notify_message .= get_settings('siteurl') . "/wp-admin/moderation.php\r\n";

$title = "$comment_count item";
if (1 < $comment_count) {
	$title .= 's';
}
$title .= ' pending moderation';
$headers = 'From: ' . get_settings('blogname') . '<' . get_settings('admin_email') . '>';

mail(get_settings('admin_email'), $title, $notify_message, $headers);

update_option('moderation_last_known', $q->comment_ID, false);

return;

} // end wp_cron_moderation

?>
