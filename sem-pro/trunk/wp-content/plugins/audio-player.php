<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'audio-player.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('mediacaster/mediacaster.php', $active_plugins) ) {
	$new_plugin = 'mediacaster/mediacaster.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);

$to_upgrade = $wpdb->get_results("
	SELECT	ID,
			post_content
	FROM	$wpdb->posts
	WHERE	post_content LIKE '%<!--podcast%'
	");

$ignore_user_abort = ignore_user_abort(true);
foreach ( $to_upgrade as $to_do ) {
	$to_do->post_content = preg_replace(
		"/<!--podcast#(.+?)-->/",
		"[mc src=\"$1\" type=\"audio\"/]",
		$to_do->post_content);
	
	$wpdb->query("
		UPDATE	$wpdb->posts
		SET		post_content = '" . $wpdb->escape($to_do->post_content) . "'
		WHERE	ID = " . $to_do->ID
		);
}
ignore_user_abort($ignore_user_abort);
?>