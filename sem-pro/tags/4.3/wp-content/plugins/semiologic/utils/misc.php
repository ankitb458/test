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
		"\" class=\"more\"",
		$content
		);
} # end remove_more_junk()

add_filter('the_content', 'remove_more_junk', 1000);


#
# sem_export_blog()
#

function sem_export_blog()
{
	if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'export'
		&& !function_exists('get_site_option')
		)
	{
		include_once ABSPATH . 'wp-content/plugins/semiologic/wizards/clone/export.php';
		export_semiologic_config();
	}
} # end sem_export_blog()

add_action('template_redirect', 'sem_export_blog');
?>