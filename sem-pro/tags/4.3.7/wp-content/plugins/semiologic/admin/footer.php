<?php

#
# update_theme_footer()
#

function update_theme_footer()
{
	check_admin_referer('sem_footer');

	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$options = get_option('semiologic');
	$options['extra_footer'] = stripslashes($_POST['extra_footer']);
	if ( !current_user_can('unfiltered_html') )
	{
		$options['extra_footer'] = stripslashes(wp_filter_post_kses($options['extra_footer']));
	}
	$options['theme_credits'] = isset($_POST['show_credits']);
	update_option('semiologic', $options);

	$GLOBALS['semiologic'] = $options;
} # end update_theme_footer()

add_action('update_theme_footer', 'update_theme_footer');
?>