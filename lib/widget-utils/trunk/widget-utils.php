<?php
/*
 * Widget Utils
 * Author: Denis de Bernardy <http://www.mesoconcepts.com>
 * Version: 2.0
 */


load_plugin_textdomain('widget-utils', null, dirname(dirname(__FILE__) . '/lang'));


/**
 * widget_utils
 *
 * @package Widget Utils
 **/

class widget_utils {
	/**
	 * post_meta_boxes()
	 *
	 * @return void
	 **/
	
	function post_meta_boxes() {
		static $done = false;
		
		if ( $done )
			return;
		
		add_meta_box('post_widget_config', __('This Post In Widgets', 'widget-utils'), array('widget_utils', 'post_widget_config'), 'post');
		add_action('save_post', array('widget_utils', 'post_save_widget_config'));

		$done = true;
	} # post_meta_boxes()
	
	
	/**
	 * page_meta_boxes()
	 *
	 * @return void
	 **/
	
	function page_meta_boxes() {
		static $done = false;
		
		if ( $done )
			return;
		
		add_meta_box('page_widget_config', __('This Page In Widgets', 'widget-utils'), array('widget_utils', 'page_widget_config'), 'page');
		add_action('save_post', array('widget_utils', 'page_save_widget_config'));
		
		$done = true;
	} # page_meta_boxes()
	
	
	/**
	 * post_widget_config()
	 *
	 * @param object $post
	 * @return void
	 **/
	
	function post_widget_config($post) {
		widget_utils::widget_config('post', $post);
	} # post_widget_config()
	
	
	/**
	 * page_widget_config()
	 *
	 * @param object $post
	 * @return void
	 **/
	
	function page_widget_config($post) {
		widget_utils::widget_config('page', $post);
	} # page_widget_config()
	
	
	/**
	 * post_save_widget_config()
	 *
	 * @param int $post_ID
	 * @return void
	 **/
	
	function post_save_widget_config($post_ID) {
		return widget_utils::save_widget_config($post_ID, 'post');
	} # post_save_widget_config()
	
	
	/**
	 * page_save_widget_config()
	 *
	 * @param int $post_ID
	 * @return void
	 **/
	
	function page_save_widget_config($post_ID) {
		return widget_utils::save_widget_config($post_ID, 'page');
	} # page_save_widget_config()
	
	
	/**
	 * widget_config()
	 *
	 * @param string $type
	 * @param object $post
	 * @return void
	 **/
	
	function widget_config($type, $post) {
		$post_ID = $post->ID;

		echo '<p>'
			. __('The following fields let you configure options shared by the following Semiologic widgets:', 'widget-utils')
			. '</p>' . "\n";

		echo '<ul class="ul-square">' . "\n";
		do_action($type . '_widget_config_affected');
		echo '</ul>' . "\n";
		
		echo '<p>'
			. __('It will <b>NOT</b> affect anything else. In particular WordPress\'s built-in Pages widget.', 'widget-utils')
			. '</p>' . "\n";
		
		echo '<table style="width: 100%;">' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row" width="120px;">'
			. __('Title', 'widget-utils')
			. '</th>' . "\n"
			. '<td>'
			. '<input type="text" size="58" class="widefat" tabindex="5"'
			. ' name="widgets_label"'
			. ' value="' . esc_attr(get_post_meta($post_ID, '_widgets_label', true)) . '"'
			. ' />'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row">'
			. __('Description', 'widget-utils')
			. '</th>' . "\n"
			. '<td>'
			. '<textarea size="58" class="widefat" tabindex="5"'
			. ' name="widgets_desc"'
			. ' />'
			. format_to_edit(get_post_meta($post_ID, '_widgets_desc', true))
			. '</textarea>'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row">'
			. 'Exclude'
			. '</th>' . "\n"
			. '<td>'
			. '<label>'
			. '<input type="checkbox" tabindex="5"'
			. ' name="widgets_exclude"'
			. ( get_post_meta($post_ID, '_widgets_exclude', true)
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. __('Exclude this entry from automatically generated lists', 'widget-utils')
			. '</label>'
		 	. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row">'
			. '&nbsp;'
			. '</th>' . "\n"
			. '<td>'
			. '<label>'
			. '<input type="checkbox" tabindex="5"'
			. ' name="widgets_exception"'
			. ( get_post_meta($post_ID, '_widgets_exception', true)
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. __('... except for silo stub, silo map and smart links.', 'widget-utils')
			. '</label>'
		 	. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '</table>';
	} # widget_config()
	
	
	/**
	 * save_widget_config()
	 *
	 * @param int $post_ID
	 * @param string $type
	 * @return void
	 **/
	
	function save_widget_config($post_ID, $type = null) {
		if ( wp_is_post_revision($post_ID) )
			return;
		
		$post = get_post($post_ID);
		
		if ( !empty($_POST) ) {
			$post =& get_post($post_ID);
			
			if ( $post->post_type != $type )
				return;
			
			if ( $_POST['widgets_exclude'] ) {
				update_post_meta($post_ID, '_widgets_exclude', '1');
				
				if ( $_POST['widgets_exception'] )
					update_post_meta($post_ID, '_widgets_exception', '1');
				else
					delete_post_meta($post_ID, '_widgets_exception');
			} else {
				delete_post_meta($post_ID, '_widgets_exclude');
				delete_post_meta($post_ID, '_widgets_exception');
			}
			
			$label = trim(strip_tags(stripslashes($_POST['widgets_label'])));
			
			if ( $label )
				update_post_meta($post_ID, '_widgets_label', $label);
			else
				delete_post_meta($post_ID, '_widgets_label');
			
			if ( current_user_can('unfiltered_html') )
				$desc = stripslashes($_POST['widgets_desc']);
			else
				$desc = stripslashes(wp_filter_post_kses(stripslashes($_POST['widgets_desc'])));
			
			if ( $desc )
				update_post_meta($post_ID, '_widgets_desc', $desc, true);
			else
				delete_post_meta($post_ID, '_widgets_desc');
		}
	} # save_widget_config()
} # widget_utils
?>