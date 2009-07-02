<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-smart-link/sem-smart-link.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('smart-links/smart-links.php', $active_plugins) )
{
	$active_plugins[] = 'smart-links/smart-links.php';
	include_once ABSPATH . PLUGINDIR . '/smart-links/smart-links.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>