<?php

function wiz_autoinstall_pro()
{
	global $wpdb;
	global $wp_rewrite;

	$options = get_option('semiologic');

	# display lists of post titles rather than entire entries as archives?
	$options['theme_archives'] = true;

	# kudos?
	$options['theme_credits'] = true;

	update_option('semiologic', $options);


	#
	# the plugins that should be installed (file names in the plugin directory)
	#

	$default_plugins = array(
				'countdown/countdown.php',
				'democracy/democracy.php',
				'mediacaster/mediacaster.php',
				'mycategoryorder/mycategoryorder.php',
				'mylinkorder/mylinkorder.php',
				'mypageorder/mypageorder.php',
				'sem-ad-space/sem-ad-space.php',
				'sem-admin-menu/sem-admin-menu.php',
				'sem-author-image/sem-author-image.php',
				'sem-bookmark-me/sem-bookmark-me.php',
				'sem-extract-terms/sem-terms2posts.php',
				'sem-extract-terms/sem-terms2posts4feeds.php',
				'sem-fancy-excerpt/sem-fancy-excerpt.php',
				'sem-frame-buster/sem-frame-buster.php',
				'sem-google-analytics/sem-google-analytics.php',
				'sem-newsletter-manager/sem-newsletter-manager.php',
				'sem-recent-posts/sem-recent-posts.php',
				'sem-search-reloaded/sem-search-reloaded.php',
				'sem-semiologic-affiliate/sem-semiologic-affiliate.php',
				'sem-smart-link/sem-smart-link.php',
				'sem-static-front/sem-static-front.php',
				'sem-subscribe-me/sem-subscribe-me.php',
				'sem-unfancy-quote/sem-unfancy-quote.php',
				'sem-wysiwyg/sem-wysiwyg.php',
				'silo/silo.php',
				'simple-trackback-validator.php',
				'star-rating/star-rating.php',
				'wp-contact-form/wp-contactform.php',
				'wp-db-backup/wp-db-backup.php',
				'wp-hashcash/wp-hashcash.php',
				'commentcontrol.php',
				'flickr_widget.php',
				'singular.php',
				'sitemap.php',
				'translator.php',
				'url-absolutifier.php',
				'wppaypal.php',
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
						'Silo Pages',
						'Categories',
						'Fuzzy Posts',
						),
				'sidebar-3' => array(
						)
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

		$wpdb->hide_errors();

		@include_once ABSPATH . 'wp-content/plugins/democracy/democracy.php';
		@jal_dem_install();

		@include_once ABSPATH . 'wp-content/plugins/now-reading/now-reading.php';
		@nr_install();

		$query = mysql_query("SHOW COLUMNS FROM $wpdb->categories LIKE 'cat_order'") or die(mysql_error());

		if (mysql_num_rows($query) == 0) {
			$wpdb->query("ALTER TABLE $wpdb->categories ADD `cat_order` INT( 4 ) NULL DEFAULT '0'");
		}

		$query2 = mysql_query("SHOW COLUMNS FROM $wpdb->links LIKE 'link_order'") or die(mysql_error());

		if (mysql_num_rows($query2) == 0) {
			$wpdb->query("ALTER TABLE $wpdb->links ADD `link_order` INT( 4 ) NULL DEFAULT '0'");
		}

		$wpdb->show_errors();

		update_option("ping_sites", $ping_sites);

		$role = get_role('administrator');

		$role->add_cap('edit_files', false);
		$role->add_cap('edit_themes', false);
		$role->add_cap('edit_plugins', false);

		update_option('permalink_redirect_hostname', 1);
	}
} # end wiz_autoinstall_pro()
?>