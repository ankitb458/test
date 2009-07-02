<?php
/*
Plugin Name: MyBlogLog Widget
Description: Adds MyBlogLog widget to your blog.
Author: MyBlogLog Team
Version: 2.0
Author URI: http://www.mybloglog.com
*/
// This gets called at the plugins_loaded action
function widget_mybloglog_init() {
	
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This saves options and prints the widget's config form.
	function widget_mybloglog_control() {
		$options = $newoptions = get_option('widget_mybloglog');
		if ( $_POST['mybloglog-submit'] ) {
      $valid_code = trim(stripslashes($_POST['mybloglog-code']));

      // Making sure it's a MyBlogLog widget
      $n_m = preg_match('/^<(script|iframe|object|a)(.+)(src|value|href)(.+)(mybloglog\.com\/)(.+)mblID=([0-9]+)(.+)<\/(script|iframe|object|a)>$/im', $valid_code);
      if($n_m == 0) {
        $valid_code = '';
      }
			$newoptions['mybloglog_code'] = $valid_code;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_mybloglog', $options);
		}
	?>
				<div>
				  <p style="text-align:left">MyBlogLog is how you get to know who reads your blog and why. Just install this widget. You'll get both a public photo lineup of who's been reading your blog recently and a private reporting page of what your readers read and clicked on.</p>
				  <p style="text-align:left">To make it work, you must go and get one of <a href="http://www.mybloglog.com" target="_new">MyBlogLog widget codes</a> and then paste it below.</p>
         	<textarea id="mybloglog-code" name="mybloglog-code" style="width:370px;height:110px;padding:0px;margin:0px;"><?php echo wp_specialchars($options['mybloglog_code'], true); ?></textarea>
				  <input type="hidden" name="mybloglog-submit" id="mybloglog-submit" value="1" />
				</div>
	<?php
	}

	// This prints the widget
	function widget_mybloglog($args) {
		extract($args);
		$defaults = array();
		$options = (array) get_option('widget_mybloglog');

		foreach ( $defaults as $key => $value )
			if ( !isset($options[$key]) )
				$options[$key] = $defaults[$key];

		?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . "" . $after_title; ?>
			<?php echo $options['mybloglog_code']; ?>
		<?php echo $after_widget; ?>
<?php
	}

	// Tell Dynamic Sidebar about our new widget and its control
	register_sidebar_widget('MyBlogLog', 'widget_mybloglog');
	register_widget_control('MyBlogLog', 'widget_mybloglog_control', 370, 300);
	
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('widgets_init', 'widget_mybloglog_init');

?>