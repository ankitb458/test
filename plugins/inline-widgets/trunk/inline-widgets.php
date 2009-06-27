<?php
/*
Plugin Name: Inline Widgets
Plugin URI: http://www.semiologic.com/software/inline-widgets/
Description: Creates a special sidebar that lets you insert arbitrary widgets in posts' and pages' content. Configure these inline widgets under <a href="widgets.php">Appearance / Widgets</a>.
Version: 2.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: inline-widgets
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('inline-widgets', null, dirname(__FILE__) . '/lang');


/**
 * inline_widgets
 *
 * @package Inline Widgets
 **/

add_action('init', array('inline_widgets', 'init'), 1000);
add_shortcode('widget', array('inline_widgets', 'shortcode'));

if ( get_option('inline_widgets_version') === false )
	add_action('init', array('inline_widgets', 'upgrade'), 1000);

class inline_widgets {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		register_sidebar(
			array(
				'id' => 'inline_widgets',
				'name' => __('Inline Widgets (for use in entries)', 'inline-widgets'),
				'before_widget' => '<div>',
				'after_widget' => '</div>' . "\n",
				'before_title' => '<h3>',
				'after_title' => '</h3>' . "\n",
				)
			);
		
		if ( !is_active_sidebar('inline_widgets') )
			add_filter('sidebars_widgets', array('inline_widgets', 'sidebars_widgets'));
	} # init()
	
	
	/**
	 * sidebars_widgets()
	 *
	 * @param array $sidebars_widgets
	 * @return array $sidebars_widgets
	 **/

	function sidebars_widgets($sidebars_widgets) {
		global $wp_widget_factory;
		global $wp_registered_sidebars;
		
		$default_widgets = array(
			'inline_widgets' => array(
				'ad_unit',
				'silo_stub',
				'silo_map',
				'contact_form',
				'newsletter_manager',
				),
			);
		
		$registered_sidebars = array_keys($wp_registered_sidebars);
		$registered_sidebars = array_diff($registered_sidebars, array('wp_inactive_widgets'));
		foreach ( $registered_sidebars as $sidebar )
			$sidebars_widgets[$sidebar] = (array) $sidebars_widgets[$sidebar];
		$sidebars_widgets['wp_inactive_widgets'] = (array) $sidebars_widgets['wp_inactive_widgets'];
		
		foreach ( $default_widgets as $panel => $widgets ) {
			if ( empty($sidebars_widgets[$panel]) )
				$sidebars_widgets[$panel] = (array) $sidebars_widgets[$panel];
			else
				continue;
			
			foreach ( $widgets as $widget ) {
				if ( !is_a($wp_widget_factory->widgets[$widget], 'WP_Widget') )
					continue;
				
				$widget_ids = array_keys((array) $wp_widget_factory->widgets[$widget]->get_settings());
				$widget_id_base = $wp_widget_factory->widgets[$widget]->id_base;
				$new_widget_number = $widget_ids ? max($widget_ids) + 1 : 2;
				foreach ( $widget_ids as $key => $widget_id )
					$widget_ids[$key] = $widget_id_base . '-' . $widget_id;
				
				# check if active already
				foreach ( $widget_ids as $widget_id ) {
					if ( in_array($widget_id, $sidebars_widgets[$panel]) )
						continue 2;
				}

				# use an inactive widget if available
				foreach ( $widget_ids as $widget_id ) {
					foreach ( array_keys($sidebars_widgets) as $sidebar ) {
						$key = array_search($widget_id, $sidebars_widgets[$sidebar]);
						
						if ( $key === false )
							continue;
						elseif ( in_array($sidebar, $registered_sidebars) ) {
							continue 2;
						}
						
						unset($sidebars_widgets[$sidebar][$key]);
						$sidebars_widgets[$panel][] = $widget_id;
						continue 3;
					}
					
					$sidebars_widgets[$panel][] = $widget_id;
					continue 2;
				}
				
				# create a widget on the fly
				$new_settings = $wp_widget_factory->widgets[$widget]->get_settings();
				
				$new_settings[$new_widget_number] = array();
				$wp_widget_factory->widgets[$widget]->_set($new_widget_number);
				$wp_widget_factory->widgets[$widget]->_register_one($new_widget_number);
				
				$widget_id = "$widget_id_base-$new_widget_number";
				$sidebars_widgets[$panel][] = $widget_id;
				
				$wp_widget_factory->widgets[$widget]->save_settings($new_settings);
			}
		}
		
		$sidebars_widgets['wp_inactive_widgets'] = array_merge($sidebars_widgets['wp_inactive_widgets']);
		
		return $sidebars_widgets;
	} # sidebars_widgets()
	
	
	/**
	 * shortcode()
	 *
	 * @param array $args
	 * @return string $out
	 **/
	
	function shortcode($args) {
		global $wp_registered_widgets;
		$wp_registered_widgets = (array) $wp_registered_widgets;
		extract($args, EXTR_SKIP);
		
		if ( !isset($id) || !isset($wp_registered_widgets[$id])
			|| !is_callable($wp_registered_widgets[$id]['callback']) )
			return '';
		
		$args = array(
			'before_widget' => '<div class="' . esc_attr($wp_registered_widgets[$id]['classname']) . '">' . "\n",
			'after_widget' => '</div>' . "\n",
			'before_title' => '%BEG_OF_TITLE%',
			'after_title' => '%END_OF_TITLE%'
			);
		
		$params = array($args, (array) $wp_registered_widgets[$id]['params'][0]);
		
		ob_start();
		call_user_func_array($wp_registered_widgets[$id]['callback'], $params);
		$widget = ob_get_clean();
		
		$widget = preg_replace("/%BEG_OF_TITLE%(.*?)%END_OF_TITLE%/", '', $widget);
		
		return $widget;
	} # shortcode()
	
	
	/**
	 * upgrade()
	 *
	 * @return void
	 **/

	function upgrade() {
		global $wpdb;
		
		$posts = $wpdb->get_results("
			SELECT	*
			FROM	$wpdb->posts
			WHERE	post_content REGEXP '\\\\[widget:'
			");
		
		foreach ( $posts as $post ) {
			$post->post_content = preg_replace_callback("/
				\[widget:\s*(.+?)\s*\]
				/ix", array('inline_widgets', 'upgrade_callback'), $post->post_content);
			wp_update_post($post);
		}
		
		update_option('inline_widget_version', '2.0');
	} # upgrade()
	
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/

	function upgrade_callback($match) {
		$widget_id = trim(array_pop($match));
		if ( in_array($widget_id, array('democracy', 'countdown', 'silo_stub', 'silo_map')) ) {
			$widget_id = "$widget_id-2";
		} elseif ( preg_match("/^link_widget-(\d+)$/", $widget_id, $match) ) {
			$widget_id = 'links-' . array_pop($match);
		} elseif ( preg_match("/^(related|random|fuzzy)-widget-(\d+)$/", $widget_id, $match) ) {
			$num = array_pop($match);
			$id_base = array_pop($match);
			$widget_id =  $id_base . '_widget-' . $num;
		}
		
		return "[widget id=\"$widget_id\"/]";
	} # upgrade_callback()
} # inline_widgets

function inline_widgets_admin() {
	include dirname(__FILE__) . '/inline-widgets-admin.php';
}

foreach ( array(
	'page-new.php', 'page.php',
	'post-new.php', 'post.php',
	) as $hook )
	add_action("load-$hook", 'inline_widgets_admin');
?>