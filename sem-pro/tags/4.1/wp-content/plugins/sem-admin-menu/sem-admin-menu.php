<?php
/*
Plugin Name: Admin Menu
Plugin URI: http://www.semiologic.com/software/admin-menu/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/admin-menu/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Adds a convenient admin menu to your blog. To customize the skin, drop a skin.css file in the plugin's directory. The sample skin is courtesy of <a href="http://www.bureaublumenberg.net">BureauBlumenberg</a>.
Author: Denis de Bernardy
Version: 3.0
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

load_plugin_textdomain('sem-admin-menu');


#
# sem_admin_menu_init()
#

function sem_admin_menu_init()
{
	if ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
		&& !( isset($_GET['action']) && $_GET['action'] == 'print' )
		)
	{
		ob_start(array(&$this, 'sem_admin_menu_add_menu'));
	}
} # end sem_admin_menu_init()

add_action('template_redirect', 'sem_admin_menu_init', -50);


#
# sem_admin_menu_add_menu()
#

function sem_admin_menu_add_menu($buffer)
{
	if ( !is_object($GLOBALS['wp_rewrite']) )
	{
		$GLOBALS['wp_rewrite'] =& new WP_Rewrite();
	}

	if ( strpos($_SERVER['REQUEST_URI'], 'wp-login') === false )
	{
		$buffer = str_replace("</head>", sem_admin_menu_display_css() . "</head>", $buffer);
		$buffer = preg_replace("/(<body[^>]*>)/", "$1\n" . sem_admin_menu_display_menu() , $buffer);
	}

	return $buffer;
} # end sem_admin_menu_add_menu()


#
# sem_admin_menu_display_css()
#

function sem_admin_menu_display_css()
{
	$o = '';

	if ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
		&& !( isset($_GET['action']) && $_GET['action'] == 'print' )
		)
	{
		$o .= '<link'
			. ' rel="stylesheet" type="text/css"'
				. ' href="' . trailingslashit(get_settings('siteurl'))
						. 'wp-content/'
						. ( function_exists('get_site_option')
							? 'mu-plugins/'
							: 'plugins/'
							)
						. 'sem-admin-menu/'
					. ( file_exists(
							ABSPATH
							. 'wp-content/'
							. ( function_exists('get_site_option')
								? 'mu-plugins/'
								: 'plugins/'
								)
							. 'sem-admin-menu/'
							. 'skin.css'
							)
						? 'skin.css'
						: 'sem-admin-menu.css'
						)
					. '" />';
	}

	return $o;
} # end sem_admin_menu_display_css()


#
# sem_admin_menu_display_menu()
#

function sem_admin_menu_display_menu()
{
	global $user_ID;
	global $user_level;

	$site_path = trailingslashit(get_settings('siteurl'));

	$o = "";

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

		$o .= ( user_can_create_draft($user_ID)
			? ( "<li class=\"new_item\">" . __('New:', 'sem-admin-menu') . " "
					. "<a href=\""
						. $site_path . "wp-admin/post.php\">"
						. __('Post', 'sem-admin-menu')
						. "</a>"
					. ( ( $user_level >= 5 )
						? ( " &bull;&nbsp;"
							. "<a href=\""
								. $site_path . "wp-admin/link-add.php\">"
								. __('Link', 'sem-admin-menu')
								. "</a>"
							. " &bull;&nbsp;"
							. "<a href=\""
								. $site_path . "wp-admin/page-new.php\">"
								. __('Page', 'sem-admin-menu')
								. "</a>"
							)
						: ""
						)
					. "</li>\n"
				. "<li>|</li>\n" )
			: ""
			)
			. ( ( $user_level >= 7 )
				? ( "<li class=\"options\">"
					. "<a href=\""
						. $site_path . "wp-admin/edit.php\">"
						. __('Manage', 'sem-admin-menu')
						. "</a>"
					. "</li>\n"
					. "<li>|</li>\n"
					. "<li class=\"options\">"
					. "<a href=\""
						. $site_path . "wp-admin/themes.php\">"
						. __('Presentation', 'sem-admin-menu')
						. "</a>"
					. "</li>\n"
					. ( $show_plugins
					? ( "<li>|</li>\n"
						. "<li class=\"options\">"
						. "<a href=\""
							. $site_path . "wp-admin/plugins.php\">"
							. __('Plugins', 'sem-admin-menu')
							. "</a>"
						. "</li>\n"
						)
					: ''
					)
					. "<li>|</li>\n"
					. "<li class=\"options\">"
					. "<a href=\""
						. $site_path . "wp-admin/options-general.php\">"
						. __('Options', 'sem-admin-menu')
						. "</a>"
					. "</li>\n"
					. "<li>|</li>\n"
					)
				: ""
				)
			. "<li class=\"dashboard\">"
				. "<a href=\""
					. $site_path . "wp-admin/\">"
					. __('Dashboard', 'sem-admin-menu')
					. "</a>"
				. "</li>\n"
			. "<li>|</li>\n"
			. "<li class=\"logout\">"
				. "<a href=\""
					. $site_path . "wp-login.php?action=logout\">"
					. __('Logout', 'sem-admin-menu')
					. "</a>"
				. "</li>\n";
	}
	else
	{
		$o .= ( ( get_settings('users_can_register') )
				? ( "<li class=\"register\">"
						. "<a href=\""
							. $site_path . "wp-register.php\">"
							. __('Register', 'sem-admin-menu')
							. "</a>"
						. "</li>\n"
					. "<li>|</li>\n"
					)
					: ""
					)
			. ( ( function_exists('get_site_option') )
				? ( "<li class=\"register\">"
						. "<a href=\""
							. $site_path . "wp-signup.php\">"
							. __('Register', 'sem-admin-menu')
							. "</a>"
						. "</li>\n"
					. "<li>|</li>\n"
					)
					: ""
					)
			. "<li class=\"login\">"
				. "<a href=\""
					. $site_path . "wp-login.php\">"
					. __('Login', 'sem-admin-menu')
					. "</a>"
				. "</li>\n";
	}

	$o .= "</ul>\n"
		. "</div>\n";

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
?>