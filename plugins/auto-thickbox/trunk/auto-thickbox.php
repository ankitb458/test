<?php
/*
Plugin Name: Auto Thickbox
Plugin URI: http://www.semiologic.com/software/auto-thickbox/
Description: Automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up).
Author: Denis de Bernardy
Version: 2.0 RC2
Author URI: http://www.getsemiologic.com
Text Domain: auto-thickbox
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


/**
 * auto_thickbox
 *
 * @package Auto Thickbox
 **/

if ( !is_admin() && strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator') === false ) {
	if ( !class_exists('anchor_utils') )
		include dirname(__FILE__) . '/anchor-utils/anchor-utils.php';
	
	action('wp_print_scripts', array('auto_thickbox', 'scripts'));
	action('wp_print_styles', array('auto_thickbox', 'styles'));
	
	action('wp_footer', array('auto_thickbox', 'thickbox_images'), 20);
	
	filter('filter_anchor', array('auto_thickbox', 'thickbox'));
}

class auto_thickbox {
	/**
	 * thickbox()
	 *
	 * @param array $anchor
	 * @return anchor $anchor
	 **/

	function thickbox($anchor) {
		if ( !preg_match("/\.(?:jpe?g|gif|png)\b/i", $anchor['attr']['href']) )
			return $anchor;
		
		if ( !preg_match("/^\s*<\s*img\s.+?>\s*$/is", $anchor['body']) )
			return $anchor;
		
		$anchor['attr']['class'][] = 'thickbox';
		$anchor['attr']['class'][] = 'no_icon';
		
		if ( in_the_loop() && !$anchor['attr']['rel'] )
			$anchor['attr']['rel'][] = 'gallery-' . get_the_ID();
		
		if ( empty($anchor['attr']['title']) ) {
			if ( preg_match("/\b(?:alt|title)\s*=\s*('|\")(.*?)\\1/i", $anchor['body'], $title) ) {
				$anchor['attr']['title'] = end($title);
			}
		}
		
		return $anchor;
	} # thickbox()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		wp_enqueue_script('thickbox');
	} # scripts()
	
	
	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		wp_enqueue_style('thickbox');
	} # styles()
	
	
	/**
	 * thickbox_images()
	 *
	 * @return void
	 **/

	function thickbox_images() {
		$includes_url = includes_url();
		
		echo <<<EOS

<script type="text/javascript">
var tb_pathToImage = "{$includes_url}js/thickbox/loadingAnimation.gif";
var tb_closeImage = "{$includes_url}js/thickbox/tb-close.png";
</script>

EOS;
	} # thickbox_images()
} # auto_thickbox
?>