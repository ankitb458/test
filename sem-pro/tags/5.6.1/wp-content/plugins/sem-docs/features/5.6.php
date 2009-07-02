<?php

# feature tree
sem_features::register(
	array(
		'seo' => array(
			'autotag',
			'enforce_permalinks',
			'mybloglog',
			'non_unique_slugs',
			'page_tags',
			'related_widgets',
			'robots_txt',
			'sem_seo',
			'silo_web_design',
			'social_bookmarking',
			'smart_pings',
			'smart_links',
			'subscribe_buttons',
			),
		'marketing' => array(
			'ad_manager',
			'book_library',
			'dealdotcom',
			'feedburner',
			'google_analytics',
			'google_sitemap',
			'newsletter_manager',
			'poll_manager',
			'redirect_manager',
			'script_manager',
			'semiologic_affiliate',
			'star_ratings',
			),
		'publishing' => array(
			'article_uploader',
			'author_image',
			'autolink_uri',
			'autotag',
			'book_library',
			'contact_form',
			'custom_query',
			'external_links',
			'inline_widgets',
			'opt_in_front',
			'podcasting',
			'smart_links',
			'social_bookmarking',
			'static_front',
			),
		'widgets' => array(
			'ad_manager',
			'archive_widgets',
			'contact_form',
			'dealdotcom',
			'event_manager',
			'feed_widgets',
			'flickr_widget',
			'fuzzy_widgets',
			'inline_widgets',
			'link_widgets',
			'mybloglog',
			'nav_menus',
			'newsletter_manager',
			'poll_manager',
			'random_widgets',
			'related_widgets',
			'silo_web_design',
			'social_bookmarking',
			'subscribe_buttons',
			'tag_cloud_widgets',
			'text_widgets',
			'widget_contexts',
			),
		'comments' => array(
			'absolute_comments',
			'akismet',
			'dofollow',
			'comment_status_manager',
			'hashcash',
			'moderate_subscribers',
			'no_self_pings',
			'tb_validator',
			'subscribe2comments',
			),
		'wp_tweaks' => array(
			'fancy_excerpt',
			'favicon',
			'feed_widgets',
			'frame_buster',
			'improved_search',
			'inline_widgets',
			'moderate_subscribers',
			'no_fancy_quotes',
			'no_self_pings',
			'non_unique_slugs',
			'sem_cache',
			'sem_fixes',
			),
		'admin_utils' => array(
			'admin_menu',
			'db_backup',
			'order_categories',
			'role_manager',
			'sem_docs',
			'sem_wizards',
			'tinymce_advanced',
			'version_checker',
			),
		)
	);


sem_features::set_handler(
	'absolute_comments',
	'ozh-absolute-comments/wp_ozh_absolutecomments.php'
	);
			
sem_features::set_handler(
	'ad_manager',
	'ad-manager/ad-manager.php'
	);

sem_features::set_handler(
	'admin_menu',
	'sem-admin-menu/sem-admin-menu.php'
	);

sem_features::set_handler(
	'akismet',
	'akismet/akismet.php'
	);

sem_features::set_handler(
	'archive_widgets',
	'archive-widgets/archive-widgets.php'
	);
	
sem_features::set_handler(
	'article_uploader',
	'article-uploader/article-uploader.php'
	);

sem_features::set_handler(
	'author_image',
	'sem-author-image/sem-author-image.php'
	);

sem_features::set_handler(
	'autolink_uri',
	'sem-autolink-uri/sem-autolink-uri.php'
	);

sem_features::set_handler(
	'autotag',
	'autotag/autotag.php'
	);

sem_features::set_handler(
	'book_library',
	'now-reading/now-reading.php'
	);
	
sem_features::set_handler(
	'comment_status_manager',
	'extended-comment-options/commentcontrol.php'
	);

sem_features::set_handler(
	'contact_form',
	'contact-form/contact-form.php'
	);

sem_features::set_handler(
	'custom_query',
	'custom-query-string.php'
	);

sem_features::set_handler(
	'db_backup',
	'wp-db-backup/wp-db-backup.php'
	);

sem_features::set_handler(
	'dealdotcom',
	'dealdotcom/dealdotcom.php'
	);

sem_features::set_handler(
	'dofollow',
	'sem-dofollow/sem-dofollow.php'
	);

sem_features::set_handler(
	'event_manager',
	'countdown/countdown.php'
	);

sem_features::set_handler(
	'external_links',
	'sem-external-links/sem-external-links.php'
	);

sem_features::set_handler(
	'fancy_excerpt',
	'sem-fancy-excerpt/sem-fancy-excerpt.php'
	);

sem_features::set_handler(
	'favicon',
	'favicon-head.php'
	);

sem_features::set_handler(
	'feedburner',
	'feedburner/feedburner.php'
	);

sem_features::set_handler(
	'feed_widgets',
	'feed-widgets/feed-widgets.php'
	);

sem_features::set_handler(
	'flickr_widget',
	'flickr_widget.php'
	);

