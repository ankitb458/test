<?php
class sem_footer_admin
{
	#
	# footer_widget_control()
	#

	function footer_widget_control()
	{
		global $sem_options;
		global $sem_captions;

		if ( $_POST['update_sem_footer']['nav_menu'] )
		{
			$new_options = $sem_options;
			$new_captions = $sem_captions;

			$new_options['float_footer'] = isset($_POST['sem_footer']['float_footer']);

			if ( current_user_can('unfiltered_html') )
			{
				$new_captions['copyright'] = stripslashes($_POST['sem_footer']['label_copyright']);
				$new_captions['credits'] = stripslashes($_POST['sem_footer']['label_credits']);
			}
			else
			{
				$new_captions['copyright'] = strip_tags(stripslashes($_POST['sem_footer']['label_copyright']));
				$new_captions['credits'] = strip_tags(stripslashes($_POST['sem_footer']['label_credits']));
			}

			if ( $new_options != $sem_options )
			{
				$sem_options = $new_options;

				update_option('sem6_options', $sem_options);
			}
			if ( $new_captions != $sem_captions )
			{
				$sem_captions = $new_captions;

				update_option('sem6_captions', $sem_captions);
			}
		}

		echo '<input type="hidden" name="update_sem_footer[nav_menu]" value="1" />';

		echo '<h3>'
			. __('Config')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="sem_footer[float_footer]"'
				. ( $sem_options['float_footer']
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. ' '
			. __('Show copyright and menu as a single line')
			. '</label>'
			. '</div>';
		
		echo '<h3>'
			. __('Captions')
			. '</h3>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Copyright Notice, e.g. Copyright %year%')
			. '<br />'
			. '<textarea class="widefat" cols="3"'
				. ' name="sem_footer[label_copyright]"'
				. '>'
			. format_to_edit($sem_captions['copyright'])
			. '</textarea>'
			. '</label>'
			. '</div>';

		echo '<div style="margin-bottom: .2em;">'
			. '<label>'
			. __('Credits, e.g. Made with %semiologic% &amp;bull; %skin_name% skin by %skin_author%')
			. '<br />'
			. '<textarea class="widefat" cols="3"'
				. ' name="sem_footer[label_credits]"'
				. '>'
			. format_to_edit($sem_captions['credits'])
			. '</textarea>'
			. '</label>'
			. '</div>';

		sem_nav_menus_admin::widget_control('footer');
	} # footer_widget_control()
	
	
	#
	# footer_boxes_widget_control()
	#
	
	function footer_boxes_widget_control()
	{
		echo '<p>'
			. 'Use this widget to place the footer boxes bar panel where you want it.'
			. '</p>';
	} # footer_boxes_widget_control()
} # sem_footer_admin
?>