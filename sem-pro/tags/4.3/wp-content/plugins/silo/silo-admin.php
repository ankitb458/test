<?php
/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


class silo_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('plugins_loaded', array('silo_admin', 'widgetize'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_widget_control') )
		{
			register_widget_control('Silo Pages', array('silo_admin', 'widget_control'));
		}
	} # widgetize()


	#
	# widget_control()
	#

	function widget_control()
	{
		$options = get_settings('silo_options');

		if ( $_POST["silo_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["silo_title"])));

			preg_match_all("/\d+/", $_POST["silo_exclude"], $exclude);
			$new_options['exclude'] = end($exclude);

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('silo_options', $options);
			}
		}
		elseif ( $options === false )
		{
			$options = array('title' => __('Browse'));
			update_option('silo_options', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		$exclude = '';
		foreach ( (array) $options['exclude'] as $val )
		{
			$exclude .= ( $exclude ? ', ' : '' ) . $val;
		}

		echo '<input type="hidden"'
				. ' id="silo_update"'
				. ' name="silo_update"'
				. ' value="1"'
				. ' />'
			. '<p>'
			. '<label for="silo_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="silo_title"'
					. ' name="silo_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</p>'
			. '<p>'
			. '<label for="silo_exclude">'
			. __('Exclude (ID list)') . ':'
			. '<br />'
			. '<input type="text" style="width: 250px;"'
				. ' id="silo_exclude" name="silo_exclude"'
				. ' value="' . $exclude . '"'
				. ' />'
			. '</label>'
			. '</p>';

	} # widget_control()
} # silo_admin

silo_admin::init();
?>