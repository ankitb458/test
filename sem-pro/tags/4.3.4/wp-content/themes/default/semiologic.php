<?php
#update_option('semiologic', '');

if ( file_exists(ABSPATH . 'wp-content/themes/semiologic')
	&& ( !get_option('semiologic') || function_exists('get_site_option') )
	)
{
	update_option('template', 'semiologic');
	update_option('stylesheet', 'semiologic');

	$sem_path = dirname(dirname(__FILE__)) . '/semiologic';

	include_once $sem_path . '/wizards/autoinstall/autoinstall.php';

	install_semiologic();
}
?>