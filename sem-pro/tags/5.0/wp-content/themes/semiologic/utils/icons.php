<?php

#
# display_theme_icon_css()
#

function display_theme_icon_css()
{
	echo "<link rel=\"stylesheet\" type=\"text/css\""
		. " href=\"" . get_template_directory_uri() . "/icons/icons.css"
		. "\" />\n";
} # end display_theme_icon_css()

add_action('wp_head', 'display_theme_icon_css', 20);
?>