<?php

#
# Step 1
# ------
# Reassign permissions to administrator role
#

if ( $role = get_role('administrator') )
{
	$role->add_cap('edit_files', true);
	$role->add_cap('edit_themes', true);
	$role->add_cap('edit_plugins', true);
}



#
# Step 2
# ------
# Activate new plugins
#

if ( ( $active_plugins = get_option('active_plugins') ) === false )
{
	$active_plugins = array();
}

$extra_plugins = array(
	'ad-manager/ad-manager.php',
	'contact-form/contact-form.php',
	'feed-widgets/feed-widgets.php',
	'inline-widgets/inline-widgets.php',
	'link-widgets/link-widgets.php',
	'nav-menus/nav-menus.php',
	'script-manager/script-manager.php',
	'sem-docs/sem-docs.php',
	'sem-fixes/sem-fixes.php',
	'sem-seo/sem-seo.php',
	'sem-wizards/sem-wizards.php',
	'text-widgets/text-widgets.php',
	'version-checker/version-checker.php',
	'widget-contexts/widget-contexts.php',
	);

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

foreach ( $extra_plugins as $plugin )
{
	if ( file_exists(ABSPATH . PLUGINDIR . '/' . $plugin) )
	{
		include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
		do_action('activate_' . $plugin);
	}
}

$GLOBALS['plugin_page'] = $plugin_page_backup;


#
# Step 3
# ------
# Nav Menus
#

$sem_nav = get_option('sem5_nav');

unset($sem_nav['sidebar_nav']);

