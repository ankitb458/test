<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# The Semiologic theme features a custom.php feature. This allows to hook into
# the template without editing its php files. That way, you won't need to worry
# about losing your changes when you upgrade your site.
#
# You'll find detailed sample files in the custom-samples folder
#



if ( apply_filters('show_entry_comments', true) ) :

$login_url = trailingslashit(get_option('siteurl')) . 'wp-login.php?redirect_to=' . urlencode(get_permalink());

if ( $post->post_password !== ''
	&& $_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password
	)
{
	echo '<p>'
		. get_caption('password_protected')
		. '</p>';

	return;
}


#
# Display comments
#

if ( $comments )
{
?>
<div id="comments" class="comments">
<h1><?php comments_number(get_caption('no_comments'), get_caption('1_comment'), get_caption('n_comments')); ?>
<?php
	echo ' ' . get_caption('on') . ' ';
	the_title();
?>
<?php
	if ( comments_open() )
	{
?>
	<span class="comment_entry"><a href="#postcomment" title="<?php echo get_caption('leave_comment'); ?> ">&raquo;</a></span>
<?php
	}
?>
</h1>
<?php

	foreach ( $comments as $comment )
	{
		if ( function_exists('wp_gravatar') )
		{
			if ( in_array(get_comment_type(), array("pingback", "trackback")) )
			{
				$gravatar_url = wp_gravatar($comment->comment_author_url);
			}
			else
			{
				$gravatar_url = wp_gravatar($comment->comment_author_email);
			}
		}
		else
		{
			$gravatar_url = false;
		}
?>
<div id="comment-<?php comment_ID() ?>" class="comment">
<div class="comment_header">
<?php

do_action('display_comment');

$cur_date = get_comment_date();
if ( !isset($prev_date) || $cur_date != $prev_date )
{
	$prev_date = $cur_date;
?>
	<h2><?php echo $cur_date; ?></h2>
<?php
}
?>
	<h3><?php
			echo ( $gravatar_url
				? '<img src="' . $gravatar_url . '" class="gravatar" alt="" />'
				: ''
				);
		?>
		<span class="comment_author"><?php comment_author_link(); ?></span>
		@ <span class="comment_time"><?php comment_date('g:i a'); ?></span><?php comment_type(__(':'), __(' (Trackback)'), __(' (Pingback)')); ?></h3>
</div>
<div class="comment_body">
<?php comment_text() ?>
</div>
<div class="spacer"></div>
<div class="comment_actions">
	<span class="action link_comment"><a href="#comment-<?php comment_ID() ?>"><?php echo get_caption('permalink') ?></a></span>
	<?php
	if ( comments_open() )
	{
	?>
	<span class="action reply_comment">&bull;&nbsp;<a href="#postcomment"><?php echo get_caption('reply'); ?></a></span>
	<?php
	}

	edit_comment_link(get_caption('edit'), ' <span class="action admin_link edit_comment">&bull;&nbsp;', '</span>'); ?>
</div>
</div> <!-- #comment -->
<?php
	} # foreach $comments as $comment
?>
</div><!-- #comments -->
<?php
} # if $comments


#
# Display comment form
#

if ( comments_open() && !( isset($_GET['action']) && $_GET['action'] == 'print' ) )
{
?>
<div id="comment_form" class="comment_form">
<h1 id="postcomment"><?php echo get_caption('leave_comment'); ?></h1>

<?php if ( get_option('comment_registration') && !$user_ID )
{
?>
<p><?php echo str_replace('%login_url%', $login_url, get_caption('login_required')); ?></p>
<?php
}
else
{
?>
<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
<?php
if ( $user_ID )
{
	$identity = '<span class="comment_author"><a href="' . trailingslashit(get_option('siteurl')) . 'wp-admin/profile.php">' . $user_identity . '</a></span>';
?>
<p><?php echo str_replace('%identity%', $identity, get_caption('logged_in_as')); ?>. <span class="loginout"><a href="<?php echo trailingslashit(get_option('siteurl')); ?>wp-login.php?action=logout"><?php echo get_caption('logout'); ?> &raquo;</a></span>.</p>
<?php
}
else
{
?>
<p><label for="author"><?php echo get_caption('name_field'); if ($req) echo ' ' . get_caption('required_field'); ?>:</label><br />
<input type="text" name="author" id="author" tabindex="1" value="<?php echo $comment_author; ?>" /></p>

<p><label for="email"><?php echo get_caption('email_field');  if ($req) echo ' ' . get_caption('required_field'); ?>:</label><br />
<input type="text" name="email" id="email" tabindex="2" value="<?php echo $comment_author_email; ?>" /></p>

<p><label for="url"><?php echo get_caption('website_field'); ?>:</label><br />
<input type="text" name="url" id="url" tabindex="3" value="<?php echo $comment_author_url; ?>" /></p>

<?php
} # if ( $user_ID )

?>

<p><textarea name="comment" id="comment" cols="48" rows="10" tabindex="4"></textarea></p>

<p><input name="submit" type="submit" id="submit" tabindex="5" value="<?php echo get_caption('submit_comment'); ?>" />
<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
</p>
<?php
do_action('comment_form', $post->ID);
?>
</form>
<?php
} # get_option('comment_registration') && !$user_ID
?>
</div><!-- #commentform -->
<?php
} # comments_open()

endif;
?>