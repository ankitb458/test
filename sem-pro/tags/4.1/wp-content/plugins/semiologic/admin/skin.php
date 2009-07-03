<?php
#
# update_theme_layout_options()
#

function update_theme_layout_options()
{
	global $semiologic;

	$semiologic['active_layout'] = $_POST['active_layout'];
	$semiologic['active_width'] = $_POST['active_width'];

	update_option('semiologic', $GLOBALS['semiologic']);
} # end update_theme_layout_options()

add_action('update_theme_options', 'update_theme_layout_options');
?>