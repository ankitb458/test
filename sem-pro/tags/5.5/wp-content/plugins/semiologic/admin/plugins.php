<?php
# autoinclude plugins

foreach ( array(
	'mypageorder/mypageorder.php',
	) as $extra_file
	)
{
	include_once ABSPATH . PLUGINDIR . '/' . $extra_file;
}
?>