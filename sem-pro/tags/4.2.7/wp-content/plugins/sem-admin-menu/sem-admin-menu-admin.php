<?php
#
# add_admin_menu_admin()
#

function add_admin_menu_admin()
{
	if ( !function_exists('get_site_option') )
	{
		add_options_page(
			__('Admin&nbsp;Menu'),
			__('Admin&nbsp;Menu'),
			7,
			str_replace("\\", "/", __FILE__),
			'display_admin_menu_admin'
			);
	}
} # end add_admin_menu_admin()

add_action('admin_menu', 'add_admin_menu_admin');


#
# update_admin_menu_options()
#

function update_admin_menu_options()
{
	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$options = array(
		'always_on' => isset($_POST['always_on'])
		);

	update_option('sem_admin_menu_params', $options);
} # end update_admin_menu_options()


#
# display_admin_menu_admin()
#

function display_admin_menu_admin()
{
?><form method="post" action="">
<?php
	if ( $_POST['update_admin_menu_options'] )
	{
		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}
?><div class="wrap">
	<h2><?php echo __('Admin Menu options'); ?></h2>
<?php
	if ( $_POST['update_admin_menu_options'] )
	{
		update_admin_menu_options();
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
		. __('Show menu even when registrations are off')
		. '</label>'
		. '</p>';

?>	<p class="submit">
	<input type="submit"
		value="<?php echo __('Update Options'); ?>"
		 />
	</p>
</div>
<?php
} # end display_admin_menu_admin()
?>