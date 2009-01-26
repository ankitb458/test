<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'tag-cloud-widgets/tag-cloud.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('tag-cloud-widgets/tag-cloud-widgets.php', $active_plugins) )
{
	$active_plugins[] = 'tag-cloud-widgets/tag-cloud-widgets.php';
}


sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>