<?php
#
# add_theme_font_options_admin()
#

function add_theme_font_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Font'),
		__('Font'),
		'switch_themes',
		basename(__FILE__),
		'display_theme_font_options_admin'
		);
} # end add_theme_font_options_admin()

add_action('admin_menu', 'add_theme_font_options_admin');


#
# update_theme_font_options()
#

function update_theme_font_options()
{
	check_admin_referer('sem_font');

	global $sem_options;

	$sem_options['active_font'] = preg_replace("/[^a-z_]/", '', $_POST['active_font']);
	$sem_options['active_font_size'] = preg_replace("/[^a-z_]/", '', $_POST['active_font_size']);

	update_option('sem5_options', $sem_options);
} # end update_theme_font_options

add_action('update_theme_font_options', 'update_theme_font_options');


#
# display_theme_font_options_admin()
#

function display_theme_font_options_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_font_options'
		)
	{
		do_action('update_theme_font_options');

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	echo '<form method="post" action="">';

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_font');

	echo '<input type="hidden"'
		. ' name="action"'
		. ' value="update_theme_font_options"'
		. ' />';

	echo '<div class="wrap">';
	echo '<h2>' . __('Font') . '</h2>';
	do_action('display_theme_font_options');
	echo '</div>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Font Size') . '</h2>';
	do_action('display_theme_font_size_options');
	echo '</div>';

	echo '</form>';
} # end display_theme_font_options_admin()


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