<?php
// obsolete file

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'wp-db-backup.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('wp-db-backup/wp-db-backup.php', $active_plugins) )
{
	$active_plugins[] = 'wp-db-backup/wp-db-backup.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>