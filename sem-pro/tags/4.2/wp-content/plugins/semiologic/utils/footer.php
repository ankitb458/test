<?php
#
# get_theme_extra_footer()
#

function get_theme_extra_footer($footer)
{
	if ( function_exists('get_site_option') )
	{
		$semiologic = get_site_option('semiologic');
	}
	else
	{
		global $semiologic;
	}

	return $semiologic['extra_footer'] . $footer;
} # end get_theme_extra_footer()

add_filter('extra_footer', 'get_theme_extra_footer', 0);


#
# get_show_credits()
#

function get_show_credits($bool)
{
	if ( function_exists('get_site_option') )
	{
		$semiologic = get_site_option('semiologic');
	}
	else
	{
		global $semiologic;
	}

	$show_credits = !isset($semiologic['theme_credits']) || $semiologic['theme_credits'];

	return $bool && ( $show_credits );
} # end get_show_credits()

add_filter('show_credits', 'get_show_credits', 0);
?>