<?php
#
# get_theme_extra_footer()
#

function get_theme_extra_footer($footer)
{
	global $sem_options;

	return $sem_options['extra_footer'] . $footer;
} # end get_theme_extra_footer()

add_filter('extra_footer', 'get_theme_extra_footer', 0);


#
# get_show_credits()
#

function get_show_credits($bool)
{
	global $sem_options;

	return $bool && $sem_options['theme_credits'];
} # end get_show_credits()

add_filter('show_credits', 'get_show_credits', 0);
?>