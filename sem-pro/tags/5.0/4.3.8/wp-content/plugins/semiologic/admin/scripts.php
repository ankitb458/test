<?php

#
# update_theme_scripts()
#

function update_theme_scripts()
{
	check_admin_referer('sem_scripts');

	if ( !current_user_can('unfiltered_html') )
	{
		return;
	}

	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$options = get_option('semiologic');

	$options['scripts']['head'] = stripslashes($_POST['head_scripts']);
	$options['scripts']['onload'] = stripslashes($_POST['onload_scripts']);

	update_option('semiologic', $options);

	$GLOBALS['semiologic'] = $options;
} # end update_theme_scripts()

add_action('update_theme_scripts', 'update_theme_scripts');
?>