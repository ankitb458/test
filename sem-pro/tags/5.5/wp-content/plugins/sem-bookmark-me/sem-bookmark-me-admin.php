<?php

class bookmark_me_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('bookmark_me_admin', 'widgetize'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_widget_control('Bookmark Me', array('bookmark_me_admin', 'widget_control'), 480, 350);
		}
	} # widgetize()


	#
	# widget_control()
	#

	function widget_control()
	{
		$options = get_option('sem_bookmark_me_params');

		if ( !$options )
		{
			$options = array(
				'title' => __('Spread the Word!'),
				'show_names' => true,
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '<h2>',
				'after_title' => '</h2>',
				);
		}

		if ( $_POST["sem_bookmark_me_widget_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["sem_bookmark_me_widget_title"])));
			$new_options['dropdown'] = isset($_POST['sem_bookmark_me_dropdown']);
			$new_options['add_nofollow'] = isset($_POST['sem_bookmark_me_add_nofollow']);
			$new_options['show_names'] = isset($_POST['sem_bookmark_me_show_names']);

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('sem_bookmark_me_params', $options);
			}

			$services = (array) $_POST['bookmark_me_services'];

			$services = array_map('strip_tags', $services);
			$services = array_map('stripslashes', $services);

			update_option('sem_bookmark_me_services', $services);
		}

		$title = htmlspecialchars($options['title']);

		$services = get_option('sem_bookmark_me_services');

		echo '<input type="hidden"'
				. ' id="sem_bookmark_me_widget_update"'
				. ' name="sem_bookmark_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<div style="margin-bottom: .2em;">'
			. '<label for="sem_bookmark_me_widget_title">'
				. __('Title:')
				. '<br />'
				. '<input style="width: 80%;"'
					. ' id="sem_bookmark_me_widget_title"'
					. ' name="sem_bookmark_me_widget_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label for="sem_bookmark_me_dropdown">'
				. '<input'
					. ' id="sem_bookmark_me_dropdown"'
					. ' name="sem_bookmark_me_dropdown"'
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
			. '<label for="sem_bookmark_me_show_names">'
				. '<input'
					. ' id="sem_bookmark_me_show_names"'
					. ' name="sem_bookmark_me_show_names"'
					. ( intval($options['show_names'])
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Show service names')
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label for="sem_bookmark_me_add_nofollow">'
				. '<input'
					. ' id="sem_bookmark_me_add_nofollow"'
					. ' name="sem_bookmark_me_add_nofollow"'
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
		$args['img_path'] = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/sem-bookmark-me/img/';

		echo '<table border="0" cellpadding="2" cellspacing="0" width="100%">';
		$i = 0;

		foreach ( array_keys((array) bookmark_me::get_services()) as $service )
		{
			$details = bookmark_me::get_service($service);

			if ( $details )
			{
				if ( !$i )
				{
					echo '<tr>';
				}
				elseif ( !( $i % 3 ) )
				{
					echo '</tr><tr>';
				}

				$i++;

				echo '<td>'
					. '<label for="bookmark_me_services__' . $service . '">'
						. '<input type="checkbox"'
							. ' name="bookmark_me_services[]" id="bookmark_me_services__' . $service . '"'
							. ' value="' . $service . '"'
							. ( in_array($service, (array) $services)
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. '&nbsp;'
						. '<span style="'
						. 'padding-left: 22px;'
						. ' background: url('
							. trailingslashit(get_option('siteurl'))
							. 'wp-content/plugins/sem-bookmark-me/img/'
							. $service . '.gif'
							. ') center left no-repeat;'
						. ' text-decoration: underline;'
						. ' color: blue;'
							. '"'
					. ' class="noicon"'
					. ( $options['add_nofollow']
						? ' rel="nofollow"'
						: ''
						)
					. '>'
					. __($details['name'])
					. '</span>'
					. '</label>'
					. '</td>';
			}
		}

		while ( $i % 3 )
		{
			echo '<td></td>';
			$i++;
		}

		echo '</tr>';

		echo '</table>'. "\n";
	} # end widget_control()
} # bookmark_me_admin

bookmark_me_admin::init();
?>