<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-google-analytics/sem-google-analytics.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('google-analytics/google-analytics.php', $active_plugins) )
{
	$active_plugins[] = 'google-analytics/google-analytics.php';
	include_once ABSPATH . PLUGINDIR . '/google-analytics/google-analytics.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>