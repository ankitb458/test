<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'wp-flv.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('mediacaster/mediacaster.php', $active_plugins) )
{
	$active_plugins[] = 'mediacaster/mediacaster.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>