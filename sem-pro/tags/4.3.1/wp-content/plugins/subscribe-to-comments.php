<?php
// obsolete file

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'subscribe-to-comments.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('subscribe-to-comments/subscribe-to-comments.php', $active_plugins) )
{
	$active_plugins[] = 'subscribe-to-comments/subscribe-to-comments.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>