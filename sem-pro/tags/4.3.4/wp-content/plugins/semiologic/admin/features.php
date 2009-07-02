<?php

function update_theme_features()
{
	check_admin_referer('sem_features');

	$all_features = get_theme_features();
	$theme_features = array();

	foreach ( $all_features as $feature_set_name => $feature_set )
	{
		$theme_features = array_merge($theme_features, array_keys($feature_set));
	}

	if ( in_array('feedburner', (array) $_POST['feature_id'])
		&& !in_array('enforce_permalink', (array) $_POST['feature_id'])
		)
	{
		$_POST['feature_id'][] = 'enforce_permalink';
	}
	elseif ( in_array('enforce_permalink', (array) $_POST['feature_id'])
		&& !in_array('feedburner', (array) $_POST['feature_id'])
		)
	{
		$_POST['feature_id'][] = 'feedburner';
	}

	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	foreach ( $theme_features as $feature_id )
	{
		if ( in_array($feature_id, (array) $_POST['feature_id']) )
		{
			theme_feature_activate($feature_id);
		}
		else
		{
			theme_feature_deactivate($feature_id);
		}
	}
} # end update_theme_features()

add_action('update_theme_features', 'update_theme_features');


#
# plugin_is_active()
#

function plugin_is_active($plugin)
{
	return file_exists(ABSPATH . 'wp-content/plugins/' . $plugin)
		&& in_array($plugin, (array) get_option('active_plugins'));
} # end plugin_is_active()


#
# plugin_activate()
#

function plugin_activate($plugin)
{
	if ( !plugin_is_active($plugin) )
	{
		$active_plugins = (array) get_option('active_plugins');

		$active_plugins[] = $plugin;

		sort($active_plugins);

		update_option('active_plugins', $active_plugins);
	}
} # end plugin_activate()


#
# plugin_deactivate()
#

function plugin_deactivate($plugin)
{
	if ( plugin_is_active($plugin) )
	{
		$active_plugins = array();

		foreach ( (array) get_option('active_plugins') as $cur_plugin )
		{
			if ( $cur_plugin != $plugin )
			{
				$active_plugins[] = $cur_plugin;
			}
		}

		sort($active_plugins);

		update_option('active_plugins', $active_plugins);
	}
} # end plugin_deactivate()


#
# theme_feature_is_active()
#

