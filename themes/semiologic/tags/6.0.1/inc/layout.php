<?php
/**
 * sem_layout
 *
 * @package Semiologic Theme
 **/

class sem_layout {
	/**
	 * admin_head()
	 *
	 * @return void
	 **/
	
	function admin_head() {
		echo <<<EOS

<style type="text/css">
#current_option img.layout {
	border: solid 1px #999;
	float: left;
	clear: right;
	margin-right: 10px;
}

.current_option_details th {
	text-align: left;
	padding-right: 5px;
}

.available_option {
	text-align: center;
	width: 275px;
}

.available_option img {
	border: solid 1px #ccc;
}

.available_option label {
	cursor: pointer !important;
}

.available_options {
	border-collapse: collapse;
}

.available_options td {
	padding: 10px;
	border: solid 1px #ccc;
}

.available_options td.top {
	border-top: none;
}

.available_options td.bottom {
	border-bottom: none;
}

.available_options td.left {
	border-left: none;
}

.available_options td.right {
	border-right: none;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(".available_options label").click(function() {
		jQuery(this).closest('td').find('input:radio').attr('checked', 'checked');
		jQuery('#option_picker').trigger('submit');
	});
});
</script>

EOS;
	} # admin_head()
	
	
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST || !current_user_can('switch_themes') )
			return;
		
		check_admin_referer('sem_layout');
		
		global $sem_options;
		
		$sem_options['active_layout'] = preg_replace("/[^mse]/", "", $_POST['layout']);
		$sem_options['active_width'] = in_array($_POST['width'], array('narrow', 'wide', 'flex'))
			? $_POST['width']
			: 'wide';
		
		update_option('sem5_options', $sem_options);
		
		echo '<div class="updated fade">'
			. '<p><strong>'
			. __('Settings saved.', 'sem-theme')
			. '</strong></p>'
			. '</div>' . "\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		echo '<div class="wrap">' . "\n";
		echo '<form method="post" action="" id="option_picker">' . "\n";
		
		wp_nonce_field('sem_layout');
		
		global $sem_options;
		$layouts = sem_layout::get_layouts();
		$widths = sem_layout::get_widths();
		
		screen_icon();
		
		echo '<h2>' . __('Manage Layout', 'sem-theme') . '</h2>' . "\n";
		
		echo '<h3>' . __('Current Layout and Width', 'sem-theme') . '</h3>' . "\n";
		
		$name = sprintf(
			__('%1$s / %2$s', 'sem-theme'),
			$layouts[$sem_options['active_layout']],
			$widths[$sem_options['active_width']]
			);
		$screenshot = sem_url . '/inc/img/' . $sem_options['active_layout'] . '.png';
		$screenshot2 = sem_url . '/inc/img/' . $sem_options['active_width'] . '.png';
		
		echo '<div id="current_option">' . "\n";
		
		echo '<img src="' . esc_url($screenshot) . '" alt="' . esc_attr($layouts[$sem_options['active_layout']]) . '" class="layout" />' . "\n";
		
		echo '<h4>' . $name . '</h4>';
		
		echo '<img src="' . esc_url($screenshot2) . '" alt="' . esc_attr($widths[$sem_options['active_width']]) . '" />' . "\n";
		
		echo '<div style="clear: both;"></div>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '<h3>' . __('Available Layouts', 'sem-theme') . '</h3>' . "\n";
		
		echo '<p class="hide-if-no-js">'
			. __('Click on a layout below to activate it.', 'sem-theme')
			. '</p>' . "\n";
		
		echo '<table class="available_options" cellspacing="0" cellpadding="0">' . "\n";
		
		$row_size = 2;
		$num_rows = ceil(count($layouts) / $row_size);
		
		$i = 0;
		
		foreach ( $layouts as $layout => $name ) {
			if ( $i && !( $i % $row_size ) )
				echo '</tr>' . "\n";
			
			if ( !( $i % $row_size ) )
				echo '<tr>' . "\n";
			
			$classes = array('available_option');
			if ( ceil(( $i + 1 ) / $row_size) == 1 )
				$classes[] = 'top';
			if ( ceil(( $i + 1 ) / $row_size) == $num_rows )
				$classes[] = 'bottom';
			if ( !( $i % $row_size ) )
				$classes[] = 'left';
			elseif ( !( ( $i + 1 ) % $row_size ) )
				$classes[] = 'right';
			
			$i++;
			
			echo '<td class="' . implode(' ', $classes) . '">' . "\n";
			
			$screenshot = sem_url . '/inc/img/' . $layout . '.png';
			
			echo '<h4>'
				. '<label for="layout-' . $layout . '">'
				. '<img src="' . esc_url($screenshot) . '" alt="' . esc_attr($name) . '"/>'
				. '</label>'
				. '</p>' . "\n"
				. '<p>'
				. '<label for="layout-' . $layout . '">'
				. '<span class="hide-if-js">'
				. '<input type="radio" name="layout" value="' . $layout . '" id="layout-' . $layout . '"'
					. checked($sem_options['active_layout'], $layout, false)
					. ' />' . '&nbsp;' . "\n"
				. '</span>'
				. $name
				. '</label>'
				. '</h4>' . "\n";
			
			echo '</td>' . "\n";
		}
		
		while ( $i % $row_size ) {
			$classes = array('available_option');
			if ( ceil(( $i + 1 ) / $row_size) == 1 )
				$classes[] = 'top';
			if ( ceil(( $i + 1 ) / $row_size) == $num_rows )
				$classes[] = 'bottom';
			if ( !( $i % $row_size ) )
				$classes[] = 'left';
			elseif ( !( ( $i + 1 ) % $row_size ) )
				$classes[] = 'right';
			
			$i++;
			
			echo '<td class="' . implode(' ', $classes) . '">&nbsp;</td>' . "\n";
		}
		
		echo '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '<p class="submit hide-if-js">'
			. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-theme')) . '" />'
			. '</p>' . "\n";
		
		echo '<h3>' . __('Available Widths', 'sem-theme') . '</h3>' . "\n";
		
		echo '<p class="hide-if-no-js">'
			. __('Click on a width below to activate it.', 'sem-theme')
			. '</p>' . "\n";
		
		echo '<table class="available_options" cellspacing="0" cellpadding="0">' . "\n";
		
		$row_size = 3;
		$num_rows = ceil(count($widths) / $row_size);
		
		$i = 0;
		
		foreach ( $widths as $width => $name ) {
			if ( $i && !( $i % $row_size ) )
				echo '</tr>' . "\n";
			
			if ( !( $i % $row_size ) )
				echo '<tr>' . "\n";
			
			$classes = array('available_option');
			if ( ceil(( $i + 1 ) / $row_size) == 1 )
				$classes[] = 'top';
			if ( ceil(( $i + 1 ) / $row_size) == $num_rows )
				$classes[] = 'bottom';
			if ( !( $i % $row_size ) )
				$classes[] = 'left';
			elseif ( !( ( $i + 1 ) % $row_size ) )
				$classes[] = 'right';
			
			$i++;
			
			echo '<td class="' . implode(' ', $classes) . '">' . "\n";
			
			$screenshot = sem_url . '/inc/img/' . $width . '.png';
			
			echo '<h4>'
				. '<label for="width-' . $width . '">'
				. '<img src="' . esc_url($screenshot) . '" alt="' . esc_attr($name) . '"/>'
				. '</label>'
				. '</p>' . "\n"
				. '<p>'
				. '<label for="width-' . $width . '">'
				. '<span class="hide-if-js">'
				. '<input type="radio" name="width" value="' . $width . '" id="width-' . $width . '"'
					. checked($sem_options['active_width'], $width, false)
					. ' />' . '&nbsp;' . "\n"
				. '</span>'
				. $name
				. '</label>'
				. '</h4>' . "\n";
			
			echo '</td>' . "\n";
		}
		
		while ( $i % $row_size ) {
			$classes = array('available_option');
			if ( ceil(( $i + 1 ) / $row_size) == 1 )
				$classes[] = 'top';
			if ( ceil(( $i + 1 ) / $row_size) == $num_rows )
				$classes[] = 'bottom';
			if ( !( $i % $row_size ) )
				$classes[] = 'left';
			elseif ( !( ( $i + 1 ) % $row_size ) )
				$classes[] = 'right';
			
			$i++;
			
			echo '<td class="' . implode(' ', $classes) . '">&nbsp;</td>' . "\n";
		}
		
		echo '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '<p class="submit hide-if-js">'
			. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-theme')) . '" />'
			. '</p>' . "\n";
		
		echo '</form>' . "\n";
		echo '</div>' . "\n";
	} # edit_options()
	
	
	/**
	 * get_layouts()
	 *
	 * @return array $layout_options
	 **/

	function get_layouts() {
		return array(
			'mse' => __('Content, Sidebar, Ext Sidebar', 'sem-theme'),
			'sme' => __('Sidebar, Content, Ext Sidebar', 'sem-theme'),
			'mss' => __('Content, Sidebar, Sidebar', 'sem-theme'),
			'sms' => __('Sidebar, Content, Sidebar', 'sem-theme'),
			'me' => __('Content, Ext Sidebar', 'sem-theme'),
			'em' => __('Ext Sidebar, Content', 'sem-theme'),
			'ms' => __('Content, Sidebar', 'sem-theme'),
			'sm' => __('Sidebar, Content', 'sem-theme'),
			'm' => __('Content', 'sem-theme'),
			'ssm' => __('Sidebar, Sidebar, Content', 'sem-theme'),
			'ems' => __('Ext Sidebar, Content, Sidebar', 'sem-theme'),
			'esm' => __('Ext Sidebar, Sidebar, Content', 'sem-theme'),
			);
	} # get_layouts()
	
	
	/**
	 * get_widths()
	 *
	 * @return array $layout_options
	 **/

	function get_widths() {
		return array(
			'narrow' => __('Narrow', 'sem-theme'),
			'wide' => __('Wide', 'sem-theme'),
			'flex' => __('Flexible', 'sem-theme'),
			);
	} # get_widths()
} # sem_layout

add_action('appearance_page_layout', array('sem_layout', 'save_options'), 0);
add_action('admin_head', array('sem_layout', 'admin_head'));
wp_enqueue_script('jquery');
?>