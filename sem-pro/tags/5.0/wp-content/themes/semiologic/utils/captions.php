<?php

#
# get_all_captions()
#

function get_all_captions()
{
	return array(
		'search' => __('Search'),
		'go' => __('Go'),
		'copyright' => __('Copyright %year%'),
		'edit' => __('Edit'),
		'by' => __('By'),
		'more' => __('More on %title%'),
		'page' => __('Page'),
		'filed_under' => __('Filed under'),
		'enclosures' => __('Enclosures'),
		'permalink' => __('Permalink'),
		'print' => __('Print'),
		'email' => __('Email'),
		'comment' => __('Comment'),
		'reply' => __('Reply'),
		'no_comments' => __('No Comments'),
		'1_comment' => __('1 Comment'),
		'n_comments' => __('% Comments'),
		'leave_comment' => __('Leave a Comment'),
		'password_protected' => __('Enter your password to view comments.'),
		'login_required' => __('You must be <a href="%login_url%">logged in</a> to post a comment.'),
		'logged_in_as' => __('Logged in as %identity%'),
		'logout' => __('Logout'),
		'on' => __('on'),
		'name_field' => __('Name'),
		'email_field' => __('Email'),
		'website_field' => __('Web site'),
		'required_field' => __('(required)'),
		'submit_comment' => __('Submit Comment'),
		'trackback_uri' => __('Trackback uri'),
		'track_this_entry' => __('Track this entry'),
		'related_entries' => __('Related Entries'),
		'no_entries_found' => __('No entries found'),
		'previous_page' => __('Previous Page'),
		'next_page' => __('Next Page'),
		'spread_the_word' => __('Spread the word')
		);
}

#
# get_caption()
#

function get_caption($caption_id)
{
	$caption = $GLOBALS['semiologic']['captions'][$caption_id];

	if ( !isset($captions) )
	{
		include_once dirname(dirname(__FILE__)) . '/admin/captions.php';

		$GLOBALS['semiologic']['captions'] = array_merge(get_all_captions(), (array) $GLOBALS['semiologic']['captions']);
		update_option('semiologic', $GLOBALS['semiologic']);

		$caption = $GLOBALS['semiologic']['captions'][$caption_id];
	}

	if ( strpos($caption, "%title%") !== false )
	{
		$caption = str_replace("%title%", get_the_title(), $caption);
	}

	return $caption;
} # end get_caption()
?>