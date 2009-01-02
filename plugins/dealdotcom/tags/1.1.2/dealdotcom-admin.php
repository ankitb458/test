<?php
class dealdotcom_admin
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

		$options = dealdotcom::get_options();

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
				if ( array('dealdotcom', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
					)
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "dealdotcom-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-dealdotcom'] as $num => $opt ) {
				$aff_id = stripslashes(strip_tags($opt['aff_id']));
				$nofollow = isset($opt['nofollow']);
				
				$options[$num] = compact( 'aff_id', 'nofollow' );
			}

			update_option('dealdotcom_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number )
		{
			$ops = dealdotcom::default_options();
			$number = '%i%';
		}
		else
		{
			$ops = $options[$number];
		}
		
		extract($ops);
		
		
		$aff_id = attribute_escape($aff_id);

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<label>'
			. '<a href="http://go.semiologic.com/dealdotcom" target="_blank">'
				. __('Affiliate ID', 'dealdotcom')
				. '</a>'
				. ':'
				. '<br />'
			. 'http://www.dealdotcom.com/invite/'
			. '<input style="width: 50px;"'
			. ' name="widget-dealdotcom[' . $number. '][aff_id]"'
			. ' type="text" value="' . $aff_id . '"'
			. ' />'
			. '</label>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<label>'
			. '<input'
			. ' name="widget-dealdotcom[' . $number. '][nofollow]"'
			. ' type="checkbox"'
			. ( $nofollow
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. __('Add nofollow', 'dealdotcom')
			. '</label>'
			. '</div>';
	} # widget_control()
} # dealdotcom_admin
?>