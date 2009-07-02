<?php
/*
Plugin Name: Moderate Authors
Plugin URI: http://www.skippy.net/blog/plugins/
Description: Forces all non-admin comments on a post into moderation
Author: Scott Merrill
Version: 1.0 (edited)
Author URI: http://www.skippy.net/blog/
*/

add_action('preprocess_comment', 'sdm_moderate_author', 1);
add_filter('pre_comment_approved', 'sdm_moderate_author_approved');

$sdm_moderate_author_check = 0;

function sdm_moderate_author($comment) {
	global $sdm_moderate_author_check;

	// no sense doing more if this is an anonymous commenter
	if (0 == $comment['user_ID']) { return $comment; }

	// fetch this comment's post
	$post = get_post($comment['comment_post_ID']);

	if ( $post->post_author == $comment['user_ID'] ) {
		// if they're not an admin, they need to go through moderation
		if (! current_user_can('administrator')) {
			$sdm_moderate_author_check = 1;
		}
	}
	return $comment;
}

function sdm_moderate_author_approved($approved) {
	global $sdm_moderate_author_check;
	if ($sdm_moderate_author_check) {
		return get_settings('comment_moderation');
	} else {
		return $approved;
	}
}
?>