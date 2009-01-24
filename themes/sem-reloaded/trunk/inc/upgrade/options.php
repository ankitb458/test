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
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'gravatars\_%';");			# gravatars
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'hashcash\_%';");			# hashcash
	if ( get_option('fuzzy_widgets') )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'kjgrc\_%';");			# recent comments
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_clean\_%';");			# legacy caching
	if ( !in_array('sem-ad-space/sem-ad-space.php', $active_plugins) )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_ad\_space\_%';");
	if ( get_option('newsletter_manager_widgets') )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_newsletter\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_opt\_in_%';");
	if ( get_option('fuzzy_widgets') )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_recent\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_search\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_sidebar\_tile\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_tile\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_static\_front\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'sem\_theme\_%';");				# Semiologic v.2!
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wp\_cron\_%';");
	if ( !in_array('wpLicense/wpLicense.php', $active_plugins) )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wp\_cc\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wp\_hashcash\_%';");
	if ( !in_array('ylsy_permalink_redirect.php', $active_plugins) )
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'permalink\_redirect\_%';");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'eco\_%';");      #extended comment status 
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'SUP\_%';");      #smart update pinger
	
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
	delete_option('ozh_absolutecomments'); 
	delete_option('plugin_simple_tb_validation2'); 
	delete_option('plugin_simple_tb_validation2_log'); 
	delete_option('sm_cpages');
	
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

	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'core\_update';");				# Duplicate
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name REGEXP '^rss\_[0-9a-f]{32}';");	# clean up magpie
	
	# drop obsolete tables
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}WP_HASHCASH;");
	
	if ( !in_array('sem-ad-space/sem-ad-space.php', $active_plugins) )
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sem_ad_spaces;");
	
	# kill duplicate options
	if ( version_compare(mysql_get_server_info(), '4.1', '>=') )
	{
		$wpdb->query("
			DELETE FROM $wpdb->options
			WHERE option_id IN (
				SELECT	o2.option_id
				FROM	wp_options as o1
				JOIN	wp_options as o2
				ON		o1.option_name = o2.option_name
				AND		o2.option_id > o1.option_id
				)");
	}
} # clean_up_options()

add_action('shutdown', 'sem_clean_up_options');
?>