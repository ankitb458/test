<?php
/**
 * sem_panels
 *
 * @package Semiologic Reloaded
 **/

sem_panels::register();

class sem_panels {
	/**
	 * register()
	 *
	 * @return void
	 **/

	function register() {
		global $sem_options;
		
		$before_widget = '<div class="widget %2$s">' . "\n"
						. '<div class="widget_top"><div class="hidden"></div></div>' . "\n"
						. '<div class="pad">';
		$after_widget = '</div>' . "\n"
						. '<div class="widget_bottom"><div class="hidden"></div></div>' . "\n"
						. '</div>' . "\n";
		
		$before_title = '</div>' . "\n"			# close <div class="pad">, due to the funky WP API
						. '<div class="widget_title_top"><div class="hidden"></div></div>' . "\n"
						. '<div class="widget_title pad">' . "\n"
						. '<h2>';
		$after_title = '</h2>' . "\n"
						. '</div>' . "\n"
						. '<div class="widget_title_bottom"><div class="hidden"></div></div>' . "\n"
						. '<div class="pad">';
		
		if ( strpos($sem_options['active_layout'], 't') !== false ) {
			register_sidebar(
				array(
					'id' => 'top_sidebar',
					'name' => 'Top Wide Sidebar',
					'before_widget' => $before_widget,
					'after_widget' => $after_widget,
					'before_title' => $before_title,
					'after_title' => $after_title,
					)
				);
			register_sidebar(
				array(
					'id' => 'sidebar-1',
					'name' => 'Left Sidebar',
					'before_widget' => $before_widget,
					'after_widget' => $after_widget,
					'before_title' => $before_title,
					'after_title' => $after_title,
					)
				);
			register_sidebar(
				array(
					'id' => 'sidebar-2',
					'name' => 'Right Sidebar',
					'before_widget' => $before_widget,
					'after_widget' => $after_widget,
					'before_title' => $before_title,
					'after_title' => $after_title,
					)
				);
			register_sidebar(
				array(
					'id' => 'bottom_sidebar',
					'name' => 'Bottom Wide Sidebar',
					'before_widget' => $before_widget,
					'after_widget' => $after_widget,
					'before_title' => $before_title,
					'after_title' => $after_title,
					)
				);
		} else {
			switch ( substr_count($sem_options['active_layout'], 's') ) {
			case 2:
				register_sidebar(
					array(
						'id' => 'sidebar-1',
						'name' => 'Left Sidebar',
						'before_widget' => $before_widget,
						'after_widget' => $after_widget,
						'before_title' => $before_title,
						'after_title' => $after_title,
						)
					);
				register_sidebar(
					array(
						'id' => 'sidebar-2',
						'name' => 'Right Sidebar',
						'before_widget' => $before_widget,
						'after_widget' => $after_widget,
						'before_title' => $before_title,
						'after_title' => $after_title,
						)
					);
				break;

			case 1:
				register_sidebar(
					array(
						'id' => 'sidebar-1',
						'name' => 'Sidebar',
						'before_widget' => $before_widget,
						'after_widget' => $after_widget,
						'before_title' => $before_title,
						'after_title' => $after_title,
						)
					);
				break;
			}
		}

		foreach ( array(
			'the_header' => 'Header Area',
			'the_header_boxes' => 'Header Boxes Bar',
			'before_the_entries' => 'Before the Entries',
			'the_entry' => 'Each Entry',
			'after_the_entries' => 'After the Entries',
			'the_footer_boxes' => 'Footer Boxes Bar',
			'the_footer' => 'Footer Area',
			'the_404' => 'Not Found Error (404)',
		) as $panel_id => $panel_label ) {
			$before_title = '<h2>';
			$after_title = '</h2>' . "\n";
			
			switch ( $panel_id ) {
			case 'the_header':
				$before_widget = "\n\t"
					. '<div class="%2$s header_widget wrapper">' . "\n\t"
					. '<div class="header_widget_top"><div class="hidden"></div></div>' . "\n\t"
					. '<div class="header_widget_bg">' . "\n\t"
					. '<div class="wrapper_item">' . "\n\t"
					. '<div class="pad">' . "\n";
				$after_widget = "\n\t"
					. '</div>' . "\n\t"
					. '</div>' . "\n\t"
					. '</div>' . "\n\t"
					. '<div class="header_widget_bottom"><div class="hidden"></div></div>' . "\n\t"
					. '</div><!-- header_widget -->' . "\n\n";
				break;
			
			case 'the_footer':
				$before_widget = "\n\t"
					. '<div class="%2$s footer_widget wrapper">' . "\n\t"
					. '<div class="footer_widget_top"><div class="hidden"></div></div>' . "\n\t"
					. '<div class="footer_widget_bg">' . "\n\t"
					. '<div class="wrapper_item">' . "\n\t"
					. '<div class="pad">' . "\n";
				$after_widget = "\n\t"
					. '</div>' . "\n\t"
					. '</div>' . "\n\t"
					. '</div>' . "\n\t"
					. '<div class="footer_widget_bottom"><div class="hidden"></div></div>' . "\n\t"
					. '</div><!-- footer_widget -->' . "\n\n";
				break;
			
			case 'the_header_boxes':
			case 'the_footer_boxes':
				$before_widget = '<div class="inline_box %2$s">' . "\n"
					. '<div class="inline_box_top"><div class="hidden"></div></div>' . "\n"
					. '<div class="pad">' . "\n";
				$after_widget = '</div>' . "\n"
					. '<div class="inline_box_bottom"><div class="hidden"></div></div>' . "\n"
					. '</div><!-- inline_box -->' . "\n";
				break;
			
			case 'before_the_entries':
			case 'after_the_entries':
				$before_widget = '<div class="%2$s main_widget">' . "\n"
					. '<div class="main_widget_top"><div class="hidden"></div></div>' . "\n"
					. '<div class="pad">' . "\n";
				$after_widget = '</div>' . "\n"
					. '<div class="main_widget_bottom"><div class="hidden"></div></div>' . "\n"
					. '</div><!-- main_widget -->' . "\n";
				break;
			
			default:
				$before_widget = '<div class="%2$s">' . "\n"
					. '<div class="pad">' . "\n";
				$after_widget = '</div>' . "\n"
					. '</div>' . "\n";
				break;
			}
			
			register_sidebar(
				array(
					'id' => $panel_id,
					'name' => $panel_label,
					'before_widget' => $before_widget,
					'after_widget' => $after_widget,
					'before_title' => $before_title,
					'after_title' => $after_title,
					)
				);
		}
	} # register()
	
	
	/**
	 * display()
	 *
	 * @param string $panel_id
	 * @return void
	 **/

