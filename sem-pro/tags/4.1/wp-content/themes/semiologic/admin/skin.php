<?php
#
# add_theme_skin_options_admin()
#

function add_theme_skin_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Skin &amp; Layout'),
		__('Skin &amp; Layout'),
		7,
		str_replace("\\", "/", basename(__FILE__)),
		'display_theme_skin_options_admin'
		);
} # end add_theme_skin_options_admin()

add_action('admin_menu', 'add_theme_skin_options_admin');



#
# update_theme_skin_options()
#

function update_theme_skin_options()
{
	global $semiologic;

	$semiologic['active_skin'] = array_merge(
			array('skin' => $_POST['active_skin']),
			get_skin_data($_POST['active_skin'])
			);
	$semiologic['active_font'] = $_POST['active_font'];

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_skin_options

add_action('update_theme_options', 'update_theme_skin_options');


#
# display_theme_skin_options_admin()
#

function display_theme_skin_options_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_skin_options'
		)
	{
		do_action('update_theme_options');

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
		. ' value="update_theme_skin_options"'
		. '>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Skin') . '</h2>';
	do_action('display_theme_skin_options');
	echo '</div>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Font') . '</h2>';
	do_action('display_theme_font_options');
	echo '</div>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Width') . '</h2>';
	do_action('display_theme_width_options');
	echo '</div>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Layout') . '</h2>';
	do_action('display_theme_layout_options');
	echo '</div>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Header Background') . '</h2>';
	do_action('display_theme_header_options');
	echo '</div>';

	echo '</form>';
} # end display_theme_options_admin()


#
# get_skin_data()
#

function get_skin_data($skin_id)
{
	$skin_data = file_get_contents(TEMPLATEPATH . '/skins/' . $skin_id . '/skin.css');

	$skin_data = str_replace("\r", "\n", $skin_data);

	preg_match('/Skin(?:\s+name)?\s*:(.*)/i', $skin_data, $name);
	preg_match('/Version\s*:(.*)/i', $skin_data, $version);
	preg_match('/Author\s*:(.*)/i', $skin_data, $author);
	preg_match('/Author\s+ur[il]\s*:(.*)/i', $skin_data, $author_uri);
	preg_match('/Description\s*:(.*)/i', $skin_data, $description);

#	echo '<pre>';
#	var_dump($name, $version, $author, $author_uri, $description);
#	echo '</pre>';

	return array(
		'name' => trim(end($name)),
		'version' => trim(end($version)),
		'author' => trim(end($author)),
		'author_uri' => trim(end($author_uri)),
		'description' => trim(end($description))
		);
} # end get_skin_data()


#
# display_theme_skin_options()
#

