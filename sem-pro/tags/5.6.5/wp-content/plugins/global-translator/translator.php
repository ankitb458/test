<?php

#
# kill translator plugin
#

if ( function_exists('get_option') )
{
	$active_plugins = get_option('active_plugins');

	if ( !is_array($active_plugins) )
	{
		$active_plugins = array();
	}

	foreach ( (array) $active_plugins as $key => $plugin )
	{
		if ( $plugin == 'global-translator/translator.php' )
		{
			unset($active_plugins[$key]);
			break;
		}
	}

	sort($active_plugins);

	#var_dump($active_plugins);

	update_option('active_plugins', $active_plugins);
}
?>