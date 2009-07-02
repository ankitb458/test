<?php
#
# Wizard Name: Upgrade
# Version: 1.0 beta
# Author: Denis de Bernardy
# Author uri: http://www.mesoconcepts.com
#

include_once dirname(__FILE__) . '/start.php';

if ( !sem_pro ) :

sem_wizards::register_step(
	'start',
	array('wiz_upgrade_start', 'show_step'),
	array('sem_wizards', 'do_done')
	);

else :

foreach ( array('http') as $file )
{
	include_once sem_path . '/inc/' . $file . '.php';
}

foreach ( array('ftp', 'zip') as $file )
{
	include_once sem_pro_path . '/inc/' . $file . '.php';
}

foreach ( array('download', 'backup', 'prepare', 'upgrade', 'cleanup', 'done') as $file )
{
	include_once sem_pro_path . '/inc/upgrade/' . $file . '.php';
}

sem_wizards::register_step(
	'start',
	array('wiz_upgrade_start', 'show_step'),
	array('wiz_upgrade_download', 'do_step')
	);

sem_wizards::register_step(
	'backup',
	array('wiz_upgrade_backup', 'show_step'),
	array('wiz_upgrade_backup', 'do_step')
	);

sem_wizards::register_step(
	'prepare',
	array('wiz_upgrade_prepare', 'show_step'),
	array('wiz_upgrade_prepare', 'do_step')
	);

sem_wizards::register_step(
	'upgrade',
	array('wiz_upgrade_upgrade', 'show_step'),
	array('wiz_upgrade_upgrade', 'do_step')
	);

sem_wizards::register_step(
	'cleanup',
	array('wiz_upgrade_cleanup', 'show_step'),
	array('wiz_upgrade_cleanup', 'do_step')
	);

sem_wizards::register_step(
	'done',
	array('wiz_upgrade_done', 'show_step')
	);

endif;
?>