<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'singular.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('sem-seo/sem-seo.php', $active_plugins) )
{
	$active_plugins[] = 'sem-seo/sem-seo.php';
}


sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>