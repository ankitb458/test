<?php
#
# Step 1
# ------
# Set default captions
#

$site_name = get_option('blogname');

$sem_captions = array(
	"1_comment_link" => "1 Comment",
	"cats_title" => "Categories",
	"comment_link" => "Comment",
	"comments_on" => "Comments on %title%",
	"comment_permalink" => "Permalink",
	"comment_trackback" => "Trackback URI",	
	"copyright" => "Copyright %year%, $site_name",
	"email_field" => "Email",
	"email_link" => "Email",
	"required_field" => "(Required Field)",
	"filed_under" => "Filed under %categories% by %author%",
	"leave_comment" => "Leave a Comment",
	"logged_in_as" => "Logged in as %identity%",
	"login_required" => "You must be logged in to comment",
	"more_link" => "More on %title%",
	"n_comments_link" => "%num% Comments",
	"name_field" => "Name",
	"next_page" => "Next Page",
	"paginate" => "Pages",
	"permalink" => "Permalink",
	"prev_page" => "Previous Page",
	"print_link" => "Print",
	"reply_link" => "Reply",
	"search_button" => "Go",
	"search_field" => "Search",
	"sidebar_nav_title" => "Navigate",
	"submit_field" => "Submit Comment",
	"tags" => "Tags: %tags%",
	"tags_title" => "Tags",
	"url_field" => "URL",
	);

# Update
update_option('sem6_captions', $sem_captions);


#
# Step 2
# ------
# Default Nav Menus
#

foreach ( array('header', 'footer') as $area )
{
	$sem_nav_menus[$area] = array(
		'items' => array(
			0 => array(
				'type' => 'home',
				'label' => 'Home',
				)
			)
		);
}

# Update
update_option('sem_nav_menus', $sem_nav_menus);


#
# Step 3
# ------
# Set theme defaults
#

# Skin, layout, font, width
$sem_options['active_skin'] = 'copywriter-gold';
$sem_options['active_layout'] = 'mts';

# Header
$sem_options['header']['mode'] = 'header';

# Template
$sem_options['show_post_date'] = true;
$sem_options['show_permalink'] = true;
$sem_options['show_print_link'] = true;
$sem_options['show_email_link'] = true;
$sem_options['show_comment_link'] = true;
$sem_options['show_search_form'] = true;
$sem_options['show_copyright'] = true;
$sem_options['show_credits'] = true;

# Version
$sem_options['version'] = sem_version;

# Update
if ( !defined('sem_install_test') )
{
	update_option('sem6_options', $sem_options);
}


#
# Step 4
# ------
# Check if this is a new site
#

global $wpdb;

