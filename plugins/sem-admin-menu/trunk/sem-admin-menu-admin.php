<?php
/**
 * sem_amdin_menu_admin
 *
 * @package Admin Menu
 **/

add_action('settings_page_admin-menu', array('sem_admin_menu_admin', 'save_options'));
add_action('submitpage_box', array('sem_admin_menu_admin', 'set_parent_id'), 0);

class sem_admin_menu_admin {
	/**
	 * set_parent_id()
	 *
	 * @return void
	 **/
	
	function set_parent_id() {
		if ( isset($_GET['parent_id']) && $GLOBALS['editing'] ) {
			global $post;
			$post->post_parent = intval($_GET['parent_id']);
		}
	} # set_parent_id()
	
	
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST ) return;
		
		check_admin_referer('admin_menu');
		
		$options = array(
			'always_on' => isset($_POST['always_on'])
			);
		
		update_option('sem_admin_menu', $options);
		
		echo '<div class="updated fade">' . "\n"
			. '<p>'
				. '<strong>'
				. __('Settings saved.', 'sem-admin-menu')
				. '</strong>'
			. '</p>' . "\n"
			. '</div>' . "\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		echo '<div class="wrap">' . "\n"
			. '<form method="post" action="">';

		wp_nonce_field('admin_menu');

		echo '<h2>' . __('Admin Menu Settings', 'sem-admin-menu') . '</h2>' . "\n";
		
		$options = sem_admin_menu::get_options();
		
		echo '<table class="form-table">' . "\n";
		
		echo '<tr>' . "\n"
			. '<td>'
			. '<label for="always_on">'
			. '<input type="checkbox"'
				. ' id="always_on" name="always_on"'
				. ( $options['always_on']
				? ' checked="checked"'
				: ''
				)
				. ' />'
			. '&nbsp;'
			. __('Display a menu bar with a login link when I am logged out.', 'sem-admin-menu')
			. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . attribute_escape(__('Save Changes', 'sem-admin-menu')) . '"'
				. ' />'
			. '</p>' . "\n";
		
		echo '</form>' . "\n"
			. '</div>' . "\n";
	} # edit_options()
} # sem_admin_menu_admin
?>