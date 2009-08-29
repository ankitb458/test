<?php
/*
Plugin Name: del.icio.us widget
Description: Adds a sidebar widget to display del.icio.us links
Author: Automattic, Inc.
Version: 1.0 (edited)
Author URI: http://automattic.com
*/

// This gets called at the plugins_loaded action
function widget_delicious_init() {

	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This saves options and prints the widget's config form.
	function widget_delicious_control() {
		$options = $newoptions = get_option('widget_delicious');
		if ( $_POST['delicious-submit'] ) {
			$newoptions['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST['delicious-title'])));
			$newoptions['username'] = stripslashes(wp_filter_post_kses(strip_tags($_POST['delicious-username'])));
			$newoptions['count'] = (int) $_POST['delicious-count'];
			$newoptions['tags'] = explode(' ', trim(stripslashes(wp_filter_post_kses(strip_tags($_POST['delicious-tags'])))));
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_delicious', $options);
		}
	?>				<div style="text-align:right">
				<label for="delicious-title" style="line-height:35px;display:block;">Widget title: <input type="text" id="delicious-title" name="delicious-title" value="<?php echo htmlspecialchars($options['title']); ?>" /></label>
				<label for="delicious-username" style="line-height:35px;display:block;">del.icio.us login: <input type="text" id="delicious-username" name="delicious-username" value="<?php echo htmlspecialchars($options['username']); ?>" /></label>
				<label for="delicious-count" style="line-height:35px;display:block;">Number of links: <input type="text" id="delicious-count" name="delicious-count" value="<?php echo $options['count']; ?>" /></label>
				<label for="delicious-tags" style="line-height:35px;display:block;">Show only these tags (separated by spaces): <textarea id="delicious-tags" name="delicious-tags" style="width:290px;height:20px;"><?php echo htmlspecialchars(implode(' ', (array) $options['tags'])); ?></textarea></label>
				<input type="hidden" name="delicious-submit" id="delicious-submit" value="1" />
				</div>
	<?php
	}

	// This prints the widget
	function widget_delicious($args) {
		extract($args);
		$defaults = array('count' => 10, 'username' => 'wordpress', 'title' => 'del.icio.us');
		$options = (array) get_option('widget_delicious');

		foreach ( $defaults as $key => $value )
			if ( !isset($options[$key]) || empty($options[$key]) )
				$options[$key] = $defaults[$key];

		$json_url = 'http://del.icio.us/feeds/json/' . rawurlencode($options['username']);
		$json_url.= count($options['tags']) ? '/' . rawurlencode(implode('+', $options['tags'])) : '';
		$json_url.= '?count=' . ((int) $options['count']) . ';';
		?>		<?php echo $before_widget; ?>			<?php echo $before_title . "<a href='http://del.icio.us/{$options['username']}'>{$options['title']}</a>" . $after_title; ?><div id="delicious-box" style="margin:0;padding:0;border:none;"> </div>
			<script type="text/javascript" src="<?php echo $json_url; ?>"></script>
			<script type="text/javascript">
			function showImage(img){ return (function(){ img.style.display='inline'; }) }
			var ul = document.createElement('ul');
			for (var i=0, post; post = Delicious.posts[i]; i++) {
				var li = document.createElement('li');
				var a = document.createElement('a');
				a.setAttribute('href', post.u);
				a.appendChild(document.createTextNode(post.d));
				li.appendChild(a);
				ul.appendChild(li);
			}
			ul.setAttribute('id', 'delicious-list');
			document.getElementById('delicious-box').appendChild(ul);
			</script>
		<?php echo $after_widget; ?><?php
	}

	// Tell Dynamic Sidebar about our new widget and its control
	register_sidebar_widget('del.icio.us', 'widget_delicious');
	register_widget_control('del.icio.us', 'widget_delicious_control');

}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('plugins_loaded', 'widget_delicious_init');

?>