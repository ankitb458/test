<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# You would lose your changes when you upgrade your site. Use php widgets instead.
#

#
# initialize
#

include dirname(__FILE__) . '/inc/init.php';

$content_width = 480;

if ( !is_admin() ) {
	add_filter('option_page_comments', 'false');
	add_filter('use_balanceTags', 'true');
}

include sem_path . '/inc/panels.php';
include sem_path . '/inc/widgets.php';
include sem_path . '/inc/template.php';



# Semiologic Pro files

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
	&& function_exists('wp_remote_fopen')
	)
{
	include $sem_file;
}

endif; # is_admin()

endif; # defined()

endforeach; # Semiologic Pro files
?>