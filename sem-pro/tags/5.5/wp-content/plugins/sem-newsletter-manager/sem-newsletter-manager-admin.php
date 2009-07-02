<?php

class sem_newsletter_manager_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('sem_newsletter_manager_admin', 'admin_menu'));
	} # init()


	#
	# upgrade_options()
	#

	function upgrade_options()
	{
		$options = get_option('sem_newsletter_params');

		$defaults = array(
			'email' => '',
			'syntax' => 'aweber',
			'redirect' => '',
			'captions' => array(
				'widget_title' => 'Newsletter',
				'widget_teaser' => '<p>Sign up to receive an occasional newsletter.</p>',
				'thank_you' => '<p>Thank you for subscribing!</p>',
				'your_name' => __('Your Name'),
				'your_email' => __('Your Email'),
				'sign_up' => __('Sign Up'),
				),
			'version' => 3
			);

		switch ( @ $options['version'] )
		{
		case null:
		case '':
			if ( $options === false )
			{
				$options = $defaults;
				break;
			}

			# replace add_subscribe with syntax
			switch ( @ $options['add_subscribe'] )
			{
			case 'aweber':
				$options['syntax'] = 'aweber';
				break;
			default:
				if ( !isset($options['add_subscribe']) || $options['add_subscribe'] )
				{
					$options['syntax'] = 'list-subscribe';
				}
				else
				{
					$options['syntax'] = 'list';
				}
				break;
			}
			unset($options['add_subscribe']);

			# captions
			$options['captions']['widget_title'] = $options['title'];
			$options['captions']['widget_teaser'] = $options['teaser'];
			$options['captions']['thank_you'] = $options['thanks'];
			$options['captions'] = array_merge($defaults['captions'], $options['captions']);
			unset($options['title']);
			unset($options['teaser']);
			unset($options['thanks']);

			# new defaults
			$options = array_merge($defaults, $options);

			# update version
			$options['version'] = 3;
		}

		update_option('sem_newsletter_manager_params', $options);

		return $options;
	} # upgrade_options()


	#
	# update_options()
	#

	function update_options()
	{
		$options = sem_newsletter_manager::get_options();

		$new_options = $options;

		foreach ( array('email', 'syntax', 'redirect') as $key )
		{
			$new_options[$key] = trim(strip_tags($_POST['newsletter'][$key]));
		}

		if ( !sem_newsletter_manager::check_email($new_options['email']) )
		{
			$new_options['email'] = '';
		}

		foreach ( array_keys($options['captions']) as $key )
		{
			$new_options['captions'][$key] = stripslashes(wp_filter_post_kses($_POST['newsletter']['caption'][ $key]));
		}

		if ( $new_options != $options )
		{
			update_option('sem_newsletter_manager_params', $new_options);
		}
	} # update_options()


	#
	# admin_menu()
	#

	function admin_menu()
	{
		add_options_page(
				__('Newsletter Manager'),
				__('Newsletter Manager'),
				'switch_themes',
				str_replace("\\", "/", __FILE__),
				array('sem_newsletter_manager_admin', 'admin_page')
				);
	} # admin_menu()


	#
	# admin_page()
	#

	function admin_page()
	{
		# Process updates, if any

		if ( $_POST['update_sem_newsletter_options'] )
		{
			check_admin_referer('newsletter_manager');

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		echo '<div class="wrap">' . "\n"
			. '<h2>' . __('Newsletter Manager Options') . '</h2>' . "\n"
			. '<form method="post" action="">' . "\n";

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('newsletter_manager');

		sem_newsletter_manager_admin::widget_control();

		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . __('Update Options') . '"'
				. ' />'
			. '</p>' . "\n";

		echo "</form>"
			. "</div>\n";
	} # admin_page()


	#
	# widget_control()
	#

	function widget_control()
	{
		if ( $_POST['update_sem_newsletter_options'] )
		{
			sem_newsletter_manager_admin::update_options();
		}

		$options = sem_newsletter_manager::get_options();

		echo '<input type="hidden" name="update_sem_newsletter_options" value="1" />';

		echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">'
			. '<tr valign="top"><td width="50%">';

		echo '<h3>'
			. __('Captions')
			. '</h3>' . "\n";

		$captions = array(
			'widget_title' => __('Widget Title'),
			'widget_teaser' => __('Widget Teaser'),
			'your_email' => __('Your Email'),
			'your_name' => __('Your Name'),
			'sign_up' => __('Sign Up'),
			'thank_you' => __('Thank You Message'),
			);

		foreach ( $captions as $key => $val )
		{
			switch ( $key )
			{
			case 'widget_teaser':
			case 'thank_you':
				echo '<div style="margin: .2em 0px;">'
					. '<label for="newsletter__caption__' . $key . '">' . $val . ':</label>' . '<br />'
					. '<textarea'
						. ' style="width: 320px; height: 60px;"'
						. ' id="newsletter__caption__' . $key . '" name="newsletter[caption][' . $key . ']"'
						. ' >'
						. htmlspecialchars($options['captions'][$key])
						. '</textarea>'
					. '</div>' . "\n";
				break;

			default:
				echo '<div style="margin: .2em 0px;">'
					. '<label for="newsletter__caption__' . $key . '">' . $val . ':</label>' . '<br />'
					. '<input type="text"'
						. ' style="width: 320px;"'
						. ' id="newsletter__caption__' . $key . '" name="newsletter[caption][' . $key . ']"'
						. ' value="' . htmlspecialchars($options['captions'][$key]) . '"'
						. ' />'
					. '</div>' . "\n";
				break;
			}
		}

		echo '</td><td width="50%">';

		echo '<h3>'
			. __('Mailing List')
			. '</h3>' . "\n";

		echo '<div style="margin: .2em 0px;">' . "\n"
			. '<label for="newsletter__email">'
				. __('Mailing List Address')
				. ':'
				. '</label>' . '<br />'
			. '<input type="text"'
				. ' style="width: 320px;"'
				. ' id="newsletter__email" name="newsletter[email]"'
				. ' value="' . htmlspecialchars($options['email']) . '"'
				. ' />'
			. '</div>' . "\n";

		echo '<div style="margin: .2em 0px;">' . "\n"
			. __('Subscription Syntax') . ':' . '<br />'
			. '<table cellpadding="0" cellspacing="4" border="0" width="100%">' . "\n"

			. '<tr valign="top">' . "\n"
			. '<td>'
			. '<input type="radio"'
				. ' id="newsletter__syntax__aweber" name="newsletter[syntax]"'
				. ' value="aweber"'
				. ( $options['syntax'] == 'aweber'
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</td>' . "\n"
			. '<td>'
				. '<label for="newsletter__syntax__aweber">'
				. __('I am using <a href="http://www.semiologic.com/go/aweber" target="_blank">aWeber</a>, as recommended in the <a href="http://www.semiologic.com/software/marketing/newsletter-manager/" target="_blank">plugin\'s documentation</a>.')
				. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n"

			. '<tr valign="top">' . "\n"
			. '<td>'
			. '<input type="radio"'
				. ' id="newsletter__syntax__list" name="newsletter[syntax]"'
				. ' value="list"'
				. ( $options['syntax'] == 'list'
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</td>' . "\n"
			. '<td>'
				. '<label for="newsletter__syntax__list">'
				. __('My list manager (e.g. <a href="http://www.semiologic.com/go/1shoppingcart" target="_blank">1ShoppingCart</a>, <a href="http://www.semiologic.com/go/getresponse" target="_blank">GetResponse</a>) lets users subscribe when they email:') . '<br />'
				. 'mylist@mydomain.com'
				. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n"

			. '<tr valign="top">' . "\n"
			. '<td>'
			. '<input type="radio"'
				. ' id="newsletter__syntax__list_subscribe" name="newsletter[syntax]"'
				. ' value="list-subscribe"'
				. ( $options['syntax'] == 'list-subscribe'
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</td>' . "\n"
			. '<td>'
				. '<label for="newsletter__syntax__list_subscribe">'
				. __('My list manager (e.g. <a href="http://www.greatcircle.com/majordomo" target="_blank">majordomo</a>) lets users subscribe when they email:') . '<br />'
				. 'mylist-subscribe@mydomain.com'
				. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n"

			. '</table>' . "\n"
			. '</div>' . "\n";

		echo '<div style="margin: .2em 0px;">' . "\n"
			. '<label for="newsletter__redirect">'
				. __('Thank You Page (optional)')
				. ':'
				. '</label>' . '<br />'
			. '<input type="text"'
				. ' style="width: 320px;"'
				. ' id="newsletter__redirect" name="newsletter[redirect]"'
				. ' value="' . htmlspecialchars($options['redirect']) . '"'
				. ' />'
			. '</div>' . "\n";

		echo '</td></tr>'
			. '</table>';
	} # widget_control()
} # sem_newsletter_manager_admin

sem_newsletter_manager_admin::init();
?>