<?php

class subscribe_me_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('subscribe_me_admin', 'widgetize'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_widget_control('Subscribe Me', array('subscribe_me_admin', 'widget_control'), 250, 300);
		}
	} # widgetize()


	#
	# widget_control()
	#

	function widget_control()
	{
		$options = get_option('sem_subscribe_me_params');

		if ( !$options )
		{
			$options = array(
				'title' => __('Syndicate'),
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '<h2>',
				'after_title' => '</h2>'
				);
		}

		if ( $_POST["sem_subscribe_me_widget_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["sem_subscribe_me_widget_title"])));
			$new_options['dropdown'] = isset($_POST['sem_subscribe_me_dropdown']);
			$new_options['add_nofollow'] = isset($_POST['sem_subscribe_me_add_nofollow']);

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('sem_subscribe_me_params', $options);
			}

			$services = (array) $_POST['subscribe_me_services'];

			$services = array_map('strip_tags', $services);
			$services = array_map('stripslashes', $services);

			update_option('sem_subscribe_me_services', $services);
		}

		$title = htmlspecialchars($options['title']);

		$services = get_option('sem_subscribe_me_services');

		echo '<input type="hidden"'
				. ' id="sem_subscribe_me_widget_update"'
				. ' name="sem_subscribe_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<div style="margin-bottom: .2em;">'
			. '<label for="sem_subscribe_me_widget_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="sem_subscribe_me_widget_title"'
					. ' name="sem_subscribe_me_widget_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
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
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label for="sem_subscribe_me_add_nofollow">'
				. '<input'
					. ' id="sem_subscribe_me_add_nofollow"'
					. ' name="sem_subscribe_me_add_nofollow"'
					. ( intval($options['add_nofollow'])
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Add nofollow')
				. '</label>'
				. '</div>'
			;


		$args['site_path'] = trailingslashit(get_option('siteurl'));
		$args['img_path'] = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/sem-subscribe-me/img/';

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
						. '<label for="subscribe_me_services__' . $service . '">'
						. '<input type="checkbox"'
							. ' name="subscribe_me_services[]" id="subscribe_me_services__' . $service . '"'
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
						. '<label for="subscribe_me_services__' . $service . '">'
						. '<input type="checkbox"'
							. ' name="subscribe_me_services[]" id="subscribe_me_services__' . $service . '"'
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
							. ' align="middle"'
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
} # subscribe_me_admin

subscribe_me_admin::init();
?>