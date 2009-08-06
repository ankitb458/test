<?php
// obsolete file
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'wp-contact-form/wp-contactform.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('contact-form/contact-form.php', $active_plugins) ) {
	$new_plugin = 'contact-form/contact-form.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

if ( !in_array('inline-widgets/inline-widgets.php', $active_plugins) ) {
	$new_plugin = 'inline-widgets/inline-widgets.php';
	$active_plugins[] = $new_plugin;
	include_once WP_PLUGIN_DIR . '/' . $new_plugin;
	do_action('activate_' . $new_plugin);
}

if ( class_exists('contact_form') && class_exists('inline_widgets') && get_option('widget_contact_form') === false) {
	$email = get_option('wpcf_email');
	if ( !is_email($email) )
		$email = get_option('admin_email');
	$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
	$sidebars_widgets['inline_widgets'][] = 'contact_form-2';
	if ( !is_admin() && $GLOBALS['_wp_sidebars_widgets'] )
		$GLOBALS['_wp_sidebars_widgets']['inline_widgets'][] = 'contact_form-2';
	update_option('widget_contact_form', array('email' => $email));
	update_option('sidebars_widgets', $sidebars_widgets);
	
	$wpdb->query("
		UPDATE	$wpdb->posts
		SET		post_content = replace(post_content, '[CONTACT-FORM]', '[widget id=\"contact_form-2\"/]')
		WHERE	post_content LIKE '%[CONTACT-FORM]%'
		");
	
	$wpdb->query("
		UPDATE	$wpdb->posts
		SET		post_content = replace(post_content, '<!--contactform-->', '[widget id=\"contact_form-2\"/]')
		WHERE	post_content LIKE '%<!--contactform-->%'
		");
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>