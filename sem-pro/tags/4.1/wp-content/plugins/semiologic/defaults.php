<?php
#
# theme configuration
#

$GLOBALS['semiologic'] = array(
#
# default nav menus are of type array($name => $url)
#
	'nav_menus' => array(
		'header_nav' => array(
			'Home' => get_bloginfo('home'),
			'Blog' => 'blog',
			'About' => 'about',
			'Contact' => 'contact'
			),
		'sidebar_nav' => array(
			),
		'footer_nav' => array(
			'Archives' => 'archives',
			'About' => 'about',
			'Contact' => 'contact'
			)
		),

#
# default skin and its details
#
	'active_skin' => array(
		# skin's directory
		'skin' => 'sky-gold',
		# details from the skin.css file
		'name' => 'Sky Gold',
		'author' => 'Denis de Bernardy',
		'author_uri' => 'http://www.mesoconcepts.com'
		),

#
# default layout:
# - e: external sidebar
# - m: main column
# - s: sidebar
# ex: ems, mse, ms, em, m
#
	'active_layout' => 'mse',

#
# default width:
# - narrow
# - wide
# - flex
#
	'active_width' => 'wide',

#
# default font:
# - antica
# - arial
# - bookman
# - comic
# - corsiva
# - courier
# - garamond
# - georgia
# - tahoma
# - times
# - verdana
#
	'active_font' => 'bookman',

#
# active header background:
#
	'active_header' => '',

#
# theme captions
#
	'captions' => array(
			'search' => __('Search'),
			'go' => __('Go'),
			'copyright' => __('Copyright %year%, %admin_name%'),
			'edit' => __('Edit'),
			'by' => __('By'),
			'more' => __('More'),
			'page' => __('Page'),
			'filed_under' => __('Filed under'),
			'permalink' => __('Permalink'),
			'print' => __('Print'),
			'email' => __('Email'),
			'comment' => __('Comment'),
			'no_comments' => __('No Comments'),
			'1_comment' => __('1 Comment'),
			'n_comments' => __('% Comments'),
			'trackback_uri' => __('Trackback uri'),
			'track_this_entry' => __('Track this entry'),
			'related_entries' => __('Related Entries'),
			'no_entries_found' => __('No entries found'),
			'previous_page' => __('Previous Page'),
			'next_page' => __('Next Page')
		),
# autopopulate keywords and description?
	'theme_meta' => true,
# display lists of post titles rather than entire entries as archives?
	'theme_archives' => true,
# show credits?
	'theme_credits' => false
	);


#
# the plugins that should be installed (file names in the plugin directory)
#

$default_plugins = array(
			'countdown/countdown.php',
			'democracy/democracy.php',
			'sem-ad-space/sem-ad-space.php',
			'sem-admin-menu/sem-admin-menu.php',
			'sem-author-image/sem-author-image.php',
			'sem-bookmark-me/sem-bookmark-me.php',
			'sem-external-links/sem-external-links.php',
			'sem-extract-terms/sem-terms2posts.php',
			'sem-fancy-excerpt/sem-fancy-excerpt.php',
			'sem-frame-buster/sem-frame-buster.php',
			'sem-google-analytics/sem-google-analytics.php',
			'sem-newsletter-manager/sem-newsletter-manager.php',
			'sem-opt-in-front/sem-opt-in-front.php',
			'sem-recent-posts/sem-recent-posts.php',
			'sem-search-reloaded/sem-search-reloaded.php',
			'sem-semiologic-affiliate/sem-semiologic-affiliate.php',
			'sem-smart-link/sem-smart-link.php',
			'sem-static-front/sem-static-front.php',
			'sem-subscribe-me/sem-subscribe-me.php',
			'sem-unfancy-quote/sem-unfancy-quote.php',
			'sem-wyswiyg/sem-wyswiyg.php',
			'star-rating/star-rating.php',
			'widgets/widgets.php',
			'wp-cache/wp-cache.php',
			'wp-contact-form/wp-contactform.php',
			'audio-player.php',
			'category-cloud.php',
			'commentcontrol.php',
			'flickr_widget.php',
			'nopingwait2.php',
			'ol_feedburner.php',
			'redirect-old-slugs.php',
			'rjs-404.php',
			'singular.php',
			'sitemap.php',
			'smart-update-pinger.php',
			'wppaypal.php',
			'wp_ozh_betterfeed.php',
			'wp-db-backup.php',
			'wp-flv.php',
			'ylsy_permalink_redirect.php'
	);


#
# stop editing here
#

if ( // a freshly installed site
	!get_settings('active_plugins')
	&& $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts") < 5
	)
{
	sort($default_plugins);

	update_option('active_plugins', $default_plugins);

	$wpdb->query("UPDATE $wpdb->posts SET comment_status = 'closed', ping_status = 'closed';");
	$wpdb->query("UPDATE $wpdb->categories SET cat_name = 'Blog', category_nicename = 'blog' WHERE cat_ID = 1;");

	update_option('default_comment_status', 'closed');
	update_option('default_ping_status', 'closed');

	update_option(
		'sidebars_widgets',
		array(
			'sidebar-1' => array(
					'Fuzzy Posts',
					'Categories'
					),
			'sidebar-2' => array(
					'Calendar',
					'Subscribe Me'
					)
			)
		);

	wp_redirect(trailingslashit(get_settings('siteurl')) . '/wp-admin/themes.php?page=skin.php');
}
?>