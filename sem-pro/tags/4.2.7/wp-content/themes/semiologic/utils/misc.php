<?php
#
# remove_wysiwyg_junk()
#

function remove_wysiwyg_junk($content)
{
	return preg_replace(
		"~
			<\s*(?:p|div|noscript)	# p, div or noscript tag
			(?:\s[^>]*)?			# optional attributes
			/\s*>					# />
		~ix",
		"",
		$content
		);
} # end remove_wysiwyg_junk()

add_filter('the_content', 'remove_wysiwyg_junk', 1000);


#
# kill_template_host()
#

function kill_template_host($dir)
{
	$dir = preg_replace("/^https?:\/\/" . $_SERVER['HTTP_HOST'] . "/", "", $dir);

	return $dir;
} # end kill_host()

add_filter('template_directory_uri', 'kill_template_host');


#
# fix_br()
#

function fix_br($buffer)
{
	return preg_replace("/<br\s*\/?>/i", "<br />", $buffer);
} # end fix_br()


#
# start_fix_br()
#

function start_fix_br()
{
	ob_start('fix_br');
} # end start_fix_br()

add_action('template_redirect', 'start_fix_br', -10000);


#
# prev_next_page_link()
#

function prev_next_page_link()
{
	if ( !is_single() && !is_page() )
	{
		echo '<div class="prev_next_page">';
		posts_nav_link(' &bull; ', '&laquo;&nbsp;' . get_caption('previous_page'), get_caption('next_page') . '&nbsp;&raquo;');
		echo '</div>';
	}
} # prev_next_page_link()

add_action('after_the_entries', 'prev_next_page_link', 0);


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


# backward compat

if ( !defined('use_post_type_fixed') )
{
	define(
		'use_post_type_fixed',
			version_compare(
				'2.1',
				$GLOBALS['wp_version'], '<='
				)
			||
			function_exists('get_site_option')
		);
}

?>