$max_id = $wpdb->get_var("
	SELECT	ID
	FROM	$wpdb->posts
	WHERE	post_type IN ( 'post', 'page' )
	ORDER BY ID DESC
	LIMIT 1
	");

if ( $max_id == 2 )
{
	$do_reset = (bool) $wpdb->get_var("
		SELECT	1 as do_reset
		FROM	$wpdb->posts as posts,
		 		$wpdb->posts as pages
		WHERE	posts.post_type = 'post'
		AND		pages.post_type = 'page'
		AND		posts.post_date = pages.post_date
		");
}
else
{
	$do_reset = false;
}


#
# Steps 5, 6 and 7 only apply to new sites
#

if ( $do_reset ) :


#
# Step 5
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

# Rename uncategorized category as Blog
$wpdb->query("
	UPDATE	$wpdb->terms
	SET		name = '" . __('Blog') . "',
			slug = '" . 'blog' . "'
	WHERE	slug = 'uncategorized'
	");


#
# Step 6
# ------
# Set permalink structure
#

$permalink_structure = '';
$cat_base = '';
$tag_base = '';

if ( !function_exists('got_mod_rewrite') )
{
	include ABSPATH . 'wp-admin/includes/file.php';
	include ABSPATH . 'wp-admin/includes/misc.php';
}

if ( got_mod_rewrite() && is_file(ABSPATH . '.htaccess') && is_writable(ABSPATH . '.htaccess') )
{
	$permalink_structure = '/%year%/%monthnum%/%day%/%postname%/';
}

update_option('permalink_structure', $permalink_structure);
update_option('category_base', $cat_base);
update_option('tag_base', $tag_base);

$GLOBALS['wp_rewrite'] =& new WP_Rewrite();
$GLOBALS['wp_rewrite']->flush_rules();


#
# Step 7
# ------
# Ping List and Comments Status
#

update_option('default_comment_status', 'closed');
update_option('default_ping_status', 'closed');


#
# last step applies to all sites
#

endif;


#
# Step 8
# ------
# Activate Semiologic Pro plugins
#

if ( ( $active_plugins = get_option('active_plugins') ) === false )
{
	$active_plugins = array();
}

$extra_plugins = array(
	'ad-manager/ad-manager.php',
	'archive-widgets/archive-widgets.php',
	'autotag/autotag.php',
	'auto-thickbox/auto-thickbox.php',
	'contact-form/contact-form.php',
	'feed-widgets/feed-widgets.php',
 	'feedburner/feedburner.php',
	'fuzzy-widgets/fuzzy-widgets.php',
	'google-analytics/google-analytics.php',
	'inline-widgets/inline-widgets.php',
	'link-widgets/link-widgets.php',
	'mediacaster/mediacaster.php',
	'newsletter-manager/newsletter-manager.php',
	'nav-menus/nav-menus.php',
	'redirect-manager/redirect-manager.php',
	'related-widgets/related-widgets.php',
	'script-manager/script-manager.php',
	'search-reloaded/search-reloaded.php',	
	'sem-admin-menu/sem-admin-menu.php',
	'sem-author-image/sem-author-image.php',
	'sem-bookmark-me/sem-bookmark-me.php',
	'sem-cloner/sem-cloner.php',
	'sem-docs/sem-docs.php',
	'sem-fancy-excerpt/sem-fancy-excerpt.php',
	'sem-fixes/sem-fixes.php',
	'sem-frame-buster/sem-frame-buster.php',
	'sem-semiologic-affiliate/sem-semiologic-affiliate.php',
	'sem-seo/sem-seo.php',
	'sem-subscribe-me/sem-subscribe-me.php',
	'sem-unfancy-quote/sem-unfancy-quote.php',
	'silo/silo.php',
	'singular.php',
	'tag-cloud-widgets/tag-cloud-widgets.php',
	'text-widgets/text-widgets.php',
	'uploads-folder/uploads-folder.php',
	'version-checker/version-checker.php',
	'widget-contexts/widget-contexts.php',
	'wp-db-backup/wp-db-backup.php',
	'wp-hashcash/wp-hashcash.php',
	);

if ( get_option('blog_public') && get_option('permalink_structure') )
{
	$extra_plugins[] = 'xml-sitemaps/xml-sitemaps.php';
}

foreach ( $extra_plugins as $plugin )
{
	if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin ) )
	{
		$active_plugins[] = $plugin;
	}
}

$active_plugins = array_unique($active_plugins);
sort($active_plugins);

update_option('active_plugins', $active_plugins);

$plugin_page_backup = $GLOBALS['plugin_page'];

unset($GLOBALS['plugin_page']);

foreach ( $active_plugins as $plugin )
{
	if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin) )
	{
		include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
		do_action('activate_' . $plugin);
	}
}

$GLOBALS['plugin_page'] = $plugin_page_backup;


#
# Step 9
# ------
# Fetch docs
#

function sem_update_docs()
{
	if ( class_exists('sem_docs') )
	{
		sem_docs::update(true);
		remove_action('init', 'sem_update_docs');
	}
} # sem_update_docs()

add_action('init', 'sem_update_docs');


#
# Step 10
# -------
# Import Semiologic 4 options if present
#

if ( get_option('semiologic') ) :

include sem_path . '/inc/upgrade/4.x.php';

endif;
?>