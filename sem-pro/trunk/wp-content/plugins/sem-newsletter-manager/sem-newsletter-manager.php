<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-newsletter-manager/sem-newsletter-manager.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('newsletter-manager/newsletter-manager.php', $active_plugins) )
{
	$active_plugins[] = 'newsletter-manager/newsletter-manager.php';
	include_once ABSPATH . PLUGINDIR . '/newsletter-manager/newsletter-manager.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>