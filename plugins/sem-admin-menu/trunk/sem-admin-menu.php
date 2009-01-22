<?php
/*
Plugin Name: Admin Menu
Plugin URI: http://www.semiologic.com/software/publishing/admin-menu/
Description: Adds a convenient admin menu to your blog.
Version: 5.1.3
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/

load_plugin_textdomain('sem-admin-menu','wp-content/plugins/sem-admin-menu');


class sem_admin_menu
{
	#
	# init()
	#

	function init()
	{
		add_action('wp_head', array('sem_admin_menu', 'display_css'));
		#add_action('wp_head', array('sem_admin_menu', 'ob_add_menu'), 1000);
		add_action('wp_footer', array('sem_admin_menu', 'display_menu'));
		# edit_page_form
		add_action('submitpage_box', array('sem_admin_menu', 'set_parent_id'), 0);
	} # init()


	#
	# display_css()
	#

	function display_css()
	{
		$site_url = trailingslashit(get_option('siteurl'));

		$path = 'wp-content/'
			. ( function_exists('get_site_option')
				? 'mu-plugins/'
				: 'plugins/'
			. 'sem-admin-menu/'
				);
		
		$file = 'sem-admin-menu.css?ver=5.0';

		echo '<link'
			. ' rel="stylesheet" type="text/css"'
				. ' href="' . $site_url . $path . $file . '"'
				. ' />' . "\n";
	} # display_css()


	#
	# display_menu()
	#

	function display_menu()
	{
		global $user_ID;

		$site_url = trailingslashit(get_option('siteurl'));

		if ( function_exists('get_site_option') )
		{
			$options = array('always_on' => true);
		}
		else
		{
			$options = get_option('sem_admin_menu_params');

			if ( $options === false )
			{
				$options = array('always_on' => true);
			}
		}

		$o = '';

		if ( $user_ID || get_option('users_can_register') || $options['always_on'] )
		{
			$o .= '<div id="am">' . "\n"
				. '<div>' . "\n";

			if ( $user_ID )
			{
				if ( current_user_can('edit_posts') || current_user_can('edit_pages') )
				{
					if ( current_user_can('edit_posts') )
					{
						$o .= '<span class="am_new">'
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
					
					if ( current_user_can('edit_pages') )
					{
						$o .= '<span class="am_new">'
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
					
					if ( current_user_can('edit_pages') && is_page() || !current_user_can('edit_posts') )
					{
						$o .= '<span class="am_manage">'
							. '<a href="'
									. $site_url
									. 'wp-admin/edit-pages.php'
									. '"'
								. '>'
								. __('Manage', 'sem-admin-menu')
								. "</a>"
							. '</span>'
							. ' ';
					}
					else
					{
						$o .= '<span class="am_manage">'
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
					
					$o .= '<span class="am_comments">'
						. '<a href="'
								. $site_url . 'wp-admin/edit-comments.php'
								. '"'
							. '>'
							. __('Comments', 'sem-admin-menu')
							. '</a>'
						. '</span>'
						. ' ';
				}
				
				if ( current_user_can('switch_themes') )
				{
					$o .= '<span class="am_options">'
						. '<a href="'
								. $site_url
								. ( $GLOBALS['wp_registered_sidebars']
									? 'wp-admin/widgets.php'
									: 'wp-admin/themes.php'
									)
								. '"'
							. '>'
							. __('Widgets', 'sem-admin-menu')
							. '</a>'
							. '</span>'
							. ' ';
				}
				
				if ( current_user_can('manage_options') )
				{
					$o .= '<span class="am_options">'
						. '<a href="'
								. $site_url . 'wp-admin/options-general.php'
								. '"'
							. '>'
							. __('Settings', 'sem-admin-menu')
							. '</a>'
							. '</span>'
							. ' ';
				}

				if ( current_user_can('activate_plugins') )
				{
					$o .= '<span class="am_options">'
						. '<a href="'
								. $site_url . 'wp-admin/plugins.php'
								. '"'
							. '>'
							. __('Plugins', 'sem-admin-menu')
							. '</a>'
							. '</span>'
							. ' ';
				}

				$o .= '<span class="am_dashboard">'
					. '<a href="'
							. $site_url . 'wp-admin/'
							. '"'
						. '>'
						. __('Dashboard', 'sem-admin-menu')
						. '</a>'
						. '</span>'
						. ' ';

				$o .= '<span class="am_user">'
						. apply_filters('loginout',
							'<a href="'
									. wp_logout_url()
									. '"'
								. '>'
								. __('Logout', 'sem-admin-menu')
								. '</a>'
							)
						. '</span>'
							. ' ';
			}
			else
			{
				if ( get_option('users_can_register') )
				{
					$o .= '<span class="am_user">'
								. '<a href="'
									. $site_url . 'wp-register.php">'
									. __('Register', 'sem-admin-menu')
									. "</a>"
								. "</span>"
								. ' ';
				}
				elseif ( function_exists('get_site_option') )
				{
					$o .= '<span class="am_user">'
								. '<a href="'
									. $site_url . 'wp-signup.php">'
									. __('Register', 'sem-admin-menu')
									. "</a>"
								. "</span>"
								. ' ';
				}

				$o .= '<span class="am_user">'
						. apply_filters('loginout',
							'<a href="'
								. $site_url . 'wp-login.php">'
								. __('Login', 'sem-admin-menu')
								. "</a>"
							)
						. "</span>";
			}

			$o .= '</div>' . "\n"
				. '</div>' . "\n";
		}
		
		echo $o;

		#return $o;
	} # display_menu()
	
	
	#
	# set_parent_id()
	#
	
	function set_parent_id()
	{
		if ( $_GET['parent_id'] && strpos($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') !== false )
		{
			global $post;
			
			$post->post_parent = intval($_GET['parent_id']);
		}
	} # set_parent_id()
} # sem_admin_menu

sem_admin_menu::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-admin-menu-admin.php';
}
?>