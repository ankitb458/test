<?php

class sem_seo_pro
{
	#
	# init()
	#

	function init()
	{
		add_action('save_post', array('sem_seo_pro', 'save_entry_seo'));

		add_action('update_theme_seo_options', array('sem_seo_pro', 'save_theme_seo'));
	} # init


	#
	# save_entry_seo()
	#

	function save_entry_seo($post_ID)
	{
		#echo '<pre>';
		#var_dump($post_ID);
		#echo '</pre>';

		if ( !isset($_REQUEST['comment_post_ID']) )
		{
			foreach ( array('title', 'keywords', 'description') as $key )
			{
				if ( isset($_POST['seo_' . $key]) )
				{
					delete_post_meta($post_ID, '_' . $key);

					$value = trim(strip_tags(stripslashes($_POST['seo_' . $key])));

					if ( $value !== '' )
					{
						add_post_meta($post_ID, '_' . $key, $value, true);
					}
				}
			}
		}

		return $post_ID;
	} # save_entry_seo()


	#
	# save_theme_seo()
	#

	function save_theme_seo()
	{
		check_admin_referer('sem_seo');

		global $sem_options;

		$sem_options['seo']['title'] = trim(stripslashes(strip_tags($_POST['seo_title'])));
		$sem_options['seo']['keywords'] = trim(stripslashes(strip_tags($_POST['seo_keywords'])));
		$sem_options['seo']['description'] = trim(stripslashes(strip_tags($_POST['seo_description'])));

		$sem_options['seo']['add_site_name'] = isset($_POST['seo_add_site_name']);

		$sem_options['theme_archives'] = isset($_POST['seo_theme_archives']);

		update_option('sem5_options', $sem_options);
	} # save_theme_seo()
} # sem_seo_pro

sem_seo_pro::init();
?>