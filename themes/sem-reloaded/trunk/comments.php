<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# You would lose your changes when you upgrade your site. Use php widgets instead.
#

global $sem_captions;
global $sem_options;


if ( $post->post_password !== ''
	&& $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password
	)
{
	echo '<p>'
		. __('Password Protected')
		. '</p>';

	return;
}

#
# Display comments
#

if ( $comments )
{
	echo '<div id="comments">' . "\n";

	$title = the_title('', '', false);

	$caption = $sem_captions['comments_on'];
	$caption = str_replace('%title%', $title, $caption);

	if ( comments_open() && !( isset($_GET['action']) && $_GET['action'] == 'print' ) )
	{

		$comment_form_link = ' <span class="comment_entry">'
			. '<a href="#postcomment" title="' . htmlspecialchars($sem_captions['leave_comment']) . '">'
			. '&raquo;'
			. '</a>'
			. '</span>';
	}
	else
	{
		$comment_form_link = false;
	}

	echo '<div class="comments_header">' . "\n"
		. '<div class="comments_header_top"><div class="hidden"></div></div>' . "\n"
		. '<div class="pad">' . "\n"
		. '<h2>' . $caption . $comment_form_link . '</h2>' . "\n"
		. '<div class="comments_header_bottom"><div class="hidden"></div></div>' . "\n"
		. '</div>' . "\n"
		. '</div>' . "\n";
	
	foreach ( (array) $comments as $comment )
	{
		$cur_date = get_comment_date();
		
		if ( !isset($prev_date) || $cur_date != $prev_date )
		{
			$prev_date = $cur_date;
			echo '<div class="comment_date">' . "\n"
				. '<div class="pad">' . "\n"
				. '<span>'
				. $cur_date
				. '</span>'
				. '</div>' . "\n"
				. '</div>' . "\n";
		}
		
		echo '<div class="spacer"></div>' . "\n";
		
		echo '<div id="comment-' . get_comment_ID() . '" class="comment">' . "\n"
			. '<div class="comment_top"><div class="hidden"></div></div>' . "\n"
			. '<div class="comment_pad">' . "\n";

		echo '<div class="comment_header">' . "\n"
			. '<div class="comment_header_top"><div class="hidden"></div></div>' . "\n"
			. '<div class="pad">' . "\n";
		
		echo '<h3>'
			. '<span class="comment_author">'
				. get_avatar($comment, 48)
				. get_comment_author_link()
				. '</span>'
			. '<br/>' . "\n"
			. '<span class="comment_time">'
			. get_comment_date('g:i a')
			. '</span>'
			. comment_type('', '<br/>' . "\n" . '(' . __('Trackback') . ')', '<br/>' . "\n". '(' . __('Pingback') . ')')
			. '</h3>' . "\n";

		echo '</div>' . "\n"
			. '<div class="comment_header_bottom"><div class="hidden"></div></div>' . "\n"
			. '</div>' . "\n";


		echo '<div class="comment_content">' . "\n"
			. '<div class="comment_content_top"><div class="hidden"></div></div>' . "\n"
			. '<div class="pad">' . "\n";
		
		if ( !( isset($_GET['action']) && $_GET['action'] == 'print' ) ) {
			echo '<div class="comment_actions">' . "\n";

			edit_comment_link(__('Edit'), '<span class="edit_comment">', '</span>' . "\n");

			if ( comments_open() ) {
				echo '<span class="reply_comment">'
				. '<a href="#postcomment">'
				. $sem_captions['reply_link']
				. '</a>'
				. '</span>' . "\n";
			}

			echo '</div>' . "\n";
		}
		
		echo apply_filters('comment_text', get_comment_text());
		
		echo '</div>' . "\n"
			. '<div class="comment_content_bottom"><div class="hidden"></div></div>' . "\n"
			. '</div>' . "\n";


		echo '<div class="spacer"></div>';

		echo '</div>' . "\n"
			. '<div class="comment_bottom"><div class="hidden"></div></div>' . "\n"
			. '</div> <!-- comment -->' . "\n";
		
		echo '<div class="spacer"></div>';
	} # foreach $comments as $comment
	
	echo '</div><!-- #comments -->' . "\n";
} # if $comments


#
# Display comment form
#

