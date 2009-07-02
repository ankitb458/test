<?php
#
# Wizard Name: Clone
# Version: 2.0 beta
# Author: Denis de Bernardy
# Author uri: http://www.mesoconcepts.com
#

include_once dirname(__FILE__) . '/start.php';

if ( !sem_pro ) :

sem_wizards::register_step(
	'start',
	array('wiz_clone_start', 'show_step'),
	array('sem_wizards', 'do_done')
	);

else :

foreach ( array('http') as $file )
{
	include_once sem_path . '/inc/' . $file . '.php';
}

foreach ( array('import', 'done') as $file )
{
	include_once sem_pro_path . '/inc/clone/' . $file . '.php';
}

sem_wizards::register_step(
	'start',
	array('wiz_clone_start', 'show_step'),
	array('wiz_clone_import', 'do_step')
	);

sem_wizards::register_step(
	'done',
	array('wiz_clone_done', 'show_step')
	);

endif;
?>