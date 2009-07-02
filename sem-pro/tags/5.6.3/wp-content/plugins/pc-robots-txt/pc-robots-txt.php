<?php
/*
Plugin Name: PC Robots.txt
Plugin URI: http://petercoughlin.com/wp-plugins/
Description: Create and manage a virtual robots.txt file for your blog.
Version: 1.0 (fork)
Author: Peter Coughlin
Author URI: http://petercoughlin.com/
*/


function pc_robots_txt() {

	if ( strpos($_SERVER['REQUEST_URI'], '/robots.txt') !== false ) {

		$pc_robots_txt =  "# This file is produced by WordPress. You can make changes\n"
						. "# from the WordPress admin pages under Options, Robots.txt\n\n";

		$options = get_option('pc_robots_txt');

		if ( !is_array($options) ) {

			$options = pc_robots_txt_set_defaults();
		}

		if ( $options['user_agents'] != '' ) {

			$pc_robots_txt .= stripslashes($options['user_agents']);
		}

		# if there's an existing sitemap.xml file or we're also using
		# the Arne Brachhold sitemap plugin, add a reference to the robots.txt file
		if ( function_exists('sm_serve_sitemap') || file_exists($_SERVER['REQUEST_URI'] . "/sitemap.xml") ) {

			$pc_robots_txt .= "\n\nSitemap: " . "http://" . $_SERVER['HTTP_HOST'] . "/sitemap.xml";
		}

		header('Content-type: text/plain; charset=UTF-8');
		echo trim($pc_robots_txt);
		exit;

	}# end if ( strpos($_SERVER['REQUEST_URI'], '/robots.txt') !== false ) {

}# end function pc_robots_txt()


function pc_robots_txt_set_defaults() {

	$options = array(
		"user_agents" => "User-agent: *\n"
		. "Disallow: /wp-\n"
		. "Disallow: /cgi-bin\n"
		. "Disallow: /wp-admin\n"
		. "Disallow: /wp-includes\n"
		. "Disallow: /wp-content/plugins\n"
		. "Disallow: /wp-content/cache\n"
		. "Disallow: /wp-content/themes\n"
		. "Disallow: /wp-login.php\n"
		. "Disallow: /wp-register.php\n"
		. "Disallow: /feed\n"
		. "Disallow: /trackback\n"
		. "Disallow: /comments\n"
		. "Disallow: /category/*/*\n"
		. "Disallow: */trackback\n"
		. "Disallow: */feed\n"
		. "Disallow: */comments\n"
		. "Disallow: /*?*\n"
		. "Disallow: /*?\n"
		. "Allow: /wp-content/uploads\n"
		. "Allow: /media\n"
		. "\n# Google Image\n"
		. "User-agent: Googlebot-Image\n"
		. "Disallow:\n"
		. "Allow: /*\n"
		. "\n# Google AdSense\n"
		. "User-agent: Mediapartners-Google*\n"
		. "Disallow:\n"
		. "Allow: /*\n"
		. "\n# Internet Archiver Wayback Machine\n"
		. "User-agent: ia_archiver\n"
		. "Disallow: /\n"
		. "\n# digg mirror\n"
		. "User-agent: duggmirror\n"
		. "Disallow: /\n"
		);
	
	update_option('pc_robots_txt', $options);

	return $options;

}# end function pc_robots_txt_set_defaults() {


function pc_robots_txt_init() {

	if ( get_option('blog_public') == '1' ) {
		
		remove_action('do_robots','do_robots');
		remove_action('template_redirect', 'redirect_canonical');
		add_action('template_redirect', 'pc_robots_txt');
	}

	if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
		include_once dirname(__FILE__) . '/pc-robots-txt-admin.php';

}# end function pc_robots_txt_init() {

add_action('init', 'pc_robots_txt_init');
?>