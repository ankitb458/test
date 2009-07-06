<?php
# obsolete file
global $wpdb;

# upgrade home page
$home_page = $wpdb->get_var("
	SELECT	ID
	FROM	$wpdb->posts
	WHERE	post_type = 'page'
	AND		post_status = 'publish'
	AND		post_name = 'home'
	");

if ( $home_page = intval($home_page) ) {
	update_option('show_on_front', 'page');
	update_option('page_on_front', $home_page);
	
	# upgrade blog page
	$blog_page = $wpdb->get_var("
		SELECT	ID
		FROM	$wpdb->posts
		WHERE	post_type = 'page'
		AND		post_status = 'publish'
		AND		post_name = 'blog'
		");

	if ( $blog_page = intval($blog_page) ) {
		update_option('page_for_posts', $blog_page);
	}
}

# deactivate plugin
$active_plugins = get_option('active_plugins', array());

foreach ( (array) $active_plugins as $key => $plugin ) {
	if ( $plugin == 'sem-static-front/sem-static-front.php' ) {
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>