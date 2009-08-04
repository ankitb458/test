<?php
/*
Plugin Name: Semiologic Packages
Plugin URI: https://api.semiologic.com/version/
Description: Interfaces with Mediacaster in order to output extra information for stable and bleeding edge downloads.
Version: 1.0
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-packages
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('sem-packages', false, dirname(plugin_basename(__FILE__)) . '/lang');


/**
 * sem_packages
 *
 * @package Semiologic Packages
 **/

class sem_packages {
	/**
	 * filter()
	 *
	 * @param string $title
	 * @param array $args
	 * @return void
	 **/

	function filter($title, $args) {
		if ( empty($args['src']) )
			return $title;
		
		global $wpdb;
		
		$package = $wpdb->get_row("
			SELECT	*
			FROM	$wpdb->packages
			WHERE	stable_package = '" . $wpdb->escape($args['src']) . "'
			OR		bleeding_package = '" . $wpdb->escape($args['src']) . "'
			");
		
		if ( !$package )
			return $title;
		
		if ( $args['src'] == $package->stable_package ) {
			$version = $package->stable_version;
			$last_mod = !empty($package->stable_modified) ? strtotime($package->stable_modified) : false;
		} else {
			$version = $package->bleeding_version;
			$last_mod = !empty($package->bleeding_modified) ? strtotime($package->bleeding_modified) : false;
		}
		
		$title = ( $version
				? sprintf(__('%1$s v.%2$s', 'sem-packages'), $title, $version)
				: $title
				)
			. ( $last_mod
				? ( '<br />' . "\n"
					. '<span class="media_info">'
					. date_i18n(__('F jS, Y', 'sem-packages'), $last_mod)
					. '</span>' )
				: ''
				);
		
		return $title;
	} # filter()
} # sem_packages

global $wpdb;

if ( defined('SEM_PACKAGES') )
	$wpdb->packages = SEM_PACKAGES;
else
	$wpdb->packages = 'packages';

add_filter('mediacaster_file', array('sem_packages', 'filter'), 10, 2);
?>