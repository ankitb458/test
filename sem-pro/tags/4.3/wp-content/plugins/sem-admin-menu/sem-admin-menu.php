<?php
/*
Plugin Name: Admin Menu
Plugin URI: http://www.semiologic.com/software/publishing/admin-menu/
Description: Adds a convenient admin menu to your blog.
Author: Denis de Bernardy
Version: 4.0
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/

The sample skin is copyright, Bureau Blumenberg <http://www.bureaublumenberg.net>. It is used and redistributed with permission under the same terms.


Hat Tips
--------

	* Mike Koepke <http://www.mikekoepke.com>
**/

load_plugin_textdomain('sem-admin-menu','wp-content/plugins/sem-admin-menu');


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;

if ( !defined('use_post_type_fixed') )
{
	define(
		'use_post_type_fixed',
			version_compare(
				'2.1',
				$GLOBALS['wp_version'], '<='
				)
			||
			function_exists('get_site_option')
		);
}


class admin_menu
{
	#
	# init()
	#

	function init()
	{
		add_action('init', array('admin_menu', 'ob_add_menu'));
	} # init()


	#
	# ob_add_menu()
	#

	function ob_add_menu()
	{
		ob_start(array('admin_menu', 'ob_add_menu_callback'));
	} # ob_add_menu()


	#
	# ob_add_menu_callback()
	#

	function ob_add_menu_callback($input)
	{
		if ( !is_feed()
			&& ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
			&& ( strpos($_SERVER['REQUEST_URI'], 'wp-login') === false )
			&& ( strpos($_SERVER['REQUEST_URI'], 'wp-register') === false )
			&& !( isset($_GET['action']) && $_GET['action'] == 'print' )
			)
		{
			$input = str_replace ('</title>', '</title>' . "\n" . admin_menu::display_css(), $input);
			$input = preg_replace("/<body[^>]*>/i", "$0" . "\n" . admin_menu::display_menu(), $input);
		}

		return $input;
	} # ob_add_menu_callback()


	#
	# display_css()
	#

	function display_css()
	{
		$site_url = trailingslashit(get_settings('siteurl'));

		$path = 'wp-content/'
			. ( function_exists('get_site_option')
				? 'mu-plugins/'
				: 'plugins/'
			. 'sem-admin-menu/'
				);
		$file = file_exists(ABSPATH . $path . 'skin.css')
			? 'skin.css'
			: 'sem-admin-menu.css'
			;

		return '<link'
			. ' rel="stylesheet" type="text/css"'
				. ' href="' . $site_url . $path . $file . '"'
				. ' />';
	} # display_css()


	#
	# display_menu()
	#

