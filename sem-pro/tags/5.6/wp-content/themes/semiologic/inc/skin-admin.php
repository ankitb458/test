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
		'switch_themes',
		basename(__FILE__),
		'display_theme_skin_options_admin'
		);
} # end add_theme_skin_options_admin()

add_action('admin_menu', 'add_theme_skin_options_admin');


#
# update_theme_skin_options()
#

function update_theme_skin_options()
{
	check_admin_referer('sem_skin');

	global $sem_options;

	$sem_options['active_skin'] = $_POST['active_skin'];
	$sem_options['active_skin'] = preg_replace("/\//", '', $_POST['active_skin']);
	
	if ( !is_dir(sem_path . '/skins/' . $sem_options['active_skin']) )
	{
		$sem_options['active_skin'] = 'sky-gold';
	}
	
	$sem_options['active_font'] = preg_replace("/[^a-z_]/", '', $_POST['active_font']);
	$sem_options['active_font_size'] = preg_replace("/[^a-z_]/", '', $_POST['active_font_size']);

	update_option('sem5_options', $sem_options);
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

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_skin');

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_skin_options"'
		. ' />';

	echo '<div class="wrap">';
	echo '<h2>' . __('Skin and Font Settings') . '</h2>';
	
	echo '<h3>Skin</h3>';
	
	do_action('display_theme_skin_options');
	
	echo '<h3>' . __('Font Family') . '</h3>';
	
	do_action('display_theme_font_options');

	echo '<h3>' . __('Font Size') . '</h3>';
	
	do_action('display_theme_font_size_options');
	
	echo '</div>';

	echo '</form>';
} # end display_theme_skin_options_admin()


#
# get_skin_data()
#

function get_skin_data($skin_id)
{
	$skin_data = file_get_contents(dirname(dirname(__FILE__)) . '/skins/' . $skin_id . '/skin.css');

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
	$skins = (array) glob(sem_path . '/skins/*/skin.css');

	sort($skins);

	global $sem_options;
	$active_skin = $sem_options['active_skin'];

	foreach ( array_keys($skins) as $key )
	{
		$skin_id = basename(dirname($skins[$key]));

		unset($skins[$key]);

		$skins[$skin_id] = get_skin_data($skin_id);
	}

	ksort($skins);

	echo '<p>' . __('Note that you can also create your own skins. Skins are automatically detected, so copying one of the existing ones (wp-content/themes/semiologic/skins/ folder) is the simplest way to start.') . '</p>';

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

		if ( file_exists(sem_path . '/skins/' . $skin_id . '/screenshot.png') )
		{
			echo '<p>'
				. '<label for="active_skin[' . $skin_id . ']">'
				. '<img src="'
					. sem_url
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
# display_theme_font_options()
#

function display_theme_font_options()
{
	global $sem_options;

	$active_font = $sem_options['active_font'];

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
		'trebuchet' => array(
			'name' => __('Trebuchet MS'),
			'face' => 'Trebuchet MS, Tahoma, Helvetica, Sans-Serif',
			'size' => 'small'
			),
		'verdana' => array(
			'name' => __('Verdana'),
			'face' => 'Verdana, Helvetica, Sans-Serif',
			'size' => 'small'
			)
		);


	$font_sizes = array(
		'small' => array(
			'name' => __('Small')
			),
		'medium' => array(
			'name' => __('Medium')
			),
		'large' => array(
			'name' => __('Large')
			),
		);

	echo '<ul style="list-style-type: none;">';

	foreach ( $fonts as $font_id => $font_data )
	{
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
	}

	echo '</ul>';

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end default_theme_font_options()

add_action('display_theme_font_options', 'display_theme_font_options');


#
# display_theme_font_size_options()
#

function display_theme_font_size_options()
{
	global $sem_options;

	$active_font_size = isset($sem_options['active_font_size']) ? $sem_options['active_font_size'] : 'small';

	$font_sizes = array(
		'small' => array(
			'name' => __('Small')
			),
		'medium' => array(
			'name' => __('Medium')
			),
		'large' => array(
			'name' => __('Large')
			),
		);

	echo '<ul style="list-style-type: none;">';

	foreach ( $font_sizes as $font_size_id => $font_size_data )
	{
		echo '<li style="'
			. ( ( $font_size_id == $active_font_size )
				? ' background-color: #eeeeee;'
				: ''
				)
			. '">'
			. '<label for="active_font_size[' . $font_size_id . ']">'
			. '<input type="radio"'
				. ' id="active_font_size[' . $font_size_id . ']" name="active_font_size"'
				. ' value="' . $font_size_id . '"'
				. ( ( $font_size_id == $active_font_size )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. '<span style="font-family: Tahoma, Helvetica, Sans-Serif; font-size: ' . $font_size_id . ';">'
			. '<strong>' . $font_size_data['name'] . '</strong>'
			. '</span>'
			. '</label>'
			. '</li>';
	}

	echo '</ul>';

	echo '<div style="clear: both;"></div>';

	echo '<div class="submit">';
	echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
	echo '</div>';
} # end display_theme_font_size_options()

add_action('display_theme_font_size_options', 'display_theme_font_size_options');
?>