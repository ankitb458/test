<?php

class subscribe_me_admin
{
	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_widget_control('Subscribe Me', array('subscribe_me_admin', 'widget_control'), 250, 280);
		}
	} # widgetize()


	#
	# widget_control()
	#

	function widget_control()
	{
		$options = get_settings('sem_subscribe_me_params');

		if ( $_POST["sem_subscribe_me_widget_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["sem_subscribe_me_widget_title"])));
			$new_options['dropdown'] = isset($_POST['sem_subscribe_me_dropdown']);

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('sem_subscribe_me_params', $options);
			}

			$services = $_POST['subscribe_me_services'];

			$services = array_map('strip_tags', $services);
			$services = array_map('stripslashes', $services);

			update_option('sem_subscribe_me_services', $services);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<input type="hidden"'
				. ' id="sem_subscribe_me_widget_update"'
				. ' name="sem_subscribe_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<p>'
			. '<label for="sem_subscribe_me_widget_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="sem_subscribe_me_widget_title"'
					. ' name="sem_subscribe_me_widget_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</p>'
			. '<p>'
			. '<label for="sem_subscribe_me_dropdown">'
				. '<input'
					. ' id="sem_subscribe_me_dropdown"'
					. ' name="sem_subscribe_me_dropdown"'
					. ( intval($options['dropdown'])
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Show as a drop down button')
				. '</label>'
				. '</p>'
			;


		$args['site_path'] = trailingslashit(get_settings('siteurl'));
		$args['img_path'] = trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/sem-subscribe-me/img/';

		$services = get_settings('sem_subscribe_me_services');

		if ( !$services )
		{
			$services = subscribe_me::default_services();
		}

		$o .= '<div style="width: 280px;">';

		foreach ( array_keys((array) subscribe_me::get_services()) as $service )
		{
			$details = subscribe_me::get_service($service);

			if ( $details )
			{
				switch( $service )
				{
				case 'local_feed':
				case 'help_link':
					$o .= '<div class="subscribe_service"'
						. ' style="float: left;'
							. ' margin: 2px 5px;'
							. ' width: 130px; height: 20px;'
							. '"'
						. '>'
						. '<label for="subscribe_me_services[' . $service . ']">'
						. '<input type="checkbox"'
							. ' name="subscribe_me_services[]" id="subscribe_me_services[' . $service . ']"'
							. ' value="' . $service . '"'
							. ( in_array($service, (array) $services)
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. '&nbsp;'
						. '<span style="background: url('
								. $args['img_path'] . $details['button']
								. ')'
								. ' center left no-repeat;'
								. ' padding-left: 18px;'
								. ' color: blue;'
								. ' text-decoration: underline;'
								. '"'
								. '>'
						. $details['name']
						. '</span>'
						. '</label>'
						. '</div>' . "\n";
					break;

				default:
					$o .= '<div class="subscribe_service"'
						. ' style="float: left;'
							. ' margin: 2px 5px;'
							. ' width: 130px; height: 20px;'
							. '"'
						. '>'
						. '<label for="subscribe_me_services[' . $service . ']">'
						. '<input type="checkbox"'
							. ' name="subscribe_me_services[]" id="subscribe_me_services[' . $service . ']"'
							. ' value="' . $service . '"'
							. ( in_array($service, (array) $services)
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. '&nbsp;'
						. '<img'
							. ' src="' . $args['img_path'] . $details['button'] . '"'
							. ' alt="' . str_replace('%feed%', $details['name'], __('Subscribe to %feed%')) . '"'
							. ' align="absmiddle"'
							. ' />'
						. '</label>'
						. '</div>' . "\n";
					break;
				}
			}
		}

		$o .= '<div style="clear: both;"></div>'
			. '</div>'. "\n";

		echo $o;
	} # end widget_control()


	#
	# add2menu()
	#

	function add2menu()
	{
		add_options_page(
				__('Subscribe&nbsp;Me', 'sem-subscribe-me'),
				__('Subscribe&nbsp;Me', 'sem-subscribe-me'),
				8,
				str_replace("\\", "/", __FILE__),
				array('subscribe_me_admin', 'display')
				);
	} # add2menu()


	#
	# display()
	#

	function display()
	{
		if ( !empty($_POST) )
		{
			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Options saved.', 'sem-subscribe-me')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}

		echo '<div class="wrap">' . "\n"
			. '<h2>' . __('Syndication Options', 'sem-subscribe-me') . '</h2>' . "\n"
			. '<form method="post" action="">' . "\n";

		subscribe_me_admin::widget_control();

		echo "<p class=\"submit\">"
			. "<input type=\"submit\""
				. " value=\"" . __('Update Options', 'sem-recent-posts') . "\""
				. " />"
			. "</p>\n";

		echo '</form>'
			. '</div>';
	} # display()
} # subscribe_me_admin

add_action('plugins_loaded', array('subscribe_me_admin', 'widgetize'));
add_action('admin_menu', array('subscribe_me_admin', 'add2menu'));
?>