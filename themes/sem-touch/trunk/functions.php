<?php
if ( !defined('sem_debug') )
	define(sem_debug, true);

if ( !isset($content_width) )
	$content_width = 450;

define('sem_version', '0.1');
define('sem_path', get_stylesheet_directory());
define('sem_url', get_stylesheet_directory_uri());

load_theme_textdomain('sem-touch', TEMPLATEPATH . '/lang');

add_custom_background();

define('HEADER_TEXTCOLOR', '');
define('HEADER_IMAGE', '');
define('HEADER_IMAGE_WIDTH', 960);
define('HEADER_IMAGE_HEIGHT', 80);

add_custom_image_header(array('sem_utils', 'header_css'), array('sem_admin', 'header_css'));

register_nav_menus(array(
	'header' => __('Header Navigation', 'sem-touch'),
	'footer' => __('Footer Navigation', 'sem-touch'),
	));

register_sidebar(array(
	'name' => __('Sidebar', 'sem-touch'),
	'id' => 'sidebar',
	'description' => __('The sidebar area', 'sem-touch'),
	'before_widget' => '<div id="%1$s" class="widget %2$s">' . "\n"
					. '<div class="widget_bg pad">' . "\n",
	'after_widget' => '</div>' . "\n"
					. '</div>' . "\n",
	'before_title' => '<h2 class="widget-title">',
	'after_title' => '</h2>',
	));

register_sidebar(array(
	'name' => __('Wide Footer Bar', 'sem-touch'),
	'id' => 'footer_boxes',
	'description' => __('The floating boxes beneath the content', 'sem-touch'),
	'before_widget' => '<div id="%1$s" class="footer_box %2$s">' . "\n"
					. '<div class="footer_box_bg pad">' . "\n",
	'after_widget' => '</div>' . "\n"
					. '</div>' . "\n",
	'before_title' => '<h2 class="widget-title">',
	'after_title' => '</h2>',
	));


include dirname(__FILE__) . '/inc/utils.php';
if ( is_admin() )
	include dirname(__FILE__) . '/inc/admin.php';
?>