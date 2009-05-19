<?php
/**
 * sem_layout_admin
 *
 * @package Semiologic Reloaded
 **/

add_action('appearance_page_layout', array('sem_layout_admin', 'save_options'), 0);
add_action('admin_head', array('sem_layout_admin', 'admin_head'));
wp_enqueue_script('jquery');

class sem_layout_admin {
	/**
	 * admin_head()
	 *
	 * @return void
	 **/

	function admin_head() {
		echo <<<EOS

<style type="text/css">
#current_layout img {
	border: solid 1px #999;
	float: left;
	clear: right;
	margin-right: 10px;
}

.current_layout_details th {
	text-align: left;
	padding-right: 5px;
}

.available_layout {
	text-align: center;
	width: 275px;
}

.available_layout img {
	border: solid 1px #ccc;
}

.available_layout label {
	cursor: pointer !important;
}

#available_layouts {
	border-collapse: collapse;
}

#available_layouts td {
	padding: 10px;
	border: solid 1px #ccc;
}

#available_layouts td.top {
	border-top: none;
}

#available_layouts td.bottom {
	border-bottom: none;
}

#available_layouts td.left {
	border-left: none;
}

#available_layouts td.right {
	border-right: none;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('#available_layouts label').click(function() {
		jQuery(this).closest('td').find('input').attr('checked', 'checked');
		jQuery('#layout_picker').trigger('submit');
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
		if ( !$_POST )
			return;
		
		check_admin_referer('sem_layout');
		
		global $sem_options;
		
		$sem_options['active_layout'] = preg_replace("/[^mst]/", "", $_POST['layout']);
		
		update_option('sem6_options', $sem_options);
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		echo '<div class="wrap">' . "\n";
		echo '<form method="post" action="" id="layout_picker">' . "\n";
		
		wp_nonce_field('sem_layout');
		
		global $sem_options;
		$layouts = apply_filters('sem_layouts', sem_layout_admin::get_layouts());
		
		screen_icon();
		
		echo '<h2>' . __('Manage Layout', 'sem-reloaded') . '</h2>' . "\n";
		
		echo '<h3>' . __('Current Layout', 'sem-reloaded') . '</h3>' . "\n";
		
		$details = $layouts[$sem_options['active_layout']];
		$screenshot = sem_url . '/inc/img/' . $sem_options['active_layout'] . '.png';
		
		echo '<div id="current_layout">' . "\n";
		
		echo '<img src="' . clean_url($screenshot) . '" alt="' . esc_attr($details['name']) . '" />' . "\n";
		
		echo '<h4>' . $details['name'] . '</h4>';
		
		echo '<table class="current_layout_details">' . "\n";
		
		foreach ( array(
			'wrapper' => __('Canvas', 'sem-reloaded'),
			'content' => __('Content', 'sem-reloaded'),
			'wide_sidebars' => __('Wide Sidebar', 'sem-reloaded'),
			'sidebars' => __('Sidebars', 'sem-reloaded'),
			'inline_boxes' => __('Inline Boxes', 'sem-reloaded'),
			) as $key => $detail ) {
			echo '<tr>'
				. '<th scope="row">'
				. $detail
				. '</th>'
				. '<td>'
				. $details[$key]
				. '</td>' . "\n";
		}	
		
		echo '</table>' . "\n";
		
		echo '<p>' . __('Note: These numbers may vary slighting depending on the skin you are using.', 'sem-reloaded') . '</p>' . "\n";
		
		echo '<div style="clear: both;"></div>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '<h3>' . __('Available Layouts', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p class="hide-if-no-js">'
			. __('Click on a layout below to activate it.', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<table id="available_layouts" cellspacing="0" cellpadding="0">' . "\n";
		
		$row_size = 2;
		$num_rows = ceil(count($layouts) / $row_size);
		
		$i = 0;
		
		foreach ( $layouts as $layout => $details ) {
			if ( $i && !( $i % $row_size ) )
				echo '</tr>' . "\n";
			
			if ( !( $i % $row_size ) )
				echo '<tr>' . "\n";
			
			$classes = array('available_layout');
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
			
			echo '<p>'
				. '<label for="layout-' . $layout . '">'
				. '<img src="' . clean_url($screenshot) . '" alt="' . esc_attr($details['name']) . '"/>'
				. '</label>'
				. '</p>' . "\n"
				. '<p>'
				. '<label for="layout-' . $layout . '">'
				. '<span class="hide-if-js">'
				. '<input type="radio" name="layout" value="' . $layout . '" id="layout-' . $layout . '"'
					. checked($sem_options['active_layout'], $layout, false)
					. ' />' . '&nbsp;' . "\n"
				. '</span>'
				. $details['name']
				. '</label>'
				. '</p>' . "\n";
			
			echo '</td>' . "\n";
		}
		
		echo '</table>' . "\n";
		
		echo '<p class="submit hide-if-js">'
			. '<input type="submit" value="' . esc_attr(__('Save Settings', 'sem-reloaded')) . '" />'
			. '</p>' . "\n";
		
		echo '</form>' . "\n";
		echo '</div>' . "\n";
	} # edit_options()
	
	
	#
	# get_layouts()
	#
	
	/**
	 * get_layouts()
	 *
	 * @return array $layout_options
	 **/

	function get_layouts() {
		return array(
			'mts' => array(
				'name' => __('Content, Wide Sidebar', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('550px (520px net)', 'sem-reloaded'),
				'wide_sidebars' => __('400px (370px net)', 'sem-reloaded'),
				'sidebars' => __('2 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px net)', 'sem-reloaded'),
				),
			'sms' => array(
				'name' => __('Sidebar, Content, Sidebar', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('550px (520px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('2 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px net)', 'sem-reloaded'),
				),
			'mms' => array(
				'name' => __('Wide Content, Sidebar', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('750px (720px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('1 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px net)', 'sem-reloaded'),
				),
			'smm' => array(
				'name' => __('Sidebar, Wide Content', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('750px (720px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('1 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px net)', 'sem-reloaded'),
				),
			'ms' => array(
				'name' => __('Content, Sidebar', 'sem-reloaded'),
				'wrapper' => __('750px', 'sem-reloaded'),
				'content' => __('550px (520px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('1 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('3 x 250px (220px net)', 'sem-reloaded'),
				),
			'sm' => array(
				'name' => __('Sidebar, Content', 'sem-reloaded'),
				'wrapper' => __('750px', 'sem-reloaded'),
				'content' => __('550px (520px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('1 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('3 x 250px (220px net)', 'sem-reloaded'),
				),
			'mm' => array(
				'name' => __('Wide Content', 'sem-reloaded'),
				'wrapper' => __('750px', 'sem-reloaded'),
				'content' => __('750px (720px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('Not Available', 'sem-reloaded'),
				'inline_boxes' => __('3 x 250px (220px net)', 'sem-reloaded'),
				),
			'm' => array(
				'name' => __('Narrow Content', 'sem-reloaded'),
				'wrapper' => __('620px', 'sem-reloaded'),
				'content' => __('620px (590px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('Not Available', 'sem-reloaded'),
				'inline_boxes' => __('2 x 310px (290px net)', 'sem-reloaded'),
				),
			'mmm' => array(
				'name' => __('Extra Wide Content', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('950px (920px net)', 'sem-reloaded'),
				'wide_sidebars' => __('Not Available', 'sem-reloaded'),
				'sidebars' => __('Not Available', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px net)', 'sem-reloaded'),
				),
			'tsm' => array(
				'name' => __('Wide Sidebar, Content', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('550px (520px net)', 'sem-reloaded'),
				'wide_sidebars' => __('400px (370px net)', 'sem-reloaded'),
				'sidebars' => __('2 x 200px (170px net)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px net)', 'sem-reloaded'),
				),
			);
	} # get_layouts()
} # sem_layout_admin
?>