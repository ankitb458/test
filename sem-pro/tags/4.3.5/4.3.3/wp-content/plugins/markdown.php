<?php
// obsolete file

$active_plugins = get_option('active_plugins');

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'markdown.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('markdown/markdown.php', $active_plugins) )
{
	$active_plugins[] = 'markdown/markdown.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>