<?php
global $wpdb;


#
# Step 1
# ------
# Reassign permissions to administrator role
#

$role = get_role('administrator');

$role->add_cap('edit_files', true);
$role->add_cap('edit_themes', true);
$role->add_cap('edit_plugins', true);


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
	'nav-menus/nav-menus.php',
	'script-manager/script-manager.php',
	'sem-fixes/sem-fixes.php',
	'sem-seo/sem-seo.php',
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
# Fetch old options
#


$old_options = get_option('semiologic');

#dump($old_options);


#
# Step 3
# ------
# Captions and Options
#

foreach ( array_keys($old_options) as $key )
{
	switch ( $key )
	{
	case 'nav_menu_cache':
	case 'nav_menus':
	case 'scripts':
	case 'footer_extra':
	case 'seo':
		break;
	case 'captions':
		foreach ( $old_options[$key] as $k => $v )
		{
			if ( $k == 'filed_under' ) continue;

			if ( isset($sem_captions[$k]) )
			{
				$sem_captions[$k] = $v;
			}
		}
		break;
	case 'active_skin':
		$sem_options[$key] = $old_options[$key]['skin'];
		break;
	default:
		$sem_options[$key] = $old_options[$key];
		break;
	}
}

if ( !defined('sem_install_test') )
{
	update_option('sem6_options', $sem_options);
}


#
# Step 4
# ------
# Nav menus
#

$sem_nav = $old_options['nav_menus'];

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
		if ( untrailingslashit($ref) == untrailingslashit(get_option('home')) )
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
		
		if ( strpos($ref, '@') !== false )
		{
			$items[] = array(
				'type' => 'url',
				'label' => $label,
				'ref' => 'mailto:' . $ref,
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
		
		# try pre-WP 2.3 cat syntax if failed
		$cat_id = $wpdb->get_var("
			SELECT	cat_ID
			FROM	{$wpdb->prefix}categories
			WHERE	cat_name = '" . $wpdb->escape($ref) . "'
			AND		category_parent = 0
			");
		
		if ( $cat_id )
		{
			if ( $ref == 'blog' )
			{
				$items[] = array(
					'type' => 'home',
					'label' => $label,
					);
			}
			else
			{
				$items[] = array(
					'type' => 'url',
					'label' => $label,
					'ref' => user_trailingslashit(trailingslashit(get_category_link($cat_id)) . $ref),
					);
			}
			
			
			continue;
		}
	}
	
	$sem_nav_menus[$nav_menu]['items'] = $items;
}

update_option('sem_nav_menus', $sem_nav_menus);


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
	'category_excerpts' => true,
	'tag_dates' => true,
	'tag_excerpts' => false,
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
# Step 8
# ------
# Rename sidebars
#

$sidebars_widgets = get_option('sidebars_widgets');

switch ( $sem_options['active_layout'] )
{
case 'em':
case 'me':
	$sidebars_widgets['ext_sidebar'] = $sidebars_widgets['sidebar-1'];
	unset($sidebars_widgets['sidebar-1']);
	break;
	
case 'esm':
case 'ems':
	$sidebars_widgets['ext_sidebar'] = $sidebars_widgets['sidebar-1'];
	$sidebars_widgets['sidebar-1'] = $sidebars_widgets['sidebar-2'];
	unset($sidebars_widgets['sidebar-2']);
	break;

case 'sme':
case 'mse':
	$sidebars_widgets['ext_sidebar'] = $sidebars_widgets['sidebar-2'];
	unset($sidebars_widgets['sidebar-2']);
	break;
	
case 'essm':
case 'esms':
case 'emss':
	$sidebars_widgets['ext_sidebar'] = $sidebars_widgets['sidebar-1'];
	$sidebars_widgets['sidebar-1'] = $sidebars_widgets['sidebar-2'];
	$sidebars_widgets['sidebar-2'] = $sidebars_widgets['sidebar-3'];
	unset($sidebars_widgets['sidebar-3']);
	break;

case 'ssme':
case 'smse':
case 'msse':
	$sidebars_widgets['ext_sidebar'] = $sidebars_widgets['sidebar-3'];
	unset($sidebars_widgets['sidebar-3']);
	break;
}

update_option('sidebars_widgets', $sidebars_widgets);


#
# Step 10
# -------
# Autofill panels
#

add_action('init', array('sem_panels', 'autofill'));


#
# Step 11
# -------
# Fix important WP widgets
#

foreach ( $sidebars_widgets as $sidebar => $widgets )
{
	if ( !is_array($widgets) ) continue;
	
	foreach ( $widgets as $key => $widget )
	{
		if ( strpos($widget, 'Text ') === 0
			|| strpos($widget, 'RSS ') === 0
			|| strpos($widget, 'Pages') === 0
			|| strpos($widget, 'Recent Posts') === 0
			)
		{
			$sidebars_widgets[$sidebar][$key] = sanitize_title($widget);
		}
	}
}

update_option('sidebars_widgets', $sidebars_widgets);


#
# Final Step
# ----------
# Clean Up
#

include_once sem_path . '/inc/upgrade/options.php';
?>