function theme_feature_is_active($feature_id)
{
	switch ( $feature_id )
	{
	case 'absolute_urls':
		return plugin_is_active('url-absolutifier.php');

	case 'ad_spaces':
		return plugin_is_active('sem-ad-space/sem-ad-space.php');

	case 'admin_menu':
		return plugin_is_active('sem-admin-menu/sem-admin-menu.php');

	case 'advanced_cache':
		return plugin_is_active('wp-cache/wp-cache.php');

	case 'akismet':
		return plugin_is_active('akismet/akismet.php');
		
	case 'around_this_date':
		return plugin_is_active('around-this-date/aroundthisdate_wdgt.php');		

	case 'authenticate_subscribers':
		return plugin_is_active('impostercide.php');

	case 'author_image':
		return plugin_is_active('sem-author-image/sem-author-image.php');

	case 'automatic_translation':
		return plugin_is_active('translator.php')
			|| plugin_is_active('global-translator/translator.php');

	case 'autolink_uri':
		return plugin_is_active('sem-autolink-uri/sem-autolink-uri.php');

	case 'autosave_entries':
		return plugin_is_active('tw-autosave.php');

	case 'blogpulse_link':
		return plugin_is_active('sem-blogpulse-link/sem-blogpulse-link.php');

	case 'book_library':
		return plugin_is_active('now-reading/now-reading.php');

	case 'bookmark_me':
		return plugin_is_active('sem-bookmark-me/sem-bookmark-me.php');

	case 'cc_license':
		return plugin_is_active('wpLicense.php');

	case 'contact_form':
		return plugin_is_active('wp-contact-form/wp-contactform.php');

	case 'cosmos_link':
		return plugin_is_active('sem-cosmos-link/sem-cosmos-link.php');

	case 'cron_dashboard':
		return plugin_is_active('wp-cron/wp-cron.php')
			&& plugin_is_active('wp-cron/wp-cron-dashboard.php');

	case 'cron_email':
		return ( get_option('mailserver_url') != 'mail.example.com' )
			&& ( get_option('mailserver_login') != 'login@example.com' )
			&& plugin_is_active('wp-cron/wp-cron.php')
			&& plugin_is_active('wp-cron/wp-cron-mail.php');

	case 'cron_moderation':
		return plugin_is_active('wp-cron/wp-cron.php')
			&& plugin_is_active('wp-cron/wp-cron-moderation.php');

	case 'cron_links':
		return plugin_is_active('wp-cron/wp-cron.php')
			&& plugin_is_active('wp-cron/wp-cron-update-links.php');

	case 'cron_pings':
		return plugin_is_active('wp-cron/wp-cron.php')
			&& plugin_is_active('wp-cron/wp-cron-future-pings.php');

	case 'delicious':
		return plugin_is_active('widgets/delicious.php');

	case 'db_backup':
		return plugin_is_active('wp-db-backup/wp-db-backup.php');

	case 'do_follow':
		return plugin_is_active('sem-dofollow/sem-dofollow.php');

	case 'dont_email_me':
		return plugin_is_active('not-to-me.php');

	case 'easy_order':
		return plugin_is_active('mycategoryorder/mycategoryorder.php')
			|| plugin_is_active('mylinkorder/mylinkorder.php')
			|| plugin_is_active('mypageorder/mypageorder.php');

	case 'enforce_permalink':
		return plugin_is_active('ylsy_permalink_redirect.php');

	case 'enhance_comment_admin':
		return plugin_is_active('commentcontrol.php');

	case 'enhance_comment_workflow':
		return plugin_is_active('impostercide.php')
			|| plugin_is_active('sdm_moderate_authors.php')
			|| plugin_is_active('not-to-me.php');

	case 'exec_php':
		return plugin_is_active('exec-php.php');

	case 'exec_php_widget':
		return plugin_is_active('execphp.php');

	case 'exernal_links':
		return plugin_is_active('sem-external-links/sem-external-links.php');

	case 'event_manager':
		return plugin_is_active('countdown/countdown.php');

	case 'fancy_excerpt':
		return plugin_is_active('sem-fancy-excerpt/sem-fancy-excerpt.php');

	case 'favicon':
		return plugin_is_active('favicon-head.php');

	case 'feedburner':
		return plugin_is_active('ylsy_permalink_redirect.php');

	case 'flickr_album':
		return plugin_is_active('falbum/wordpress-falbum-plugin.php');

	case 'flickr_widget':
		return plugin_is_active('flickr_widget.php');

	case 'google_analytics':
		return plugin_is_active('sem-google-analytics/sem-google-analytics.php');

	case 'google_sitemap':
		return plugin_is_active('sitemap.php');

	case 'google_search':
		return plugin_is_active('widgets/gsearch.php');

	case 'gravatars':
		return plugin_is_active('gravatars2.php')
			|| plugin_is_active('gravatars2-wpcron.php');

	case 'hashcash':
		return plugin_is_active('wp-hashcash/wp-hashcash.php');

	case 'highlight_search':
		return plugin_is_active('google-hilite.php');

	case 'hitslink':
		return plugin_is_active('hitslink/hitslink.php');

	case 'improved_search':
		return plugin_is_active('sem-search-reloaded/sem-search-reloaded.php');

	case 'kill_index_php':
		return plugin_is_active('kill-www-kill-index-php.php');

	case 'kill_frames':
		return plugin_is_active('sem-frame-buster/sem-frame-buster.php');

	case 'moderate_subscribers':
		return plugin_is_active('sdm_moderate_authors.php');

	case 'more_in_feeds':
		return plugin_is_active('wp_ozh_betterfeed.php') || !plugin_is_active('full_feed.php');

	case 'newsletter_manager':
		return plugin_is_active('sem-newsletter-manager/sem-newsletter-manager.php');

	case 'non_unique_slugs':
		return plugin_is_active('singular.php');

	case 'opt_in_front_page':
		return plugin_is_active('sem-opt-in-front/sem-opt-in-front.php');

	case 'paypal_donate':
		return plugin_is_active('wppaypal.php');

	case 'performancing_metrics':
		return plugin_is_active('zelig-performancing.php');

	case 'php_markdown':
		return plugin_is_active('markdown.php');

	case 'podcasting':
		return plugin_is_active('mediacaster/mediacaster.php');

	case 'poll_manager':
		return plugin_is_active('democracy/democracy.php');

	case 'post_count':
		return plugin_is_active('custom-query-string.php');

	case 'remove_fancy_quotes':
		return plugin_is_active('sem-unfancy-quote/sem-unfancy-quote.php');

	case 'related_entries':
		return plugin_is_active('sem-extract-terms/sem-terms2posts.php');

	case 'related_entries4feeds':
		return plugin_is_active('sem-extract-terms/sem-terms2posts4feeds.php');

	case 'related_searches':
		return plugin_is_active('sem-extract-terms/sem-terms2search.php');

	case 'related_tags':
		return plugin_is_active('sem-extract-terms/sem-terms2tags.php');

	case 'recent_comments':
		return plugin_is_active('sem-recent-comments/sem-recent-comments.php');

	case 'recent_links':
		return plugin_is_active('sem-recent-links/sem-recent-links.php');

	case 'recent_posts':
		return plugin_is_active('sem-recent-posts/sem-recent-posts.php');

	case 'recent_updates':
		return plugin_is_active('sem-recent-updates/sem-recent-updates.php');

	case 'rss_aggregator':
		return plugin_is_active('wp-autoblog/wp-autoblog.php');

	case 'role_manager':
		return plugin_is_active('role-manager/role-manager.php');

	case 'sem_affiliate':
		return plugin_is_active('sem-semiologic-affiliate/sem-semiologic-affiliate.php');

	case 'shopping_cart':
		return plugin_is_active('wp-shopping-cart/wp-shopping-cart.php');

	case 'sidebar_tile':
		return plugin_is_active('sem-tile-sidebar/sem-tile-sidebar.php');

	case 'sidebar_widgets':
		return plugin_is_active('widgets/widgets.php');

	case 'silo_site':
		return plugin_is_active('silo/silo.php');

	case 'site_unavailable':
		return plugin_is_active('unavailable.php');

	case 'smart_links':
		return plugin_is_active('sem-smart-link/sem-smart-link.php');

	case 'smart_pings':
		return plugin_is_active('nopingwait2.php')
			|| plugin_is_active('smart-update-pinger.php');

	case 'social_poster':
		return plugin_is_active('social-poster/mm_post.php');

	case 'star_rating':
		return plugin_is_active('star-rating/star-rating.php');

	case 'static_front_page':
		return plugin_is_active('sem-static-front/sem-static-front.php');

	case 'subscribe2comments':
		return plugin_is_active('subscribe-to-comments/subscribe-to-comments.php');

	case 'subscribe_me':
		return plugin_is_active('sem-subscribe-me/sem-subscribe-me.php');

	case 'tag_cloud':
		return plugin_is_active('category-cloud.php');

	case 'tb_validator':
		return plugin_is_active('simple-trackback-validation.php');

	case 'theme_archives':
		return isset($GLOBALS['semiologic']['theme_archives'])
				&& $GLOBALS['semiologic']['theme_archives'];

	case 'theme_credits':
		return get_show_credits(true);

	case 'videocasting':
		return plugin_is_active('wp-flv.php');

	case 'wysiwyg_editor':
		return plugin_is_active('sem-wysiwyg/sem-wysiwyg.php');

	default:
		return false;
	}
} # end theme_feature_is_active()


