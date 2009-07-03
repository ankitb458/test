<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'tinymce-advanced/tinymce-advanced.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('sem-fixes/sem-fixes.php', $active_plugins) )
{
	$active_plugins[] = 'sem-fixes/sem-fixes.php';
}


sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>