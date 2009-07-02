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

	global $sem_options;

	$sem_options['extra_footer'] = stripslashes($_POST['extra_footer']);

	if ( !current_user_can('unfiltered_html') )
	{
		$sem_options['extra_footer'] = stripslashes(wp_filter_post_kses($sem_options['extra_footer']));
	}

	$sem_options['theme_credits'] = isset($_POST['show_credits']);

	update_option('sem5_options', $sem_options);
} # end update_theme_footer()

add_action('update_theme_footer', 'update_theme_footer');


#
# save_entry_footer()
#

function save_entry_footer($post_ID)
{
	if ( !current_user_can('switch_themes') )
	{
		return;
	}

	if ( isset($_POST['entry_footer']) )
	{
		delete_post_meta($post_ID, '_footer');

		$value = stripslashes($_POST['entry_footer']);

		if ( !current_user_can('unfiltered_html') )
		{
			$value = stripslashes(wp_filter_post_kses($value));
		}

		if ( $value !== '' )
		{
			add_post_meta($post_ID, '_footer', $value, true);
		}
	}
} # save_entry_footer()

add_action('save_post', 'save_entry_footer');
?>