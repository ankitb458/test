<?php

function wiz_autoinstall_pro()
{
	global $wpdb;
	global $wp_rewrite;

	# autopopulate keywords and description?
	$GLOBALS['semiologic']['theme_meta'] = true;

	# display lists of post titles rather than entire entries as archives?
	$GLOBALS['semiologic']['theme_archives'] = true;

	# kudos?
	if ( !function_exists('get_site_option') )
	{
		$GLOBALS['semiologic']['theme_credits'] = true;
	}


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
				'sem-extract-terms/sem-terms2posts4feeds.php',
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
				'sem-wysiwyg/sem-wysiwyg.php',
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
				'singular.php',
				'sitemap.php',
				'smart-update-pinger.php',
				'translator.php',
				'wppaypal.php',
				'wp_ozh_betterfeed.php',
				'wp-db-backup.php',
				'wp-hashcash.php',
				'wp-flv.php',
				'ylsy_permalink_redirect.php'
		);

	# default comment and ping status
	$default_comment_status = 'closed';

	# default sidebars
	$default_sidebars = array(
				'sidebar-1' => array(
						'Sidebar Ad',
						'Sidebar Nav',
						'Democracy',
						'Countdown',
						'Paypal Donate',
						),
				'sidebar-2' => array(
						'Newsletter',
						'Subscribe Me',
						'Fuzzy Posts',
						'Pages',
						'Categories',
						),
				'sidebar-3' => array(
						)
				);

	$ping_sites = "
http://rpc.pingomatic.com
http://ping.weblogs.se/
http://blogmatcher.com/u.php
http://coreblog.org/ping/
http://www.blogpeople.net/servlet/weblogUpdates
http://bulkfeeds.net/rpc
http://trackback.bakeinu.jp/bakeping.php
http://ping.myblog.jp
http://ping.bitacoras.com
http://ping.bloggers.jp/rpc/
http://ping.blogmura.jp/rpc/
http://xmlrpc.blogg.de
http://1470.net/api/ping
http://bblog.com/ping.php
http://blog.goo.ne.jp/XMLRPC
";


	$prefix = '';
	$post_permalink = '';
	$tag_permalink = '';

	if ( is_file(ABSPATH . '.htaccess') && is_writable(ABSPATH . '.htaccess') )
	{
		if ( !got_mod_rewrite() )
			$prefix = '/index.php';
		$post_permalink = $prefix . '/%year%/%monthnum%/%day%/%postname%/';
	}


	#
	# stop editing here
	#

	$post_count = intval($wpdb->get_var("SELECT ID FROM $wpdb->posts ORDER BY ID DESC"));

	if ( // a freshly installed site
		( !get_settings('active_plugins')
			&& $post_count < 5
			)
		// or the wizard
		|| ( isset($_POST['action'])
			&& $_POST['action'] = 'wizard'
			)
		)
	{
		sort($default_plugins);

		update_option('active_plugins', $default_plugins);

		$wpdb->query("UPDATE $wpdb->categories SET cat_name = 'Blog', category_nicename = 'blog' WHERE cat_ID = 1;");

		update_option('permalink_structure', $post_permalink);
		update_option('category_base', $tag_permalink);
		$wp_rewrite->set_permalink_structure($post_permalink);
		$wp_rewrite->set_category_base($tag_permalink);

		if ( $post_count <= 2 && !isset($_POST['action']) )
		{
			$wpdb->query("DELETE FROM $wpdb->posts;");
			$wpdb->query("DELETE FROM $wpdb->postmeta;");
			$wpdb->query("DELETE FROM $wpdb->post2cat;");
			$wpdb->query("UPDATE $wpdb->categories SET category_count = 0;");
			$wpdb->query("DELETE FROM $wpdb->comments;");
			$wpdb->query("DELETE FROM $wpdb->links;");
		}

		$wpdb->query("UPDATE $wpdb->posts SET comment_status = '$default_comment_status', ping_status = '$default_comment_status';");
		update_option('default_comment_status', $default_comment_status);
		update_option('default_ping_status', $default_comment_status);

		update_option(
			'sidebars_widgets',
			$default_sidebars
			);

		if ( file_exists(ABSPATH . 'wp-content/plugins/democracy/democracy.php') )
		{
			require_once ABSPATH . 'wp-content/plugins/democracy/democracy.php';
			jal_dem_install();
		}
		elseif ( file_exists(ABSPATH . 'wp-content/mu-plugins/democracy/democracy.php') )
		{
			require_once ABSPATH . 'wp-content/mu-plugins/democracy/democracy.php';
			jal_dem_install();
		}

		if ( file_exists(ABSPATH . 'wp-content/plugins/now-reading/now-reading.php') )
		{
			require_once ABSPATH . 'wp-content/plugins/now-reading/now-reading.php';
			nr_install();
		}

		update_option("ping_sites", $ping_sites);

		if ( function_exists('get_site_option')
			&& get_site_option($ping_sites) === false
			)
		{
			update_site_option("ping_sites", $ping_sites);
		}
	}
} # end wiz_autoinstall_pro()
?>