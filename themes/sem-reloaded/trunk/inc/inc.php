<?php


#
# include utils
#

$sem_files = array(
	'entry',
	'footer',
	'header',
	'layout',
	'nav-menus',
	'panels',
	'skin',
	);

$sem_admin_files = array(
	'entry',
	'footer',
	'header',
	'layout',
	'nav-menus',
	'skin',
	);


foreach ( $sem_files as $inc_file )
{
	include sem_path . '/inc/' . $inc_file . '.php';
}


#
# include admin screens
#

if ( is_admin() )
{
	foreach ( $sem_admin_files as $inc_file )
	{
		include sem_path . '/inc/' . $inc_file . '-admin.php';
	}
}


# Semiologic Pro files

add_option('sem_api_key', '');

foreach ( array('sem_docs', 'sem_fixes') as $sem_plugins ) :

$sem_plugin_path = $sem_plugins . '_path';

if ( defined($sem_plugin_path) ) :

$sem_plugin_path = constant($sem_plugin_path);
$sem_plugin_files = $sem_plugins . '_files';
$sem_plugin_admin_files = $sem_plugins . '_admin_files';

global $$sem_plugin_files;
global $$sem_plugin_admin_files;

foreach ( $$sem_plugin_files as $sem_file )
{
	include_once $sem_plugin_path . '/' . $sem_file;
}

if ( is_admin() ) :

foreach ( $$sem_plugin_admin_files as $sem_file )
{
	include_once $sem_plugin_path . '/' . $sem_file;
}

$sem_file = ABSPATH . PLUGINDIR . '/version-checker/sem-api-key.php';

if ( !get_option('sem_api_key')
	&& !class_exists('sem_api_key') && file_exists($sem_file)
	&& version_compare($GLOBALS['wp_version'], '2.7', '>=')
	)
{
	include $sem_file;
}

endif; # is_admin()

endif; # defined()

endforeach; # Semiologic Pro files



#
# print template
#

if ( $_GET['action'] == 'print' ) :

function print_template()
{
	include_once sem_path . '/print.php';
	die;
} # print_template()

add_action('template_redirect', 'print_template');

endif;
?>