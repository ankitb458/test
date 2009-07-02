<?php

require_once ABSPATH . 'wp-content/themes/semiologic/admin/captions.php';
require_once ABSPATH . 'wp-content/themes/semiologic/admin/nav-menus.php';


#
# install_semiologic()
#

function install_semiologic()
{
	# Reset
	$GLOBALS['semiologic'] = array();

	# Header Nav
	$GLOBALS['semiologic']['nav_menus']['header_nav'] = array(
				'Home' => get_bloginfo('home'),
				'Blog' => 'blog',
				'About' => 'about',
				'Contact' => 'contact'
				);

	# Sidebar Nav
	$GLOBALS['semiologic']['nav_menus']['sidebar_nav'] = array(
				);

	# Footer Nav
	$GLOBALS['semiologic']['nav_menus']['footer_nav'] = array(
				'Archives' => 'archives',
				'About' => 'about',
				'Contact' => 'contact'
				);

	# Skin, layout, font, width
	$GLOBALS['semiologic']['active_skin'] = get_skin_data('sky-gold');
	$GLOBALS['semiologic']['active_layout'] = 'mse';
	$GLOBALS['semiologic']['active_width'] = 'wide';
	$GLOBALS['semiologic']['active_font'] = 'trebuchet';
	$GLOBALS['semiologic']['active_font_size'] = 'small';

	# Header
	$GLOBALS['semiologic']['active_header'] = '';

	# Captions
	$GLOBALS['semiologic']['captions'] = get_all_captions();

	if ( function_exists('wiz_autoinstall_pro') )
	{
		wiz_autoinstall_pro();
	}

	update_option('semiologic', $GLOBALS['semiologic']);
	regen_theme_nav_menu_cache();
} # end install_semiologic()


if ( file_exists(ABSPATH . 'wp-content/plugins/semiologic/wizards/autoinstall/autoinstall.php') )
{
	include_once ABSPATH . 'wp-content/plugins/semiologic/wizards/autoinstall/autoinstall.php';
}
?>