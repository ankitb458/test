<?php
/*
Plugin Name: Admin Menu
Plugin URI: http://www.semiologic.com/software/admin-menu/
Description: Adds a convenient admin menu to your blog. Configure its visibility under <a href="options-general.php?page=admin-menu">Settings / Admin Menu</a>.
Version: 5.2 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-admin-menu-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('sem-admin-menu', null, dirname(__FILE__) . '/lang');


/**
 * sem_admin_menu
 *
 * @package Admin Menu
 **/

if ( !is_admin() ) {
	add_action('wp_print_styles', array('sem_admin_menu', 'add_css'));
	add_action('wp_footer', array('sem_admin_menu', 'display_menu'));

	add_filter('body_class', array('sem_admin_menu', 'body_class'));
} else {
	add_action('admin_menu', array('sem_admin_menu', 'admin_menu'));
}

class sem_admin_menu {
	/**
	 * add_css
	 *
	 * @return void
	 **/

	function add_css() {
		$folder = plugin_dir_url(__FILE__);
		$css = $folder . 'css/sem-admin-menu.css';
		
		wp_enqueue_style('sem_admin_menu', $css, null, '5.2');
	} # add_css()
	
	
	/**
	 * body_class()
	 *
	 * @param array $classes
	 * @return array $classes
	 **/

	function body_class($classes) {
		if ( isset($_GET['action']) && $_GET['action'] == 'print' )
			return $classes;
		
		$user = wp_get_current_user();
		$options = sem_admin_menu::get_options();
		
		if ( !$user->ID && !$options['always_on'] )
			return $classes;
		
		$classes[] = 'sem_admin_menu';
		
		return $classes;
	} # body_class()


	/**
	 * display_menu()
	 *
	 * @return void
	 **/

