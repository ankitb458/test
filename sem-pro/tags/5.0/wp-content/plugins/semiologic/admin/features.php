<?php

class sem_pro_features
{
	#
	# init()
	#

	function init()
	{
		add_action('_admin_menu', array('sem_pro_features', 'update'));
	} # init()


	#
	# is_enabled()
	#

	function is_enabled($key)
	{
		global $sem_features;

		if ( !is_null($sem_features[$key]['is_enabled']) )
		{
			return call_user_func($sem_features[$key]['is_enabled']);
		}
		else
		{
			$active_plugins = get_option('active_plugins');

			foreach ( (array) $sem_features[$key]['plugins'] as $plugin )
			{
				if ( in_array($plugin, $active_plugins) )
				{
					return true;
				}
			}

			return (bool) $sem_features[$key]['locked'];
		}
	} # is_enabled()


	#
	# register()
	#

	function register($key, $plugins = null, $activate = null, $deactivate = null, $is_enabled = null)
	{
		global $sem_features;

		$feature =& $sem_features[$key];

		$feature['plugins'] = (array) $plugins;
		$feature['activate'] = $activate;
		$feature['deactivate'] = $deactivate;
		$feature['is_enabled'] = $is_enabled;

		$sem_features[$key] =& $feature;
	} # register()


	#
	# update()
	#

	function update()
	{
		if ( isset($_POST['update_theme_features']) )
		{
			check_admin_referer('sem_features');

			global $sem_features;

			$active_plugins = get_option('active_plugins');

			$all_features = array_keys($sem_features);
			$activate = array_keys((array) $_POST['sem_features']);
			$deactivate = array_diff($all_features, $activate);

			#echo '<pre>';
			#sort($all_features);
			#sort($deactivate);
			#sort($activate);

			#var_dump($all_features);
			#var_dump($deactivate);
			#var_dump($activate);
			#echo '</pre>';


			foreach ( $deactivate as $key )
			{
				foreach ( (array) $sem_features[$key]['plugins'] as $plugin )
				{
					$plugin_key = array_search($plugin, $active_plugins);

					if ( $plugin_key !== false )
					{
						unset($active_plugins[$plugin_key]);
					}
				}

				if ( !is_null($sem_features[$key]['deactivate']) )
				{
					call_user_func($sem_features[$key]['deactivate']);
				}
			}

			$plugin_page = $GLOBALS['plugin_page'];

			unset($GLOBALS['plugin_page']);

			foreach ( $activate as $key )
			{
				foreach ( (array) $sem_features[$key]['plugins'] as $plugin )
				{
					if ( !in_array($plugin, $active_plugins)
						&& file_exists(ABSPATH . 'wp-content/plugins/' . $plugin)
						)
					{
						include_once ABSPATH . 'wp-content/plugins/' . $plugin;
					}
				}

				if ( !is_null($sem_features[$key]['activate']) )
				{
					call_user_func($sem_features[$key]['activate']);
				}

				foreach ( (array) $sem_features[$key]['plugins'] as $plugin )
				{
					if ( !in_array($plugin, $active_plugins)
						&& file_exists(ABSPATH . 'wp-content/plugins/' . $plugin)
						)
					{
						$active_plugins[] = $plugin;

						do_action('active_plugin' . $plugin);
					}
				}
			}

			$active_plugins = array_unique($active_plugins);
			ksort($active_plugins);
			update_option('active_plugins', $active_plugins);

			$GLOBALS['plugin_page'] = $plugin_page;
		}
	} # update()
} # sem_pro_features

sem_pro_features::init();


sem_pro_features::register(
	'absolute_urls',
	'url-absolutifier.php'
	);

