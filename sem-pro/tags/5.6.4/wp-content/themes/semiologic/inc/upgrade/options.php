<?php
#
# Cleaning up obsolete options of all sorts
#

function sem_clean_up_options()
{
	global $wpdb;
	$active_plugins = get_option('active_plugins');

	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'bas%';");					# bad ass stats
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'botd%';");					# blog of the day
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'gravatars_%';");			# gravatars
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'hashcash_%';");			# hashcash
	if ( get_option('fuzzy_widgets') )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'kjgrc_%';");			# recent comments
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_clean_%';");			# legacy caching
	if ( !in_array('sem-ad-space/sem-ad-space.php', $active_plugins) )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_ad_space_%';");
	if ( get_option('newsletter_manager_widgets') )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_newsletter_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_opt_in_%';");
	if ( get_option('fuzzy_widgets') )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_recent_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_search_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_sidebar_tile_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_tile_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_static_front_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem_theme_%';");				# Semiologic v.2!
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wp_cron_%';");
	if ( !in_array('wpLicense/wpLicense.php', $active_plugins) )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wp_cc_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wp_hashcash_%';");
	if ( !in_array('ylsy_permalink_redirect.php', $active_plugins) )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'permalink_redirect_%';");
	
	delete_option('analytics_uastring');				# legacy analytics plugin
	delete_option('caplist');							# role manager
	delete_option('download_mgr');						# download manager
	delete_option('do_not_email');						# subscribe to comments
	delete_option('preformatted_version');				# an old caching plugin
	if ( get_option('bookmark_me_widgets') )
		delete_option('sem_bookmark_me_params');
	delete_option('sem_cache_created');					# legacy caching
	delete_option('sem_fancy_excerpt_params');
	if ( get_option('google_analytics') )
		delete_option('sem_google_analytics_params');
	if ( get_option('subscribe_me_widgets') )
		delete_option('sem_subscribe_me_params');
	if ( get_option('silo_widgets') )
		delete_option('silo_options');
	delete_option('speller_options');
	delete_option('xdash_settings');
	delete_option('yt_cache');
	
	delete_option('sem_widget_contexts');
	delete_option('sem5_nav');
	delete_option('semiologic');
	
	# clean up cache
	foreach ( glob(ABSPATH . "wp-content/cache/yt-*") as $cache_file )
	{
		@unlink($cache_file);
	}

	# fix wp schema upgrade bugs
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'fileupload_%';");
	delete_option('open_proxy_check');
	
	if ( strpos((string) get_option('links_recently_updated_prepend'), '&lt;') !== false )
	{
		update_option('links_recently_updated_prepend', '<em>');
		update_option('links_recently_updated_append', '</em>');
	}

	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'core_update';");				# Duplicate
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name REGEXP '^rss_[0-9a-f]{32}';");	# clean up magpie
	
	# drop obsolete tables
	$wpdb->query("DROP IF EXISTS TABLE {$wpdb->prefix}WP_HASHCASH;");
	$wpdb->query("DROP IF EXISTS TABLE {$wpdb->prefix}sem_ad_spaces;");
} # clean_up_options()

add_action('shutdown', 'sem_clean_up_options');
?>