<?php
# obsolete file
if ( function_exists('get_option') ) {
	$active_plugins = get_option('active_plugins', array());

	foreach ( (array) $active_plugins as $key => $plugin ) {
		if ( $plugin == 'translator.php' ) {
			unset($active_plugins[$key]);
			break;
		}
	}

	sort($active_plugins);

	update_option('active_plugins', $active_plugins);
# else redirect
} elseif ( preg_match("/translator\.php$/i", $_SERVER['PHP_SELF']) ) {
	$is_IIS = strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') ? 1 : 0;

	if ($is_IIS)
		header("Refresh: 0;url=/");
	else
		header("Location: /");
}
?>