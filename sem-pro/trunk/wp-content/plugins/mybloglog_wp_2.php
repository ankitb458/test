<?php
// obsolete file

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'mybloglog_wp_2.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('mybloglog-recent-reader-widget/mybloglog-reader_roll.php', $active_plugins) )
{
	$active_plugins[] = 'mybloglog-recent-reader-widget/mybloglog-reader_roll.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>