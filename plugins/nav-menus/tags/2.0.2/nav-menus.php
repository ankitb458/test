<?php
/*
Plugin Name: Nav Menus
Plugin URI: http://www.semiologic.com/software/nav-menus/
Description: WordPress widgets that let you create navigation menus.
Version: 2.0.2
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: nav-menus
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('nav-menus', false, dirname(plugin_basename(__FILE__)) . '/lang');

if ( !defined('widget_utils_textdomain') )
	define('widget_utils_textdomain', 'nav-menus');

if ( !defined('sem_widget_cache_debug') )
	define('sem_widget_cache_debug', false);


/**
 * nav_menu
 *
 * @package Nav Menus
 **/

class nav_menu extends WP_Widget {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_nav_menu') === false ) {
			foreach ( array(
				'nav_menus' => 'upgrade',
				'silo_options' => 'upgrade_1x',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * editor_init()
	 *
	 * @return void
	 **/

	function editor_init() {
		if ( !class_exists('widget_utils') )
			include dirname(__FILE__) . '/widget-utils/widget-utils.php';
		widget_utils::page_meta_boxes();
		add_action('page_widget_config_affected', array('nav_menu', 'widget_config_affected'));
	} # editor_init()
	
	
	/**
	 * widget_config_affected()
	 *
	 * @return void
	 **/

	function widget_config_affected() {
		echo '<li>'
			. __('Nav Menu Widgets', 'nav-menus')
			. '</li>' . "\n";
	} # widget_config_affected()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('nav_menu');
	} # widgets_init()
	
	
	/**
	 * admin_print_scripts()
	 *
	 * @return void
	 **/

	function admin_print_scripts() {
		$folder = plugin_dir_url(__FILE__) . 'js';
		wp_enqueue_script('nav-menus', $folder . '/admin.js', array('jquery-ui-sortable'),  '20090903', true);
		
		add_action('admin_footer', array('nav_menu', 'admin_footer'));
	} # admin_print_scripts()
	
	
	/**
	 * admin_print_styles()
	 *
	 * @return void
	 **/

	function admin_print_styles() {
		$folder = plugin_dir_url(__FILE__) . 'css';
		wp_enqueue_style('nav-menus', $folder . '/admin.css', null, '20090903');
	} # admin_print_styles()
	
	
	/**
	 * nav_menu()
	 *
	 * @return void
	 **/

	function nav_menu() {
		$widget_ops = array(
			'classname' => 'nav_menu',
			'description' => __('A navigation menu', 'nav-menus'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->init();
		$this->WP_Widget('nav_menu', __('Nav Menu', 'nav-menus'), $widget_ops, $control_ops);
	} # nav_menu()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, nav_menu::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. ( $title
					? ( $before_title . $title . $after_title )
					: ''
					)
				. $after_widget;
			return;
		}
		
		if ( is_page() ) {
			global $_wp_using_ext_object_cache;
			global $wp_the_query;
			$page_id = $wp_the_query->get_queried_object_id();
			$cache_id = "_$widget_id";
			if ( $_wp_using_ext_object_cache )
				$o = wp_cache_get($page_id, $widget_id);
			else
				$o = get_post_meta($page_id, $cache_id, true);
		} else {
			$cache_id = "$widget_id";
			if ( is_home() && !is_paged() ) {
				$context = 'home';
			} elseif ( !is_search() && !is_404() ) {
				$context = 'blog';
			} else {
				$context = 'search';
			}
			$cache = get_transient($cache_id);
			$o = isset($cache[$context]) ? $cache[$context] : false;
		}
		
		if ( !sem_widget_cache_debug && !is_preview() && $o ) {
			#dump('cache');
			echo $o;
			return;
		}
		
		nav_menu::cache_pages();
		
		if ( !$items ) {
			$items = nav_menu::default_items();
		}
		
		$title = apply_filters('widget_title', $title);
		
		$root_pages = wp_cache_get(0, 'page_children');
		
		ob_start();
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo '<ul>' . "\n";
		
		foreach ( $items as $item ) {
			switch ( $item['type'] ) {
			case 'home':
				nav_menu::display_home($item, $desc);
				break;
			case 'url':
				nav_menu::display_url($item);
				break;
			case 'page':
				if ( in_array($item['ref'], $root_pages) )
					nav_menu::display_page($item, $desc);
				break;
			}
		}
		
		echo '</ul>' . "\n";
		
		echo $after_widget;
		
		$o = ob_get_clean();
		
		if ( !is_preview() ) {
			if ( is_page() ) {
				if ( $_wp_using_ext_object_cache )
					wp_cache_set($page_id, $o, $widget_id);
				else
					update_post_meta($page_id, $cache_id, $o);
			} else {
				$cache[$context] = $o;
				set_transient($cache_id, $cache);
			}
		}
		
		echo $o;
	} # widget()
	
	
	/**
	 * display_home()
	 *
	 * @param array $item
	 * @param bool $desc
	 * @return void
	 **/

