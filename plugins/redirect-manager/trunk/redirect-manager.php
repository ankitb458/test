<?php
/*
Plugin Name: Redirect Manager
Plugin URI: http://www.semiologic.com/software/marketing/redirect-manager/
Description: Lets you manage redirects on your site without messing around with .htaccess files
Version: 1.1 beta
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Update Package: https://members.semiologic.com/media/plugins/redirect-manager/redirect-manager.zip
*/


/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('redirect-manager', null, basename(dirname(__FILE__)) . '/lang');


/**
 * redirect_manager
 *
 * @package Redirect Manager
 * @author Denis
 **/

add_action('template_redirect', array('redirect_manager', 'redirect'), -1000000);

class redirect_manager {
	/**
	 * redirect()
	 *
	 * @return void
	 * @author Denis
	 **/
	
	function redirect() {
		if ( is_feed() || !is_singular() )
		 	return;
		
		global $wp_query;
		$post_id = $wp_query->get_queried_object_id();
		
		if ( $location = get_post_meta($post_id, '_redirect_url', true) ) {
			if ( !current_user_can('edit_post', $post_id) ) {
				wp_redirect($location, 301);
				die;
			} else {
				add_filter('the_content', array('redirect_manager', 'display'));
			}
		}
	} # redirect()
	
	
	/**
	 * display($content)
	 *
	 * @param string $content the entry's content
	 * @return string $content
	 * @author Denis
	 **/
	
	function display($content) {
		if ( is_feed() || !is_singular() )
			return $content;
		
		global $wp_query;
		$post_id = $wp_query->get_queried_object_id();
		$location = get_post_meta($post_id, '_redirect_url', true);
		
		if ( $location ) {
			$caption = __('Redirects to <a href="%1$s">%2$s</a>', 'redirect-manager');
			$content = '<p>' . sprintf($caption, attribute_escape($location), $location) . '</p>';
		}
		
		return $content;
	} # display()
} # redirect_manager

if ( is_admin() )
	include dirname(__FILE__) . '/redirect-manager-admin.php';
?>