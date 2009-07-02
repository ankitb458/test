<?php
#
# remove_frontpage_junk()
#

function remove_frontpage_junk($content)
{
	return preg_replace(
		"~
			^.*
			<\s*body		# <body
			(?:\s[^>]*)?	# optional attributes
			>				# >
			(.*)
			<\s*/body\s*>	# </body>
			.*$
		~isx",
		"$1",
		$content
		);
} # end remove_frontpage_junk()

add_filter('the_content', 'remove_frontpage_junk', 0);


#
# remove_more_junk()
#

function remove_more_junk($content)
{
	return preg_replace(
		"~
			\#more-\d+\"
		~ix",
		"\"",
		$content
		);
} # end remove_more_junk()

add_filter('the_content', 'remove_more_junk', 1000);


#
# sem_export_blog()
#

function sem_export_blog($bool)
{
	if ( $_REQUEST['method'] == 'export' )
	{
		include_once sem_pro_path . '/inc/clone/export.php';
	}

	return $bool;
} # end sem_export_blog()

add_filter('option_gzipcompression', 'sem_export_blog', -1000000);


if ( isset($_GET['send_diagnosis']) )
{
	if ( current_user_can('administrator') )
	{
		include_once sem_pro_path . '/inc/diagnosis.php';
	}
	else
	{

		add_action('init', create_function('', '
			header("HTTP/1.1 301 Moved Permanently");
	        header("Status: 301 Moved Permanently");
			wp_redirect(get_option("home"));
			'));
	}
}

if ( isset($_GET['add_stops']) )
{
	if ( current_user_can('administrator') )
	{
		add_action('option_gzipcompression', create_function('$in', '
			return add_stop($in, "WP Loaded");
			'), 10000000);

		add_action('wp_footer', create_function('$in', '
			return add_stop($in, "Page Loaded");
			'), 10000000);
	}
	else
	{

		add_action('init', create_function('', '
			header("HTTP/1.1 301 Moved Permanently");
	        header("Status: 301 Moved Permanently");
			wp_redirect(get_option("home"));
			'));
	}
}


#
# add_stops()
#

function add_stops()
{
	add_filter('option_gzipcompression', 'add_stop', 1000000);
	add_action('wp_footer', 'add_stop', 1000000);
} # add_stops()


if ( isset($simple_tags) ) :

#
# sem_option_simpletags()
#

function sem_option_simpletags($options)
{
	$options['inc_page_tag_search'] = 1;
	$options['use_tag_pages'] = 1;
	$options['use_tag_links'] = 0;

	return $options;
} # sem_option_simpletags()

add_action('option_simpletags', 'sem_option_simpletags');

$simple_tags->SimpleTags();

endif;


?>