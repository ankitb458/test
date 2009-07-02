<?php
#
# add_semiologic_affiliate_admin()
#

function add_semiologic_affiliate_admin()
{
	if ( !function_exists('get_site_option') || is_site_admin() )
	{
		add_options_page(
				__('Semiologic&nbsp;Affiliate'),
				__('Semiologic&nbsp;Affiliate'),
				7,
				str_replace("\\", "/", __FILE__),
				'display_semiologic_affiliate_admin'
				);
	}
} # end add_semiologic_affiliate_admin()

add_action('admin_menu', 'add_semiologic_affiliate_admin');


#
# update_semiologic_affiliate_options()
#

function update_semiologic_affiliate_options()
{
	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$options = array(
		'aff_id' => trim(preg_replace("/[^0-9a-zA-Z_-]/", "", $_POST['aff_id']))
		);

	if ( function_exists('get_site_option') )
	{
		update_site_option('sem_semiologic_affiliate_params', $options);
	}
	else
	{
		update_option('sem_semiologic_affiliate_params', $options);
	}
} # end update_semiologic_affiliate_options()


#
# display_semiologic_affiliate_admin()
#

function display_semiologic_affiliate_admin()
{
?><form method="post" action="">
<?php
	if ( $_POST['update_semiologic_affiliate_options'] )
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
	<h2><?php echo __('Semiologic Affiliate options'); ?></h2>

	<p><a href="http://www.semiologic.com/partners/"><?php echo __('Semiologic Affiliate Program details'); ?></a></p>
<?php
	if ( $_POST['update_semiologic_affiliate_options'] )
	{
		update_semiologic_affiliate_options();
	}

?><input type="hidden" name="update_semiologic_affiliate_options" value="1" />
<?php
	$options = function_exists('get_site_option')
		? get_site_option('sem_semiologic_affiliate_params')
		: get_settings('sem_semiologic_affiliate_params');

	if ( !$options )
	{
		$options = array(
			'aff_id' => ''
			);

		if ( function_exists('get_site_option') )
		{
			update_site_option('sem_semiologic_affiliate_params', $options);
		}
		else
		{
			update_option('sem_semiologic_affiliate_params', $options);
		}
	}


		echo '<p>'
			. '<label for="aff_id">'
			. __('Your Affiliate ID') . ':'
			. '<br />'
			. '<input type="text"'
				. ' name="aff_id" id="aff_id"'
				. ' value="' . htmlspecialchars($options['aff_id'], ENT_QUOTES) . '"'
				. ' />'
			. '</label>'
			. '</p>';

?>	<p class="submit">
	<input type="submit"
		value="<?php echo __('Update Options'); ?>"
		 />
	</p>
</div>
<?php
} # end display_semiologic_affiliate_admin()
?>