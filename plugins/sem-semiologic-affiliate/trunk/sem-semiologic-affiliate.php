<?php
/*
Plugin Name: Semiologic Affiliate
Plugin URI: http://www.semiologic.com/software/sem-affiliate/
Description: Automatically adds your affiliate ID to all links to Semiologic.
Version: 1.9 beta
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-semiologic-affiliate-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('semiologic-affiliate', null, dirname(__FILE__) . '/lang');


/**
 * semiologic_affiliate
 *
 * @package Semiologic Affiliate
 **/

if ( !defined('semiologic_affiliate_debug') )
	define('semiologic_affiliate_debug', false);

add_action('admin_menu', array('semiologic_affiliate', 'admin_menu'));

if ( !is_admin() && semiologic_affiliate::get_campaign() ) {
	if ( !semiologic_affiliate_debug ) {
		add_action('wp_head', array('semiologic_affiliate', 'ob_start'), 1000);
	} else {
		add_filter('the_content', array('semiologic_affiliate', 'filter'));
		add_filter('comment_text', array('semiologic_affiliate', 'filter'));
	}
}

class semiologic_affiliate {
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/

	function admin_menu() {
		add_options_page(
			__('Semiologic Affiliate', 'semiologic-affiliate'),
			__('Semiologic Affiliate', 'semiologic-affiliate'),
			'manage_options',
			'semiologic_affiliate',
			array('semiologic_affiliate_admin', 'edit_options')
			);
	} # admin_menu()
	
	
	/**
	 * get_campaign()
	 *
	 * @return string $aff_id
	 **/

	function get_campaign() {
		$o = get_option('sem_campaign_key');
		
		if ( $o === false )
			$o = semiologic_affiliate::init_campaign();
		
		return $o;	
	} # get_campaign()
	
	
	/**
	 * init_campaign()
	 *
	 * @return string $aff_id
	 **/

	function init_campaign() {
		$o = get_option('sem_semiologic_affiliate_params');
		
		if ( $o !== false ) {
			$o = isset($options['aff_id']) ? trim($options['aff_id']) : '';
			delete_option('sem_semiologic_affiliate_params');
		} else {
			$o = '';
		}
		
		update_option('sem_campaign_key', $o);
		
		return $o;
	} # init_campaign()
	
	
	/**
	 * ob_start()
	 *
	 * @return void
	 **/

	function ob_start() {
		ob_start(array('semiologic_affiliate', 'filter'));
	} # ob_start()
	
	
	/**
	 * filter()
	 *
	 * @param string $buffer
	 * @return string $buffer
	 **/

	function filter($str) {
		return preg_replace_callback("/<a\s+(.*?)>/ix", array('semiologic_affiliate', 'filter_callback'), $str);
	} # filter()
	
	
	/**
	 * filter_callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function filter_callback($match) {
		preg_match("/\bhref=([\"'])(.+?)\\1/i", $match[1], $href);
		
		if ( !preg_match("/\b(?:get)?semiologic\.com\b/i", $href[0]) )
			return $match[0];
		
		$raw_href = current($href);
		$new_href = end($href);
		
		$campaign_key = semiologic_affiliate::get_campaign();
		
		if ( strpos($new_href, '?') === false )
			$new_href .= '?aff=' . urlencode($campaign_key);
		
		return str_replace($raw_href, 'href="' . $new_href . '"', $match[0]);
	} # filter_callback()
} # semiologic_affiliate


function semiologic_affiliate_admin() {
	include dirname(__FILE__) . '/sem-semiologic-affiliate-admin.php';
}

add_action('load-settings_page_semiologic_affiliate', 'semiologic_affiliate_admin');
?>