	function display($panel_id) {
		if ( $panel_id && !class_exists('widget_contexts') && is_letter() )
			break;
		global $$panel_id;
		$$panel_id = true;
		
		switch ( $panel_id ) {
		case 'left_sidebar':
			dynamic_sidebar('sidebar-1');
			break;
		case 'right_sidebar':
			dynamic_sidebar('sidebar-2');
			break;
		case 'top_sidebar':
		case 'bottom_sidebar':
		case 'the_header':
		case 'before_the_entries':
		case 'the_404':
		case 'after_the_entries':
		case 'the_footer':
		case 'the_entry':
			dynamic_sidebar($panel_id);
			break;
		case 'the_header_boxes':
		case 'the_footer_boxes':
			$class = ( $panel_id == 'the_header_boxes' ) ? 'header_boxes' : 'footer_boxes';
			$sidebars_widgets = wp_get_sidebars_widgets(false);

			if ( !empty($sidebars_widgets[$panel_id]) ) {
				echo '<div class="spacer"></div>' . "\n"
					. '<div id="' . $class . '" class="wrapper">' . "\n"
					. '<div id="' . $class . '_top"><div class="hidden"></div></div>' . "\n"
					. '<div id="' . $class . '_bg">' . "\n"
					. '<div class="wrapper_item">' . "\n";
				dynamic_sidebar($panel_id);
				echo '<div class="spacer"></div>' . "\n"
					. '</div>' . "\n"
					. '</div>' . "\n"
					. '<div id="' . $class . '_bottom"><div class="hidden"></div></div>' . "\n"
					. '</div><!-- ' . $class . ' -->' . "\n";
			}
			break;
		}
		$$panel_id = false;
	} # display()
} # sem_panels


