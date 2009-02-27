<?php
/*
Plugin Name: Admin Menu
Plugin URI: http://www.semiologic.com/software/publishing/admin-menu/
Description: Adds a convenient admin menu to your blog.
Version: 5.2 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('sem-admin-menu', null, basename(dirname(__FILE__)) . '/lang');


/**
 * sem_admin_menu
 *
 * @package Admin Menu
 **/

if ( !( isset($_GET['action']) && $_GET['action'] == 'print' ) ) {
	add_action('wp_print_styles', array('sem_admin_menu', 'add_css'));
	add_action('wp_footer', array('sem_admin_menu', 'display_menu'));
}

class sem_admin_menu
{
	/**
	 * add_css
	 *
	 * @return void
	 **/

	function add_css() {
		$folder = plugins_url() . '/' . basename(dirname(__FILE__));
		$css = $folder . '/css/sem-admin-menu.css';
		
		wp_enqueue_style('sem_admin_menu', $css, null, '5.2');
	} # add_css()


	/**
	 * display_menu()
	 *
	 * @return void
	 **/

	function display_menu() {
		$user = wp_get_current_user();

		$site_url = trailingslashit(site_url(null, 'admin'));

		$options = sem_admin_menu::get_options();

		if ( !$user->ID && $options['always_on'] ) {
			echo '<div id="am">' . "\n"
				. '<div>' . "\n";
			
			if ( get_option('users_can_register') ) {
				echo '<span class="am_user">'
							. '<a href="'
								. wp_login_url() . '?action=register">'
								. __('Register', 'sem-admin-menu')
								. "</a>"
							. "</span>"
							. ' ';
			}

			echo '<span class="am_user">'
					. apply_filters('loginout',
						'<a href="'
							. wp_login_url()
							. '">'
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
					. '<a href="'
						. $site_url
						. 'wp-admin/post-new.php'
						. '"'
					. '>'
					. __('New Post', 'sem-admin-menu')
					. "</a>"
					. '</span>'
					. ' ';
			}
			
			if ( current_user_can('edit_pages') ) {
				echo '<span class="am_new">'
					. '<a href="'
						. $site_url
						. 'wp-admin/page-new.php'
						. ( is_page() && !is_front_page()
							? ( '?parent_id=' . $GLOBALS['wp_query']->get_queried_object_id() )
							: ''
							)
						. '"'
					. '>'
					. __('New Page', 'sem-admin-menu')
					. '</a>'
					. '</span>'
					. ' ';
			}
			
			if ( is_page() && current_user_can('edit_pages') ) {
				echo '<span class="am_manage">'
					. '<a href="'
							. $site_url
							. 'wp-admin/edit-pages.php'
							. '"'
						. '>'
						. __('Manage', 'sem-admin-menu')
						. "</a>"
					. '</span>'
					. ' ';
			} elseif ( current_user_can('edit_posts') ) {
				echo '<span class="am_manage">'
					. '<a href="'
							. $site_url
							. 'wp-admin/edit.php'
							. '"'
						. '>'
						. __('Manage', 'sem-admin-menu')
						. "</a>"
					. '</span>'
					. ' ';
			}
			
			if ( current_user_can('edit_posts') || current_user_can('edit_pages') ) {
				echo '<span class="am_comments">'
					. '<a href="'
							. $site_url . 'wp-admin/edit-comments.php'
							. '"'
						. '>'
						. __('Comments', 'sem-admin-menu')
						. '</a>'
					. '</span>'
					. ' ';
			}
				
			if ( current_user_can('activate_plugins') ) {
				echo '<span class="am_options">'
					. '<a href="'
							. $site_url . 'wp-admin/plugins.php'
							. '"'
						. '>'
						. __('Plugins', 'sem-admin-menu')
						. '</a>'
						. '</span>'
						. ' ';
			}
			
			if ( current_user_can('switch_themes') ) {
				echo '<span class="am_options">'
					. '<a href="'
							. $site_url
							. ( $GLOBALS['wp_registered_sidebars']
								? 'wp-admin/widgets.php'
								: 'wp-admin/themes.php'
								)
							. '"'
						. '>'
						. ( $GLOBALS['wp_registered_sidebars']
							? __('Widgets', 'sem-admin-menu')
							: __('Themes', 'sem-admin-menu')
							)
						. '</a>'
						. '</span>'
						. ' ';
			}

			if ( current_user_can('manage_options') ) {
				echo '<span class="am_options">'
					. '<a href="'
							. $site_url . 'wp-admin/options-general.php'
							. '"'
						. '>'
						. __('Settings', 'sem-admin-menu')
						. '</a>'
						. '</span>'
						. ' ';
			}
			
			echo '<span class="am_dashboard">'
				. '<a href="'
						. $site_url . 'wp-admin/'
						. '"'
					. '>'
					. __('Dashboard', 'sem-admin-menu')
					. '</a>'
					. '</span>'
					. ' ';

			echo '<span class="am_user">'
					. apply_filters('loginout',
						'<a href="'
								. wp_logout_url()
								. '"'
							. '>'
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
			$o = array_merge($o, $old);
			delete_option('sem_admin_menu_params');
		}
		
		update_option('sem_admin_menu', $o);
		
		return $o;
	} # init_options()
} # sem_admin_menu


if ( is_admin() )
	include dirname(__FILE__) . '/sem-admin-menu-admin.php';
?>