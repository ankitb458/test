<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'TBValidator/trackback_validator.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('simple-trackback-validator.php', $active_plugins) )
{
	$active_plugins[] = 'simple-trackback-validator.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>