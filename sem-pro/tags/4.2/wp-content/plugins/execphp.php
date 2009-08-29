<?php
/*
Plugin Name: Executable PHP widget
Description: Like the Text widget, but it will take PHP code as well. Up to 9 instances of this widget may exist. Heavily derived from the Text widget code included with the widget plugin by Automattic, Inc.
Author: Otto
Version: 1.0
Author URI: http://ottodestruct.com
*/

function widget_execphp_init()
{
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	function widget_execphp($args, $number = 1) {
		extract($args);
		$options = get_option('widget_execphp');
		$title = $options[$number]['title'];
		$text = $options[$number]['text'];
	?>
			<?php echo $before_widget; ?>
				<?php $title ? print($before_title . $title . $after_title) : null; ?>
				<div class="execphpwidget"><?php eval('?>'.$text); ?></div>
			<?php echo $after_widget; ?>
	<?php
	}

	function widget_execphp_control($number = 1) {
		$options = $newoptions = get_option('widget_execphp');
		if ( $_POST["execphp-submit-$number"] ) {
			$newoptions[$number]['title'] = strip_tags(stripslashes($_POST["execphp-title-$number"]));
			$newoptions[$number]['text'] = stripslashes($_POST["execphp-text-$number"]);
			if ( !current_user_can('unfiltered_html') )
				$newoptions[$number]['text'] = stripslashes(wp_filter_post_kses($newoptions[$number]['text']));
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_execphp', $options);
		}
		$title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
		$text = htmlspecialchars($options[$number]['text'], ENT_QUOTES);
	?>
				<input style="width: 450px;" id="execphp-title-<?php echo "$number"; ?>" name="execphp-title-<?php echo "$number"; ?>" type="text" value="<?php echo $title; ?>" />
				<p>PHP Code (MUST be enclosed in &lt;?php and ?&gt; tags!):</p>
				<textarea style="width: 450px; height: 230px;" id="execphp-text-<?php echo "$number"; ?>" name="execphp-text-<?php echo "$number"; ?>"><?php echo $text; ?></textarea>
				<input type="hidden" id="execphp-submit-<?php echo "$number"; ?>" name="execphp-submit-<?php echo "$number"; ?>" value="1" />
	<?php
	}

	function widget_execphp_setup() {
		$options = $newoptions = get_option('widget_execphp');
		if ( isset($_POST['execphp-number-submit']) ) {
			$number = (int) $_POST['execphp-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_execphp', $options);
			widget_execphp_register($options['number']);
		}
	}

	function widget_execphp_page() {
		$options = $newoptions = get_option('widget_execphp');
	?>
		<div class="wrap">
			<form method="POST">
				<h2>PHP Code Widgets</h2>
				<p style="line-height: 30px;"><?php _e('How many PHP Code widgets would you like?'); ?>
				<select id="execphp-number" name="execphp-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="execphp-number-submit" id="execphp-number-submit" value="<?php _e('Save'); ?>" /></span></p>
			</form>
		</div>
	<?php
	}

	function widget_execphp_register() {
		$options = get_option('widget_execphp');
		$number = isset($options['number']) ? $options['number'] : 1;
		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;
		for ($i = 1; $i <= 9; $i++) {
			$name = array('PHP Code %s', null, $i);
			register_sidebar_widget($name, $i <= $number ? 'widget_execphp' : /* unregister */ '', $i);
			register_widget_control($name, $i <= $number ? 'widget_execphp_control' : /* unregister */ '', 460, 350, $i);
		}
		add_action('sidebar_admin_setup', 'widget_execphp_setup');
		add_action('sidebar_admin_page', 'widget_execphp_page');
	}
	// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
	widget_execphp_register();
}


// Tell Dynamic Sidebar about our new widget and its control
add_action('plugins_loaded', 'widget_execphp_init');

?>