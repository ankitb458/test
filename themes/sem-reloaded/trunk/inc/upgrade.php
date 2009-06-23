<?php
global $wpdb;


#
# Upgrade
#

if ( version_compare($sem_options['version'], '5.1', '<') ) :

include sem_path . '/inc/upgrade/5.0.php';

endif;

if ( version_compare($sem_options['version'], '5.6', '<') ) :

include sem_path . '/inc/upgrade/5.5.php';

endif;

if ( version_compare($sem_options['version'], '5.7', '<') ) :

include sem_path . '/inc/upgrade/5.7.php';

endif;

if ( version_compare($sem_options['version'], '5.8', '<') ) :

include sem_path . '/inc/upgrade/5.8.php';

endif;

include sem_path . '/inc/upgrade/reloaded.php';

include sem_path . '/inc/upgrade/options.php';


#
# Update Version
#

$sem_options['version'] = sem_version;

if ( !defined('sem_install_test') )
{
	update_option('sem6_options', $sem_options);
	update_option('sem6_captions', $sem_captions);
	update_option('sem_nav_menus', $sem_nav_menus);
}

?>