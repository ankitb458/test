<?php

class link_widgets_admin
{
	#
	# widget_links_control()
	#
	
	function widget_links_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('link_widgets');
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
				if ( array('link_widgets', 'widget_links') == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "link_widget-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
						
					link_widgets::clear_cache();
				}
			}

			foreach ( (array) $_POST['links-widgets'] as $widget_number => $ops ) {
				$filter = $ops['filter'] ? intval($ops['filter']) : false;
				$desc = isset($ops['desc']);
				$options[$widget_number] = compact('filter', 'desc');
			}
			
			update_option('link_widgets', $options);

			$updated = true;
		}

		if ( -1 == $number ) {
			$filter = false;
			$desc = true;
			$number = '%i%';
		} else {
			$filter = intval($options[$number]['filter']);
			$desc = intval($options[$number]['desc']);
		}
		
		echo '<p>'
			. '<select name="links-widgets[' . $number . '][filter]" style="width: 90%;">'
			. '<option value=""'
				. ( $filter === ''
					? ' selected="selected"'
					: ''
					)
				. '>' . 'All Links' . '</option>';
		
		foreach ( get_terms('link_category', "hide_empty=1") as $cat )
		{
			echo '<option value="' . $cat->term_id . '"'
				. ( $filter == $cat->term_id
					? ' selected="selected"'
					: ''
					)
				. '>' . attribute_escape($cat->name) . '</option>';
		}
		
		echo '</select>'
			. '</p>';
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="links-widgets[' . $number .'][desc]"'
				. ( $desc
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'Show Link Descriptions'
			. '</label>'
			. '</p>';
	} # widget_links_control()
} # link_widgets_admin

?>