function display_theme_skin_options()
{
	$skins = (array) glob(TEMPLATEPATH . '/skins/*/skin.css');

	sort($skins);

	$active_skin = $GLOBALS['semiologic']['active_skin']['skin'];

	foreach ( array_keys($skins) as $key )
	{
		$skin_id = basename(dirname($skins[$key]));

		unset($skins[$key]);

		$skins[$skin_id] = get_skin_data($skin_id);
	}

	ksort($skins);

	echo '<p>' . __('You can drop a custom.css file and/or a custom.php file into your skin\'s directory, in order to customize the look and feel of your skin without editing the theme\'s files. This greatly simplifies upgrades.') . ' <a href="' . get_template_directory_uri() . '/skins/custom-samples/">' . __('Sample custom.css and custom.php files') . '</a>' . '</p>';

	echo '<p>' . __('You can also create your own skins. Skins are automatically detected, so copying one of the existing ones is the simplest way to start.') . '</p>';

	foreach ( $skins as $skin_id => $skin_data )
	{
		echo '<div style="text-align: center; width: 360px; height: 360px; float: left; margin-bottom: 12px;'
			. ( ( $skin_id == $active_skin )
				? ' background-color: #eeeeee;'
				: ''
				)
			. '">';

		echo '<h3>'
				. '<label for="active_skin[' . $skin_id . ']">'
				. '<input type="radio"'
					. ' id="active_skin[' . $skin_id . ']" name="active_skin"'
					. ' value="' . $skin_id . '"'
					. ( ( $skin_id == $active_skin )
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. $skin_data['name']
				. ' '
				. $skin_data['version']
				. '</label>'
				. '<br />'
				. __('by') . ' '
				. '<a href="' . $skin_data['author_uri'] . '">'
				. $skin_data['author']
				. '</a>'
			. '</h3>';

		if ( file_exists(TEMPLATEPATH . '/skins/' . $skin_id . '/screenshot.png') )
		{
			echo '<p>'
				. '<label for="active_skin[' . $skin_id . ']">'
				. '<img src="'
					. get_template_directory_uri()
					. '/skins/' . $skin_id . '/screenshot.png" width="320" />'
				. '</label>'
				. '</p>';
		}

		echo '<p>'
			. '<label for="active_skin[' . $skin_id . ']">'
			. $skin_data['description']
			. '</label>'
			. '</p>';

		echo '</div>';
	}

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end display_theme_skin_options()

add_action('display_theme_skin_options', 'display_theme_skin_options');



#
# update_theme_header_options()
#

function update_theme_header_options()
{
	global $semiologic;

	$semiologic['active_header'] = $_POST['active_header'];

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_header_options

add_action('update_theme_options', 'update_theme_header_options');


#
# display_theme_header_options()
#

function display_theme_header_options()
{
	echo '<div style="clear: both;"></div>';

	echo '<p>' . __('You can drop additional .jpg images into the theme\'s headers directory.') . '</p>';

	echo '<p>' . __('The theme also features a few built-in integration shortcuts. Drop any of the following <strong>into your skin directory</strong> and things will work out of the box:') . '</p>';

	echo '<ul>';

	echo '<li>' . __('header.jpg (or .jpeg, .png, .gif) will insert itself as the site\'s name -- leaving the header and tagline untouched.') . '</li>';

	echo '<li>' . __('header-background.jpg (or .jpeg, .png, .gif) will insert itself as the header -- in place of the header.') . '</li>';

	echo '<li>' . __('header.swf (flash file) will insert itself as the header -- in place of the header.') . '</li>';

	echo '</ul>';

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
				. trailingslashit(get_settings('siteurl'))
				. ( 'wp-content/themes/semiologic/headers/' . $header )
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


#
# display_theme_font_options()
#

function display_theme_font_options()
{
	$active_font = $GLOBALS['semiologic']['active_font'];

	$fonts = array(
		'arial' => array(
			'name' => __('Arial'),
			'face' => 'Arial, Helvetica, Sans-Serif',
			'size' => 'small'
			),
		'antica' => array(
			'name' => __('Book Antica'),
			'face' => 'Book Antica, Times, Serif',
			'size' => 'medium'
			),
		'bookman' => array(
			'name' => __('Bookman Old Style'),
			'face' => 'Bookman Old Style, Times, Serif',
			'size' => 'small'
			),
		'comic' => array(
			'name' => __('Comic Sans MS'),
			'face' => 'Comic Sans MS, Helvetica, Sans-Serif',
			'size' => 'small'
			),
		'courier' => array(
			'name' => __('Courier New'),
			'face' => 'Courier New, Courier, Monospace',
			'size' => 'small'
			),
		'garamond' => array(
			'name' => __('Garamond'),
			'face' => 'Garamond, Times, Serif',
			'size' => 'medium'
			),
		'georgia' => array(
			'name' => __('Georgia'),
			'face' => 'Georgia, Times, Serif',
			'size' => 'small'
			),
		'corsiva' => array(
			'name' => __('Monotype Corsiva'),
			'face' => 'Monotype Corsiva, Courier, Monospace',
			'size' => 'medium'
			),
		'tahoma' => array(
			'name' => __('Tahoma'),
			'face' => 'Tahoma, Helvetica, Sans-Serif',
			'size' => 'small'
			),
		'times' => array(
			'name' => __('Times New Roman'),
			'face' => 'Times New Roman, Times, Serif',
			'size' => 'medium'
			),
		'verdana' => array(
			'name' => __('Verdana'),
			'face' => 'Verdana, Helvetica, Sans-Serif',
			'size' => 'small'
			)
		);

	foreach ( $fonts as $font_id => $font_data )
	{
		echo '<ul style="list-style-type: none;">';

		echo '<li style="'
			. ( ( $font_id == $active_font )
				? ' background-color: #eeeeee;'
				: ''
				)
			. '">'
			. '<label for="active_font[' . $font_id . ']">'
			. '<input type="radio"'
				. ' id="active_font[' . $font_id . ']" name="active_font"'
				. ' value="' . $font_id . '"'
				. ( ( $font_id == $active_font )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. '<span style="font-family: ' . $font_data['face'] . '; font-size: ' . $font_data['size'] . ';">'
			. '<strong>' . $font_data['name'] . '</strong>'
			. ' '
			. '(' . $font_data['face'] . ')'
			. '</span>'
			. '</label>'
			. '</li>';

		echo '</ul>';
	}

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end default_theme_font_options()

add_action('display_theme_font_options', 'display_theme_font_options');



#
# display_theme_width_options()
#

function display_theme_width_options()
{
	if ( !function_exists('update_theme_layout_options') )
	{
		echo '<p>'
			. __('This is a <a href="http://www.semiologic.com/solutions/">Semiologic Pro Theme</a> feature.')
			. '</p>';
	}

	$active_width = $GLOBALS['semiologic']['active_width'];

	$widths = array(
		'narrow' => array(
			'name' => __('Narrow'),
			'width' => '770px'
			),
		'wide' => array(
			'name' => __('Wide'),
			'width' => '970px'
			),
		'flex' => array(
			'name' => __('Flexible'),
			'width' => '100%'
			)
		);

	foreach ( $widths as $width_id => $width_data )
	{
		echo '<div style="text-align: center; width: 320px; height: 280px; float: left;'
			. ( ( $width_id == $active_width )
				? ' background-color: #eeeeee;'
				: ''
				)
			. '">';

		echo '<h3>'
				. '<label for="active_width[' . $width_id . ']">'
				. '<input type="radio"'
					. ' id="active_width[' . $width_id . ']" name="active_width"'
					. ' value="' . $width_id . '"'
					. ( ( $width_id == $active_width )
						? ' checked="checked"'
						: ''
						)
					. ( !function_exists('update_theme_layout_options')
						? ' disabled="disabled"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. $width_data['name']
				. ' ('
				. $width_data['width']
				. ')'
				. '</label>'
			. '</h3>';

		echo '<p>'
			. '<label for="active_width[' . $width_id . ']">'
			. '<img src="' . get_template_directory_uri() . '/admin/img/' . $width_id . '.png" width="240" />'
			. '</label>'
			. '</p>';

		echo '</div>';
	}

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end default_theme_width_options()

add_action('display_theme_width_options', 'display_theme_width_options');


#
# display_theme_layout_options()
#

function display_theme_layout_options()
{
	if ( !function_exists('update_theme_layout_options') )
	{
		echo '<p>'
			. __('This is a <a href="http://www.semiologic.com/solutions/">Semiologic Pro Theme</a> feature.')
			. '</p>';
	}

	$active_layout = $GLOBALS['semiologic']['active_layout'];

	$layouts = array(
		'ems' => array(
			'name' => __('Ext Sidebar, Main, Sidebar')
			),
		'esm' => array(
			'name' => __('Ext Sidebar, Sidebar, Main')
			),
		'mse' => array(
			'name' => __('Main, Sidebar, Ext Sidebar')
			),
		'sme' => array(
			'name' => __('Sidebar, Main, Ext Sidebar')
			),
		'me' => array(
			'name' => __('Main, Ext Sidebar')
			),
		'em' => array(
			'name' => __('Ext Sidebar, Main')
			),
		'ms' => array(
			'name' => __('Main, Sidebar')
			),
		'sm' => array(
			'name' => __('Sidebar, Main')
			),
		'm' => array(
			'name' => __('Main')
			)
		);

	foreach ( $layouts as $layout_id => $layout_data )
	{
		echo '<div style="text-align: center; width: 360px; height: 320px; float: left; margin-bottom: 12px;'
			. ( ( $layout_id == $active_layout )
				? ' background-color: #eeeeee;'
				: ''
				)
			. '">';

		echo '<h3>'
				. '<label for="active_layout[' . $layout_id . ']">'
				. '<input type="radio"'
					. ' id="active_layout[' . $layout_id . ']" name="active_layout"'
					. ' value="' . $layout_id . '"'
					. ( ( $layout_id == $active_layout )
						? ' checked="checked"'
						: ''
						)
					. ( !function_exists('update_theme_layout_options')
						? ' disabled="disabled"'
						: ''
						)
					. ' />'
				. '&nbsp;'
				. $layout_data['name']
				. '</label>'
			. '</h3>';

		echo '<p>'
			. '<label for="active_layout[' . $layout_id . ']">'
			. '<img src="' . get_template_directory_uri() . '/admin/img/' . $layout_id . '.png" width="320" />'
			. '</label>'
			. '</p>';

		echo '</div>';
	}

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end display_theme_layout_options()

add_action('display_theme_layout_options', 'display_theme_layout_options');
?>