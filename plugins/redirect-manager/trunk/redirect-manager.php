<?php
/*
Plugin Name: Redirect Manager
Plugin URI: http://www.semiologic.com/software/marketing/redirect-manager/
Description: Lets you manage redirects on your site without messing around with .htaccess files
Author: Denis de Bernardy
Version: 1.0.1 alpha
Author URI: http://www.getsemiologic.com
Update Package: https://members.semiologic.com/media/plugins/redirect-manager.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class redirect_manager
{
	#
	# init()
	#

	function init()
	{
		add_action('template_redirect', array('redirect_manager', 'redirect'), -1000000);
	} # init()


	#
	# redirect()
	#
	
	function redirect()
	{
		if ( is_singular() && !is_feed() )
		{
			global $wp_query;
			$post_id = $wp_query->get_queried_object_id();
			
			if ( $url = get_post_meta($post_id, '_redirect_url', true) )
			{
				header('HTTP/1.1 301 Moved Permanently');
				header('Status: 301 Moved Permanently');
				
				global $is_IIS;
				if ( $is_IIS ) {
					header("Refresh: 0;url=$url");
				} else {
					header("Location: $url");
				}
				die;
			}
		}
	}
} # redirect_manager

redirect_manager::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/redirect-manager-admin.php';
}
?>