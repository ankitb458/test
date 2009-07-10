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
	
	add_action('wp_print_scripts', array('auto_thickbox', 'add_scripts'));
	add_action('wp_print_styles', array('auto_thickbox', 'add_css'));
	
	add_action('wp_footer', array('auto_thickbox', 'add_thickbox_images'), 20);
	
	add_filter('filter_anchor', array('auto_thickbox', 'add_thickbox'));
}

class auto_thickbox {
	/**
	 * add_thickbox()
	 *
	 * @param array $anchor
	 * @return anchor $anchor
	 **/

	function add_thickbox($anchor) {
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
	} # add_thickbox()
	
	
	/**
	 * add_scripts()
	 *
	 * @return void
	 **/

	function add_scripts() {
		wp_enqueue_script('thickbox');
	} # add_scripts()
	
	
	/**
	 * add_css()
	 *
	 * @return void
	 **/

	function add_css() {
		wp_enqueue_style('thickbox');
	} # add_css()
	
	
	/**
	 * add_thickbox_images()
	 *
	 * @return void
	 **/

	function add_thickbox_images() {
		$includes_url = includes_url();
		
		$js = <<<EOS

<script type="text/javascript">
var tb_pathToImage = "{$includes_url}js/thickbox/loadingAnimation.gif";
var tb_closeImage = "{$includes_url}js/thickbox/tb-close.png";
</script>

EOS;
		
		echo $js;
	} # add_thickbox_images()
} # auto_thickbox
?>