<?php

class redirect_manager_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('sem_api_key_protected', array('redirect_manager_admin', 'sem_api_key_protected'));

		add_action('admin_menu', array('redirect_manager_admin', 'meta_boxes'));
	} # init()


	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/marketing/redirect-manager/redirect-manager.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# meta_boxes()
	#
	
	function meta_boxes()
	{
		if ( current_user_can('edit_pages') || current_user_can('publish_posts') )
		{
			add_meta_box('redirect_manager', 'Redirect', array('redirect_manager_admin', 'entry_editor'), 'post');
			add_meta_box('redirect_manager', 'Redirect', array('redirect_manager_admin', 'entry_editor'), 'page');
			add_action('save_post', array('redirect_manager_admin', 'save_entry'));
		}
	} # meta_boxes()
	
	
	#
	# entry_editor()
	#
	
	function entry_editor()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];
		
		$value = '';
		
		if ( $post_ID > 0 )
		{
			$value = get_post_meta($post_ID, '_redirect_url', true);
		}
		
		echo '<p>'
			. '<input type="text" name="redirect_manager" size="58" tabindex="5" class="code" style="width: 90%;"'
				. ' value="' . attribute_escape($value) . '"'
				. ' />'
			. '</p>';
		
		echo '<p>'
			. 'Enter the url to which visitors of this entry should automatically be redirected.'
			. '</p>';
	} # entry_editor()
	

	#
	# save_entry()
	#

	function save_entry($post_ID)
	{
		$post = get_post($post_ID);
		
		if ( $post->post_type == 'revision' ) return;
		
		if ( current_user_can('edit_pages') || current_user_can('publish_posts') )
		{
			delete_post_meta($post_ID, '_redirect_url');
			
			$value = stripslashes($_POST['redirect_manager']);
			$value = strip_tags($value);
			$value = trim($value);
			
			if ( $value )
			{
				add_post_meta($post_ID, '_redirect_url', $value, true);
			}
		}
	} # save_entry()
} # redirect_manager_admin

redirect_manager_admin::init();

?>