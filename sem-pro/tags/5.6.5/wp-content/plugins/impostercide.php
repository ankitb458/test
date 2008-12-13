<?php
/*
Copyright (c) 2005 Scott Merrill (skippy@skippy.net)
many thanks to Mark Jaquith for the name "Impostercide"
GPL Licensed
*/

add_filter ('preprocess_comment', 'sdm_protect_email');

if (! function_exists ('sdm_protect_email')) :
function sdm_protect_email ($data) {
global $wpdb, $user_ID, $user_login, $user_email;

extract ($data);
if ('' != $comment_type) {
	// it's a pingback or trackback, let it through
	return $data;
}

get_currentuserinfo();
if ( ($user_ID > 0) && ($comment_author_email == $user_email) ) {
	// they're logged in, so let them comment
	return $data;
}

// if we've made it this far, then we don't know
// who this commenter is.

// a name was supplied, so let's check the login names
if ('' != $comment_author) {
        $result = $wpdb->get_var("SELECT count(ID) FROM $wpdb->users WHERE user_login='$comment_author'");
        if ($result > 0) {
                die ("The name you provided belongs to a registered user. Please login to make your comment.");
        }
}

// an email was supplued, so let's see if we know about it
if ('' != $comment_author_email) {
	$result = $wpdb->get_var("SELECT count(ID) FROM $wpdb->users WHERE user_email='$comment_author_email'");
	if ($result > 0) {
		die ("The email address you provided belongs to a registered user. Please login to make your comment.");
	}
}

// a URL was supplied, so let's check that
if ('' != $comment_author_url) {
	$result = $wpdb->get_var("SELECT count(ID) FROM $wpdb->users WHERE user_url='$comment_author_url'");
	if ($result > 0) {
		die ("The URI you provided belongs to a registered user. Please login to make your comment.");
	}
}

return $data;
}
endif;

?>