<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'link-widgets/link-widgets.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);

if ( $sem_opt = get_option('link_widgets') ) {
	$wp_opt = get_option('widget_links');
	if ( !isset($wp_opt['_multiwidget']) ) {
		$sem_opt['_multiwidget'] = 1;
		update_option('widget_links' ,$sem_opt);
	}
	delete_option('link_widgets');
}
?>