<?php
# obsolete plugin

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'no-self-pings.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('no-self-pings/no-self-pings.php', $active_plugins) )
{
	$active_plugins[] = 'no-self-pings/no-self-pings.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>