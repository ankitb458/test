<?php
/*
Plugin Name: Site Unavailable
Plugin URI: http://www.semiologic.com/software/site-unavailable/
Description: Activate this plugin when you want to make your site unavailable for maintenance.
Author: Denis de Bernardy
Author URI: http://www.semiologic.com
Version: 1.0
*/


function site_unavailable()
{
	if ( !strstr($_SERVER['PHP_SELF'], 'feed')
		&& !strstr($_SERVER['PHP_SELF'], 'wp-admin')
		)
	{
		echo '<div style="margin: 20px; padding: 20px; width: 66%; border: solid 1px dimgray; background-color: ghostwhite; font-family: Monospace;">';

	    echo '<h1>Scheduled Maintenance</h1>'
		. '<p>This site is undergoing a scheduled maintenance. Please try again in 60 minutes. Sorry for the inconvenience.</p>';

		if ( $GLOBALS['user_ID'] )
		{
			echo '<p><strong>Note</strong>: To make your site available again, visit the <a href="' . trailingslashit(get_settings('siteurl')) . 'wp-admin/themes.php?page=features.php">Semiologic features screen</a>, and disable \'Site Unavailable\' under \'Site Management\'.</p>';
		}

		echo '</div>';

		exit();
	}
	elseif ( strstr($_SERVER['PHP_SELF'], 'feed')
		|| strstr($_SERVER['PHP_SELF'], 'trackback')
		)
	{
		header("HTTP/1.0 503 Service Unavailable");
		header("Retry-After: 3600");
		exit();
	}
}

add_action('template_redirect', 'site_unavailable', -10);
?>