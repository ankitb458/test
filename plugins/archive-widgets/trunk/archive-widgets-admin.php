<?php

class archive_widgets_admin
{
	#
	# init()
	#
	
	function init()
	{
//		add_filter('sem_api_key_protected', array('archive_widgets_admin', 'sem_api_key_protected'));
	} # init()
	
		
	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/widgets/archive-widgets/archive-widgets.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# widget_archives_control()
	#
	
	function widget_archives_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('archive_widgets');
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
				if ( array('archive_widgets', 'widget_archives') == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "archive_widget-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['archive-widgets'] as $widget_number => $ops ) {
				$title = strip_tags(stripslashes($ops['title']));
				$dropdown = isset($ops['dropdown']);
				$count = isset($ops['count']);				
				$options[$widget_number] = compact('title', 'dropdown', 'count');
			}
			
			update_option('archive_widgets', $options);

			$updated = true;
		}

		if ( -1 == $number ) {
			$title = '';
			$dropdown = false;
			$count = false;
			$number = '%i%';
		} else {
			$title = attribute_escape($options[$number]['title']);
			$count = intval($options[$number]['count']);			
			$dropdown = intval($options[$number]['dropdown']);
		}
		
		echo '<p>'
			. '<input class="widefat" id="text-title-'
			. $number .
			'" name="archive-widgets[' . $number .'][title]" type="text" value="' . $title . '" />' 
			. '<br />'
			. '</p>';			

		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="archive-widgets[' . $number .'][count]"'
				. ( $count
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'Show post counts'
			. '</label>'
			. '</p>';
			
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="archive-widgets[' . $number .'][dropdown]"'
				. ( $dropdown
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'Display as a drop down'
			. '</label>'
			. '</p>';
	} # widget_archives_control()
} # archive_widgets_admin

archive_widgets_admin::init();

?>