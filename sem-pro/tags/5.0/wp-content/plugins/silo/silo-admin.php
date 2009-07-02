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
		add_action('widgets_init', array('silo_admin', 'widgetize'));

		add_action('edit_page_form', array('silo_admin', 'page_tags'));
	} # init()


	#
	# page_tags()
	#

	function page_tags()
	{
		if ( !isset($GLOBALS['simple_tags_admin']) )
		{
			global $post_ID;
?>
<fieldset id="tagdiv">
	<legend><?php _e('Tags (separate multiple tags with commas: cats, pet food, dogs).'); ?></legend>
	<div><input type="text" name="tags_input" class="tags-input" id="tags-input" size="30" tabindex="3" value="<?php echo get_tags_to_edit( $post_ID ); ?>" /></div>
</fieldset>
<?php
		}
	} # page_tags()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_widget_control') )
		{
			register_widget_control('Silo Pages', array('silo_admin', 'widget_pages_control'));
		}
	} # widgetize()


	#
	# widget_pages_control()
	#

	function widget_pages_control()
	{
		$options = get_option('silo_options');

		if ( $_POST["silo_pages_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["silo_pages_title"])));

			preg_match_all("/\d+/", $_POST["silo_pages_exclude"], $exclude);
			$new_options['exclude'] = end($exclude);

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('silo_options', $options);

				silo::flush_cache(0);
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
				. ' id="silo_pages_update"'
				. ' name="silo_pages_update"'
				. ' value="1"'
				. ' />'
			. '<p>'
			. '<label for="silo_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="silo_pages_title"'
					. ' name="silo_pages_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</p>'
			. '<p>'
			. '<label for="silo_pages_exclude">'
			. __('Exclude (ID list)') . ':'
			. '<br />'
			. '<input type="text" style="width: 250px;"'
				. ' id="silo_pages_exclude" name="silo_pages_exclude"'
				. ' value="' . $exclude . '"'
				. ' />'
			. '</label>'
			. '</p>'
			;
	} # widget_pages_control()
} # silo_admin

silo_admin::init();
?>