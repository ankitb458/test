<?php
#
# add_theme_layout_options_admin()
#

function add_theme_layout_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Layout'),
		__('Layout'),
		7,
		str_replace("\\", "/", basename(__FILE__)),
		'display_theme_layout_options_admin'
		);
} # end add_theme_layout_options_admin()

add_action('admin_menu', 'add_theme_layout_options_admin');


#
# update_theme_layout_options()
#

function update_theme_layout_options()
{
	global $semiologic;

	$semiologic['active_width'] = $_POST['active_width'];
	$semiologic['active_layout'] = $_POST['active_layout'];

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_layout_options

add_action('update_theme_layout_options', 'update_theme_layout_options');


#
# display_theme_layout_options_admin()
#

function display_theme_layout_options_admin()
{
	if ( !empty($_POST)
		&& isset($_POST['action'])
		&& $_POST['action'] == 'update_theme_layout_options'
		)
	{
		do_action('update_theme_layout_options');

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
		. ' value="update_theme_layout_options"'
		. '>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Width') . '</h2>';
	do_action('display_theme_width_options');
	echo '</div>';

	echo '<div class="wrap">';
	echo '<h2>' . __('Layout') . '</h2>';
	do_action('display_theme_layout_options');
	echo '</div>';

	echo '</form>';
} # end display_theme_skin_options_admin()


#
# display_theme_width_options()
#

function display_theme_width_options()
{
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
	$active_layout = $GLOBALS['semiologic']['active_layout'];

	$layouts = array(
		'essm' => array(
			'name' => __('Ext Sidebar, Sidebar, Sidebar, Main')
			),
		'esms' => array(
			'name' => __('Ext Sidebar, Sidebar, Main, Sidebar')
			),
		'emss' => array(
			'name' => __('Ext Sidebar, Main, Sidebar, Sidebar')
			),
		'ssme' => array(
			'name' => __('Sidebar, Sidebar, Main, Ext Sidebar')
			),
		'smse' => array(
			'name' => __('Sidebar, Main, Sidebar, Ext Sidebar')
			),
		'msse' => array(
			'name' => __('Main, Sidebar, Sidebar, Ext Sidebar')
			),
		'ssm' => array(
			'name' => __('Sidebar, Sidebar, Main')
			),
		'sms' => array(
			'name' => __('Sidebar, Main, Sidebar')
			),
		'mss' => array(
			'name' => __('Main, Sidebar, Sidebar')
			),
		'esm' => array(
			'name' => __('Ext Sidebar, Sidebar, Main')
			),
		'ems' => array(
			'name' => __('Ext Sidebar, Main, Sidebar')
			),
		'sme' => array(
			'name' => __('Sidebar, Main, Ext Sidebar')
			),
		'mse' => array(
			'name' => __('Main, Sidebar, Ext Sidebar')
			),
		'em' => array(
			'name' => __('Ext Sidebar, Main')
			),
		'me' => array(
			'name' => __('Main, Ext Sidebar')
			),
		'sm' => array(
			'name' => __('Sidebar, Main')
			),
		'ms' => array(
			'name' => __('Main, Sidebar')
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