	function display_menu()
	{
		global $user_ID;

		$site_url = trailingslashit(get_settings('siteurl'));

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

		if ( $user_ID || get_settings('users_can_register') || $options['always_on'] )
		{
			$o .= '<div id="sem_admin_menu">' . "\n"
				. '<ul>' . "\n";

			if ( $user_ID )
			{
				if ( current_user_can('edit_posts') )
				{
					$o .= '<li class="new_item">'
						. __('New:', 'sem-admin-menu')
						. ' '
						. '<a href="'
								. $site_url . 'wp-admin/'
								. ( use_post_type_fixed
									? 'post-new.php'
									: 'post.php'
									)
								. '"'
							. '>'
							. __('Post', 'sem-admin-menu')
							. "</a>";

					if ( current_user_can('edit_pages') )
					{
						$o .= ' &bull;&nbsp;'
							. '<a href="'
									. $site_url
									. 'wp-admin/page-new.php'
									. '"'
								. '>'
								. __('Page', 'sem-admin-menu')
								. '</a>';
					}

					if ( current_user_can('manage_links') )
					{
						$o .= ' &bull;&nbsp;'
							. '<a href="'
									. $site_url
									. 'wp-admin/link-add.php'
									. '"'
								. '>'
								. __('Link', 'sem-admin-menu')
								. '</a>';
					}

					$o .= '</li>' . "\n"
						. '<li>|</li>' . "\n";

					$o .= '<li class="options">'
						. '<a href="'
								. $site_url . 'wp-admin/edit.php'
								. '"'
							. '>'
							. __('Manage', 'sem-admin-menu')
							. '</a>'
							. '</li>' . "\n"
						. '<li>|</li>' . "\n";

					$o .= '<li class="options">'
						. '<a href="'
								. $site_url . 'wp-admin/edit-comments.php'
								. '"'
							. '>'
							. __('Comments', 'sem-admin-menu')
							. '</a>'
							. '</li>' . "\n"
						. '<li>|</li>' . "\n";
				}

				if ( current_user_can('switch_themes') )
				{
					$o .= '<li class="options">'
						. '<a href="'
								. $site_url . 'wp-admin/themes.php'
								. '"'
							. '>'
							. __('Presentation', 'sem-admin-menu')
							. '</a>'
							. '</li>' . "\n"
						. '<li>|</li>' . "\n";
				}

				if ( current_user_can('activate_plugins') )
				{
					$o .= '<li class="options">'
						. '<a href="'
								. $site_url . 'wp-admin/plugins.php'
								. '"'
							. '>'
							. __('Plugins', 'sem-admin-menu')
							. '</a>'
							. '</li>' . "\n"
						. '<li>|</li>' . "\n";
				}

				if ( current_user_can('manage_options') )
				{
					$o .= '<li class="options">'
						. '<a href="'
								. $site_url . 'wp-admin/options-general.php'
								. '"'
							. '>'
							. __('Options', 'sem-admin-menu')
							. '</a>'
							. '</li>' . "\n"
						. '<li>|</li>' . "\n";
				}

				$o .= '<li class="dashboard">'
					. '<a href="'
							. $site_url . 'wp-admin/'
							. '"'
						. '>'
						. __('Dashboard', 'sem-admin-menu')
						. '</a>'
						. '</li>' . "\n"
					. '<li>|</li>' . "\n";

				if ( function_exists('get_site_option') )
				{
					$o .= '<li class="register">'
						. '<a href="'
								. $site_url . 'wp-signup.php'
								. '"'
							. '>'
							. __('New Blog', 'sem-admin-menu')
							. '</a>'
							. '</li>' . "\n"
						. '<li>|</li>' . "\n";
				}

				$o .= '<li class="profile">'
					. '<a href="'
							. $site_url . 'wp-admin/profile.php'
							. '"'
						. '>'
						. __('Profile', 'sem-admin-menu')
						. '</a>'
						. '</li>' . "\n"
					. '<li>&nbsp;&bull;&nbsp;</li>'
					. '<li class="logout">'
						. apply_filters('loginout',
							'<a href="'
									. $site_url . 'wp-login.php?action=logout'
									. '"'
								. '>'
								. __('Logout', 'sem-admin-menu')
								. '</a>'
							)
						. '</li>' . "\n";
			}
			else
			{
				if ( get_option('users_can_register') )
				{
					$o .= "<li class=\"register\">"
								. "<a href=\""
									. $site_url . "wp-register.php\">"
									. __('Register', 'sem-admin-menu')
									. "</a>"
								. "</li>\n"
							. "<li>|</li>\n";
				}
				elseif ( function_exists('get_site_option') )
				{
					$o .= "<li class=\"register\">"
								. "<a href=\""
									. $site_url . "wp-signup.php\">"
									. __('Register', 'sem-admin-menu')
									. "</a>"
								. "</li>\n"
							. "<li>|</li>\n";
				}

				$o .= "<li class=\"login\">"
						. apply_filters('loginout',
							"<a href=\""
								. $site_url . "wp-login.php\">"
								. __('Login', 'sem-admin-menu')
								. "</a>"
							)
						. "</li>\n";
			}

			$o .= '</ul>' . "\n"
				. '</div>' . "\n";
		}

		return $o;
	} # display_menu()
} # admin_menu

admin_menu::init();


#
# sem_admin_menu_display_menu()
#

function sem_admin_menu_display_menu()
{
	global $user_ID;

	$o = "";

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

	if ( $user_ID || get_settings('users_can_register') || $options['always_on'] )
	{
		$site_path = trailingslashit(get_settings('siteurl'));

		$o .= "<div id=\"sem_admin_menu\">\n"
			. "<ul>\n";

		if ( $user_ID )
		{
			if ( function_exists('get_site_option') )
			{
				$menu_items = get_site_option( "menu_items" );
				$show_plugins = isset($menu_items['plugins']);
			}
			else
			{
				$show_plugins = true;
			}
		}
		else
		{

		}

		$o .= "</ul>\n"
			. "</div>\n";
	}

	return $o;
} # end sem_admin_menu_display_menu()


########################
#
# Backward compatibility
#

function the_admin_menu()
{
} # end the_admin_menu()

function sem_admin_menu()
{
} # end sem_admin_menu()


if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/sem-admin-menu-admin.php';
}
?>