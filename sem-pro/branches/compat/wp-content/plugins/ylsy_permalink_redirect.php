<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'ylsy_permalink_redirect.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>