	function display_home($item, $desc = false) {
		if ( get_option('show_on_front') == 'page' && get_option('page_on_front') ) {
			$item['type'] = 'page';
			$item['ref'] = get_option('page_on_front');
			return nav_menu::display_page($item, $desc);
		}
		
		extract($item, EXTR_SKIP);
		if ( (string) $label === '' )
			$label = __('Home', 'nav-menus');
		$url = esc_url(user_trailingslashit(get_option('home')));
		
		$classes = array('nav_home');
		$link = $label;
		
		if ( !is_front_page() || is_front_page() && is_paged() )
			$link = '<a href="' . $url . '" title="' . esc_attr(get_option('blogname')) . '">'
				. $link
				. '</a>';
		if ( !is_search() && !is_404() && !is_page() )
			$link = '<span class="nav_active">' . $link . '</span>';
		
		echo '<li class="' . implode(' ', $classes) . '">'
			. $link;
		
		if ( $desc ) {
			$descr = get_option('blogdescription');
			if ( $descr )
				echo "\n\n" . wpautop(apply_filters('widget_text', $descr));
		}
		
		echo '</li>' . "\n";
	} # display_home()
	
	
	/**
	 * display_url()
	 *
	 * @param array $item
	 * @return void
	 **/

	function display_url($item) {
		extract($item, EXTR_SKIP);
		if ( (string) $label === '' )
			$label = __('Untitled', 'nav-menus');
		$url = esc_url($ref);
		if ( !$url || $url == 'http://' )
			return;
		
		if ( rtrim($url, '/') == rtrim(get_option('home'), '/') )
			return nav_menu::display_home($item);
		
		if ( !nav_menu::is_local_url($url) ) {
			$classes = array('nav_url');
		} else {
			$bits = parse_url($url);
			if ( !empty($bits['query']) )
				$classes = array('nav_leaf');
			elseif ( empty($bits['path']) || substr($bits['path'], -1) == '/' )
				$classes = array('nav_branch');
			elseif ( strpos(basename($bits['path']), '.') !== false )
				$classes = array('nav_leaf');
			else
				$classes = array('nav_branch');
		}
		
		$link = '<a href="' . $url . '" title="' . esc_attr($label) . '">'
			. $label
			. '</a>';
		
		echo '<li class="' . implode(' ', $classes) . '">'
			. $link
			. '</li>' . "\n";
	} # display_url()
	
	
	/**
	 * display_page()
	 *
	 * @param array $item
	 * @param bool $desc
	 * @return void
	 **/

	function display_page($item, $desc = false) {
		extract($item, EXTR_SKIP);
		$ref = (int) $ref;
		$page = get_page($ref);
		
		if ( !$page || $page->post_parent != 0 && get_post_meta($page->ID, '_widgets_exclude', true) )
			return;
		
		if ( is_page() ) {
			global $wp_the_query;
			$page_id = $wp_the_query->get_queried_object_id();
		} elseif ( get_option('show_on_front') == 'page' ) {
			$page_id = (int) get_option('page_for_posts');
		} else {
			$page_id = 0;
		}
		
		if ( (string) $label === '' )
			$label = get_post_meta($page->ID, '_widgets_label', true);
		if ( (string) $label === '' )
			$label = $page->post_title;
		if ( (string) $label === '' )
			$label = __('Untitled', 'nav-menus');
		
		$url = esc_url(apply_filters('the_permalink', get_permalink($page->ID)));
		
		$ancestors = $page_id ? wp_cache_get($page_id, 'page_ancestors') : array();
		$children = wp_cache_get($page->ID, 'page_children');
		
		$classes = array();
		$link = $label;
		
		if ( get_option('show_on_front') == 'page' && get_option('page_on_front') == $page->ID ) {
			$classes[] = 'nav_home';
			if ( !is_front_page() || is_front_page() && is_paged() )
				$link = '<a href="' . user_trailingslashit($url) . '" title="' . esc_attr($label) . '">'
					. $link
					. '</a>';
			if ( is_front_page() || in_array($page->ID, $ancestors) )
				$link = '<span class="nav_active">' . $link . '</span>';
		} elseif ( get_option('show_on_front') == 'page' && get_option('page_for_posts') == $page->ID ) {
			$classes[] = 'nav_blog';
			if ( !is_home() || is_home() && is_paged() )
				$link = '<a href="' . $url . '" title="' . esc_attr($label) . '">'
					. $link
					. '</a>';
			if ( !is_search() && !is_404() && ( !is_page() || in_array($page->ID, $ancestors) ) )
				$link = '<span class="nav_active">' . $link . '</span>';
		} else {
			if ( $children )
				$classes[] = 'nav_branch';
			else
				$classes[] = 'nav_leaf';
			
			if ( $page->ID != $page_id )
				$link = '<a href="' . $url . '" title="' . esc_attr($label) . '">'
					. $link
					. '</a>';
			
			$link_classes = array('nav_page-' . sanitize_html_class($page->post_name, $page->ID));
			if ( $page->ID == $page_id || in_array($page->ID, $ancestors) )
				$link_classes[] = 'nav_active';
			$link = '<span class="' . implode(' ', $link_classes) . '">' . $link . '</span>';
		}
		
		echo '<li class="' . implode(' ', $classes) . '">'
			. $link;
		
		if ( $desc ) {
			$descr = trim(get_post_meta($page->ID, '_widgets_desc', true));
			if ( $descr )
				echo "\n\n" . wpautop(apply_filters('widget_text', $descr));
		}
		
		if ( $children && ( $page->ID == $page_id || in_array($page->ID, $ancestors) ) ) {
			echo "\n"
				. '<ul>' . "\n";
			foreach ( $children as $child_id ) {
				$item = array(
					'type' => 'page',
					'ref' => $child_id,
					'desc' => $desc,
					);
				nav_menu::display_page($item, $desc);
			}
			echo '</ul>' . "\n";
		}
		
		echo '</li>' . "\n";
	} # display_page()
	
	
	/**
	 * cache_pages()
	 *
	 * @return void
	 **/

