<?php
/*
Plugin Name: AutoTag
Plugin URI: http://www.semiologic.com/software/autotag/
Description: Leverages Yahoo!'s term extraction web service to automatically tag your posts.
Version: 3 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: autotag-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('autotag', null, dirname(__FILE__) . '/lang');


/**
 * autotag
 *
 * @package AutoTag
 **/

add_action('admin_menu', array('autotag', 'meta_boxes'));

class autotag {
	/**
	 * meta_boxes()
	 *
	 * @return void
	 **/

	function meta_boxes() {
		add_meta_box('autotag', __('AutoTag', 'autotag'), array('autotag_admin', 'entry_editor'), 'post', 'normal');
	} # meta_boxes()
} # autotag


function load_autotag_admin() {
	include dirname(__FILE__) . '/autotag-admin.php';
}

foreach ( array('post.php', 'post-new.php', 'page.php', 'page-new.php') as $hook )
	add_action("load-$hook", 'load_autotag_admin');

if ( !function_exists('load_yterms') ) :
function load_yterms() {
	if ( !class_exists('yterms') )
		include dirname(__FILE__) . '/yterms/yterms.php';
}
endif;
?>