#
# theme_feature_activate()
#

function theme_feature_activate($feature_id)
{
	global $wpdb;

	switch ( $feature_id )
	{
	case 'absolute_urls':
		plugin_activate('url-absolutifier.php');
		break;

	case 'ad_spaces':
		plugin_activate('sem-ad-space/sem-ad-space.php');
		break;

	case 'admin_menu':
		plugin_activate('sem-admin-menu/sem-admin-menu.php');
		break;

	case 'advanced_cache':
		plugin_activate('wp-cache/wp-cache.php');
		break;

	case 'akismet':
		plugin_activate('akismet/akismet.php');
		break;

	case 'around_this_date':
		plugin_activate('around-this-date/aroundthisdate_wdgt.php');
		break;
		
	case 'authenticate_subscribers':
		plugin_activate('impostercide.php');
		break;

	case 'author_image':
		plugin_activate('sem-author-image/sem-author-image.php');
		break;

	case 'automatic_translation':
		plugin_deactivate('translator.php');
		plugin_activate('global-translator/translator.php');
		break;

	case 'autolink_uri':
		plugin_activate('sem-autolink-uri/sem-autolink-uri.php');
		break;

	case 'autosave_entries':
		plugin_activate('tw-autosave.php');
		break;

	case 'blogpulse_link':
		plugin_activate('sem-blogpulse-link/sem-blogpulse-link.php');
		break;

	case 'book_library':
		plugin_activate('now-reading/now-reading.php');
		plugin_activate('widgets/now-reading.php');
		break;

	case 'bookmark_me':
		plugin_activate('sem-bookmark-me/sem-bookmark-me.php');
		break;

	case 'cc_license':
		plugin_activate('wpLicense.php');
		break;

	case 'contact_form':
		plugin_activate('wp-contact-form/wp-contactform.php');
		break;

	case 'cosmos_link':
		plugin_activate('sem-cosmos-link/sem-cosmos-link.php');
		break;

	case 'cron_dashboard':
		plugin_activate('wp-cron/wp-cron.php');
		plugin_activate('wp-cron/wp-cron-dashboard.php');
		break;

	case 'cron_email':
		if ( ( get_option('mailserver_url') != 'mail.example.com' )
			&& ( get_option('mailserver_login') != 'login@example.com' )
			)
		{
			plugin_activate('wp-cron/wp-cron.php');
			plugin_activate('wp-cron/wp-cron-mail.php');
		}
		else
		{
			plugin_deactivate('wp-cron/wp-cron-mail.php');
		}
		break;

	case 'cron_moderation':
		plugin_activate('wp-cron/wp-cron.php');
		plugin_activate('wp-cron/wp-cron-moderation.php');
		break;

	case 'cron_links':
		plugin_activate('wp-cron/wp-cron.php');
		plugin_activate('wp-cron/wp-cron-update-links.php');
		break;

	case 'cron_pings':
		plugin_activate('wp-cron/wp-cron.php');
		plugin_activate('wp-cron/wp-cron-future-pings.php');
		break;

	case 'delicious':
		plugin_activate('widgets/delicious.php');
		break;

	case 'db_backup':
		plugin_activate('wp-db-backup/wp-db-backup.php');
		break;

	case 'do_follow':
		plugin_activate('sem-dofollow/sem-dofollow.php');
		break;

	case 'dont_email_me':
		plugin_activate('not-to-me.php');
		break;

	case 'easy_order':
		plugin_activate('mycategoryorder/mycategoryorder.php');
		plugin_activate('mylinkorder/mylinkorder.php');
		plugin_activate('mypageorder/mypageorder.php');

		$query = mysql_query("SHOW COLUMNS FROM $wpdb->categories LIKE 'cat_order'") or die(mysql_error());

		if (mysql_num_rows($query) == 0) {
			$wpdb->query("ALTER TABLE $wpdb->categories ADD `cat_order` INT( 4 ) NULL DEFAULT '0'");
		}

		$query2 = mysql_query("SHOW COLUMNS FROM $wpdb->links LIKE 'link_order'") or die(mysql_error());

		if (mysql_num_rows($query2) == 0) {
			$wpdb->query("ALTER TABLE $wpdb->links ADD `link_order` INT( 4 ) NULL DEFAULT '0'");
		}
		break;

	case 'enforce_permalink':
		plugin_activate('ylsy_permalink_redirect.php');
		break;

	case 'enhance_comment_admin':
		plugin_activate('commentcontrol.php');
		break;

	case 'enhance_comment_workflow':
		plugin_activate('impostercide.php');
		plugin_activate('sdm_moderate_authors.php');
		plugin_activate('not-to-me.php');
		break;

	case 'exec_php':
		plugin_activate('exec-php.php');
		break;

	case 'exec_php_widget':
		plugin_activate('execphp.php');
		break;

	case 'exernal_links':
		plugin_activate('sem-external-links/sem-external-links.php');
		break;

	case 'event_manager':
		plugin_activate('countdown/countdown.php');
		break;

	case 'fancy_excerpt':
		plugin_activate('sem-fancy-excerpt/sem-fancy-excerpt.php');
		break;

	case 'favicon':
		plugin_activate('favicon-head.php');
		break;

	case 'feedburner':
		plugin_activate('ylsy_permalink_redirect.php');
		break;

	case 'flickr_album':
		plugin_activate('falbum/wordpress-falbum-plugin.php');
		break;

	case 'flickr_widget':
		plugin_activate('flickr_widget.php');
		break;

	case 'google_analytics':
		plugin_activate('sem-google-analytics/sem-google-analytics.php');
		break;

	case 'google_sitemap':
		plugin_activate('sitemap.php');
		break;

	case 'google_search':
		plugin_activate('widgets/gsearch.php');
		break;

	case 'gravatars':
		plugin_deactivate('gravatars.php');
		plugin_activate('gravatars2.php');
		plugin_deactivate('wp-cron/wp-cron-gravcache.php');
		plugin_activate('gravatars2-wpcron.php');
		break;

	case 'hashcash':
		plugin_activate('wp-hashcash/wp-hashcash.php');
		break;

	case 'highlight_search':
		plugin_activate('google-hilite.php');
		break;

	case 'hitslink':
		plugin_activate('hitslink/hitslink.php');
		break;

	case 'improved_search':
		plugin_activate('sem-search-reloaded/sem-search-reloaded.php');
		break;

	case 'kill_index_php':
		plugin_activate('kill-www-kill-index-php.php');
		break;

	case 'kill_frames':
		plugin_activate('sem-frame-buster/sem-frame-buster.php');
		break;

	case 'moderate_subscribers':
		plugin_activate('sdm_moderate_authors.php');
		break;

	case 'more_in_feeds':
		plugin_activate('wp_ozh_betterfeed.php');
		plugin_deactivate('full_feed.php');
		break;

	case 'newsletter_manager':
		plugin_activate('sem-newsletter-manager/sem-newsletter-manager.php');
		break;

	case 'non_unique_slugs':
		plugin_activate('singular.php');
		break;

	case 'opt_in_front_page':
		plugin_activate('sem-opt-in-front/sem-opt-in-front.php');
		break;

	case 'paypal_donate':
		plugin_activate('wppaypal.php');
		break;

	case 'performancing_metrics':
		plugin_activate('zelig-performancing.php');
		break;

	case 'php_markdown':
		plugin_activate('markdown/markdown.php');
		break;

	case 'podcasting':
		plugin_activate('mediacaster/mediacaster.php');
		break;

	case 'poll_manager':
		plugin_activate('democracy/democracy.php');
		if ( file_exists(ABSPATH . 'wp-content/plugins/democracy/democracy.php') )
		{
			require_once ABSPATH . 'wp-content/plugins/democracy/democracy.php';
			jal_dem_install();
		}
		break;

	case 'post_count':
		plugin_activate('custom-query-string.php');
		break;

	case 'remove_fancy_quotes':
		plugin_activate('sem-unfancy-quote/sem-unfancy-quote.php');
		break;

	case 'related_entries':
		plugin_activate('sem-extract-terms/sem-terms2posts.php');
		break;

	case 'related_entries4feeds':
		plugin_activate('sem-extract-terms/sem-terms2posts4feeds.php');
		break;

	case 'related_searches':
		plugin_activate('sem-extract-terms/sem-terms2search.php');
		break;

	case 'related_tags':
		plugin_activate('sem-extract-terms/sem-terms2tags.php');
		break;

	case 'recent_comments':
		plugin_activate('sem-recent-comments/sem-recent-comments.php');
		break;

	case 'recent_links':
		plugin_activate('sem-recent-links/sem-recent-links.php');
		break;

	case 'recent_posts':
		plugin_activate('sem-recent-posts/sem-recent-posts.php');
		break;

	case 'recent_updates':
		plugin_activate('sem-recent-updates/sem-recent-updates.php');
		break;

	case 'rss_aggregator':
		plugin_activate('wp-autoblog/wp-autoblog.php');
		break;

	case 'role_manager':
		plugin_activate('role-manager/role-manager.php');
		break;

	case 'sem_affiliate':
		plugin_activate('sem-semiologic-affiliate/sem-semiologic-affiliate.php');
		break;

	case 'shopping_cart':
		plugin_activate('wp-shopping-cart/wp-shopping-cart.php');
		break;

	case 'sidebar_tile':
		plugin_activate('sem-tile-sidebar/sem-tile-sidebar.php');
		plugin_deactivate('widgets/widgets.php');
		break;

	case 'sidebar_widgets':
		plugin_deactivate('sem-tile-sidebar/sem-tile-sidebar.php');
		plugin_activate('widgets/widgets.php');
		break;

	case 'silo_site':
		plugin_activate('silo/silo.php');
		break;

	case 'site_unavailable':
		plugin_activate('unavailable.php');
		break;

	case 'smart_links':
		plugin_activate('sem-smart-link/sem-smart-link.php');
		break;

	case 'smart_pings':
		plugin_activate('nopingwait2.php');
		plugin_activate('smart-update-pinger.php');
		break;

	case 'social_poster':
		plugin_activate('social-poster/mm_post.php');
		break;

	case 'star_rating':
		plugin_activate('star-rating/star-rating.php');
		break;

	case 'static_front_page':
		plugin_activate('sem-static-front/sem-static-front.php');
		break;

	case 'subscribe2comments':
		plugin_activate('subscribe-to-comments/subscribe-to-comments.php');
		break;

	case 'subscribe_me':
		plugin_activate('sem-subscribe-me/sem-subscribe-me.php');
		break;

	case 'tag_cloud':
		plugin_activate('category-cloud.php');
		break;

	case 'tb_validator':
		plugin_activate('simple-trackback-validation.php');
		break;

	case 'theme_archives':
		$options = get_option('semiologic');
		$options['theme_archives'] = true;
		update_option('semiologic', $options);
		break;

	case 'theme_credits':
		$options = get_option('semiologic');
		$options['theme_credits'] = true;
		update_option('semiologic', $options);
		break;

	case 'videocasting':
		plugin_activate('wp-flv.php');
		break;

	case 'wysiwyg_editor':
		plugin_activate('sem-wysiwyg/sem-wysiwyg.php');
		break;
	}
} # end theme_feature_activate()


