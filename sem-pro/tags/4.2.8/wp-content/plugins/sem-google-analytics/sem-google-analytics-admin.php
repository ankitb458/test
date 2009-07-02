<?php
class sem_google_analytics_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_google_analytics_admin', 'add_option_page'));
	} # init()


	#
	# add_option_page()
	#

	function add_option_page()
	{
		if ( !function_exists('get_site_option') || is_site_admin() )
		{
			add_options_page(
					__('Google&nbsp;Analytics'),
					__('Google&nbsp;Analytics'),
					7,
					str_replace("\\", "/", __FILE__),
					array('sem_google_analytics_admin', 'display_options')
					);
		}
	} # add_option_page()


	#
	# update_options()
	#

	function update_options()
	{
		$_POST['sem_google_analytics']['script'] = stripslashes($_POST['sem_google_analytics']['script']);

		if ( preg_match("/
					\b
					_uacct
					\s*
					=
					\s*
					\"
					(?:
						your_id
					|
					)
					\"
				/iux",
				$_POST['sem_google_analytics']['script']
				)
			)
		{
			$_POST['sem_google_analytics']['script'] = false;
		}

		if ( function_exists('get_site_option') && is_site_admin() )
		{
			update_site_option('sem_google_analytics_params', $_POST['sem_google_analytics']);
		}
		elseif ( !function_exists('get_site_option') )
		{
			update_option('sem_google_analytics_params', $_POST['sem_google_analytics']);
		}
	} # update_options()


	#
	# display_options()
	#

	function display_options()
	{
		# Process updates, if any

		if ( isset($_POST['action'])
			&& ( $_POST['action'] == 'update_sem_google_analytics' )
			)
		{
			sem_google_analytics_admin::update_options();

			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Options saved.', 'sem-google-analytics')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}

		$options = sem_google_analytics::get_options();

		if ( !$options['script'] )
		{
			$options['script'] = <<<EOF
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "your_id";
urchinTracker();
</script>
EOF;
		}

		# Display admin page

		echo '<div class="wrap">' . "\n"
			. "<h2>" . __('Google Analytics Options', 'sem-google-analytics') . "</h2>\n"
			. '<form method="post" action="">' . "\n"
			. '<input type="hidden" name="action" value="update_sem_google_analytics" />' . "\n";

		echo '<fieldset class="options">' . "\n"
			. "<legend>" . __('Google analytics script', 'sem-google-analytics') . "</legend>\n";

		echo '<p style="padding-bottom: 6px;">'
				. '<label for="script">'
				. __('Paste the generic <a href="http://analytics.google.com">Google analytics</a> script into the following textarea:', 'sem-google-analytics')
				. '</label></p>' ."\n"
				. '<textarea id="script" name="sem_google_analytics[script]"'
					. ' style="width: 590px; height: 240px;">'
				. htmlspecialchars($options['script'], ENT_QUOTES)
				. "</textarea>\n";

		echo "</fieldset>\n";

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options', 'sem-google-analytics') . '"'
				. " />"
			. "</p>\n";

		echo "</form>\n";

		echo "<h2>" . __('Google Analytics Options', 'sem-google-analytics') . "</h2>\n"
			. '<p>' . __('The following will work out of the box:', 'sem-google-analytics') . "</p>\n"
			. '<ul>'
			. '<li>' . __('Authors, editors and site admins are not tracked when logged in') . '</li>'
			. '<li>' . __('Outbound links are tracked as /outbound/[url]?ref=[referrer]') . '</li>'
			. '<li>' . __('File downloads are tracked as /file/[file]?ref=[referrer]') . '</li>'
			. '<li>' . __('<a href="http://www.semiologic.com/software/newsletter-manager/">Mailing list subscriptions</a> are tracked as /subscription/[referrer]') . '</li>'
			. '<li>' . __('Search queries are tracked as /search/[keyword]?ref=[referrer]') . '</li>'
			. '<li>' . __('404 errors (page not found) are tracked as /404/[url]?ref=[referrer]') . '</li>'
			. '</ul>';

		echo "</div>\n";
	} # display_options()
} # sem_google_analytics_admin

sem_google_analytics_admin::init();
?>