<?php

global $wpdb;
global $wp_rewrite;

include_once sem_pro_path . '/admin/misc.php';

# display lists of post titles rather than entire entries as archives?
$sem_options['theme_archives'] = true;

# kudos?
$sem_options['theme_credits'] = false;


# Permalinks

$prefix = '';
$permalink_structure = '';
$cat_base = '';
$tag_base = '';

if ( got_mod_rewrite() && is_file(ABSPATH . '.htaccess') && is_writable(ABSPATH . '.htaccess') )
{
	$permalink_structure = '/%year%/%monthnum%/%day%/%postname%/';
}


#
# the plugins that should be installed (file names in the plugin directory)
#

$default_plugins = array(
			'autotag/autotag.php',
			'feedburner/feedburner.php',
			'fuzzy-widgets/fuzzy-widgets.php',
			'mediacaster/mediacaster.php',
			'ozh-who-sees-ads/wp_ozh_whoseesads.php',
			'related-widgets/related-widgets.php',
			'sem-admin-menu/sem-admin-menu.php',
			'sem-author-image/sem-author-image.php',
			'sem-bookmark-me/sem-bookmark-me.php',
			'sem-fancy-excerpt/sem-fancy-excerpt.php',
			'sem-frame-buster/sem-frame-buster.php',
			'sem-google-analytics/sem-google-analytics.php',
			'sem-newsletter-manager/sem-newsletter-manager.php',
			'sem-search-reloaded/sem-search-reloaded.php',
			'sem-semiologic-affiliate/sem-semiologic-affiliate.php',
			'sem-static-front/sem-static-front.php',
			'sem-subscribe-me/sem-subscribe-me.php',
			'sem-unfancy-quote/sem-unfancy-quote.php',
			'sem-wysiwyg/sem-wysiwyg.php',
			'silo/silo.php',
			'simple-tags/simple-tags.php',
			'version-checker/version-checker.php',
			'wp-cache/wp-cache.php',
			'singular.php',
			'sitemap.php',
			'url-absolutifier.php',
	);

# default comment and ping status
$default_comment_status = 'closed';

# default sidebars
$default_sidebars["the_entry"] = array(
		"entry-header",
		"author-image",
		"entry-content",
		"entry-tags",
		"entry-categories",
		"bookmark-me",
		"entry-actions",
		"related-widget-1",
		"entry-comments",
		);

$default_sidebars['sidebar-1'] = array(
		'newsletter',
		'subscribe-me',
		'silo-pages',
		'fuzzy-widget-1',
		);


$ping_sites = "
http://rpc.pingomatic.com
http://www.blogpeople.net/servlet/weblogUpdates
http://bulkfeeds.net/rpc
http://ping.myblog.jp
http://ping.bitacoras.com
http://ping.bloggers.jp/rpc/
http://bblog.com/ping.php
http://blogsearch.google.com/ping/RPC2
";


#
# stop editing here
#

$post_count = intval($wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type = 'post' ORDER BY ID DESC LIMIT 1"));

if ( $post_count <= 1 && !isset($_POST['action']) )
{
	$wpdb->hide_errors();

	$wpdb->query("DELETE FROM $wpdb->posts;");
	$wpdb->query("DELETE FROM $wpdb->postmeta;");
	$wpdb->query("DELETE FROM $wpdb->comments;");
	$wpdb->query("DELETE FROM $wpdb->links;");
	$wpdb->query("DELETE FROM $wpdb->term_relationships;");
	$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = 0;");

	update_option('permalink_structure', $permalink_structure);
	update_option('category_base', $cat_base);
	update_option('tag_base', $tag_base);

	$wp_rewrite->flush_rules();

	$wpdb->query("
		UPDATE	$wpdb->terms
		SET		name = '" . __('Blog') . "',
				slug = '" . 'blog' . "'
		WHERE	slug = 'uncategorized'
		");

	$role = get_role('administrator');

	$role->add_cap('edit_files', false);
	$role->add_cap('edit_themes', false);
	$role->add_cap('edit_plugins', false);

	$wpdb->query("UPDATE $wpdb->posts SET comment_status = '$default_comment_status', ping_status = '$default_comment_status';");

	update_option('default_comment_status', $default_comment_status);
	update_option('default_ping_status', $default_comment_status);

	update_option("ping_sites", $ping_sites);

	sort($default_plugins);

	$old_plugin_page = $GLOBALS['plugin_page'];
	unset($GLOBALS['plugin_page']);

	foreach ( $default_plugins as $plugin )
	{
		if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin) )
		{
			include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
			do_action('activate_' . $plugin);
		}
	}

	$GLOBALS['plugin_page'] = $old_plugin_page;

	update_option('active_plugins', $default_plugins);

	$wpdb->show_errors();
}
?>