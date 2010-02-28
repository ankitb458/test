<?php
/*
Plugin Name: Semiologic Packages
Plugin URI: https://api.semiologic.com/version/
Description: Interfaces with Mediacaster in order to output extra information for stable and bleeding edge downloads.
Version: 1.0.2 beta
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
		
		$package = wp_cache_get($args['src'], 'sem_packages');
		
		if ( $package === false ) {
			$package = $wpdb->get_row("
				SELECT	*
				FROM	$wpdb->packages
				WHERE	stable_package = '" . $wpdb->_real_escape($args['src']) . "'
				OR		bleeding_package = '" . $wpdb->_real_escape($args['src']) . "'
				");
			
			if ( $package ) {
				wp_cache_set($package->stable_package, $package, 'sem_packages', 900);
				wp_cache_set($package->bleeding_package, $package, 'sem_packages', 900);
				wp_cache_set($package->package, $package, 'sem_packages', 900);
			} else {
				$package = (object) $package;
				wp_cache_set($args['src'], $package, 'sem_packages', 900);
			}
		}
		
		if ( empty($package) )
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
		
		$title = '<strong>' . $title . '</strong><input type="hidden" class="event_label" value="' . esc_attr($title) . '" />';
		
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
	
	
	/**
	 * changelog()
	 *
	 * @param array $args
	 * @param string $content
	 * @return string $changelog
	 **/

	function changelog($args, $content = '') {
		extract($args, EXTR_SKIP);
		
		if ( empty($package) )
			return;
		
		global $wpdb;
		
		$readme = wp_cache_get($package, 'sem_packages');
		
		if ( $readme === false ) {
			$readme = $wpdb->get_row("
				SELECT	*
				FROM	$wpdb->packages
				WHERE	package = '" . $wpdb->_real_escape($package) . "-'
				");
			
			if ( $readme ) {
				wp_cache_set($readme->stable_package, $readme, 'sem_packages', 900);
				wp_cache_set($readme->bleeding_package, $readme, 'sem_packages', 900);
				wp_cache_set($readme->package, $readme, 'sem_packages', 900);
			} else {
				$readme = (object) $readme;
				wp_cache_set($package, $readme, 'sem_packages', 900);
			}
		}
		
		if ( empty($readme) )
			return;
		
		if ( !function_exists('Markdown') )
			include_once dirname(__FILE__) . '/markdown/markdown.php';
		
		$changelog = $readme->bleeding_readme
			? $readme->bleeding_readme
			: $readme->stable_readme;
		
		if ( !trim($changelog) )
			return;
		
		$changelog = preg_split("/^\s*(==[^=].+?)\s*$/m", $changelog, null, PREG_SPLIT_DELIM_CAPTURE);
		
		# dump header
		array_shift($changelog);
		
		if ( !$changelog )
			return;
		
		do {
			$section = array_shift($changelog);
			$section = trim($section);
			
			if ( preg_match("/^==\s*Change\s*Log\s*(?:==)?$/i", $section) ) {
				$changelog = array_shift($changelog);
				$changelog = preg_replace("/^=([^=].+?)=?$/m", "### " . sprintf(__('Version %s', 'sem-packages'), "$1"), $changelog);
				$changelog = markdown($changelog);
				return $changelog;
			}
		} while ( $changelog );
		
		return;
	} # changelog()
} # sem_packages

global $wpdb;

if ( defined('SEM_PACKAGES') )
	$wpdb->packages = SEM_PACKAGES;
else
	$wpdb->packages = 'packages';

add_filter('mediacaster_file', array('sem_packages', 'download'), 10, 2);
add_shortcode('changelog', array('sem_packages', 'changelog'));
?>