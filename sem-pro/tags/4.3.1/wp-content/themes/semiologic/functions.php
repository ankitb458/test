<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# The Semiologic theme features a custom.php feature. This allows to hook into
# the template without editing its php files. That way, you won't need to worry
# about losing your changes when you upgrade your site.
#
# You'll find detailed sample files in the custom-samples folder
#


#
# initialize
#

$GLOBALS['semiologic'] = get_option('semiologic');

#echo '<pre>';
#var_dump($GLOBALS['semiologic']);
#echo '</pre>';

if ( @ !$GLOBALS['semiologic'] || !strlen($GLOBALS['semiologic']['active_layout']) )
{
	include_once dirname(__FILE__) . '/wizards/autoinstall/autoinstall.php';

	install_semiologic();
}


#
# true() and false()
# these are useful to override theme defaults in the custom.php file
# e.g. add_filter('show_entry_date', 'false');
#

if ( !function_exists('true') )
{
	function true($bool = null)
	{
		return true;
	} # end true()
}

if ( !function_exists('false') )
{
	function false($bool = null)
	{
		return false;
	} # end false()
}


#
# reset_plugin_hook()
#

function reset_plugin_hook($plugin_hook = null)
{
	if ( isset($plugin_hook) )
	{
		unset($GLOBALS['wp_filter'][$plugin_hook]);
	}
} # end reset_plugin_hook()


#
# include utils
#

foreach ( (array) glob(dirname(__FILE__) . '/utils/*.php') as $inc_file )
{
	@include_once $inc_file;
}
foreach ( (array) glob(ABSPATH . 'wp-content/plugins/semiologic/utils/*.php') as $inc_file )
{
	@include_once $inc_file;
}


#
# include admin screens
#

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	foreach ( (array) glob(dirname(__FILE__) . '/admin/*.php') as $inc_file )
	{
		@include_once $inc_file;
	}
	foreach ( (array) glob(ABSPATH . 'wp-content/plugins/semiologic/admin/*.php') as $inc_file )
	{
		@include_once $inc_file;
	}
}


#
# include custom.php and skin.php files
#

$inc_file = TEMPLATEPATH . '/custom.php';

@include_once $inc_file;


$inc_file = TEMPLATEPATH . '/skins/' . get_active_skin() . '/skin.php';

@include_once $inc_file;


$inc_file = TEMPLATEPATH . '/skins/' . get_active_skin() . '/custom.php';

@include_once $inc_file;



#
# register sidebars widgets
#

if ( function_exists('register_sidebars') )
{
	register_sidebars(strlen($GLOBALS['semiologic']['active_layout']) - 1);
}


#
# sem_pro
#

if ( !defined('sem_pro') )
{
	define('sem_pro', false);
}
?>