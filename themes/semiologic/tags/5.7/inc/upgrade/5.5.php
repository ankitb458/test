<?php

#
# Step 2
# ------
# Activate new plugins
#

if ( ( $active_plugins = get_option('active_plugins') ) === false )
{
	$active_plugins = array();
}

$extra_plugins = array(
	'tinymce-advanced/tinymce-advanced.php',
	'archive-widgets/archive-widgets.php',
	);

foreach ( $extra_plugins as $plugin )
{
	if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin ) )
	{
		$active_plugins[] = $plugin;
	}
}

$active_plugins = array_unique($active_plugins);
sort($active_plugins);

update_option('active_plugins', $active_plugins);

$plugin_page_backup = $GLOBALS['plugin_page'];

unset($GLOBALS['plugin_page']);

foreach ( $extra_plugins as $plugin )
{
	if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin) )
	{
		include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
		do_action('activate_' . $plugin);
	}
}

$GLOBALS['plugin_page'] = $plugin_page_backup;

#
# Step 3
# ------
# Widget Contexts
#

if ( class_exists('widget_contexts')
	&& ( $sem_widget_contexts = get_option('sem_widget_contexts') ) !== false
	) :

$widget_contexts = array();

foreach ( $sem_widget_contexts as $widget => $contexts )
{
	switch ( $widget )
	{
	case 'archives':
		if ( class_exists('archive_widgets') )
		{
			$widget = 'archive_widget-1';
		}
		break;
	
	case 'tag_cloud':
		if ( class_exists('tag_cloud_widgets') )
		{
			$widget = 'tag_cloud_widget-1';
		}
		break;
		
	}

	
	foreach ( $contexts as $context => $active )
	{
		switch ( $context )
		{
		case 'home':
			$widget_contexts[$widget]['home'] = $active;
			$widget_contexts[$widget]['blog'] = $active;
			break;
			
		case 'post':
			$widget_contexts[$widget]['post'] = $active;
			$widget_contexts[$widget]['attachment'] = $active;
			break;

		case 'page':
			$widget_contexts[$widget]['page'] = $active;
			break;

		case 'sell':
			$widget_contexts[$widget]['template_letter'] = $active;
			break;

		case 'special':
			$widget_contexts[$widget]['template_special'] = $active;
			break;

		case 'list':
			$widget_contexts[$widget]['template_archives'] = $active;
			$widget_contexts[$widget]['template_links'] = $active;
			break;

		case 'archive':
			$widget_contexts[$widget]['categories'] = $active;
			$widget_contexts[$widget]['tags'] = $active;
			$widget_contexts[$widget]['archive'] = $active;
			break;

		case 'search':
			$widget_contexts[$widget]['search'] = $active;
			$widget_contexts[$widget]['404_error'] = $active;
			break;
		}
	}
}

update_option('widget_contexts', $widget_contexts);

endif;

#
# Debug
#

#dump($sem_options);
#die;
?>