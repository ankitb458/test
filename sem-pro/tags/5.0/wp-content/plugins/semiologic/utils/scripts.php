<?php
#
# theme_head_scripts()
#

function theme_head_scripts($scripts)
{
	global $sem_options;

	return $scripts . "\n"
		. $sem_options['scripts']['head'];
} # end theme_head_scripts()

add_filter('head_scripts', 'theme_head_scripts', 0);


#
# theme_onload_scripts()
#

function theme_onload_scripts($scripts)
{
	global $sem_options;

	return $scripts . "\n"
		. $sem_options['scripts']['onload'];
} # end theme_head_scripts()

add_filter('onload_scripts', 'theme_onload_scripts', 0);


#
# display_theme_head_scripts()
#

function display_theme_head_scripts()
{
	global $sem_options;

	if ( $sem_options['scripts']['head'] )
	{
		echo $sem_options['scripts']['head'];
	}

	if ( is_singular() )
	{
		$post_ID = intval($GLOBALS['wp_query']->get_queried_object_id());
		echo get_post_meta($post_ID, '_head', true);
	}
} # display_theme_head_scripts()

add_action('wp_head', 'display_theme_head_scripts');


#
# display_theme_onload_scripts()
#

function display_theme_onload_scripts()
{
	global $sem_options;

	echo '<script type="text/javascript">' . "\n";

	echo $sem_options['scripts']['onload'] . "\n";

	if ( is_singular() )
	{
		$post_ID = intval($GLOBALS['wp_query']->get_queried_object_id());
		echo get_post_meta($post_ID, '_onload', true) . "\n";
	}

	echo '</script>' . "\n";
} # display_theme_onload_scripts()

add_action('wp_footer', 'display_theme_onload_scripts', 1000000);

?>