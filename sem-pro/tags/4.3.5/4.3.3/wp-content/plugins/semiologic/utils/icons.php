<?php

#
# display_theme_icon_css()
#

function display_theme_icon_css()
{
	echo "<link rel=\"stylesheet\" type=\"text/css\""
		. " href=\"" . trailingslashit(get_settings('siteurl')) . "wp-content/plugins/semiologic/icons/icons.css"
		. "\" />\n";
} # end display_theme_icon_css()

add_action('wp_head', 'display_theme_icon_css', 20);

if ( class_exists('sem_theme_icons') )
{
	remove_action('wp_head', array(&$GLOBALS['sem_theme_icons'], 'display_css'), 20);
}
?>