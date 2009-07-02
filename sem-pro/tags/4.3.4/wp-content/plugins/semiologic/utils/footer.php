<?php
#
# get_theme_extra_footer()
#

function get_theme_extra_footer($footer)
{
	return $GLOBALS['semiologic']['extra_footer'] . $footer;
} # end get_theme_extra_footer()

add_filter('extra_footer', 'get_theme_extra_footer', 0);


#
# get_show_credits()
#

function get_show_credits($bool)
{
	$show_credits = !isset($GLOBALS['semiologic']['theme_credits']) || $GLOBALS['semiologic']['theme_credits'];

	return $bool && ( $show_credits );
} # end get_show_credits()

add_filter('show_credits', 'get_show_credits', 0);
?>