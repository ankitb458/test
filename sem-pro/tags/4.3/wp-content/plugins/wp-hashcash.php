<?php
// obsolete file

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'wp-hashcash.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('wp-hashcash/wp-hashcash.php', $active_plugins) )
{
	$active_plugins[] = 'wp-hashcash/wp-hashcash.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>