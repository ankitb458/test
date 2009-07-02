<?php
# autoinclude plugins

foreach ( array(
	'impostercide.php',
	'not-to-me.php',
	) as $extra_file
	)
{
	include_once ABSPATH . PLUGINDIR . '/' . $extra_file;
}
?>