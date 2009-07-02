<?php
#
# add_theme_skin_options_admin()
#

function add_theme_skin_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Skin'),
		__('Skin'),
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

	$semiologic['active_skin'] = get_skin_data($_POST['active_skin']);

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_skin_options

add_action('update_theme_skin_options', 'update_theme_skin_options');


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
		do_action('update_theme_skin_options');

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

	echo '</form>';
} # end display_theme_skin_options_admin()


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
		'skin' => $skin_id,
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

	echo '<p>' . __('You can drop a custom.css file and/or a custom.php file into your skin\'s directory, in order to customize the look and feel of your skin without editing the theme\'s files. This greatly simplifies upgrades.') . ' <a href="' . get_template_directory_uri() . '/custom-samples/">' . __('Sample custom.css and custom.php files') . '</a>' . '</p>';

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
?>