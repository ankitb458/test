<?php
#
# This is the admin page for the PC Robots.txt plugin
#

function pc_robots_txt_admin_menu() {
	
	if ( function_exists('add_options_page') ) {

		#add_options_page(page_title, menu_title, access_level/capability, file, [function]);
		add_options_page('Robots.txt', 'Robots.txt', 'manage_options', str_replace("\\", "/", __FILE__), 'pc_robots_txt_options');
	}

}# end function pc_robots_txt_admin_menu() {


function pc_robots_txt_options() {

	$options = get_option('pc_robots_txt');
	if ( !is_array($options) )
		$options = pc_robots_txt_set_defaults();

	if ( isset($_POST['update']) ){

		check_admin_referer('pc_robots_txt_options');

		$options['user_agents'] = $_POST['user_agents'];

		update_option('pc_robots_txt', $options);

		echo "<div id=\"message\" class=\"updated fade\"><p>Settings saved.</p></div>";
	}

	if ( isset($_POST['reset']) ) {

		check_admin_referer('pc_robots_txt_options');

		$options = pc_robots_txt_set_defaults();

		echo "<div id=\"message\" class=\"updated fade\"><p>Settings reset to defaults.</p></div>";
	}

	echo '<div class="wrap">'
		. '<h2>PC Robots.txt Options</h2>'
		. '<form method="post">';
	if ( function_exists('wp_nonce_field') ) wp_nonce_field('pc_robots_txt_options');
	echo '<h3>User Agents &amp; rules for this blog</h3>'
		. '<p>As a general rule, it\'s best to put the most specific rules at the top of the list and work down towards more generic rules. Put conditions that apply to all robots at the bottom of the list.</p><p>You can <a href="'; echo get_bloginfo('url') . '/robots.txt' . '" target="_blank">preview your robots.txt file</a> or visit <a href="http://www.robotstxt.org/" target="_blank" title="robotstxt.org">robotstxt.org</a> to find out more about the robots.txt standard.</p>'
		. '<p><table class="form-table">'
		. '<tr valign="top">'
			. '<td><textarea name="user_agents" id="user_agents" style="width:99%;height:200px;">' . stripslashes($options['user_agents']) . '</textarea></td>'
		. '</tr>'
		. '</table></p>'
		. '<p class="submit"><input type="submit" name="update" value="Save Changes &raquo;" /> &nbsp;  &nbsp; <input type="submit" name="reset" value="Restore Defaults &raquo;" /></p>'
		. '</form>'
		. '</div>';

}# end function pc_robots_txt_options() {

add_action('admin_menu', 'pc_robots_txt_admin_menu');
?>