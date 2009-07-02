<?php

#
# html2wp_kill_formatting()
#

function html2wp_kill_formatting()
{
	if ( get_post_meta($GLOBALS['post']->ID, '_kill_formatting', true) )
	{
		if ( class_exists('Markdown') )
		{
			remove_filter('the_content',     'Markdown', 6);
			remove_filter('get_the_excerpt', 'Markdown', 6);
		}
		else
		{
			remove_filter('the_content', 'wpautop');
			remove_filter('the_excerpt', 'wpautop');
		}

		remove_filter('the_content', 'wptexturize');
	}

	#echo '<pre>';
	#var_dump($GLOBALS['wp_filter']['the_content']);
	#echo '</pre>';
	#die();

	#reset_plugin_hook('get_the_content');
	#reset_plugin_hook('the_content');
} # html2wp_kill_formatting()

add_action('the_entry', 'html2wp_kill_formatting', -100000);


#
# html2wp_revive_formatting()
#

function html2wp_revive_formatting()
{
	if ( get_post_meta($GLOBALS['post']->ID, '_kill_formatting', true) )
	{
		if ( class_exists('Markdown') )
		{
			add_filter('the_content',     'Markdown', 6);
			add_filter('get_the_excerpt', 'Markdown', 6);
		}
		else
		{
			add_filter('the_content', 'wpautop');
			add_filter('the_excerpt', 'wpautop');
		}

		add_filter('the_content', 'wptexturize');
	}
} # html2wp_revive_formatting()

add_filter('after_the_entry', 'html2wp_revive_formatting', -10000);
?>