	function cache_pages() {
		if ( is_page() ) {
			global $wp_the_query;
			$page_id = (int) $wp_the_query->get_queried_object_id();
			$page = get_page($page_id);
		} else {
			$page_id = 0;
			$page = null;
		}
		
		if ( get_option('show_on_front') == 'page' ) {
			$front_page_id = (int) get_option('page_on_front');
			$front_page = get_page($front_page_id);
			$blog_page_id = (int) get_option('page_for_posts');
			$blog_page = $blog_page_id ? get_page($blog_page_id) : null;
		} else {
			$front_page_id = 0;
			$front_page = null;
			$blog_page_id = 0;
			$blog_page = null;
		}
		
		$ancestors = $page_id ? wp_cache_get($page_id, 'page_ancestors') : array();
		if ( $ancestors === false ) {
			$ancestors = array();
			while ( $page && $page->post_parent != 0 ) {
				$ancestors[] = (int) $page->post_parent;
				$page = get_page($page->post_parent);
			}
			$ancestors = array_reverse($ancestors);
			wp_cache_set($page_id, $ancestors, 'page_ancestors');
		}
		
		$front_page_ancestors = $front_page_id ? wp_cache_get($front_page_id, 'page_ancestors') : array();
		if ( $front_page_ancestors === false ) {
			$front_page_ancestors = array();
			while ( $front_page && $front_page->post_parent != 0 ) {
				$front_page_ancestors[] = (int) $front_page->post_parent;
				$front_page = get_page($front_page->post_parent);
			}
			$front_page_ancestors = array_reverse($front_page_ancestors);
			wp_cache_set($front_page_id, $front_page_ancestors, 'page_ancestors');
		}
		
		$blog_page_ancestors = $blog_page_id ? wp_cache_get($blog_page_id, 'page_ancestors') : array();
		if ( $blog_page_ancestors === false ) {
			$blog_page_ancestors = array();
			while ( $blog_page && $blog_page->post_parent != 0 ) {
				$blog_page_ancestors[] = (int) $blog_page->post_parent;
				$blog_page = get_page($blog_page->post_parent);
			}
			$blog_page_ancestors = array_reverse($blog_page_ancestors);
			wp_cache_set($blog_page_id, $blog_page_ancestors, 'page_ancestors');
		}
		
		$parent_ids = array_merge($ancestors, $front_page_ancestors, $blog_page_ancestors);
		array_unshift($parent_ids, 0);
		if ( $page_id )
			$parent_ids[] = $page_id;
		if ( $front_page_id )
			$parent_ids[] = $front_page_id;
		if ( $blog_page_id )
			$parent_ids[] = $blog_page_id;
		$parent_ids = array_map('intval', $parent_ids);
		$parent_ids = array_unique($parent_ids);
		sort($parent_ids);
		
		$cached = true;
		foreach ( $parent_ids as $parent_id ) {
			$children_ids = wp_cache_get($parent_id, 'page_children');
			$cached = is_array($children_ids);
			if ( $cached === false )
				break;
			foreach ( $children_ids as $children_id ) {
				$cached = is_array(wp_cache_get($children_id, 'page_children'));
				if ( $cached === false )
					break 2;
			}
		}
		
		if ( $cached )
			return;
		
		global $wpdb;
		
		$root_ids = array();
		if ( $page_id ) {
			$parent_page = get_post($page_id);
			while ( $parent_page->post_parent ) {
				$root_ids[] = $parent_page->post_parent;
				$parent_page = get_post($parent_page->post_parent);
			}
		}
		$root_ids = array_merge($root_ids, array(0, $page_id, $front_page_id, $blog_page_id));
		$root_ids = array_map('intval', $root_ids);
		$root_ids = array_unique($root_ids);
		sort($root_ids);
		
		$roots = (array) $wpdb->get_col("
			SELECT	posts.ID
			FROM	$wpdb->posts as posts
			WHERE	posts.post_type = 'page'
			AND		post_status <> 'trash'
			AND		posts.post_parent IN ( " . implode(',', $root_ids) . " )
			");
		
		$parent_ids = array_merge($parent_ids, $roots, array($page_id, $front_page_id, $blog_page_id));
		$parent_ids = array_map('intval', $parent_ids);
		$parent_ids = array_unique($parent_ids);
		sort($parent_ids);
		
		$pages = (array) $wpdb->get_results("
			SELECT	posts.*
			FROM	$wpdb->posts as posts
			WHERE	posts.post_type = 'page'
			AND		posts.post_status = 'publish'
			AND		posts.post_parent IN ( " . implode(',', $parent_ids) . " )
			ORDER BY posts.menu_order, posts.post_title
			");
		
		$children = array();
		$to_cache = array();
		
		foreach ( $parent_ids as $parent_id )
			$children[$parent_id] = array();
		
		foreach ( $pages as $page ) {
			$children[$page->post_parent][] = $page->ID;
			$to_cache[] = $page->ID;
		}
		
		$all_ancestors = array();
		
		foreach ( $children as $parent => $child_ids ) {
			foreach ( $child_ids as $key => $child_id )
				$all_ancestors[$child_id][] = $parent;
			wp_cache_set($parent, $child_ids, 'page_children');
		}
		
		foreach ( $all_ancestors as $child_id => $parent_ids ) {
			while ( $parent_ids[0] && is_array($all_ancestors[$parent_ids[0]]) )
				$parent_ids = array_merge($all_ancestors[$parent_ids[0]], $parent_ids);
			wp_cache_set($child_id, $parent_ids, 'page_ancestors');
		}
		
		foreach ( array_keys($pages) as $k ) {
			$ancestors = wp_cache_get($pages[$k]->ID, 'page_ancestors');
			array_shift($ancestors);
			$pages[$k]->ancestors = $ancestors;
		}
		
		update_post_cache($pages);
		update_postmeta_cache($to_cache);
	} # cache_pages()
	
	
	/**
	 * is_local_url()
	 *
	 * @param string $url
	 * @return bool $is_local_url
	 **/

	function is_local_url($url) {
		if ( in_array(substr($url, 0, 1), array('?', '#')) || strpos($url, '://') === false )
			return true;
		elseif ( preg_match("~/go(/|\.)~i", $url) )
			return false;
		
		static $site_domain;
		
		if ( !isset($site_domain) ) {
			$site_domain = get_option('home');
			$site_domain = parse_url($site_domain);
			$site_domain = $site_domain['host'];
			$site_domain = preg_replace("/^www\./i", '', $site_domain);
			
			# The following is not bullet proof, but it's good enough for a WP site
			if ( $site_domain != 'localhost' && !preg_match("/\d+(\.\d+){3}/", $site_domain) ) {
				if ( preg_match("/\.([^.]+)$/", $site_domain, $tld) ) {
					$tld = end($tld);
				} else {
					$site_domain = false;
					return false;
				}
				
				$site_domain = substr($site_domain, 0, strlen($site_domain) - 1 - strlen($tld));
				
				if ( preg_match("/\.([^.]+)$/", $site_domain, $subtld) ) {
					$subtld = end($subtld);
					if ( strlen($subtld) <= 4 ) {
						$site_domain = substr($site_domain, 0, strlen($site_domain) - 1 - strlen($subtld));
						$site_domain = explode('.', $site_domain);
						$site_domain = array_pop($site_domain);
						$site_domain .= ".$subtld";
					} else {
						$site_domain = $subtld;
					}
				}
				
				$site_domain .= ".$tld";
			}
			
			$site_domain = strtolower($site_domain);
		}
		
		if ( !$site_domain )
			return false;
		
		$link_domain = parse_url($url);
		$link_domain = $link_domain['host'];
		$link_domain = preg_replace("/^www\./i", '', $link_domain);
		$link_domain = strtolower($link_domain);
		
		if ( $site_domain == $link_domain ) {
			return true;
		} else {
			$site_elts = explode('.', $site_domain);
			$link_elts = explode('.', $link_domain);
			
			while ( ( $site_elt = array_pop($site_elts) ) && ( $link_elt = array_pop($link_elts) ) ) {
				if ( $site_elt !== $link_elt )
					return false;
			}
			
			return empty($link_elts) || empty($site_elts);
		}
	} # is_local_url()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = nav_menu::defaults();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['desc'] = isset($new_instance['desc']);
		foreach ( array_keys((array) $new_instance['items']['type']) as $key ) {
			$item = array();
			$item['type'] = $new_instance['items']['type'][$key];
			
			if ( !in_array($item['type'], array('home', 'url', 'page')) ) {
				continue;
			}
			
			$label = trim(strip_tags(stripslashes($new_instance['items']['label'][$key])));
			
			switch ( $item['type'] ) {
				case 'home':
					$item['label'] = $label;
					break;
				case 'url':
					$item['ref'] = trim(strip_tags(stripslashes($new_instance['items']['ref'][$key])));
					$item['label'] = $label;
					break;
				case 'page':
					$item['ref'] = intval($new_instance['items']['ref'][$key]);
					$page = get_post($item['ref']);
					if ( $page->post_title != $label ) {
						update_post_meta($item['ref'], '_widgets_label', $label);
					} else {
						delete_post_meta($item['ref'], '_widgets_label');
					}
					break;
			}
			
			$instance['items'][] = $item;
		}
		
		nav_menu::flush_cache();
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, nav_menu::defaults());
		$pages = wp_cache_get('nav_menu_roots', 'nav_menu_roots');
		
		if ( $pages === false ) {
			global $wpdb;
			$pages = $wpdb->get_results("
				SELECT	posts.*
				FROM	$wpdb->posts as posts
				WHERE	posts.post_type = 'page'
				AND		posts.post_status = 'publish'
				AND		posts.post_parent = 0
				ORDER BY posts.menu_order, posts.post_title
				");
			update_post_cache($pages);
			$to_cache = array();
			foreach ( $pages as $page )
				$to_cache[] = $page->ID;
			update_postmeta_cache($to_cache);
			wp_cache_set('nav_menu_roots', $pages, 'nav_menu_roots');
		}
		
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Config', 'nav-menus') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'nav-menus') . '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="' . $this->get_field_name('desc') . '"'
				. checked($desc, true, false)
				. ' />'
			. '&nbsp;'
			. __('Show Descriptions', 'nav-menus') . "\n"
			. '</label>'
			. '</p>' . "\n";
		
		echo '<div class="hide-if-no-js">' . "\n";
		
		echo '<h3>' . __('Menu Items', 'nav-menus') . '</h3>' . "\n";
		
		
		echo '<div class="nav_menu_items">' . "\n";
		
		echo '<input type="hidden" class="nav_menu_base"'
			. ' value="' . $this->get_field_name('items') . '" />' . "\n";
		
		
		echo '<div class="nav_menu_items_controller">' . "\n";
		
		echo '<select class="nav_menu_item_select">' . "\n"
			. '<option value="">'
				. esc_attr(__('- Select a menu item -', 'nav-menus'))
				. '</option>' . "\n"
			. '<optgroup label="' . esc_attr(__('Special', 'nav-menus')) . '">' . "\n"
			. '<option value="home" class="nav_menu_item_home">'
				. __('Home', 'nav-menus')
				. '</option>' . "\n"
			. '<option value="url" class="nav_menu_item_url">'
				. __('Url', 'nav-menus')
				. '</option>' . "\n"
			. '</optgroup>' . "\n"
			. '<optgroup class="nav_menu_item_pages"'
				. ' label="' . esc_attr(__('Pages', 'nav-menus')) . '"'
				. '>' . "\n"
			;
		
		foreach ( $pages as $page ) {
			$label = get_post_meta($page->ID, '_widgets_label', true);
			if ( $label === '' )
				$label = $page->post_title;
			if ( $label === '' )
				$label = __('Untitled', 'nav-menus');
			$label = strip_tags($label);
			echo '<option value="page-' . $page->ID . '">'
				. esc_attr($label)
				. '</option>' . "\n";
		}
		
		echo '</optgroup>' . "\n";
		
		echo '</select>';
		
		echo '&nbsp;';
		
		echo '<input type="button" class="nav_menu_item_add" value="&nbsp;+&nbsp;" />' . "\n";
		
		echo '</div>' . "\n"; # controller
		
		echo '<p>'
			. __('Drag and drop menu items to rearrange them.', 'nav-menus')
			. '</p>' . "\n";
		
		
		echo '<div class="nav_menu_item_sortables">' . "\n";
		
		foreach ( $items as $item ) {
			$label = $item['label'];
			$type = $item['type'];
			switch ( $type ) {
			case 'home':
				$ref = 'home';
				$url = user_trailingslashit(get_option('home'));
				$handle = 'home';
				break;
			case 'url':
				$ref = $item['ref'];
				$url = $ref;
				$handle = 'url';
				break;
			case 'page':
				$ref = $item['ref'];
				$page = get_post($ref);
				if ( !$page )
					continue 2;
				$url = apply_filters('the_permalink', get_permalink($ref));
				$handle = 'page-' . $ref;
				$label = get_post_meta($page->ID, '_widgets_label', true);
				if ( $label === '' )
					$label = $page->post_title;
				if ( $label === '' )
					$label = __('Untitled', 'nav-menus');
				break;
			}
			
			echo '<div class="nav_menu_item nav_menu_item-' . $handle . ' button">' . "\n"
				. '<div class="nav_menu_item_data">' ."\n"
				. '<input type="text" class="nav_menu_item_label"'
					. ' onchange="navMenus.onLabelChange(this);"'
					. ' name="' . $this->get_field_name('items') . '[label][]"'
					. ' value="' . esc_attr($label) . '"'
					. ' />' . "\n"
				. '&nbsp;'
				. '<input type="button" class="nav_menu_item_remove" value="&nbsp;-&nbsp;" />' . "\n"
					. '<input type="hidden"'
						. ' name="' . $this->get_field_name('items') . '[type][]"'
						. ' value="' . $type . '"'
						. ' />' . "\n"
				. '<input type="' . ( $handle == 'url' ? 'text' : 'hidden' ) . '"'
					. ' class="nav_menu_item_ref"'
					. ( $handle == 'url' ? ' onchange="navMenus.onRefChange(this);"' : '' )
					. ' name="' . $this->get_field_name('items') . '[ref][]"'
					. ' value="' . $ref . '"'
					. ' />' . "\n"
				. '</div>' . "\n" # data
				. '<div class="nav_menu_item_preview">' . "\n"
				. '&rarr;&nbsp;<a href="' . esc_url($url) . '"'
					. ' target="_blank">'
					. $label
					. '</a>'
				. '</div>' . "\n" # preview
				. '</div>' . "\n"; # item
		}
		
		if ( !$items ) {
			echo '<div class="nav_menu_item_blank">' . "\n"
				. '<p>' . __('Empty Navigation Menu. Leave it empty to populate it automatically.', 'nav-menus') . '</p>' . "\n"
				. '</div>' . "\n";
		} elseif ( defined('DOING_AJAX') ) {
			echo <<<EOS
<script type="text/javascript">
jQuery('div.nav_menu_item_sortables:has(.nav_menu_item)').sortable({});
</script>
EOS;
		}
		
		echo '</div>' . "\n"; # sortables
		
		echo '</div>' . "\n"; # items
		
		echo '</div>' . "\n"; # hide-if-no-js
	} # form()
	
	
	/**
	 * admin_footer()
	 *
	 * @return void
	 **/

	function admin_footer() {
		$pages = wp_cache_get('nav_menu_roots', 'nav_menu_roots');
		
		if ( $pages === false ) {
			global $wpdb;
			$pages = $wpdb->get_results("
				SELECT	posts.*
				FROM	$wpdb->posts as posts
				WHERE	posts.post_type = 'page'
				AND		posts.post_status = 'publish'
				AND		posts.post_parent = 0
				ORDER BY posts.menu_order, posts.post_title
				");
			update_post_cache($pages);
			$to_cache = array();
			foreach ( $pages as $page )
				$to_cache[] = $page->ID;
			update_postmeta_cache($to_cache);
			wp_cache_set('nav_menu_roots', $pages, 'nav_menu_roots');
		}
		
		echo '<div id="nav_menu_item_defaults" style="display: none;">' . "\n";
		
		echo '<div class="nav_menu_item_blank">' . "\n"
			. '<p>' . __('Empty Navigation Menu. Leave it empty to populate it automatically.', 'nav-menus') . '</p>' . "\n"
			. '</div>' . "\n";
		
		$default_items = array(
			array(
				'type' => 'home',
				'label' => __('Home', 'nav-menus'),
				),
			array(
				'type' => 'url',
				'ref' => 'http://',
				'label' => __('Url Label', 'nav-menus'),
				),
			);
		
		foreach ( $pages as $page ) {
			$label = get_post_meta($page->ID, '_widgets_label', true);
			if ( $label === '' )
				$label = $page->post_title;
			if ( $label === '' )
				$label = __('Untitled', 'nav-menus');
			$label = strip_tags($label);
			$default_items[] = array(
				'type' => 'page',
				'ref' => $page->ID,
				'label' => $label,
				);
		}
		
		foreach ( $default_items as $item ) {
			$label = $item['label'];
			$type = $item['type'];
			switch ( $type ) {
			case 'home':
				$ref = 'home';
				$url = user_trailingslashit(get_option('home'));
				$handle = 'home';
				break;
			case 'url':
				$ref = $item['ref'];
				$url = $ref;
				$handle = 'url';
				break;
			case 'page':
				$ref = $item['ref'];
				$url = apply_filters('the_permalink', get_permalink($ref));
				$handle = 'page-' . $ref;
				$page = get_post($ref);
				$label = get_post_meta($page->ID, '_widgets_label', true);
				if ( $label === '' )
					$label = $page->post_title;
				if ( $label === '' )
					$label = __('Untitled', 'nav-menus');
				$label = strip_tags($label);
				break;
			}
			
			echo '<div class="nav_menu_item nav_menu_item-' . $handle . ' button">' . "\n"
				. '<div class="nav_menu_item_data">' ."\n"
				. '<input type="text" class="nav_menu_item_label"'
					. ' onchange="navMenus.onLabelChange(this)"'
					. ' name="[label][]"'
					. ' value="' . esc_attr($label) . '"'
					. ' />' . "\n"
				. '&nbsp;'
				. '<input type="button" class="nav_menu_item_remove"'
					. ' value="&nbsp;-&nbsp;" />' . "\n"
				. '<input type="hidden"'
					. ' name="[type][]"'
					. ' value="' . $type . '"'
					. ' />' . "\n"
				. '<input type="' . ( $handle == 'url' ? 'text' : 'hidden' ) . '"'
					. ' class="nav_menu_item_ref"'
					. ( $handle == 'url' ? ' onchange="navMenus.onRefChange(this)"' : '' )
					. ' name="[ref][]"'
					. ' value="' . $ref . '"'
					. ' />' . "\n"
				. '</div>' . "\n" # data
				. '<div class="nav_menu_item_preview">' . "\n"
				. '&rarr;&nbsp;<a href="' . esc_url($url) . '"'
					. ' target="_blank">'
					. $label
					. '</a>'
				. '</div>' . "\n" # preview
				. '</div>' . "\n"; # item
		}
		
		echo '</div>' . "\n"; # defaults
	} # admin_footer()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance default options
	 **/

	function defaults() {
		return array(
			'title' => __('Browse', 'nav-menus'),
			'desc' => false,
			'items' => array(),
			);
	} # defaults()
	
	
	/**
	 * default_items()
	 *
	 * @return array $items
	 **/

	function default_items() {
		$items = array(array('type' => 'home'));
		
		$roots = wp_cache_get(0, 'page_children');
		
		if ( !$roots )
			return $items;
		
		$front_page_id = get_option('show_on_front') == 'page'
			? (int) get_option('page_on_front')
			: 0;

		foreach ( $roots as $root_id ) {
			if ( $root_id == $front_page_id )
				continue;
			if ( get_post_meta($root_id, '_widgets_exclude', true) )
				continue;
			
			$items[] = array(
				'type' => 'page',
				'ref' => $root_id,
				);
		}
		
		return $items;
	} # default_items()
	
	
	/**
	 * pre_flush_post()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function pre_flush_post($post_id) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return;
		
		$post = get_post($post_id);
		if ( !$post || $post->post_type != 'page' || wp_is_post_revision($post_id) )
			return;
		
		$old = wp_cache_get($post_id, 'pre_flush_post');
		if ( $old === false )
			$old = array();
		
		$update = false;
		foreach ( array(
			'post_title',
			'post_status',
			) as $field ) {
			if ( !isset($old[$field]) ) {
				$old[$field] = $post->$field;
				$update = true;
			}
		}
		
		if ( !isset($old['permalink']) ) {
			$old['permalink'] = apply_filters('the_permalink', get_permalink($post_id));
			$update = true;
		}
		
		foreach ( array(
			'widgets_label', 'widgets_desc',
			'widgets_exclude',
			) as $key ) {
			if ( !isset($old[$key]) ) {
				$old[$key] = get_post_meta($post_id, "_$key", true);
				$update = true;
			}
		}
		
		
		if ( $update )
			wp_cache_set($post_id, $old, 'pre_flush_post');
	} # pre_flush_post()
	
	
	/**
	 * flush_post()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function flush_post($post_id) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return;
		
		# prevent mass-flushing when the permalink structure hasn't changed
		remove_action('generate_rewrite_rules', array('nav_menu', 'flush_cache'));
		
		$post = get_post($post_id);
		if ( !$post || $post->post_type != 'page' || wp_is_post_revision($post_id) )
			return;
		
		$old = wp_cache_get($post_id, 'pre_flush_post');
		
		if ( $post->post_status != 'publish' && ( !$old || $old['post_status'] != 'publish' ) )
			return;
		
		if ( $old === false )
			return nav_menu::flush_cache();
		
		extract($old, EXTR_SKIP);
		foreach ( array_keys($old) as $key ) {
			switch ( $key ) {
			case 'widgets_label':
			case 'widgets_desc':
			case 'widgets_exclude':
				if ( $$key != get_post_meta($post_id, "_$key", true) )
					return nav_menu::flush_cache();
				break;
			
			case 'permalink':
				if ( $$key != apply_filters('the_permalink', get_permalink($post_id)) )
					return nav_menu::flush_cache();
				break;
			
			case 'post_title':
			case 'post_status':
				if ( $$key != $post->$key )
					return nav_menu::flush_cache();
				break;
			}
		}
	} # flush_post()
	
	
	/**
	 * flush_cache()
	 *
	 * @param mixed $in
	 * @return mixed $in
	 **/

	function flush_cache($in = null) {
		static $done = false;
		if ( $done )
			return $in;
		
		$done = true;
		$option_name = 'nav_menu';
		
		$widgets = get_option("widget_$option_name");
		
		if ( !$widgets )
			return $in;
		
		unset($widgets['_multiwidget']);
		unset($widgets['number']);
		
		if ( !$widgets )
			return $in;
		
		$cache_ids = array();
		
		global $_wp_using_ext_object_cache;
		foreach ( array_keys($widgets) as $widget_id ) {
			$cache_id = "$option_name-$widget_id";
			delete_transient($cache_id);
			delete_post_meta_by_key("_$cache_id");
			if ( $_wp_using_ext_object_cache )
				$cache_ids[] = $cache_id;
		}
		
		if ( $cache_ids ) {
			$page_ids = wp_cache_get('page_ids', 'widget_queries');
			if ( $page_ids === false ) {
				global $wpdb;
				$page_ids = $wpdb->get_col("
					SELECT	ID
					FROM	$wpdb->posts
					WHERE	post_type = 'page'
					AND		post_status <> 'trash'
					");
				wp_cache_set('page_ids', $page_ids, 'widget_queries');
			}
			foreach ( $cache_ids as $cache_id ) {
				foreach ( $page_ids as $page_id )
					wp_cache_delete($page_id, $cache_id);
			}
		}
		
		return $in;
	} # flush_cache()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		unset($ops['header']);
		unset($ops['footer']);
		
		foreach ( $ops as $k => $o ) {
			if ( isset($widget_contexts['nav_menu-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['nav_menu-' . $k];
			}
		}
		
		$extra = get_option('silo_widgets');
		
		if ( $extra !== false ) {
			foreach ( $extra as $k => $o ) {
				if ( !isset($ops[$k]) ) {
					$ops[$k] = $extra[$k];
					if ( isset($widget_contexts['silo_widget-' . $k]) ) {
						$ops[$k]['widget_contexts'] = $widget_contexts['silo_widget-' . $k];
					}
				} else {
					unset($extra[$k]);
				}
			}
		}
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			if ( !$GLOBALS['_wp_sidebars_widgets'] )
				$GLOBALS['_wp_sidebars_widgets'] = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets =& $GLOBALS['_wp_sidebars_widgets'];
		}
		
		$keys = array_keys($extra);
		
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( !is_array($widgets) )
				continue;
			foreach ( $keys as $k ) {
				$key = array_search("silo_widget-$k", $widgets);
				if ( $key !== false ) {
					$sidebars_widgets[$sidebar][$key] = 'nav_menu-' . $k;
					unset($keys[array_search($k, $keys)]);
					continue;
				}
			}
		}
		
		if ( is_admin() )
			update_option('sidebars_widgets', $sidebars_widgets);
		
		return $ops;
	} # upgrade()
	
	
	/**
	 * upgrade_1x()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade_1x($ops) {
		if ( is_array($ops['exclude']) ) {
			foreach ( $ops['exclude'] as $post_id )
				update_post_meta($post_id, '_widgets_exclude', '1');
		}
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			if ( !$GLOBALS['_wp_sidebars_widgets'] )
				$GLOBALS['_wp_sidebars_widgets'] = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets =& $GLOBALS['_wp_sidebars_widgets'];
		}
		
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( !is_array($widgets) )
				continue;
			$key = array_search('silo-pages', $widgets);
			if ( $key !== false ) {
				$sidebars_widgets[$sidebar][$key] = 'nav_menu-2';
				break;
			}
		}
		
		if ( is_admin() )
			update_option('sidebars_widgets', $sidebars_widgets);
		
		return $ops;
	} # upgrade_1x()
} # nav_menu

add_action('widgets_init', array('nav_menu', 'widgets_init'));
add_action('admin_print_scripts-widgets.php', array('nav_menu', 'admin_print_scripts'));
add_action('admin_print_styles-widgets.php', array('nav_menu', 'admin_print_styles'));

foreach ( array('page.php', 'page-new.php') as $hook )
	add_action('load-' . $hook, array('nav_menu', 'editor_init'));

foreach ( array(
		'switch_theme',
		'update_option_active_plugins',
		'update_option_show_on_front',
		'update_option_page_on_front',
		'update_option_page_for_posts',
		'update_option_sidebars_widgets',
		'update_option_sem5_options',
		'update_option_sem6_options',
		'generate_rewrite_rules',
		
		'flush_cache',
		'after_db_upgrade',
		) as $hook )
	add_action($hook, array('nav_menu', 'flush_cache'));

add_action('pre_post_update', array('nav_menu', 'pre_flush_post'));

foreach ( array(
		'save_post',
		'delete_post',
		) as $hook )
	add_action($hook, array('nav_menu', 'flush_post'), 1); // before _save_post_hook()

register_activation_hook(__FILE__, array('nav_menu', 'flush_cache'));
register_deactivation_hook(__FILE__, array('nav_menu', 'flush_cache'));

wp_cache_add_non_persistent_groups(array('nav_menu_roots', 'page_ancestors', 'page_children'));
wp_cache_add_non_persistent_groups(array('widget_queries', 'pre_flush_post'));
?>