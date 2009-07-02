<?php
class sem_api
{
	#
	# init()
	#

	function init()
	{
		if ( current_user_can('administrator') )
		{
			add_action('admin_menu', array('sem_api', 'admin_menu'));
		}
	} # init()


	#
	# admin_menu()
	#

	function admin_menu()
	{
		add_submenu_page(
			'themes.php',
			__('API Key'),
			__('API Key'),
			'administrator',
			basename(__FILE__),
			array('sem_api', 'admin_page')
			);
	} # admin_menu()


	#
	# admin_page()
	#

	function admin_page()
	{
		if ( !empty($_POST)
			&& isset($_POST['action'])
			&& $_POST['action'] == 'update_theme_api_options'
			)
		{
			sem_api::update();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		global $sem_options;

		echo '<form method="post" action="">';

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_api');

		echo '<input type="hidden"'
			. ' name="action"'
			. ' value="update_theme_api_options"'
			. ' />';

		echo '<div class="wrap">'
			. '<h2>' . __('Semiologic API Key') . '</h2>';

		echo '<p>'
			. __('Entering your API key will let you take advantage of advanced <a href="http://www.getsemiologic.com">Semiologic Pro</a> features such as automated upgrades and documentation updates.')
			. '</p>';

		echo '<table width="100%" cellspacing="2" cellpadding="5" class="editform">';

		echo '<tr>'
			. '<th style="text-align: right; width: 160px;">'
			. '<label for="api_key">'
			. __('API Key:')
			. '</label>'
			. '</th>'
			. '<td>'
			. '<input type="text"'
				. ' style="width: 420px;"'
				. ' id="api_key" name="api_key"'
				. ' value="' . htmlspecialchars($sem_options['api_key']) . '"'
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ' />'
			. '</td>'
			. '</tr>'
			;

		echo '</table>';

		echo '<p>'
			. __('Note: Saving your API key will trigger a documentation update. This can take a minute.')
			. '</p>';

		echo '<div class="submit">';
		echo '<input type="submit" value="' . __('Update Options') . ' &raquo;" />';
		echo '</div>';

?>

<h3>API Key FAQ</h3>

<p>Semiologic Pro's terms of use are short and extremely liberal: You're granted unrestricted use of the software, <em>provided you're using it for yourself</em>. You additionally get a limited duration membership that entitles you to free updates and value-added services -- this is where your API key comes in.</p>

<p>You'll find your API key and membership details in semiologic.com's <a href="http://members.semiologic.com">members' area</a>.</p>

<p>Do not share your API key. It is equivalent to your username and password to the members' area. It lets you access and modify sensitive information related to your account. This includes your contact details and the paypal address to which your affiliate commissions are sent.</p>

<p>Your API key is personal. Do not use it for the benefit of others. There are no exceptions. There are no gray zones. If you (or your organization) aren't a site's primary user, that site should not be using your API key. If you wish to maintain Semiologic Pro sites for your customers, please <a href="mailto:sales@semiologic.com">sign up as a reseller</a> .</p>

<p>Lastly, please keep in mind that API key usage is monitored. API keys get locked without notice when suspicious activity is detected.</p>

<?php
		echo '<div style="clear: both;"></div>';

		echo '</div>';

		echo '</form>';
	} # admin_page()


	#
	# update()
	#

	function update()
	{
		check_admin_referer('sem_api');

		global $sem_options;

		$sem_options['api_key'] = $_POST['api_key'];

		if ( !preg_match("/^[0-9a-f]{32}$/", $sem_options['api_key']) )
		{
			$sem_options['api_key'] = '';
		}

		update_option('sem5_options', $sem_options);

		sem_docs::update(true);
	} # update()
} # end sem_api

sem_api::init();
?>