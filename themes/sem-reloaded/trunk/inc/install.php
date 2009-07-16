<?php
#
# Step 1
# ------
# Set theme defaults
#

# Skin, layout, font
$sem_options['active_skin'] = 'copywriter-gold';
$sem_options['active_layout'] = 'mts';
$sem_options['active_font'] = '';

# Credits
$sem_options['credits'] = __('Made with %1$s &bull; %2$s skin by %3$s', 'sem-reloaded');

# Version
$sem_options['version'] = sem_version;

add_option('init_sem_panels', '1');

# Update
if ( !defined('sem_install_test') )
	update_option('sem6_options', $sem_options);


if ( !empty($sem_pro_version) ) :
#
# Step 2
# ------
# Check if this is a new site
#

$max_id = $wpdb->get_var("
	SELECT	ID
	FROM	$wpdb->posts
	WHERE	post_type IN ( 'post', 'page' )
	ORDER BY ID DESC
	LIMIT 1
	");

if ( $max_id == 2 ) {
	$do_reset = (bool) $wpdb->get_var("
		SELECT	1 as do_reset
		FROM	$wpdb->posts as posts,
		 		$wpdb->posts as pages
		WHERE	posts.post_type = 'post'
		AND		pages.post_type = 'page'
		AND		posts.post_date = pages.post_date
		");
} else {
	$do_reset = false;
}


if ( $do_reset) :
#
# Step 3
# ------
# Flush WP junk
#

# Delete default posts, links and comments
$wpdb->query("DELETE FROM $wpdb->posts;");
$wpdb->query("DELETE FROM $wpdb->postmeta;");
$wpdb->query("DELETE FROM $wpdb->comments;");
$wpdb->query("DELETE FROM $wpdb->links;");
$wpdb->query("DELETE FROM $wpdb->term_relationships;");
$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = 0;");

# Rename Uncategorized category as Blog
$wpdb->query("
	UPDATE	$wpdb->terms
	SET		name = '" . $wpdb->escape(__('Blog', 'sem-reloaded')) . "',
			slug = 'blog'
	WHERE	slug = 'uncategorized'
	");


#
# Step 4
# ------
# Set permalink structure
#

$permalink_structure = '';

if ( !function_exists('got_mod_rewrite') ) {
	include_once ABSPATH . 'wp-admin/includes/file.php';
	include_once ABSPATH . 'wp-admin/includes/misc.php';
}

if ( got_mod_rewrite() && is_file(ABSPATH . '.htaccess') && is_writable(ABSPATH . '.htaccess') ) {
	$permalink_structure = '/%year%/%monthnum%/%postname%/';
	update_option('permalink_structure', $permalink_structure);
}

$wp_rewrite =& new WP_Rewrite;
$wp_rewrite->flush_rules();


#
# Step 5
# ------
# Override default options
#

update_option('default_comment_status', 'closed');
update_option('default_ping_status', 'closed');
update_option('use_balanceTags', '1');
update_option('users_can_register', '0');


endif;


#
# Step 6
# ------
# Activate Semiologic Pro plugins
#

$active_plugins = get_option('active_plugins', array());

$extra_plugins = array(
	'ad-manager/ad-manager.php',
	'auto-thickbox/auto-thickbox.php',
	'contact-form/contact-form.php',
 	'feedburner/feedburner.php',
	'fuzzy-widgets/fuzzy-widgets.php',
	'google-analytics/google-analytics.php',
	'inline-widgets/inline-widgets.php',
	'mediacaster/mediacaster.php',
	'newsletter-manager/newsletter-manager.php',
	'nav-menus/nav-menus.php',
	'redirect-manager/redirect-manager.php',
	'related-widgets/related-widgets.php',
	'script-manager/script-manager.php',
	'sem-admin-menu/sem-admin-menu.php',
	'sem-bookmark-me/sem-bookmark-me.php',
	'sem-fancy-excerpt/sem-fancy-excerpt.php',
	'sem-fixes/sem-fixes.php',
	'sem-frame-buster/sem-frame-buster.php',
	'sem-semiologic-affiliate/sem-semiologic-affiliate.php',
	'sem-seo/sem-seo.php',
	'sem-subscribe-me/sem-subscribe-me.php',
	'silo/silo.php',
	'version-checker/version-checker.php',
	'wp-hashcash/wp-hashcash.php',
	);

if ( get_option('blog_public') && get_option('permalink_structure') ) {
	$extra_plugins[] = 'xml-sitemaps/xml-sitemaps.php';
}

foreach ( $extra_plugins as $plugin ) {
	if ( file_exists(WP_PLUGIN_DIR . '/' . $plugin ) )
		$active_plugins[] = $plugin;
}

$active_plugins = array_unique($active_plugins);
sort($active_plugins);

update_option('active_plugins', $active_plugins);

if ( !empty($plugin_page) ) {
	$plugin_page_backup = $plugin_page;
	unset($plugin_page);
} else {
	$plugin_page_backup = false;
}

foreach ( $active_plugins as $plugin ) {
	if ( file_exists(WP_PLUGIN_DIR . '/' . $plugin) ) {
		include_once WP_PLUGIN_DIR . '/' . $plugin;
		do_action('activate_' . $plugin);
	}
}

if ( $plugin_page_backup ) {
	$plugin_page = $plugin_page_backup;
	unset($plugin_page_backup);
}

endif; # sem pro
?>