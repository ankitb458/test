<?php

class text_widgets_admin
{
	#
	# widget_text_control()
	#
	
	function widget_text_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('widget_text');
		if ( !is_array($options) )
			$options = array();

		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('text_widgets', 'widget_text') == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "text-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-text'] as $widget_number => $widget_text ) {
				$title = strip_tags(stripslashes($widget_text['title']));
				if ( current_user_can('unfiltered_html') )
					$text = stripslashes( $widget_text['text'] );
				else
					$text = stripslashes(wp_filter_post_kses( stripslashes($widget_text['text']) ));
				$filter = isset($widget_text['filter']);
				$options[$widget_number] = compact( 'title', 'text', 'filter' );
			}

			update_option('widget_text', $options);
			$updated = true;
		}

		if ( -1 == $number ) {
			$title = '';
			$text = '';
			$filter = false;
			$number = '%i%';
		} else {
			$title = attribute_escape($options[$number]['title']);
			$text = format_to_edit($options[$number]['text']);
			$filter = intval($options[$number]['filter']);
		}
		
		echo '<p>'
			. '<input class="widefat" id="text-title-'. $number .'" name="widget-text[' . $number .'][title]" type="text" value="' . $title . '" />' . '<br />'
			. '<textarea class="widefat" rows="16" cols="20" id="text-text-' . $number . '" name="widget-text[' .  $number . '][text]">' . $text . '</textarea>' . '<br />'
			. '<input type="hidden" id="text-submit-' . $number . '" name="text-submit-' . $number . '" value="1" />'
			. '<label>'
			. '<input type="checkbox" name="widget-text[' . $number .'][filter]"'
				. ( $filter
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'Automatically insert paragraphs'
			. '</label>'
			. '</p>';
	} # widget_text_control()
} # text_widgets_admin
?>