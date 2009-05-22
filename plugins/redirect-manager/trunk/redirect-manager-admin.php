<?php
/**
 * redirect_manager_admin class
 *
 * @package Redirect Manager
 **/

add_action('save_post', array('redirect_manager_admin', 'save_entry'));

class redirect_manager_admin {
	/**
	 * edit_entry()
	 *
	 * @param object $post
	 * @return void
	 **/
	
	function edit_entry($post) {
		if ( $post->ID > 0 ) {
			$value = get_post_meta($post->ID, '_redirect_url', true);
		}
		
		echo '<p>'
			. '<input type="text" name="redirect_manager" size="58" tabindex="5" class="code" style="width: 90%;"'
				. ' value="' . esc_attr($value) . '"'
				. ' />'
			. '</p>';
		
		echo '<p>'
			. __('Enter the url to which visitors of this entry should automatically be redirected.', 'redirect-manager')
			. '</p>';
	} # edit_entry()
	
	
	/**
	 * save_entry()
	 *
	 * @param int $post_id
	 * @return void
	 **/
	
	function save_entry($post_id) {
		if ( wp_is_post_revision($post_id) )
			return;
		
		if ( current_user_can('edit_post', $post_id) ) {
			$value = $_POST['redirect_manager'];
			$value = stripslashes($value);
			$value = esc_url_raw($value);
			$value = addslashes($value);
			
			if ( $value ) {
				update_post_meta($post_id, '_redirect_url', $value);
			} else {
				delete_post_meta($post_id, '_redirect_url');
			}
		}
	} # save_entry()
} # redirect_manager_admin
?>