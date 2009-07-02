<?php
// obsolete file

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'kill-www-kill-index-php.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>