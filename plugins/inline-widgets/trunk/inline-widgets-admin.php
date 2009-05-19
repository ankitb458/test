<?php
/**
 * inline_widgets_admin
 *
 * @package default
 **/

add_filter('admin_footer', array('inline_widgets_admin', 'footer_js'));
add_filter('mce_external_plugins', array('inline_widgets_admin', 'editor_plugin'));
add_filter('mce_buttons_4', array('inline_widgets_admin', 'editor_button'));

class inline_widgets_admin {
	/**
	 * footer_js()
	 *
	 * @package default
	 **/
	
	function footer_js() {
		if ( !$GLOBALS['editing'] )
			return;
		
		global $wp_registered_widgets;
		$widgets = wp_get_sidebars_widgets(false);
		
		$wp_registered_widgets = (array) $wp_registered_widgets;
		$widgets = (array) $widgets['inline_widgets'];
		
		$js_options = array();
		$_widgets = array();
		
		foreach ( $widgets as $key )
			$_widgets[$key] = false;
		
		$widgets = $_widgets;
		
		foreach ( array_keys($widgets) as $id ) {
			if ( isset($wp_registered_widgets[$id]) && is_callable($wp_registered_widgets[$id]['callback']) )
				$widgets[$id] = $wp_registered_widgets[$id];
			else
				unset($widgets[$id]);
		}
		
		$args = array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '%BEG_OF_TITLE%',
			'after_title' => '%END_OF_TITLE%'
			);
		
		foreach ( $widgets as $id => $widget ) {
			$params = array($args, (array) $widget['params'][0]);
			
			ob_start();
			call_user_func_array($widget['callback'], $params);
			$label = ob_get_clean();
			
			if ( preg_match("/%BEG_OF_TITLE%(.*?)%END_OF_TITLE%/", "$label", $label) ) {
				$label = end($label);
				$label = strip_tags($label);
				$label = html_entity_decode($label, ENT_COMPAT, get_option('blog_charset'));
				$label = $widget['name'] . ': ' . $label;
			} else {
				$label = $widget['name'];
			}
			
			$widgets[$id] = $label;
		}
		
		$i = 0;
		$js_options = array();
		
		foreach ( $widgets as $id => $label ) {
			$js_option = "inlineWidgetItems['"
				. $i++
				. "']"
				. "= {"
				. "label: '" . str_replace(
						array("\\", "'"),
						array("\\\\", "\\'"),
						$label
					) . "', "
				. "value: '" . str_replace(
						array("\\", "'"),
						array("\\\\", "\\'"),
						$id
					) . "'"
				. "};";
			//var_dump($js_option);
			$js_options[] = $js_option;
		}

?><script type="text/javascript">
var inlineWidgetItems = new Array();
<?php echo implode("\n", $js_options) . "\n"; ?>
document.inlineWidgetItems = inlineWidgetItems;
//alert(document.inlineWidgetItems);

if ( document.getElementById('quicktags') ) {
	function inlineWidgetsAddWidget(elt) {
		if ( elt.value != '' ) {
			edInsertContent(edCanvas, '[widget:' + elt.value + ']');
		}

		elt.selectedIndex = 0;
	} // inlineWidgetsAddWidget()

	var inlineWidgetsQTButton = '<select class="ed_button" style="width: 100px;" onchange="return inlineWidgetsAddWidget(this);">';

	inlineWidgetsQTButton += '<option value="" selected="selected"><?php echo __('Widget', 'inline-widgets'); ?><\/option>';

	var i;
	var label;
	var value;

	for ( i = 0; i < inlineWidgetItems.length; i++ ) {
		label = new String(inlineWidgetItems[i].label);
		value = new String(inlineWidgetItems[i].value);
		value = value.replace("\"", "&quot;");
	
		inlineWidgetsQTButton += '<option value="' + value + '">' + label + '<\/option>';
	}

	inlineWidgetsQTButton += '<\/select>';

	document.getElementById('ed_toolbar').innerHTML += inlineWidgetsQTButton;
} // end if
</script>
<?php
	} # footer_js()
	
	
	/**
	 * editor_plugin()
	 *
	 * @param array $plugins_array
	 * @return array $plugins_array
	 **/
	
	function editor_plugin($plugin_array) {
		if ( get_user_option('rich_editing') == 'true') {
			$plugin = plugin_dir_url(__FILE__) . 'tinymce/editor_plugin.js';
				
			$plugin_array['inline_widgets'] = $plugin;
		}
		
		return $plugin_array;
	} # editor_plugin()
	
	
	/**
	 * editor_button()
	 *
	 * @param array $buttons
	 * @return array $buttons
	 **/
	
	function editor_button($buttons) {
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') )
			return $buttons;
		
		if ( !empty($buttons) )
			$buttons[] = '|';
		
		$buttons[] = 'inline_widgets';
		
		return $buttons;
	} # editor_button()
} # inline_widgets_admin
?>