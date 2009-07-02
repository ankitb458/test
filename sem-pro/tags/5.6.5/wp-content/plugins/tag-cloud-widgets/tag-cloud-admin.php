<?php
class tag_cloud_widgets_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('sem_api_key_protected', array('tag_cloud_widgets_admin', 'sem_api_key_protected'));
	} # init()
	
		
	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/widgets/tag-cloud-widgets/tag-cloud-widgets.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# widget_tag_cloud_control()
	#
	
	function widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = tag_cloud_widgets::get_options();
		
		if ( !$updated && !empty($_POST['sidebar']) ) 
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('tag_cloud_widgets', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "tag_cloud_widget-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
						
					tag_cloud_widgets::clear_cache();
				}
			}

			foreach ( (array) $_POST['tag-cloud-widget'] as $widget_number => $ops ) 
			{
				$title = strip_tags(stripslashes($ops['title']));
				$number_tags = intval($ops['number_tags']);
				$unit = $ops['unit'];
				$smallest = intval($ops['smallest']);
				$largest = intval($ops['largest']);
				$format = $ops['format'];
				$orderby = $ops['orderby'];
				$order = $ops['order'];
				$showcount = isset($ops['showcount']);
				$showcats = isset($ops['showcats']);
				$showempty = isset($ops['showempty']);
				//$newoptions['tags'] = explode(' ', trim(strip_tags(stripslashes($ops['tags']))));
				
				$options[$widget_number] = compact('title', 'number_tags', 'unit', 'smallest', 'largest', 'mincolor', 'maxcolor',
					'format', 'orderby', 'order', 'showcount', 'showcats', 'showempty');
			}
			
			update_option('tag_cloud_widgets', $options);

			$updated = true;
		}

		if ( -1 == $number ) 
		{
			$number = '%i%';
			$options = tag_cloud_widgets::default_options();
		}
		else
		{
			$options = $options[$number];		
		}
		
		extract($options);	

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-title-' . $number . '">'
			. __('Title', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 300px;"'
			. ' id="tag-cloud-widget-title-' . $number . '" name="tag-cloud-widget[' . $number . '][title]"'
			. ' type="text" value="' . attribute_escape($title) . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 150px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-number-' . $number . '">'
			. __('Number to Display', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 40px;"'
			. ' id="tag-cloud-widget-number-' . $number . '" name="tag-cloud-widget[' . $number . '][number_tags]"'
			. ' type="text" value="' . $number_tags . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';	

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-unit-' . $number . '">'
			. __('Font Display Unit', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<select'
			. ' style="width: 100px;"'
			. ' id="tag-cloud-widget-unit-' . $number . '" name="tag-cloud-widget[' . $number . '][unit]"'
			. '>';

		echo '<option'
			. ' value="px"'
			. ( $unit == 'px'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Pixel', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="pt"'
			. ( $unit == 'pt'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Point', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="em"'
			. ( $unit == 'em'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Em', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="%"'
			. ( $unit == '%'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Percent', 'tag_cloud_widgets')
			. '</option>';
			
		
		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';
	
		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 150px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-smallest-' . $number . '">'
			. __('Smallest Font Size', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 40px;"'
			. ' id="tag-cloud-widget-smallest-' . $number . '" name="tag-cloud-widget[' . $number . '][smallest]"'
			. ' type="text" value="' . $smallest . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';		
			
		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 150px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-largest-' . $number . '">'
			. __('Largest Font Size', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input style="width: 40px;"'
			. ' id="tag-cloud-widget-largest-' . $number . '" name="tag-cloud-widget[' . $number . '][largest]"'
			. ' type="text" value="' . $largest . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';		

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-format-' . $number . '">'
			. __('Cloud Format', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<select'
			. ' style="width: 100px;"'
			. ' id="tag-cloud-widget-format-' . $number . '" name="tag-cloud-widget[' . $number . '][format]"'
			. '>';

		echo '<option'
			. ' value="flat"'
			. ( $format == 'flat'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Flat', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="list"'
			. ( $format == 'list'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('List', 'tag_cloud_widgets')
			. '</option>';
		
		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';	
		
		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 150px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-showcats-' . $number . '">'
			. __('Categories in cloud?', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input type="checkbox"'
			. ' id="tag-cloud-widget-showcats-' . $number . '" name="tag-cloud-widget[' . $number . '][showcats]"'			
				. ( $showcats
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 150px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-showempty-' . $number . '">'
			. __('Show Empty?', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input type="checkbox"'
			. ' id="tag-cloud-widget-showempty-' . $number . '" name="tag-cloud-widget[' . $number . '][showempty]"'			
				. ( $showempty
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 150px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-showcount-' . $number . '">'
			. __('Display Post Count?', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<input type="checkbox"'
			. ' id="tag-cloud-widget-showcount-' . $number . '" name="tag-cloud-widget[' . $number . '][showcount]"'			
				. ( $showcount
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'			
			. '</div>';					

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-orderby-' . $number . '">'
			. __('Sort By', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<select'
			. ' style="width: 100px;"'
			. ' id="tag-cloud-widget-orderby-' . $number . '" name="tag-cloud-widget[' . $number . '][orderby]"'
			. '>';

		echo '<option'
			. ' value="name"'
			. ( $orderby == 'name'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Name', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="count"'
			. ( $orderby == 'count'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Count', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="rand"'
			. ( $orderby == 'rand'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('Random', 'tag_cloud_widgets')
			. '</option>';
			
		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';	
			
			echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 120px; float: left; padding-top: 2px;">'
			. '<label for="tag-cloud-widget-order-' . $number . '">'
			. __('Sort Order', 'tag_cloud_widgets')
			. '</label>'
			. '</div>'
			. '<div style="width: 330px; float: right;">'
			. '<select'
			. ' style="width: 100px;"'
			. ' id="tag-cloud-widget-order-' . $number . '" name="tag-cloud-widget[' . $number . '][order]"'
			. '>';

		echo '<option'
			. ' value="ASC"'
			. ( $order == 'ASC'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('ASC', 'tag_cloud_widgets')
			. '</option>';

		echo '<option'
			. ' value="DESC"'
			. ( $order == 'DESC'
				? ' selected="selected"'
				: ''
				)
			. '>'
			. __('DESC', 'tag_cloud_widgets')
			. '</option>';

		echo '</select>'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';				
	}
}

tag_cloud_widgets_admin::init();

?>