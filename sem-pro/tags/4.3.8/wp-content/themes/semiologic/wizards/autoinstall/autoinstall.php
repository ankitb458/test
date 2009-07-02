<?php

include_once ABSPATH . 'wp-admin/admin-functions.php';

$sem_path = dirname(dirname(dirname(__FILE__)));

include_once $sem_path . '/utils/wizards.php';
include_once $sem_path . '/utils/skin.php';
include_once $sem_path . '/utils/layout.php';
include_once $sem_path . '/utils/captions.php';

include_once $sem_path . '/admin/skin.php';
include_once $sem_path . '/admin/nav-menus.php';


#
# install_semiologic()
#

function install_semiologic()
{
	if ( get_option('semiologic') && !current_user_can('administrator') )
	{
		return;
	}

	# Reset
	$options = array();

	# Header Nav
	$options['nav_menus']['header_nav'] = array(
				'Home' => get_bloginfo('home'),
				'Blog' => 'blog',
				'About' => 'about',
				'Contact' => 'contact'
				);

	# Sidebar Nav
	$options['nav_menus']['sidebar_nav'] = array(
				);

	# Footer Nav
	$options['nav_menus']['footer_nav'] = array(
				'Archives' => 'archives',
				'About' => 'about',
				'Contact' => 'contact'
				);

	# Skin, layout, font, width
	$options['active_skin'] = get_skin_data('sky-gold');
	$options['active_layout'] = 'mse';
	$options['active_width'] = 'wide';
	$options['active_font'] = 'trebuchet';
	$options['active_font_size'] = 'small';

	# Header
	$options['header']['mode'] = 'header';

	# Captions
	$options['captions'] = get_all_captions();

	update_option('semiologic', $options);

	if ( function_exists('wiz_autoinstall_pro') )
	{
		wiz_autoinstall_pro();
	}

	regen_theme_nav_menu_cache();

	if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false )
	{
		wp_redirect($_SERVER['REQUEST_URI']);
		die;
	}

	$GLOBALS['semiologic'] = get_option('semiologic');
} # end install_semiologic()


if ( file_exists(ABSPATH . 'wp-content/plugins/semiologic/wizards/autoinstall/autoinstall.php') )
{
	include_once ABSPATH . 'wp-content/plugins/semiologic/wizards/autoinstall/autoinstall.php';
}
?>