<?php
/*
Version: 1.0.1
*/
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-wizards/sem-wizards.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('sem-cloner/sem-cloner.php', $active_plugins) )
{
	$active_plugins[] = 'sem-cloner/sem-cloner.php';
}

if ( !in_array('version-checker/version-checker.php', $active_plugins) )
{
	$active_plugins[] = 'version-checker/version-checker.php';
}


sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>