if ( comments_open() && !( isset($_GET['action']) && $_GET['action'] == 'print' ) )
{
	echo '<div id="respond">' . "\n";
	
	$sem_captions['leave_reply'] = __('Leave a Reply to %user%');
	$sem_captions['leave_reply'] = str_replace('%user%', '%s', $sem_captions['leave_reply']);
	
	echo '<div class="comments_header" id="postcomment">' . "\n"
		. '<div class="comments_header_top"><div class="hidden"></div></div>' . "\n"
		. '<div class="pad">' . "\n"
		. '<h2>';
	comment_form_title($sem_captions['leave_comment'], $sem_captions['leave_reply']);
	echo '</h2>' . "\n";
	
	echo '<p class="cancel-comment-reply">';
	cancel_comment_reply_link();
	echo '</p>' . "\n";
	
	echo '</div>' . "\n"
		. '<div class="comments_header_bottom"><div class="hidden"></div></div>' . "\n"
		. '</div>' . "\n";

	if ( get_option('comment_registration') && !$user_ID )
	{
		$login_url = trailingslashit(site_url('login'))
			. 'wp-login.php?redirect_to='
			. urlencode(get_permalink());
		
		$login_url = apply_filters('loginout',
			'<a href="'
					. htmlspecialchars($login_url)
					. '"'
				. '>'
				. __('Logout')
				. '</a>'
			);
			
		echo '<div class="comments_login">' . "\n"
			. '<div class="pad">' . "\n"
			. '<p>'
			. str_replace('%login_url%', $login_url, $sem_captions['login_required'])
			. '</p>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n";
	}
	else
	{
		echo '<form method="post" id="commentform"'
			. ' action="' . trailingslashit(site_url()) . 'wp-comments-post.php"'
			. '>' . "\n"
			. '<div class="pad">' . "\n";

		if ( $user_ID )
		{
			$logout_url = apply_filters('loginout',
				'<a href="'
						. htmlspecialchars(wp_logout_url())
						. '"'
					. '>'
					. __('Logout')
					. '</a>'
				);

			$identity = '<span class="signed_in_author">'
				. '<a href="' . trailingslashit(site_url()) . 'wp-admin/profile.php">'
				. $user_identity
				. '</a>'
				. '</span>';

			echo '<p>'
				. str_replace(array('%identity%', '%logout_url%'), array($identity, $logout_url), $sem_captions['logged_in_as'])
				. '</p>' . "\n";
		}
		else
		{
			echo '<p class="comment_label name_label">'
				. '<label for="author">'
				. $sem_captions['name_field']
				. ( $req
					? ' (*)'
					: ''
					)
				. '</label>'
				. '</p>' . "\n";
			
			echo '<p class="comment_field name_field">'
				. '<input type="text" name="author" id="author"'
					. ' value="' . htmlspecialchars($comment_author) . '" />'
				. '</p>' . "\n";
			
			echo '<div class="spacer"></div>' . "\n";
			
			echo '<p class="comment_label email_label">'
				. '<label for="email">'
				. $sem_captions['email_field']
				. ( $req
					? ' (*)'
					: ''
					)
				. '</label>'
				. '</p>' . "\n";
			
			echo '<p class="comment_field email_field">'
				. '<input type="text" name="email" id="email"'
					. ' value="' . htmlspecialchars($comment_author_email) . '" />'
				. '</p>' . "\n";
			
			echo '<div class="spacer"></div>' . "\n";
			
			echo '<p class="comment_label url_label">'
				. '<label for="url">'
				. $sem_captions['url_field']
				. '</label>'
				. '</p>' . "\n";
			
			echo '<p class="comment_field url_field">'
				. '<input type="text" name="url" id="url"'
					. ' value="' . htmlspecialchars($comment_author_url) . '" />'
				. '</p>' . "\n";
			
			echo '<div class="spacer"></div>' . "\n";
		} # if ( $user_ID )


		echo '<textarea name="comment" id="comment" cols="48" rows="10"></textarea>' . "\n";
		
		if ( !$user_ID && $req )
			echo '<p>'
				.  $sem_captions['required_fields']
				. '</p>' . "\n";
		
		echo '<p class="submit">'
			. '<input name="submit" type="submit" id="submit" class="button"'
				. ' value="' . htmlspecialchars($sem_captions['submit_field']) . '"'
				. ' />'
			. '</p>' . "\n";

		do_action('comment_form', $post->ID);
		
		comment_id_fields();
		
		echo '</div>' . "\n"
			. '</form>' . "\n";

		if ( function_exists('show_manual_subscription_form') )
		{
			show_manual_subscription_form();
		}
	} # get_option('comment_registration') && !$user_ID
	
	echo '</div><!-- #commentform -->' . "\n";
} # comments_open()
?>