	function display_menu() {
		if ( isset($_GET['action']) && $_GET['action'] == 'print' )
			return;
		
		$user = wp_get_current_user();

		$admin_url = trailingslashit(admin_url());

		$options = sem_admin_menu::get_options();

		if ( !$user->ID && $options['always_on'] ) {
			echo '<div id="am">' . "\n"
				. '<div>' . "\n";
			
			if ( get_option('users_can_register') ) {
				echo '<span class="am_user">'
							. apply_filters('loginout',
								'<a href="' . wp_login_url() . '?action=register">'
									. __('Register', 'sem-admin-menu')
									. "</a>"
								)
							. "</span>"
							. ' ';
			}

			echo '<span class="am_user">'
					. apply_filters('loginout',
						'<a href="' . wp_login_url() . '">'
							. __('Login', 'sem-admin-menu')
							. "</a>"
						)
					. "</span>";
			
			echo '</div>' . "\n"
				. '</div>' . "\n";
		} elseif ( $user->ID ) {
			echo '<div id="am">' . "\n"
				. '<div>' . "\n";
			
			if ( current_user_can('edit_posts') ) {
				echo '<span class="am_new">'
					. '<a href="' . $admin_url . 'post-new.php">'
						. __('New Post', 'sem-admin-menu')
						. "</a>"
					. '</span>'
					. ' ';
			}
			
			if ( current_user_can('edit_pages') ) {
				if ( is_page() && !is_front_page() )
					$parent_id = $GLOBALS['wp_the_query']->get_queried_object_id();
				elseif ( !is_page() && is_home() && get_option('show_on_front') == 'page' )
					$parent_id = (int) get_option('page_for_posts');
				else
					$parent_id = '';
				
				if ( $parent_id )
					$parent_id = '?parent_id=' . $parent_id;
				
				echo '<span class="am_new">'
					. '<a href="' . $admin_url . 'page-new.php' . $parent_id . '">'
						. __('New Page', 'sem-admin-menu')
						. '</a>'
					. '</span>'
					. ' ';
			}
			
			if ( is_page() && current_user_can('edit_pages') ) {
				echo '<span class="am_manage">'
					. '<a href="' . $admin_url . 'edit-pages.php">'
						. __('Manage', 'sem-admin-menu')
						. "</a>"
					. '</span>'
					. ' ';
			} elseif ( current_user_can('edit_posts') ) {
				echo '<span class="am_manage">'
					. '<a href="' . $admin_url . 'edit.php">'
						. __('Manage', 'sem-admin-menu')
						. "</a>"
					. '</span>'
					. ' ';
			}
			
			if ( current_user_can('edit_posts') || current_user_can('edit_pages') ) {
				echo '<span class="am_comments">'
					. '<a href="' . $admin_url . 'edit-comments.php">'
						. __('Comments', 'sem-admin-menu')
						. '</a>'
					. '</span>'
					. ' ';
			}
				
			if ( current_user_can('activate_plugins') ) {
				echo '<span class="am_options">'
					. '<a href="' . $admin_url . 'plugins.php">'
						. __('Plugins', 'sem-admin-menu')
						. '</a>'
					. '</span>'
					. ' ';
			}
			
			if ( current_user_can('switch_themes') ) {
				global $wp_registered_sidebars;
				$using_widgets = !empty($wp_registered_sidebars)
					&& ( !isset($wp_registered_sidebars['wp_inactive_widgets'])
					|| isset($wp_registered_sidebars['wp_inactive_widgets'])
						&& count($wp_registered_sidebars) > 1
					);
				
				echo '<span class="am_options">'
					. '<a href="' . $admin_url . ( $using_widgets ? 'widgets.php' : 'themes.php' ) . '">'
						. ( $using_widgets ? __('Widgets', 'sem-admin-menu') : __('Themes', 'sem-admin-menu') )
						. '</a>'
					. '</span>'
					. ' ';
			}

			if ( current_user_can('manage_options') ) {
				echo '<span class="am_options">'
					. '<a href="' . $admin_url . 'options-general.php">'
						. __('Settings', 'sem-admin-menu')
						. '</a>'
					. '</span>'
					. ' ';
			}
			
			echo '<span class="am_dashboard">'
				. '<a href="' . $admin_url . '">'
					. __('Dashboard', 'sem-admin-menu')
					. '</a>'
				. '</span>'
				. ' ';

			echo '<span class="am_user">'
					. '<a href="' . $admin_url . 'profile.php">'
						. __('Profile', 'sem-admin-menu')
						. '</a>'
					. '</span>'
					. ' ';

			echo '<span class="am_user">'
					. apply_filters('loginout',
						'<a href="' . wp_logout_url() . '">'
							. __('Logout', 'sem-admin-menu')
							. '</a>'
						)
					. '</span>';
			
			echo '</div>' . "\n"
				. '</div>' . "\n";
		}
	} # display_menu()
	
	
	/**
	 * get_options()
	 *
	 * @return void
	 **/

	function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('sem_admin_menu');
		
		if ( $o === false )
			$o = sem_admin_menu::init_options();
		
		return $o;
	} # get_options()
	
	
	/**
	 * init_options()
	 *
	 * @return void
	 **/

	function init_options() {
		$o = array(
			'always_on' => true
			);
		
		if ( $old = get_option('sem_admin_menu_params') ) {
			$o = wp_parse_args($old, $o);
			delete_option('sem_admin_menu_params');
		}
		
		update_option('sem_admin_menu', $o);
		
		return $o;
	} # init_options()
	
	
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/
	
	function admin_menu() {
		add_options_page(
			__('Admin Menu', 'sem-admin-menu'),
			__('Admin Menu', 'sem-admin-menu'),
			'manage_options',
			'admin-menu',
			array('sem_admin_menu_admin', 'edit_options')
			);
	} # admin_menu()
} # sem_admin_menu


/**
 * sem_admin_menu_admin()
 *
 * @return void
 **/

function sem_admin_menu_admin() {
 	include dirname(__FILE__) . '/sem-admin-menu-admin.php';
} # sem_admin_menu_admin()

foreach ( array('load-page-new.php', 'load-settings_page_admin-menu') as $hook )
	add_action($hook, 'sem_admin_menu_admin');
?>