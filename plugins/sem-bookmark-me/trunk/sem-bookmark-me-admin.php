<?php

class bookmark_me_admin
{
	#
	# widget_control()
	#

	function widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = bookmark_me::get_options();

		if ( !$updated && !empty($_POST['sidebar']) )
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id )
			{
				if ( array('bookmark_me', 'widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
					)
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "bookmark_me-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
					
					bookmark_me::clear_cache();
				}
			}

			foreach ( (array) $_POST['widget-bookmark-me'] as $num => $opt ) {
				$title = stripslashes(wp_filter_post_kses(strip_tags($opt['title'])));
				$dropdown = isset($opt['dropdown']);
				$add_nofollow = isset($opt['add_nofollow']);
				$show_names = isset($opt['show_names']);
				
				$services = (array) $opt['services'];
				$services = array_map('strip_tags', $services);
				$services = array_map('stripslashes', $services);
				
				$options[$num] = compact( 'title', 'dropdown', 'add_nofollow', 'show_names', 'services' );
			}
			
			update_option('bookmark_me_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number )
		{
			$ops = bookmark_me::default_options();
			$number = '%i%';
		}
		else
		{
			$ops = $options[$number];
		}
		
		extract($ops);
		
		
		$title = attribute_escape($title);
		
		echo '<input type="hidden"'
				. ' id="sem_bookmark_me_widget_update"'
				. ' name="sem_bookmark_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. __('Title:')
				. '<br />'
				. '<input style="width: 440px;"'
					. ' name="widget-bookmark-me[' . $number. '][title]"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. '<input'
					. ' name="widget-bookmark-me[' . $number. '][dropdown]"'
					. ( $dropdown
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Show as a drop down button')
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. '<input'
					. ' name="widget-bookmark-me[' . $number. '][show_names]"'
					. ( $show_names
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Show service names')
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. '<input'
					. ' name="widget-bookmark-me[' . $number. '][add_nofollow]"'
					. ( $add_nofollow
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

		echo '<table border="0" cellpadding="1" cellspacing="0" style="width: 440px;">';
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
					. '<label>'
						. '<input type="checkbox"'
							. ' name="widget-bookmark-me[' . $number. '][services][]"'
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
?>