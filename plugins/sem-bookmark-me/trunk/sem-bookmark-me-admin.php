<?php
/**
 * bookmark_me_admin
 *
 * @package Bookmark Me
 **/

class bookmark_me_admin {
	function widget_control($widget_args) {
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = bookmark_me::get_options();

		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('bookmark_me', 'widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
				) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "bookmark_me-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
				
				wp_cache_delete($_widget_id, 'widget');
			}

			foreach ( (array) $_POST['widget-bookmark-me'] as $num => $opt ) {
				$title = stripslashes(wp_filter_post_kses(strip_tags($opt['title'])));
				
				$options[$num] = compact('title');
			}
			
			update_option('bookmark_me_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number ) {
			$ops = bookmark_me::default_options();
			$number = '%i%';
		} else {
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
				. __('Title:', 'bookmark-me')
				. '<br />'
				. '<input style="width: 300px;"'
					. ' name="widget-bookmark-me[' . $number. '][title]"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</div>' . "\n"
			;
	} # widget_control()
} # bookmark_me_admin
?>