class old_sem_panels
{
	#
	# init()
	#
	
	function init()
	{
		if ( is_admin() || isset($_GET['preview']) && isset($_GET['template']) && isset($_GET['stylesheet']) )
		{
			add_action('init', array('sem_panels', 'autofill'));
		}
	} # init()
	
	
	#
	# autofill()
	#
	
	function autofill()
	{
		$sidebars_widgets = get_option('sidebars_widgets');
		#dump($sidebars_widgets);
		#die;
		#$sidebars_widgets = array();
		
		$update = false;
		
		if ( !$sidebars_widgets['the_entry'] )
		{
			$update = true;
			$sidebars_widgets['the_entry'][] = 'entry_header';
			$sidebars_widgets['the_entry'][] = 'entry_content';
			$sidebars_widgets['the_entry'][] = 'entry_tags';
			$sidebars_widgets['the_entry'][] = 'entry_categories';
			if ( method_exists('bookmark_me', 'new_widget') )
			{
				$sidebars_widgets['the_entry'][] = bookmark_me::new_widget(1);
			}
			if ( method_exists('related_widgets', 'new_widget') )
			{
				$sidebars_widgets['the_entry'][] = related_widgets::new_widget();
			}
			$sidebars_widgets['the_entry'][] = 'entry_comments';
		}
		
		if ( $update )
		{
			global $sem_options;
			
			if ( !$sidebars_widgets['the_header'] )
			{
				$sidebars_widgets['the_header'][] = 'header';
				$sidebars_widgets['the_header'][] = 'navbar';
				$sidebars_widgets['the_header'][] = 'header_boxes';
			}

			if ( !$sidebars_widgets['before_the_entries'] )
			{
				$sidebars_widgets['before_the_entries'][] = 'archives_header';
			}

			if ( !$sidebars_widgets['after_the_entries'] )
			{
				$sidebars_widgets['after_the_entries'][] = 'next_prev_posts';
			}

			if ( !$sidebars_widgets['the_footer'] )
			{
				$sidebars_widgets['the_footer'][] = 'footer_boxes';
				$sidebars_widgets['the_footer'][] = 'footer';
			}

			if ( $sem_options['active_layout'] == 'mts'
				&& !$sidebars_widgets['sidebar-1'] && !$sidebars_widgets['sidebar-2']
			) {
				if ( method_exists('silo', 'new_widget') )
				{
					$sidebars_widgets['sidebar-1'][] = silo::new_widget();
				}
				if ( method_exists('fuzzy_widgets', 'new_widget') )
				{
					$sidebars_widgets['sidebar-1'][] = fuzzy_widgets::new_widget();
				}
				if ( method_exists('newsletter_manager', 'new_widget') )
				{
					$sidebars_widgets['sidebar-2'][] = newsletter_manager::new_widget();
				}
				if ( method_exists('subscribe_me', 'new_widget') )
				{
					$sidebars_widgets['sidebar-2'][] = subscribe_me::new_widget();
				}
			}
			
			update_option('sidebars_widgets', $sidebars_widgets);
			
			if ( method_exists('inline_widgets', 'autofill') )
			{
				inline_widgets::autofill();
			}

			if ( method_exists('feed_widgets', 'autofill') )
			{
				feed_widgets::autofill();
			}
			
			if ( function_exists('export_ad_spaces')
				&& class_exists('ad_manager')
				&& class_exists('inline_widgets')
				)
			{
				export_ad_spaces();
			}
			
			#dump( get_option('sidebars_widgets') );
			wp_redirect($_SERVER['REQUEST_URI']);
			die;
		}
		
		#dump( $sidebars_widgets );
	} # autofill()
} # old_sem_panels
?>