foreach ( $sem_nav as $nav_menu => $nav_items )
{
	switch ( $nav_menu )
	{
	case 'header_nav':
		$nav_menu = 'header';
		break;
	
	case 'footer_nav':
		$nav_menu = 'footer';
		break;
	}
	
	$items = array();
	
	foreach ( $nav_items as $label => $ref )
	{
		if ( $ref == get_option('home') )
		{
			$items[] = array(
				'type' => 'home',
				'label' => $label,
				);
			
			continue;
		}
		
		if ( strpos($ref, '/') !== false )
		{
			$items[] = array(
				'type' => 'url',
				'label' => $label,
				'ref' => $ref,
				);
			
			continue;
		}

		if ( !$ref )
		{
			$ref = sanitize_title($label);
		}
		else
		{
			$ref = sanitize_title($ref);
		}
		
		$page_id = $wpdb->get_var("
			SELECT	ID
			FROM	$wpdb->posts
			WHERE	post_name = '" . $wpdb->escape($ref) . "'
			");
		
		if ( $page_id )
		{
			$items[] = array(
				'type' => 'page',
				'label' => $label,
				'ref' => $page_id,
				);
			
			continue;
		}
		
		$cat_id = $wpdb->get_var("
			SELECT	terms.term_id
			FROM	$wpdb->terms as terms
			JOIN	$wpdb->term_taxonomy as term_taxonomy
			ON		term_taxonomy.term_id = terms.term_id
			AND		term_taxonomy.taxonomy = 'category'
			WHERE	parent = 0
			AND		term_slug = '" . $wpdb->escape($ref) . "'
			");
		
		if ( $cat_id )
		{
			$items[] = array(
				'type' => 'url',
				'label' => $label,
				'ref' => get_category_link($cat_id),
				);
			
			continue;
		}
	}
	
	$sem_nav_menus[$nav_menu]['items'] = $items;
}


#
# Step 4
# ------
# Options
#

$sem_options['active_skin'] = $sem_options['active_skin']['skin'];


#
# Step 5
# ------
# SEO
#

if ( class_exists('sem_seo') ) :

$sem_seo = array(
	'title' => (string) $sem_options['seo']['title'],
	'add_site_name' => (bool) $sem_options['seo']['add_site_name'],
	'archives' => (bool) $sem_options['theme_archives'],
	'category_dates' => true,
	'category_excerpts' => false,
	'tag_dates' => true,
	'tag_excerpts' => false,
	'enforce_www_preference' => false,
	'keywords' => (string) $sem_options['seo']['keywords'],
	'description' => (string) $sem_options['seo']['description']
	);

update_option('sem_seo', $sem_seo);

endif;


#
# Step 6
# ------
# Scripts
#

if ( class_exists('script_manager') ) :

$script_manager = array(
	'head' => (string) $sem_options['scripts']['head'],
	'footer' => (string) $sem_options['extra_footer'],
	'onload' => (string) $sem_options['scripts']['onload'],
	);

update_option('script_manager', $script_manager);

$wpdb->query("
	UPDATE	$wpdb->postmeta
	SET		meta_key = '_scripts_head'
	WHERE	meta_key = '_head'
	");

$wpdb->query("
	UPDATE	$wpdb->postmeta
	SET		meta_key = '_scripts_footer'
	WHERE	meta_key = '_footer'
	");

$wpdb->query("
	UPDATE	$wpdb->postmeta
	SET		meta_key = '_scripts_onload'
	WHERE	meta_key = '_onload'
	");

endif;


#
# Step 7
# ------
# Widgets
#

if ( ( $sidebars_widgets = get_option('sidebars_widgets') ) !== false ) :

foreach ( $sidebars_widgets as $sidebar => $widgets )
{
	if ( !is_array($widgets) ) continue;
	
	foreach ( $widgets as $k => $widget )
	{
		switch ( $widget )
		{
		case 'header-nav-menu':
			$sidebars_widgets[$sidebar][$k] = 'navbar';
			break;
		
		case 'entry-header':
			$sidebars_widgets[$sidebar][$k] = 'entry_header';
			break;

		case 'entry-content':
			$sidebars_widgets[$sidebar][$k] = 'entry_content';
			break;

		case 'entry-categories':
			$sidebars_widgets[$sidebar][$k] = 'entry_categories';
			break;

		case 'entry-tags':
			$sidebars_widgets[$sidebar][$k] = 'entry_tags';
			break;

		case 'entry-actions':
			$sidebars_widgets[$sidebar][$k] = 'entry_actions';
			break;

		case 'entry-comments':
			$sidebars_widgets[$sidebar][$k] = 'entry_comments';
			break;

		case 'footer-nav-menu':
			$sidebars_widgets[$sidebar][$k] = 'footer';
			break;
		
		case 'nextprev-posts':
			$sidebars_widgets[$sidebar][$k] = 'next_prev_posts';
			break;
		}
	}
}

$sidebars_widgets['before_the_entries'][] = 'archives_header';

update_option('sidebars_widgets', $sidebars_widgets);

endif;


#
# Step 7
# ------
# Widget Contexts
#

if ( class_exists('widget_contexts')
	&& ( $sem_widget_contexts = get_option('sem_widget_contexts') ) !== false
	) :

$widget_contexts = array();

foreach ( $sem_widget_contexts as $widget => $contexts )
{
	switch ( $widget )
	{
	case 'header-nav-menu':
		$widget = 'navbar';
		break;
	
	case 'entry-header':
		$widget = 'entry_header';
		break;

	case 'entry-content':
		$widget = 'entry_content';
		break;

	case 'entry-categories':
		$widget = 'entry_categories';
		break;

	case 'entry-tags':
		$widget = 'entry_tags';
		break;

	case 'entry-actions':
		$widget = 'entry_actions';
		break;

	case 'entry-comments':
		$widget = 'entry_comments';
		break;

	case 'footer-nav-menu':
		$widget = 'footer';
		break;
	
	case 'nextprev-posts':
		$widget = 'next_prev_posts';
		break;
	
	case 'silo-pages':
		$widget = 'silo_widgets-1';
		break;
	
	case 'dealdotcom':
		$widget = 'dealdotcom-1';
		break;
	
	case 'bookmark-me':
		$widget = 'bookmark_me-1';
		break;
	
	case 'subscribe-me':
		$widget = 'subscribe_me-1';
		break;
	
	case 'author-image':
		$widget = 'author_image-1';
		break;
	
	case 'newsletter':
		$widget = 'newsletter_widget-1';
		break;
	
	case 'links':
		if ( class_exists('links_widgets') )
		{
			$widget = 'link_widget-1';
		}
		break;
	}
	
	foreach ( $contexts as $context => $active )
	{
		switch ( $context )
		{
		case 'home':
			$widget_contexts[$widget]['home'] = $active;
			$widget_contexts[$widget]['blog'] = $active;
			break;
			
		case 'post':
			$widget_contexts[$widget]['post'] = $active;
			$widget_contexts[$widget]['attachment'] = $active;
			break;

		case 'page':
			$widget_contexts[$widget]['page'] = $active;
			break;

		case 'sell':
			$widget_contexts[$widget]['template_letter'] = $active;
			break;

		case 'special':
			$widget_contexts[$widget]['template_special'] = $active;
			break;

		case 'list':
			$widget_contexts[$widget]['template_archives'] = $active;
			$widget_contexts[$widget]['template_links'] = $active;
			break;

		case 'archive':
			$widget_contexts[$widget]['categories'] = $active;
			$widget_contexts[$widget]['tags'] = $active;
			$widget_contexts[$widget]['archive'] = $active;
			break;

		case 'search':
			$widget_contexts[$widget]['search'] = $active;
			$widget_contexts[$widget]['404_error'] = $active;
			break;
		}
	}
}

update_option('widget_contexts', $widget_contexts);

endif;


#
# Step 8
# ------
# Page Templates
#

foreach ( array(
	'sell.php' => 'letter.php',
	'raw.php' => 'special.php',
	'article.php' => 'default',
	'standalone.php' => 'default',
	) as $old => $new )
{
	$wpdb->query("
		UPDATE	$wpdb->postmeta
		SET		meta_value = '$new'
		WHERE	meta_key = '_wp_page_template'
		AND		meta_value = '$old'
		");
}


#
# Step 9
# ------
# API Key
#

update_option('sem_api_key', $sem_options['api_key']);


#
# Final Step
# ----------
# Clean Up
#

unset($sem_options['api_key']);
unset($sem_options['theme_archives']);
unset($sem_options['theme_credits']);
unset($sem_options['seo']);
unset($sem_options['scripts']);

include_once sem_path . '/inc/upgrade/options.php';


#
# Debug
#

#dump($sem_options);
#die;
?>