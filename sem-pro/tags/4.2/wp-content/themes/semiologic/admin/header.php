<?php
#
# add_theme_header_options_admin()
#

function add_theme_header_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Header'),
		__('Header'),
		7,
		str_replace("\\", "/", basename(__FILE__)),
		'display_theme_header_options_admin'
		);
} # end add_theme_header_options_admin()

add_action('admin_menu', 'add_theme_header_options_admin');


#
# display_theme_header_options_admin()
#

function display_theme_header_options_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_header_options'
		)
	{
		do_action('update_theme_header_options');

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
		. ' value="update_theme_header_options"'
		. '>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Header') . '</h2>';
	do_action('display_theme_header_options');
	echo '</div>';

	echo '</form>';
} # end display_theme_header_options_admin()


#
# update_theme_header_options()
#

function update_theme_header_options()
{
	global $semiologic;

	$semiologic['active_header'] = $_POST['active_header'];

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_header_options

add_action('update_theme_header_options', 'update_theme_header_options');


#
# display_theme_header_options()
#

function display_theme_header_options()
{
	echo '<div style="clear: both;"></div>';

	echo '<p>' . __('The headers below will display beneath the site\'s name and tagline. You can drop additional .jpg images into the theme\'s headers directory.') . '</p>';

	echo '<p>' . __('<strong>The theme also features a few built-in integration shortcuts that allow to insert a logo or replace the entire header with an image or a flash file</strong>. Drop any of the following into your skin directory and things will work out of the box:') . '</p>';

	echo '<ul>';

	echo '<li>' . __('header.jpg (or .jpeg, .png, .gif) will insert itself as the site\'s name -- leaving the header and tagline untouched.') . '</li>';

	echo '<li>' . __('header-background.jpg (or .jpeg, .png, .gif) will insert itself as the header -- in place of the header.') . '</li>';

	echo '<li>' . __('header.swf (flash file) will insert itself as the header -- in place of the header.') . '</li>';

	echo '</ul>';

	echo '<p>' . __('Header files should be 590px or 750px wide if you are using one of the default widths. The height adjust itself automagically.') . '</p>';

	$headers = (array) glob(TEMPLATEPATH . '/headers/*');

	natsort($headers);

	$active_header = $GLOBALS['semiologic']['active_header'];

		echo '<h3>'
			. '<label for="active_header[]">'
			. '<input type="radio"'
				. ' id="active_header[]" name="active_header"'
				. ' value=""'
				. ( $active_header == ''
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('None')
			. '</label>'
			. '</h3>';

	foreach ( $headers as $header )
	{
		$header = basename($header);

		echo '<h3>'
			. '<label for="active_header[' . $header . ']">'
			. '<input type="radio"'
				. ' id="active_header[' . $header . ']" name="active_header"'
				. ' value="' . $header . '"'
				. ( ( $active_header == $header )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. $header
			. '</label>'
			. '</h3>'
			. '<p>'
			. '<label for="active_header[' . $header . ']">'
			. '<img src="'
				. get_template_directory_uri() . '/headers/' . $header
				. '"'
				. 'alt=""'
				. ' />'
			. '</label>'
			. '</p>';
	}

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end display_theme_header_options()

add_action('display_theme_header_options', 'display_theme_header_options');

?>