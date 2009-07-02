<?php

class sem_admin_menu_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_admin_menu_admin', 'add_admin_page'));
	} # init()


	#
	# add_admin_page()
	#

	function add_admin_page()
	{
		if ( !function_exists('get_site_option') )
		{
			add_options_page(
				__('Admin&nbsp;Menu', 'sem-admin-menu'),
				__('Admin&nbsp;Menu', 'sem-admin-menu'),
				'manage_options',
				str_replace("\\", "/", __FILE__),
				array('sem_admin_menu_admin', 'display_admin_page')
				);
		}
	} # end add_admin_page()


	#
	# update_options()
	#

	function update_options()
	{
		check_admin_referer('admin_menu');
		#echo '<pre>';
		#var_dump($_POST);
		#echo '</pre>';

		$options = array(
			'always_on' => isset($_POST['always_on'])
			);

		update_option('sem_admin_menu_params', $options);
	} # end update_options()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		echo '<form method="post" action="">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('admin_menu');

		if ( $_POST['update_admin_menu_options'] )
		{
			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.', 'sem-admin-menu')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}
	?><div class="wrap">
		<h2><?php echo __('Admin Menu options', 'sem-admin-menu'); ?></h2>
	<?php
		if ( $_POST['update_admin_menu_options'] )
		{
			sem_admin_menu_admin::update_options();
		}

	?><input type="hidden" name="update_admin_menu_options" value="1" />
	<?php
		$options = get_settings('sem_admin_menu_params');

		if ( !$options )
		{
			$options = array(
				'always_on' => true
				);

			update_option('sem_admin_menu_params', $options);
		}


		echo '<p><label for="always_on">'
			. '<input type="checkbox"'
				. ' id="always_on" name="always_on"'
				. ( ( !isset($options['always_on']) || $options['always_on'] )
				? ' checked="checked"'
				: ''
				)
				. ' />'
			. '&nbsp;'
			. __('Show menu when registrations are turned off', 'sem-admin-menu')
			. '</label>'
			. '</p>';

	?>	<p class="submit">
		<input type="submit"
			value="<?php echo __('Update Options', 'sem-admin-menu'); ?>"
			 />
		</p>
	</div>
	<?php
	} # end display_admin_page()
} # sem_admin_menu_admin

sem_admin_menu_admin::init();
?>