<?php


#
# add_theme_captions_options_admin()
#

function add_theme_captions_admin()
{
	add_submenu_page(
		'themes.php',
		__('Captions'),
		__('Captions'),
		'switch_themes',
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

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_captions');

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_captions"'
		. ' />';

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
	check_admin_referer('sem_captions');

	$options = get_option('semiologic');

	$all_captions = get_all_captions();

	foreach ( array_keys((array) $_POST['caption']) as $key )
	{
		$_POST['caption'][$key] = trim(stripslashes(wp_filter_post_kses($_POST['caption'][$key])));

		if ( $_POST['caption'][$key] == '' )
		{
			$_POST['caption'][$key] = $all_captions[$key];
		}
	}

	$options['captions'] = $_POST['caption'];

	update_option('semiologic', $options);
} # end update_theme_captions()

add_action('update_theme_captions', 'update_theme_captions');


#
# display_theme_captions()
#

function display_theme_captions()
{
	$all_captions = get_all_captions();

	$options = get_option('semiologic');

	foreach ( array_keys($all_captions) as $caption_id )
	{
?>	<p>
		<label for="caption[<?php echo $caption_id; ?>]">
		<?php echo $caption_id . ' (' . htmlspecialchars($all_captions[$caption_id], ENT_QUOTES) . ')'; ?>:<br />
		<input type="text" style="width: 360px;"
			id="caption[<?php echo $caption_id; ?>]" name="caption[<?php echo $caption_id; ?>]"
			value="<?php echo htmlspecialchars(
				( $options['captions'][$caption_id]
					? $options['captions'][$caption_id]
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