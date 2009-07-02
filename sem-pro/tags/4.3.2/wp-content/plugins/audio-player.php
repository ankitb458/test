<?php
// obsolete file

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'audio-player.php' )
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