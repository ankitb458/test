<?php
#
# default_page_title()
#

function default_page_title()
{
	bloginfo('name');

	wp_title();
} # end default_page_title()

add_action('display_page_title', 'default_page_title');


#
# display_page_meta()
#

function display_page_meta()
{
?><meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
<link rel="alternate" type="application/rss+xml" title="<?php _e('RSS feed'); ?>" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php
} # end display_page_metat()

add_action('wp_head', 'display_page_meta', 20);


#
# display_print_meta()
#

function display_print_meta()
{
?><meta name="robots" content="noindex, nofollow" />
<link rel="stylesheet" type="text/css" href="<?php echo  get_stylesheet_directory_uri() . '/print.css'; ?>" />
<?php
} # display_print_meta()


#
# print_template()
#

function print_template()
{
	if ( isset($_GET['action']) && $_GET['action'] == 'print' )
	{
		add_filter('active_layout', 'force_m');
		add_filter('active_width', 'force_narrow');
		add_action('wp_head', 'display_print_meta');

		reset_plugin_hook('before_the_wrapper');
		reset_plugin_hook('before_the_header');
		reset_plugin_hook('display_header');
		reset_plugin_hook('display_navbar');
		reset_plugin_hook('after_the_header');
		reset_plugin_hook('before_the_entries');
		reset_plugin_hook('before_the_entry');
		reset_plugin_hook('after_the_entry');
		reset_plugin_hook('after_the_entries');
		reset_plugin_hook('before_the_footer');
		reset_plugin_hook('display_footer');
		reset_plugin_hook('after_the_footer');
		reset_plugin_hook('wp_footer');
		reset_plugin_hook('after_the_wrapper');

		include_once get_template_directory() . '/print.php';
		die();
	}
} # print_template()

add_action('template_redirect', 'print_template');
?>