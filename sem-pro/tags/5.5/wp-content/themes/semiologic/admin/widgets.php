<?php
function sem_widget_controls_init()
{
	global $wp_registered_widget_controls;
	global $sem_widget_control_callback;

	foreach ( $wp_registered_widget_controls as $widget_id => $widget )
	{
		if ( is_array($widget) )
		{
			if ( sem_pro )
			{
				if ( $widget['callback'] == 'wp_widget_text_control' )
				{
					$widget['callback'] = 'sem_widget_text_control';
				}
				elseif ( $widget['callback'] == 'wp_widget_pages_control' )
				{
					$widget['callback'] = 'sem_widget_pages_control';
				}
			}

			$sem_widget_control_callback[$widget_id] = $widget['callback'];

			$widget['callback'] = create_function('$number = 1', 'sem_widget_control_callback(\'' . $widget_id . '\', $number);');

			$widget['default_width'] = $widget['width'];
			$widget['width'] += 150;

			if ( $widget['height'] < 250 )
			{
				$widget['height'] = 250;
			}
		}

		$wp_registered_widget_controls[$widget_id] = $widget;
	}
} # sem_widget_controls_init()

add_action('init', 'sem_widget_controls_init');


function sem_widget_control_callback($widget_id, $number = 1)
{
	global $wp_registered_widget_controls;
	global $sem_widget_control_callback;

	$width = $wp_registered_widget_controls[$widget_id]['default_width'] - 40;

	if ( isset($sem_widget_control_callback[$widget_id]) )
	{
		echo '<div>'
			. '<div style="float: left; width: ' . $width . 'px;">';

		call_user_func($sem_widget_control_callback[$widget_id], $number);

		echo '</div>'
			. '<div style="float: right; width: 120px;">';

		$contexts = sem_widgets_get_contexts();

		echo '<input type="hidden" name="update_widget_context[' . $widget_id . ']" value="1"' . ( !sem_pro ? ' disabled="disabled"' : '' ) . '/>';

		if ( $_POST['update_widget_context'][$widget_id] )
		{
			sem_widget_controls_update($widget_id);
		}

		global $sem_widget_contexts;

		echo '<h3>'
			. __('Context')
			. '</h3>' . "\n";

		foreach ( $contexts as $context => $label )
		{
			echo '<div style="margin-bottom: .1em;">'
				. '<label>'
				. '<input type="checkbox"'
				. ' name="widget_context[' . $widget_id . '][' . $context . ']"'
				. ( ( !isset($sem_widget_contexts[$widget_id][$context]) || $sem_widget_contexts[$widget_id][$context] )
					? ' checked="checked"'
					: ''
					)
				. ( !sem_pro
					? ' disabled="disabled"'
					: ''
					)
				. ' />'
				. '&nbsp;'
				. $label
				. '</label>'
				. '</div>';
		}

		echo '</div>'
			. '<div class="clear: both;"></div>'
			. '</div>';
	}
} # sem_widget_control_callback()
?>