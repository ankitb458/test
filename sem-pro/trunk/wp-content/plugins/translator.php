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
		if ( $plugin == 'translator.php' )
		{
			unset($active_plugins[$key]);
			break;
		}
	}

	if ( !in_array('global-translator/translator.php', $active_plugins) )
	{
		$active_plugins[] = 'global-translator/translator.php';
	}

	sort($active_plugins);

	#var_dump($active_plugins);

	update_option('active_plugins', $active_plugins);
}


#
# 301 redirect to new translator url
#

if ( preg_match("/translator\.php$/i", $_SERVER['PHP_SELF']) )
{
	$is_IIS = strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') ? 1 : 0;

	$location = preg_replace("/translator\.php$/", "global-translator/translator.php", $_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING'];

	$location = preg_replace('|[^a-z0-9-~+_.?#=&;,/:%]|i', '', $location);

	$strip = array('%0d', '%0a');
	$location = str_replace($strip, '', $location);

	#var_dump($location);

	if ($is_IIS)
		header("Refresh: 0;url=$location");
	else
		header("Location: $location");

}
?>