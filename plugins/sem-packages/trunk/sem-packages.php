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
	 * download()
	 *
	 * @param string $title
	 * @param array $args
	 * @return void
	 **/

	function download($title, $args) {
		if ( empty($args['src']) )
			return $title;
		
		global $wpdb;
		
		$package = $wpdb->get_row("
			SELECT	stable_version, stable_requires, stable_compat, stable_modified,
					bleeding_version, bleeding_requires, bleeding_compat, bleeding_modified
			FROM	$wpdb->packages
			WHERE	stable_package = '" . $wpdb->escape($args['src']) . "'
			OR		bleeding_package = '" . $wpdb->escape($args['src']) . "'
			");
		
		if ( !$package )
			return $title;
		
		if ( $args['src'] == $package->stable_package ) {
			$version = $package->stable_version;
			$last_mod = !empty($package->stable_modified)
				? strtotime($package->stable_modified)
				: false;
			$requires = !empty($package->stable_requires)
				? $package->stable_requires
				: false;
			$compat = !empty($package->stable_compat)
				? $package->stable_compat
				: false;
		} else {
			$version = $package->bleeding_version;
			$last_mod = !empty($package->bleeding_modified)
				? strtotime($package->bleeding_modified)
				: false;
			$requires = !empty($package->bleeding_requires)
				? $package->stable_requires
				: false;
			$compat = !empty($package->bleeding_compat)
				? $package->bleeding_compat
				: false;
		}
		
		if ( $version ) {
			$title = sprintf(
				__('%1$s v.%2$s', 'sem-packages'),
				$title,
				$version
				);
		}
		
		$title = '<strong>' . $title . '</strong>';
		
		if ( $last_mod ) {
			$title = sprintf(
				__('%1$s %2$s', 'sem-packages'),
				$title,
				'<span class="media_info">'
					. date_i18n(__('(M jS, Y)', 'sem-packages'), $last_mod)
					. '</span>'
				);
		}
		
		$extra = '';
		
		if ( $requires && $compat ) {
			$extra = sprintf(__('Requires WP %1$s. Tested up to %2$s.', 'sem-packages'), $requires, $compat);
		} elseif ( $requires ) {
			$extra = sprintf(__('Requires WP %s.', 'sem-packages'), $requires);
		} elseif ( $compat ) {
			$extra = sprintf(__('Tested up to WP %s.', 'sem-packages'), $compat);
		}
		
		if ( $extra ) {
			$title .= '<br />' . "\n"
				. '<span class="media_info">'
				. $extra
				. '</span>';
		}
		
		return $title;
	} # download()
} # sem_packages

global $wpdb;

if ( defined('SEM_PACKAGES') )
	$wpdb->packages = SEM_PACKAGES;
else
	$wpdb->packages = 'packages';

add_filter('mediacaster_file', array('sem_packages', 'download'), 10, 2);
?>