<?php
/*
Plugin Name: PC Robots.txt
Plugin URI: http://petercoughlin.com/wp-plugins/
Description: Create and manage a virtual robots.txt file for your blog.
Version: 1.2 fork
Author: Peter Coughlin
Author URI: http://petercoughlin.com/
*/


function pc_robots_txt() {

	if ( strpos($_SERVER['REQUEST_URI'], '/robots.txt') !== false ) {
		$options = get_option('pc_robots_txt');
		
		if ( !is_array($options) || !$options['user_agents'] )
			$options = pc_robots_txt_set_defaults();
		
		$pc_robots_txt = stripslashes($options['user_agents']);
		
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo trim($pc_robots_txt) . "\n";
		
		do_action( 'do_robotstxt' );
	}# end if ( strpos($_SERVER['REQUEST_URI'], '/robots.txt') !== false ) {

}# end function pc_robots_txt()


function pc_robots_txt_set_defaults() {

	$options = array(
		"user_agents" => "User-agent: *\n"
		. "Disallow: /wp-*\n"
		. "Allow: /wp-content/uploads\n"
		);
	
	update_option('pc_robots_txt', $options);

	return $options;

}# end function pc_robots_txt_set_defaults() {


function pc_robots_txt_init() {

	if ( get_option('blog_public') == '1' ) {
		remove_action('do_robots', 'do_robots');
		add_action('do_robots', 'pc_robots_txt', -1000);
	}

	if ( is_admin() )
		include_once dirname(__FILE__) . '/pc-robots-txt-admin.php';

}# end function pc_robots_txt_init() {

add_action('init', 'pc_robots_txt_init');
?>