<?php
#
# safely delete this file
#

$active_plugins = get_settings('active_plugins');

foreach ( $active_plugins as $key => $plugin )
{
	if ( $plugin == 'widgets/now-reading.php' )
	{
		unset($active_plugins[$key]);
		sort($active_plugins);

		update_option('active_plugins', $active_plugins);
		break;
	}
}
?>