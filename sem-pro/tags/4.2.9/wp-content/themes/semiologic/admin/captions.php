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
# add_theme_captions_options_admin()
#

function add_theme_captions_admin()
{
	add_submenu_page(
		'themes.php',
		__('Captions'),
		__('Captions'),
		7,
		str_replace("\\", "/", basename(__FILE__)),
		'display_theme_captions_admin'
		);
} # end add_theme_captions_admin()

add_action('admin_menu', 'add_theme_captions_admin');


#
# display_theme_captions_admin()
#

function display_theme_captions_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_captions'
		)
	{
		do_action('update_theme_captions');

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	echo '<form method="post" action="">';

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_captions"'
		. '>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Captions') . '</h2>';
	do_action('display_theme_captions');
	echo '</div>';

	echo '</form>';
} # end display_theme_captions_admin()


#
# update_theme_captions()
#

function update_theme_captions()
{
	$all_captions = get_all_captions();

	foreach ( array_keys((array) $_POST['caption']) as $key )
	{
		$_POST['caption'][$key] = trim(stripslashes(wp_filter_post_kses($_POST['caption'][$key])));

		if ( $_POST['caption'][$key] == '' )
		{
			$_POST['caption'][$key] = $all_captions[$key];
		}
	}

	$GLOBALS['semiologic']['captions'] = $_POST['caption'];

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_captions()

add_action('update_theme_captions', 'update_theme_captions');

#
# display_theme_captions()
#

function display_theme_captions()
{
	$all_captions = get_all_captions();

	foreach ( array_keys($all_captions) as $caption_id )
	{
?>	<p>
		<label for="caption[<?php echo $caption_id; ?>]">
		<?php echo $caption_id . ' (' . htmlspecialchars($all_captions[$caption_id], ENT_QUOTES) . ')'; ?>:<br />
		<input type="text" style="width: 360px;"
			id="caption[<?php echo $caption_id; ?>]" name="caption[<?php echo $caption_id; ?>]"
			value="<?php echo htmlspecialchars(
				( $GLOBALS['semiologic']['captions'][$caption_id]
					? $GLOBALS['semiologic']['captions'][$caption_id]
					: $all_captions[$caption_id]
					),
				ENT_QUOTES
				); ?>"
			/>&nbsp;
			</label>
	</p>
<?php
	}

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end display_theme_captions()

add_action('display_theme_captions', 'display_theme_captions');
?>