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

	global $sem_options;

	$sem_options['scripts']['head'] = stripslashes($_POST['head_scripts']);
	$sem_options['scripts']['onload'] = stripslashes($_POST['onload_scripts']);

	update_option('sem5_options', $sem_options);
} # end update_theme_scripts()

add_action('update_theme_scripts', 'update_theme_scripts');



#
# save_entry_scripts()
#

function save_entry_scripts($post_ID)
{
	if ( !current_user_can('unfiltered_html') )
	{
		return;
	}

	foreach ( array('head', 'onload') as $key )
	{
		if ( isset($_POST[$key . '_scripts']) )
		{
			delete_post_meta($post_ID, '_' . $key);

			$value = stripslashes($_POST[$key . '_scripts']);

			if ( $value !== '' )
			{
				add_post_meta($post_ID, '_' . $key, $value, true);
			}
		}
	}
} # save_entry_scripts()

add_action('save_post', 'save_entry_scripts');
?>