sem_pro_features::register(
	'ad_manager',
	'ozh-who-sees-ads/wp_ozh_whoseesads.php',
	create_function('', '
		remove_action("admin_menu", "wp_ozh_wsa_addmenu");
		add_action("admin_menu", array("sem_ads", "admin_menu"));
		$GLOBALS["wp_ozh_wsa"] = $wp_ozh_wsa;
		'),
	create_function('', '
		remove_action("admin_menu", array("sem_ads", "admin_menu"));
		')
	);

sem_pro_features::register(
	'admin_menu',
	'sem-admin-menu/sem-admin-menu.php'
	);

sem_pro_features::register(
	'advanced_cache',
	'wp-cache/wp-cache.php'
	);

sem_pro_features::register(
	'akismet',
	'akismet/akismet.php',
	null,
	create_function('', "remove_action('admin_footer', 'akismet_warning');")
	);

sem_pro_features::register(
	'author_image',
	'sem-author-image/sem-author-image.php'
	);

sem_pro_features::register(
	'autolink_uri',
	'sem-autolink-uri/sem-autolink-uri.php'
	);

sem_pro_features::register(
	'autotag',
	'autotag/autotag.php'
	);

sem_pro_features::register(
	'book_library',
	'now-reading/now-reading.php'
	);

sem_pro_features::register(
	'comment_status_manager',
	'commentcontrol.php'
	);

sem_pro_features::register(
	'contact_form',
	'wp-contact-form/wp-contactform.php'
	);

sem_pro_features::register(
	'custom_query',
	'custom-query-string.php'
	);

sem_pro_features::register(
	'db_backup',
	'wp-db-backup/wp-db-backup.php'
	);

sem_pro_features::register(
	'dealdotcom',
	'dealdotcom/dealdotcom.php'
	);

sem_pro_features::register(
	'do_follow',
	'sem-dofollow/sem-dofollow.php'
	);

sem_pro_features::register(
	'event_manager',
	'countdown/countdown.php'
	);

sem_pro_features::register(
	'external_links',
	'sem-external-links/sem-external-links.php'
	);

sem_pro_features::register(
	'fancy_excerpt',
	'sem-fancy-excerpt/sem-fancy-excerpt.php'
	);

sem_pro_features::register(
	'favicon',
	'favicon-head.php'
	);

sem_pro_features::register(
	'feedburner',
	'feedburner/feedburner.php'
	);

sem_pro_features::register(
	'flickr_widget',
	'flickr_widget.php'
	);

sem_pro_features::register(
	'frame_buster',
	'sem-frame-buster/sem-frame-buster.php'
	);

sem_pro_features::register(
	'full_text_feed',
	'full_feed.php'
	);

sem_pro_features::register(
	'fuzzy_widgets',
	'fuzzy-widgets/fuzzy-widgets.php'
	);

sem_pro_features::register(
	'google_analytics',
	'sem-google-analytics/sem-google-analytics.php'
	);

sem_pro_features::register(
	'google_sitemap',
	'sitemap.php'
	);

sem_pro_features::register(
	'hashcash',
	'wp-hashcash/wp-hashcash.php'
	);

sem_pro_features::register(
	'hitslink',
	'hitslink/hitslink.php'
	);

sem_pro_features::register(
	'improved_search',
	'sem-search-reloaded/sem-search-reloaded.php'
	);

sem_pro_features::register(
	'moderate_subscribers',
	'sdm_moderate_authors.php'
	);

sem_pro_features::register(
	'mybloglog',
	'mybloglog_wp_2.php'
	);

sem_pro_features::register(
	'newsletter_manager',
	'sem-newsletter-manager/sem-newsletter-manager.php'
	);

sem_pro_features::register(
	'no_fancy_quotes',
	'sem-unfancy-quote/sem-unfancy-quote.php'
	);

sem_pro_features::register(
	'no_self_pings',
	'no-self-pings.php'
	);

sem_pro_features::register(
	'non_unique_slugs',
	'singular.php'
	);

sem_pro_features::register(
	'opt_in_front',
	'sem-opt-in-front/sem-opt-in-front.php'
	);

sem_pro_features::register(
	'paypal_widget',
	'wppaypal.php'
	);

sem_pro_features::register(
	'podcasting',
	'mediacaster/mediacaster.php'
	);

sem_pro_features::register(
	'poll_manager',
	'democracy/democracy.php'
	);

sem_pro_features::register(
	'random_widgets',
	'random-widgets/random-widgets.php'
	);

sem_pro_features::register(
	'related_widgets',
	'related-widgets/related-widgets.php'
	);

sem_pro_features::register(
	'role_manager',
	'role-manager/role-manager.php'
	);

sem_pro_features::register(
	'semiologic_affiliate',
	'sem-semiologic-affiliate/sem-semiologic-affiliate.php'
	);

sem_pro_features::register(
	'silo_web_design',
	'silo/silo.php'
	);

sem_pro_features::register(
	'smart_links',
	'sem-smart-link/sem-smart-link.php'
	);

sem_pro_features::register(
	'social_bookmarking',
	'sem-bookmark-me/sem-bookmark-me.php'
	);

sem_pro_features::register(
	'social_poster',
	'social-poster/mm_post.php'
	);

sem_pro_features::register(
	'star_ratings',
	'star-rating/star-rating.php'
	);

sem_pro_features::register(
	'static_front',
	'sem-static-front/sem-static-front.php'
	);

sem_pro_features::register(
	'subscribe_buttons',
	'sem-subscribe-me/sem-subscribe-me.php'
	);

sem_pro_features::register(
	'subscribe2comments',
	'subscribe-to-comments/subscribe-to-comments.php'
	);

sem_pro_features::register(
	'tag_manager',
	'simple-tags/simple-tags.php'
	);

sem_pro_features::register(
	'tb_validator',
	'simple-trackback-validation.php'
	);

sem_pro_features::register(
	'version_checker',
	'version-checker/version-checker.php'
	);

sem_pro_features::register(
	'wysiwyg_editor',
	'sem-wysiwyg/sem-wysiwyg.php'
	);
?>