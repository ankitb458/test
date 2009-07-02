<?php
#
# theme_head_scripts()
#

function theme_head_scripts($scripts)
{
	global $semiologic;

	return $scripts . "\n"
		. $semiologic['scripts']['head'];
} # end theme_head_scripts()

add_filter('head_scripts', 'theme_head_scripts', 0);


#
# theme_onload_scripts()
#

function theme_onload_scripts($scripts)
{
	global $semiologic;

	return $scripts . "\n"
		. $semiologic['scripts']['onload'];
} # end theme_head_scripts()

add_filter('onload_scripts', 'theme_onload_scripts', 0);


#
# display_theme_head_scripts()
#

function display_theme_head_scripts()
{
	global $semiologic;

	if ( $semiologic['scripts']['head'] )
	{
		echo $semiologic['scripts']['head'];
	}
} # display_theme_head_scripts()

add_action('wp_head', 'display_theme_head_scripts');


#
# display_theme_onload_scripts()
#

function display_theme_onload_scripts()
{
	global $semiologic;

	if ( $semiologic['scripts']['onload'] )
	{
		echo '<script type="text/javascript">' . "\n"
			. ' function sem_onload()' . "\n"
			. '{' . "\n"
			. $semiologic['scripts']['onload'] . "\n"
			. '}' . "\n"
			. '</script>' . "\n";

		echo '<img src="' . get_template_directory_uri() . '/scripts.gif" alt="" onload="sem_onload();" />' . "\n";
	}
} # display_theme_onload_scripts()

add_action('wp_footer', 'display_theme_onload_scripts', 1000000);

?>