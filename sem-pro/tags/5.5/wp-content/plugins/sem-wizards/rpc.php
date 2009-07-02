<?php
class sem_rpc
{
	#
	# init()
	#
	
	function init()
	{
		if ( $_REQUEST['wizard'] && $_REQUEST['method'] )
		{
			add_action('init', array('sem_rpc', 'exec'));
		}
	} # init()
	
	
	#
	# exec()
	#
	
	function exec()
	{
		global $sem_wizards;
		
		// Reset WP

		$GLOBALS['wp_filter'] = array();

		while ( @ob_end_clean() );

		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		// always modified
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		// HTTP/1.1
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		// HTTP/1.0
		header('Pragma: no-cache');

		# Set the response format.
		header( 'Content-Type:text/xml; charset=utf-8' );
		echo '<?xml version="1.0" encoding="utf-8" ?>';
		
		# Validate user
		
		$user = wp_authenticate($_REQUEST['user'], $_REQUEST['pass']);
		
		if ( is_wp_error($user)
			|| !$user->has_cap('administrator')
			)
		{
			die('<error>Access Denied</error>');
		}
		
		# Execute RPC
		
		if ( preg_match("/^[a-z_-]+$/i", $_REQUEST['wizard'])
			&& preg_match("/^[a-z_-]+$/i", $_REQUEST['method'])
			&& file_exists(sem_wizards_path . '/' . $_REQUEST['wizard'] . '/wizard.php')
			)
		{
			$GLOBALS['cache_enabled'] = false;
			
			include sem_wizards_path . '/wizards.php';
			include sem_wizards_path . '/' . $_REQUEST['wizard'] . '/wizard.php';
			
			do_action('wizard_' . $_REQUEST['wizard'] . '_' . $_REQUEST['method']);
			die;
		}
		else
		{
			die('<error>Invalid Wizard</error>');
		}
	} # exec()
} # sem_rpc

sem_rpc::init();
?>