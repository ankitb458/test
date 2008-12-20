<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sitemap.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('xml-sitemaps/xml-sitemaps.php', $active_plugins) )
{
	$active_plugins[] = 'xml-sitemaps/xml-sitemaps.php';
}


sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>