<?php
class sem_layout_admin
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
			__('Width &amp; Layout'),
			__('Width &amp; Layout'),
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
	
	
	#
	# get_layouts()
	#
	
	function get_layouts()
	{
		return array(
			'mts' => array(
				'name' => __('Content, Top Sidebar + 2 Sidebars')
				),
			'sms' => array(
				'name' => __('Sidebar, Content, Sidebar')
				),
			'mms' => array(
				'name' => __('Wide Content, Sidebar')
				),
			'smm' => array(
				'name' => __('Sidebar, Wide Content')
				),
			'ms' => array(
				'name' => __('Content, Sidebar')
				),
			'sm' => array(
				'name' => __('Sidebar, Content')
				),
			'mm' => array(
				'name' => __('Wide Content')
				),
			'm' => array(
				'name' => __('Narrow Content')
				),
			'mmm' => array(
				'name' => __('Extra Wide Content')
				),
			'tsm' => array(
				'name' => __('Top Sidebar + 2 Sidebars, Content')
				),
			);
	} # get_layouts()
} # sem_layout_admin

sem_layout_admin::init();
?>