sem_features::set_handler(
	'frame_buster',
	'sem-frame-buster/sem-frame-buster.php'
	);

sem_features::set_handler(
	'fuzzy_widgets',
	'fuzzy-widgets/fuzzy-widgets.php'
	);

sem_features::set_handler(
	'google_analytics',
	'google-analytics/google-analytics.php'
	);
	
sem_features::set_handler(
	'google_sitemap',
	'sitemap.php'
	);

sem_features::set_handler(
	'hashcash',
	'wp-hashcash/wp-hashcash.php'
	);

sem_features::set_handler(
	'improved_search',
	'search-reloaded/search-reloaded.php'
	);

sem_features::set_handler(
	'inline_widgets',
	'inline-widgets/inline-widgets.php'
	);

sem_features::set_handler(
	'link_widgets',
	'link-widgets/link-widgets.php'
	);

sem_features::set_handler(
	'moderate_subscribers',
	'sdm_moderate_authors.php'
	);

sem_features::set_handler(
	'mybloglog',
	'mybloglog-recent-reader-widget/mybloglog-reader_roll.php'
	);

sem_features::set_handler(
	'nav_menus',
	'nav-menus/nav-menus.php'
	);

sem_features::set_handler(
	'newsletter_manager',
	'newsletter-manager/newsletter-manager.php'
	);

sem_features::set_handler(
	'no_fancy_quotes',
	'sem-unfancy-quote/sem-unfancy-quote.php'
	);

sem_features::set_handler(
	'no_self_pings',
	'no-self-pings.php'
	);

sem_features::set_handler(
	'non_unique_slugs',
	'singular.php'
	);

sem_features::set_handler(
	'opt_in_front',
	'sem-opt-in-front/sem-opt-in-front.php'
	);

sem_features::set_handler(
	'order_categories',
	'order-categories/category-order.php'
	);

sem_features::set_handler(
	'page_tags',
	'page-tags/page-tags.php'
	);
	
sem_features::set_handler(
	'podcasting',
	'mediacaster/mediacaster.php'
	);

sem_features::set_handler(
	'poll_manager',
	'democracy/democracy.php'
	);

sem_features::set_handler(
	'random_widgets',
	'random-widgets/random-widgets.php'
	);

sem_features::set_handler(
	'redirect_manager',
	'redirect-manager/redirect-manager.php'
	);

sem_features::set_handler(
	'related_widgets',
	'related-widgets/related-widgets.php'
	);

sem_features::set_handler(
	'robots_txt',
	'pc-robots-txt/pc-robots-txt.php'
	);
	
sem_features::set_handler(
	'role_manager',
	'role-manager/role-manager.php'
	);

sem_features::set_handler(
	'script_manager',
	'script-manager/script-manager.php'
	);


sem_features::set_handler(
	'sem_cache',
	'sem-cache/sem-cache.php'
	);
	
sem_features::set_handler(
	'sem_fixes',
	'sem-fixes/sem-fixes.php'
	);

sem_features::set_handler(
	'sem_seo',
	'sem-seo/sem-seo.php'
	);

sem_features::set_handler(
	'sem_wizards',
	'sem-wizards/sem-wizards.php'
	);

sem_features::set_handler(
	'semiologic_affiliate',
	'sem-semiologic-affiliate/sem-semiologic-affiliate.php'
	);

sem_features::set_handler(
	'silo_web_design',
	'silo/silo.php'
	);

sem_features::set_handler(
	'smart_links',
	'smart-links/smart-links.php'
	);

sem_features::set_handler(
	'smart_pings',
	'smart-update-pinger.php'
	);
	
sem_features::set_handler(
	'social_bookmarking',
	'sem-bookmark-me/sem-bookmark-me.php'
	);

sem_features::set_handler(
	'social_poster',
	'social-poster/mm_post.php'
	);

sem_features::set_handler(
	'star_ratings',
	'star-rating/star-rating.php'
	);

sem_features::set_handler(
	'subscribe_buttons',
	'sem-subscribe-me/sem-subscribe-me.php'
	);

sem_features::set_handler(
	'subscribe2comments',
	'subscribe-to-comments/subscribe-to-comments.php'
	);

sem_features::set_handler(
	'tag_cloud_widgets',
	'tag-cloud-widgets/tag-cloud.php'
	);
	
sem_features::set_handler(
	'tb_validator',
	'simple-trackback-validation.php'
	);

sem_features::set_handler(
	'text_widgets',
	'text-widgets/text-widgets.php'
	);

sem_features::set_handler(
	'tinymce_advanced',
	'tinymce-advanced/tinymce-advanced.php'
	);
	
sem_features::set_handler(
	'version_checker',
	'version-checker/version-checker.php'
	);

sem_features::set_handler(
	'widget_contexts',
	'widget-contexts/widget-contexts.php'
	);


# lock built-in features

sem_features::lock('enforce_permalinks');
sem_features::lock('static_front');
sem_features::lock('sem_docs');
sem_features::lock('version_checker');

if ( defined('sem_fixes_path') ) sem_features::lock('sem_fixes');
?>