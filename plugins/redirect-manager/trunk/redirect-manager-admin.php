<?php

/**
 * redirect_manager_admin class
 *
 * @package Redirect Manager
 * @author Denis
 **/

add_action('admin_menu', array('redirect_manager_admin', 'meta_boxes'));
add_action('save_post', array('redirect_manager_admin', 'save_entry'));

class redirect_manager_admin {
	/**
	 * meta_boxes()
	 *
	 * adds meta boxes to post and page editors
	 *
	 * @return void
	 **/
	
	function meta_boxes() {
		if ( current_user_can('edit_posts') )
			add_meta_box('redirect_manager', __('Redirect', 'redirect-manager'), array('redirect_manager_admin', 'entry_editor'), 'post');
		
		if ( current_user_can('edit_pages') )
			add_meta_box('redirect_manager', __('Redirect', 'redirect-manager'), array('redirect_manager_admin', 'entry_editor'), 'page');
	} # meta_boxes()
	
	
	/**
	 * entry_editor($post)
	 *
	 * @param object $post the edited post
	 * @return void
	 * @author Denis
	 **/
	
	function entry_editor($post) {
		if ( $post->ID > 0 ) {
			$value = get_post_meta($post->ID, '_redirect_url', true);
		}
		
		echo '<p>'
			. '<input type="text" name="redirect_manager" size="58" tabindex="5" class="code" style="width: 90%;"'
				. ' value="' . attribute_escape($value) . '"'
				. ' />'
			. '</p>';
		
		echo '<p>'
			. __('Enter the url to which visitors of this entry should automatically be redirected.', 'redirect-manager')
			. '</p>';
	} # entry_editor()
	
	
	/**
	 * save_entry($post_id)
	 *
	 * @param int $post_id the entry's ID (zero for a draft)
	 * @return void
	 * @author Denis
	 **/
	
	function save_entry($post_id) {
		if ( wp_is_post_revision($post_id) )
			return;
		
		if ( current_user_can('edit_post', $post_id) ) {
			$value = $_POST['redirect_manager'];
			$value = stripslashes($value);
			$value = clean_url($value);
			
			if ( $value ) {
				update_post_meta($post_id, '_redirect_url', $value);
			} else {
				delete_post_meta($post_id, '_redirect_url');
			}
		}
	} # save_entry()
} # redirect_manager_admin
?>