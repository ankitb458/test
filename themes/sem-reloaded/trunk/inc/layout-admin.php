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

.available_layout label {
	cursor: pointer !important;
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
			'wide_sidebars' => __('Wide Sidebars', 'sem-reloaded'),
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
			if ( ceil($i / $row_size) == 1 )
				$classes[] = 'top';
			if ( ceil($i / $row_size) == $num_rows )
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
				'name' => __('Content, Wide Sidebars + 2 Sidebars', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('550px (520px)', 'sem-reloaded'),
				'wide_sidebars' => __('400px (370px)', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px)', 'sem-reloaded'),
				),
			'sms' => array(
				'name' => __('Sidebar, Content, Sidebar', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('550px (520px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px)', 'sem-reloaded'),
				),
			'mms' => array(
				'name' => __('Wide Content, Sidebar', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('750px (720px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px)', 'sem-reloaded'),
				),
			'smm' => array(
				'name' => __('Sidebar, Wide Content', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('750px (720px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px)', 'sem-reloaded'),
				),
			'ms' => array(
				'name' => __('Content, Sidebar', 'sem-reloaded'),
				'wrapper' => __('750px', 'sem-reloaded'),
				'content' => __('550px (520px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('3 x 250px (220px)', 'sem-reloaded'),
				),
			'sm' => array(
				'name' => __('Sidebar, Content', 'sem-reloaded'),
				'wrapper' => __('750px', 'sem-reloaded'),
				'content' => __('550px (520px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('3 x 250px (220px)', 'sem-reloaded'),
				),
			'mm' => array(
				'name' => __('Wide Content', 'sem-reloaded'),
				'wrapper' => __('750px', 'sem-reloaded'),
				'content' => __('750px (720px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('Unavailable', 'sem-reloaded'),
				'inline_boxes' => __('3 x 250px (220px)', 'sem-reloaded'),
				),
			'm' => array(
				'name' => __('Narrow Content', 'sem-reloaded'),
				'wrapper' => __('620px', 'sem-reloaded'),
				'content' => __('620px (590px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('Unavailable', 'sem-reloaded'),
				'inline_boxes' => __('2 x 310px (290px)', 'sem-reloaded'),
				),
			'mmm' => array(
				'name' => __('Extra Wide Content', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('950px (920px)', 'sem-reloaded'),
				'wide_sidebars' => __('Unavailable', 'sem-reloaded'),
				'sidebars' => __('Unavailable', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px)', 'sem-reloaded'),
				),
			'tsm' => array(
				'name' => __('Wide Sidebars + 2 Sidebars, Content', 'sem-reloaded'),
				'wrapper' => __('950px', 'sem-reloaded'),
				'content' => __('550px (520px)', 'sem-reloaded'),
				'wide_sidebars' => __('400px (370px)', 'sem-reloaded'),
				'sidebars' => __('200px (170px)', 'sem-reloaded'),
				'inline_boxes' => __('4 x 237px (207px)', 'sem-reloaded'),
				),
			);
	} # get_layouts()
} # sem_layout_admin


class old_sem_layout_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_action('admin_menu', array('sem_layout_admin', 'admin_menu'));
	} # init()
	
	
	#
	# admin_menu()
	#
	
	function admin_menu()
	{
		add_theme_page(
			__('Layout'),
			__('Layout'),
			'switch_themes',
			basename(__FILE__),
			array('sem_layout_admin', 'admin_page')
			);
	} # admin_menu()
	
	
	#
	# update_options()
	#
	
	function update_options()
	{
		check_admin_referer('sem_layout');

		global $sem_options;

		$sem_options['active_layout'] = preg_replace("/[^mest]/", "", $_POST['active_layout']);

		update_option('sem6_options', $sem_options);
	} # update_options()
	
	
	#
	# admin_page()
	#
	
	function admin_page()
	{
		global $sem_options;

		echo '<div class="wrap">' . "\n";
		echo '<form method="post" action="">' . "\n";

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_layout');

		echo '<input type="hidden"'
			. ' name="update_layout_options"'
			. ' value="1"'
			. ' />' . "\n";

		if ( $_POST['update_layout_options'] )
		{
			sem_layout_admin::update_options();
			
			echo '<div class="updated">' . "\n"
				. "<p>"
					. "<strong>"
					. __('Settings saved.')
					. "</strong>"
				. "</p>" . "\n"
				. "</div>" . "\n";
		}
		
		echo '<h2>' . __('Layout Settings') . '</h2>' . "\n";
		
		$layouts = sem_layout_admin::get_layouts();

		if ( !in_array($sem_options['active_layout'], array_keys($layouts)) )
		{
			$sem_options['active_layout'] = 'mts';
			update_option('sem6_options', $sem_options);
		}
		
		$active_layout = $sem_options['active_layout'];
		
		$i = 0;
		$last_id = key(array_reverse($layouts));
		
		echo '<table>' . "\n";
		
		foreach ( $layouts as $layout_id => $layout_data )
		{
			$i++;
			
			if ( $i % 2 )
				echo '<tr align="center">' . "\n";
			
			echo '<td'
				. ( ( $layout_id == $active_layout )
					? ' style="width: 50%; background-color: #ddd;"'
					: ' style="width: 50%;"'
					)
				. '>' . "\n";
			
			echo '<h3>'
					. '<label for="active_layout__' . $layout_id . '">'
					. '<input type="radio"'
						. ' id="active_layout__' . $layout_id . '" name="active_layout"'
						. ' value="' . $layout_id . '"'
						. ( ( $layout_id == $active_layout )
							? ' checked="checked"'
							: ''
							)
						. ' />'
					. '&nbsp;'
					. $layout_data['name']
					. '</label>'
				. '</h3>' . "\n";

			echo '<p>'
				. '<label for="active_layout__' . $layout_id . '">'
				. '<img src="' . sem_url . '/inc/img/' . $layout_id . '.png" />'
				. '</label>'
				. '</p>' . "\n";

			echo '</td>' . "\n";
			
			if ( $i % 2 && $skin_id == $last_id ) {
				echo '<td>&nbsp;</td>' . "\n";
				$i++;
			}
			
			if ( !( $i % 2 ) ) {
				echo '</tr>' . "\n"
					. '<tr>' . "\n"
					. '<td colspan="2">'
					. '<div class="submit">'
					. '<input type="submit" value="' . attribute_escape(__('Save Changes')) . '" />'
					. '</div>' . "\n"
					. '</td>'
					. '</tr>' . "\n";
			}
		}

		echo '</table>' . "\n";
		
		echo '</form>' . "\n";
		echo '</div>' . "\n";
	} # admin_page()
} # sem_layout_admin

#sem_layout_admin::init();
?>