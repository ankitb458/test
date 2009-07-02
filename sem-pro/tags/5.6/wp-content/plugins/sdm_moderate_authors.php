<?php
/*
Plugin Name: Moderate Subscribers
Plugin URI: http://www.skippy.net/blog/plugins/
Description: Process comments by subscribers and contributors into the normal moderation queue, just like anonymous comments. This can become useful when registrations are open on your blog.
Author: Scott Merrill
Version: 1.3 fork
Author URI: http://www.skippy.net/blog/
*/

global $sdm_moderate_author_check;

$sdm_moderate_author_check = 0;

function moderate_authors_init()
{
	if ( !get_option('moderate_authors_init') )
	{
		foreach ( array('administrator', 'editor', 'author') as $profile )
		{
			if ( !( $role = get_role($profile) ) )
			{
				return ;
			}
			$role->add_cap('auto_approve_comment', true);
		}

		update_option('moderate_authors_init', 1);
	}
} # moderate_authors_init()

add_action('init', 'moderate_authors_init');

add_action('preprocess_comment', 'sdm_moderate_author', 1);
add_filter('pre_comment_approved', 'sdm_moderate_author_approved');

function sdm_moderate_author($comment) {
	global $sdm_moderate_author_check;

	// no sense doing more if this is an anonymous commenter
	if (0 == $comment['user_ID']) { return $comment; }

	// fetch this comment's post
	$post = get_post($comment['comment_post_ID']);

	if ( $post->post_author == $comment['user_ID'] ) {
		// if they're not an admin, they need to go through moderation
		if ( !current_user_can('auto_approve_comment')
			) {
			$sdm_moderate_author_check = 1;
		}
	}
	return $comment;
}

function sdm_moderate_author_approved($approved) {
	global $sdm_moderate_author_check;
	if ($sdm_moderate_author_check) {
		return get_option('comment_moderation');
	} else {
		return $approved;
	}
}

?>