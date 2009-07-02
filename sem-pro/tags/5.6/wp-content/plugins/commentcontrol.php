<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'commentcontrol.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('extended-comment-options/commentcontrol.php', $active_plugins) )
{
	$active_plugins[] = 'extended-comment-options/commentcontrol.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>