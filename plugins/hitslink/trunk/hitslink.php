<?php
/*
Plugin Name: HitsLink
Plugin URI: http://www.semiologic.com/software/hitslink/
Description: Adds <a href="http://www.semiologic.com/go/hitslink">HitsLink</a> tracking to your site.
Author: Denis de Bernardy
Version: 2.0 RC
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('hitslink', null, dirname(__FILE__) . '/lang');


/**
 * hitslink
 *
 * @package HitsLink
 **/

add_action('wp_head', array('hitslink', 'display'));
add_action('admin_menu', array('hitslink', 'admin_menu'));

class hitslink {
	/**
	 * display()
	 *
	 * @return void
	 **/
	
	function display() {
		$script = hitslink::get_options();

		if ( !$script ) {
			echo '<!-- '
				. __('You need to configure the HitsLink plugin under Settings / HitsLink', 'hitslink')
				. ' -->' . "\n";
		} elseif ( current_user_can('publish_posts') || current_user_can('publish_pages') ) {
			echo '<!-- '
				. __('The HitsLink plugin does not track site authors, editors and admins when they are logged in', 'hitslink')
				. ' -->' . "\n";
		} else {
			echo $script;
		}
	} # display()
	
	
	/**
	 * get_options()
	 *
	 * @return void
	 **/
	
	function get_options() {
		$o = get_option('hitslink');
		
		if ( $o === false ) {
			$o = get_option('hitslink_params');
			if ( is_array($o) )
				$o = $o['script'];
			else
				$o = '';
			update_option('hitslink', $o);
		}
		
		return $o;
	} # get_options()
	
	
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/

	function admin_menu() {
		if ( !current_user_can('unfiltered_html') )
			return;
		
		add_options_page(
			__('HitsLink', 'hitslink'),
			__('HitsLink', 'histlink'),
			'manage_options',
			'hitslink',
			array('hitslink_admin', 'edit_options')
			);
	} # admin_menu()
} # hitslink()

function hitslink_admin() {
	include dirname(__FILE__) . '/hitslink-admin.php';
}

add_action('load-settings_page_hitslink', 'hitslink_admin');
?>