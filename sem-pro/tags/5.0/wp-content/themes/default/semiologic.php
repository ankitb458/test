<?php
#delete_option('semiologic');

if ( !get_option('sem5_options') )
{
	update_option('template', 'semiologic');
	update_option('stylesheet', 'semiologic');

	include_once ABSPATH . 'wp-content/themes/semiologic/inc/init.php';
}
else
{
	#
	# dump()
	#

	function dump()
	{
		foreach ( func_get_args() as $var )
		{
			echo '<pre style="padding: 10px; border: solid 1px black; background-color: ghostwhite; color: black;">';
			var_dump($var);
			echo '</pre>';
		}
	} # dump()


	#
	# dump_time()
	#

	function dump_time($where = '')
	{
		echo '<div style="margin: 10px auto; text-align: center;">';
		echo ( $where ? ( $where . ': ' ) : '' ) . get_num_queries() . " - " . timer_stop();
		echo '</div>';
	} # dump_time()
}
?>