#
# theme_feature_deactivate()
#

function theme_feature_deactivate($feature_id)
{
	switch ( $feature_id )
	{
	case 'absolute_urls':
		plugin_deactivate('url-absolutifier.php');
		break;

	case 'ad_spaces':
		plugin_deactivate('sem-ad-space/sem-ad-space.php');
		break;

	case 'admin_menu':
		plugin_deactivate('sem-admin-menu/sem-admin-menu.php');
		break;

	case 'advanced_cache':
		plugin_deactivate('wp-cache/wp-cache.php');
		break;

	case 'akismet':
		plugin_deactivate('akismet/akismet.php');
		break;

	case 'around_this_date':
		plugin_deactivate('around-this-date/aroundthisdate_wdgt.php');
		break;
		
	case 'authenticate_subscribers':
		plugin_deactivate('impostercide.php');
		break;

	case 'author_image':
		plugin_deactivate('sem-author-image/sem-author-image.php');
		break;

	case 'automatic_translation':
		plugin_deactivate('translator.php');
		plugin_deactivate('global-translator/translator.php');
		break;

	case 'autolink_uri':
		plugin_deactivate('sem-autolink-uri/sem-autolink-uri.php');
		break;

	case 'autosave_entries':
		plugin_deactivate('tw-autosave.php');
		break;

	case 'blogpulse_link':
		plugin_deactivate('sem-blogpulse-link/sem-blogpulse-link.php');
		break;

	case 'book_library':
		plugin_deactivate('now-reading/now-reading.php');
		plugin_deactivate('widgets/now-reading.php');
		break;

	case 'bookmark_me':
		plugin_deactivate('sem-bookmark-me/sem-bookmark-me.php');
		break;

	case 'cc_license':
		plugin_deactivate('wpLicense.php');
		break;

	case 'contact_form':
		plugin_deactivate('wp-contact-form/wp-contactform.php');
		break;

	case 'cosmos_link':
		plugin_deactivate('sem-cosmos-link/sem-cosmos-link.php');
		break;

	case 'cron_dashboard':
		plugin_deactivate('wp-cron/wp-cron-dashboard.php');
		break;

	case 'cron_email':
		plugin_deactivate('wp-cron/wp-cron-mail.php');
		break;

	case 'cron_moderation':
		plugin_deactivate('wp-cron/wp-cron-moderation.php');
		break;

	case 'cron_links':
		plugin_deactivate('wp-cron/wp-cron-update-links.php');
		break;

	case 'cron_pings':
		plugin_deactivate('wp-cron/wp-cron-future-pings.php');
		break;

	case 'delicious':
		plugin_deactivate('widgets/delicious.php');
		break;

	case 'db_backup':
		plugin_deactivate('wp-db-backup/wp-db-backup.php');
		break;

	case 'do_follow':
		plugin_deactivate('sem-dofollow/sem-dofollow.php');
		break;

	case 'dont_email_me':
		plugin_deactivate('not-to-me.php');
		break;

	case 'easy_order':
		plugin_deactivate('mycategoryorder/mycategoryorder.php');
		plugin_deactivate('mylinkorder/mylinkorder.php');
		plugin_deactivate('mypageorder/mypageorder.php');
		break;

	case 'enforce_permalink':
		plugin_deactivate('ylsy_permalink_redirect.php');
		break;

	case 'enhance_comment_admin':
		plugin_deactivate('commentcontrol.php');
		break;

	case 'enhance_comment_workflow':
		plugin_deactivate('impostercide.php');
		plugin_deactivate('sdm_moderate_authors.php');
		plugin_deactivate('not-to-me.php');
		break;

	case 'exec_php':
		plugin_deactivate('exec-php.php');
		break;

	case 'exec_php_widget':
		plugin_deactivate('execphp.php');
		break;

	case 'exernal_links':
		plugin_deactivate('sem-external-links/sem-external-links.php');
		break;

	case 'event_manager':
		plugin_deactivate('countdown/countdown.php');
		break;

	case 'fancy_excerpt':
		plugin_deactivate('sem-fancy-excerpt/sem-fancy-excerpt.php');
		break;

	case 'favicon':
		plugin_deactivate('favicon-head.php');
		break;

	case 'feedburner':
		plugin_deactivate('ylsy_permalink_redirect.php');
		break;

	case 'flickr_album':
		plugin_deactivate('falbum/wordpress-falbum-plugin.php');
		break;

	case 'flickr_widget':
		plugin_deactivate('flickr_widget.php');
		break;

	case 'google_analytics':
		plugin_deactivate('sem-google-analytics/sem-google-analytics.php');
		break;

	case 'google_sitemap':
		plugin_deactivate('sitemap.php');
		break;

	case 'google_search':
		plugin_deactivate('widgets/gsearch.php');
		break;

	case 'gravatars':
		plugin_deactivate('gravatars.php');
		plugin_deactivate('gravatars2.php');
		plugin_deactivate('wp-cron/wp-cron-gravcache.php');
		plugin_deactivate('gravatars2-wpcron.php');
		break;

	case 'hashcash':
		plugin_deactivate('wp-hashcash/wp-hashcash.php');
		break;

	case 'highlight_search':
		plugin_deactivate('google-hilite.php');
		break;

	case 'hitslink':
		plugin_deactivate('hitslink/hitslink.php');
		break;

	case 'improved_search':
		plugin_deactivate('sem-search-reloaded/sem-search-reloaded.php');
		break;

	case 'kill_index_php':
		plugin_deactivate('kill-www-kill-index-php.php');
		break;

	case 'kill_frames':
		plugin_deactivate('sem-frame-buster/sem-frame-buster.php');
		break;

	case 'moderate_subscribers':
		plugin_deactivate('sdm_moderate_authors.php');
		break;

	case 'more_in_feeds':
		plugin_deactivate('wp_ozh_betterfeed.php');
		plugin_activate('full_feed.php');
		break;

	case 'newsletter_manager':
		plugin_deactivate('sem-newsletter-manager/sem-newsletter-manager.php');
		break;

	case 'non_unique_slugs':
		plugin_deactivate('singular.php');
		break;

	case 'opt_in_front_page':
		plugin_deactivate('sem-opt-in-front/sem-opt-in-front.php');
		break;

	case 'paypal_donate':
		plugin_deactivate('wppaypal.php');
		break;

	case 'performancing_metrics':
		plugin_deactivate('zelig-performancing.php');
		break;

	case 'php_markdown':
		plugin_deactivate('markdown/markdown.php');
		break;

	case 'podcasting':
		plugin_deactivate('mediacaster/mediacaster.php');
		break;

	case 'poll_manager':
		plugin_deactivate('democracy/democracy.php');
		break;

	case 'post_count':
		plugin_deactivate('custom-query-string.php');
		break;

	case 'remove_fancy_quotes':
		plugin_deactivate('sem-unfancy-quote/sem-unfancy-quote.php');
		break;

	case 'related_entries':
		plugin_deactivate('sem-extract-terms/sem-terms2posts.php');
		break;

	case 'related_entries4feeds':
		plugin_deactivate('sem-extract-terms/sem-terms2posts4feeds.php');
		break;

	case 'related_searches':
		plugin_deactivate('sem-extract-terms/sem-terms2search.php');
		break;

	case 'related_tags':
		plugin_deactivate('sem-extract-terms/sem-terms2tags.php');
		break;

	case 'recent_comments':
		plugin_deactivate('sem-recent-comments/sem-recent-comments.php');
		break;

	case 'recent_links':
		plugin_deactivate('sem-recent-links/sem-recent-links.php');
		break;

	case 'recent_posts':
		plugin_deactivate('sem-recent-posts/sem-recent-posts.php');
		break;

	case 'recent_updates':
		plugin_deactivate('sem-recent-updates/sem-recent-updates.php');
		break;

	case 'rss_aggregator':
		plugin_deactivate('wp-autoblog/wp-autoblog.php');
		break;

	case 'role_manager':
		plugin_deactivate('role-manager/role-manager.php');
		break;

	case 'sem_affiliate':
		plugin_deactivate('sem-semiologic-affiliate/sem-semiologic-affiliate.php');
		break;

	case 'shopping_cart':
		plugin_deactivate('wp-shopping-cart/wp-shopping-cart.php');
		break;

	case 'sidebar_tile':
		plugin_deactivate('sem-tile-sidebar/sem-tile-sidebar.php');
		break;

	case 'sidebar_widgets':
		plugin_deactivate('widgets/widgets.php');
		break;

	case 'silo_site':
		plugin_deactivate('silo/silo.php');
		break;

	case 'site_unavailable':
		plugin_deactivate('unavailable.php');
		break;

	case 'smart_links':
		plugin_deactivate('sem-smart-link/sem-smart-link.php');
		break;

	case 'smart_pings':
		plugin_deactivate('nopingwait2.php');
		plugin_deactivate('smart-update-pinger.php');
		break;

	case 'social_poster':
		plugin_deactivate('social-poster/mm_post.php');
		break;

	case 'star_rating':
		plugin_deactivate('star-rating/star-rating.php');
		break;

	case 'static_front_page':
		plugin_deactivate('sem-static-front/sem-static-front.php');
		break;

	case 'subscribe2comments':
		plugin_deactivate('subscribe-to-comments/subscribe-to-comments.php');
		break;

	case 'subscribe_me':
		plugin_deactivate('sem-subscribe-me/sem-subscribe-me.php');
		break;

	case 'tag_cloud':
		plugin_deactivate('category-cloud.php');
		break;

	case 'tb_validator':
		plugin_deactivate('simple-trackback-validation.php');
		break;

	case 'theme_archives':
		$options = get_option('semiologic');
		$options['theme_archives'] = false;
		update_option('semiologic', $options);
		break;

	case 'theme_credits':
		$options = get_option('semiologic');
		$options['theme_credits'] = false;
		update_option('semiologic', $options);
		break;

	case 'videocasting':
		plugin_deactivate('wp-flv.php');
		break;

	case 'wysiwyg_editor':
		plugin_deactivate('sem-wysiwyg/sem-wysiwyg.php');
		break;
	}
} # end theme_feature_deactivate()
?>