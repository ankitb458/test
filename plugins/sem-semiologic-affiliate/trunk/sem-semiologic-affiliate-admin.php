<?php
#
# add_semiologic_affiliate_admin()
#

function add_semiologic_affiliate_admin()
{
	if ( !function_exists('is_site_admin') || is_site_admin() )
	{
		add_options_page(
				__('Semiologic&nbsp;Affiliate'),
				__('Semiologic&nbsp;Affiliate'),
				'manage_options',
				__FILE__,
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
	check_admin_referer('sem_affiliate');

	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';

	$aff_id = $_POST['aff_id'];

	if ( preg_match("/^http:\/\/www\.getsemiologic.com\?aff=(.+)/i", $aff_id, $match) )
	{
		$aff_id = end($match);
	}

	$aff_id = trim(preg_replace("/[^0-9a-zA-Z_-]/", "", $aff_id));

	$options = array(
		'aff_id' => $aff_id
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
	echo '<form method="post" action="">';

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_affiliate');

	if ( $_POST['update_semiologic_affiliate_options'] )
	{
		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Settings saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}
?><div class="wrap">
	<h2><?php echo __('Semiologic Affiliate Settings'); ?></h2>

<?php
	if ( $_POST['update_semiologic_affiliate_options'] )
	{
		update_semiologic_affiliate_options();
	}

?><input type="hidden" name="update_semiologic_affiliate_options" value="1" />
<?php
	$options = get_option('sem_semiologic_affiliate_params');

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


	echo '<table class="form-table">';
	
	echo '<tr>'
		. '<th scope="row">'
		. '<label for="aff_id">'
		. __('Your Affiliate ID')
		. '</label>'
		. '</th>'
		. '<td>'
		. '<label for="aff_id">'
		. 'http://www.getsemiologic.com?aff=<input type="text"'
			. ' name="aff_id" id="aff_id"'
			. ' value="' . htmlspecialchars($options['aff_id'], ENT_QUOTES) . '"'
			. ' />'
		. '</label>'
		. '<p><a href="http://www.semiologic.com/partners/">' . __('Semiologic Affiliate Program details') . '</a></p>'
		. '</td>'
		. '</tr>'
		. '</table>';

?>	<p class="submit">
	<input type="submit"
		value="<?php echo esc_attr(__('Save Changes')); ?>"
		 />
	</p>
</div>
<?php
} # end display_semiologic_affiliate_admin()
?>