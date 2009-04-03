<?php
class sem_panels
{
	#
	# init()
	#
	
	function init()
	{
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
		
		if ( strpos($sem_options['active_layout'], 't') !== false )
		{
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
		}
		else
		{
			switch ( substr_count($sem_options['active_layout'], 's') )
			{
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
			'the_top_sidebar',
			'the_bottom_sidebar',
			'the_left_sidebar',
			'the_right_sidebar',
			) as $panel_id )
		{
			add_action($panel_id, array('sem_panels', $panel_id));
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
			) as $panel_id => $panel_label )
		{
			add_action($panel_id, array('sem_panels', $panel_id));
			
			$before_title = '<h2>';
			$after_title = '</h2>' . "\n";
			
			switch ( $panel_id )
			{
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
			$sidebars_widgets['the_entry'][] = 'entry_actions';
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

			if ( !$sidebars_widgets['sidebar-1'] && $sem_options['active_layout'] == 'ms' )
			{
				if ( method_exists('newsletter_manager', 'new_widget') )
				{
					$sidebars_widgets['sidebar-1'][] = newsletter_manager::new_widget();
				}
				if ( method_exists('subscribe_me', 'new_widget') )
				{
					$sidebars_widgets['sidebar-1'][] = subscribe_me::new_widget();
				}
				if ( method_exists('silo', 'new_widget') )
				{
					$sidebars_widgets['sidebar-1'][] = silo::new_widget();
				}
				if ( method_exists('fuzzy_widgets', 'new_widget') )
				{
					$sidebars_widgets['sidebar-1'][] = fuzzy_widgets::new_widget();
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
	
	
	#
	# the_header()
	#
	
	function the_header()
	{
		$GLOBALS['the_header'] = true;
		
		dynamic_sidebar('the_header');
		
		$GLOBALS['the_header'] = false;
	} # the_header()
	
	
	#
	# the_header_boxes()
	#
	
	function the_header_boxes()
	{
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( $sidebars_widgets['the_header_boxes'] )
		{
			$GLOBALS['the_header_boxes'] = true;
			echo '<div class="spacer"></div>' . "\n"
				. '<div id="header_boxes" class="wrapper">' . "\n"
				. '<div id="header_boxes_top"><div class="hidden"></div></div>' . "\n"
				. '<div id="header_boxes_bg">' . "\n"
				. '<div class="wrapper_item">' . "\n";
			dynamic_sidebar('the_header_boxes');
			echo '<div class="spacer"></div>' . "\n"
				. '</div>' . "\n"
				. '</div>' . "\n"
				. '<div id="header_boxes_bottom"><div class="hidden"></div></div>' . "\n"
				. '</div><!-- header_boxes -->' . "\n";
			$GLOBALS['the_header_boxes'] = false;
		}
	} # the_header_boxes()
	
	
	#
	# before_the_entries()
	#

	function before_the_entries()
	{
		$GLOBALS['before_the_entries'] = true;
		dynamic_sidebar('before_the_entries');
		$GLOBALS['before_the_entries'] = false;
	} # before_the_entries()


	#
	# the_entry()
	#
	
	function the_entry()
	{
		$GLOBALS['the_entry'] = true;
		$GLOBALS['sem_entry'] = array();
		dynamic_sidebar('the_entry');
		$GLOBALS['the_entry'] = false;
	} # the_entry()


	#
	# after_the_entries()
	#

	function after_the_entries()
	{
		$GLOBALS['after_the_entries'] = true;
		dynamic_sidebar('after_the_entries');
		$GLOBALS['after_the_entries'] = false;
	} # after_the_entries()
	
	
	#
	# the_footer_boxes()
	#
	
	function the_footer_boxes()
	{
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( $sidebars_widgets['the_footer_boxes'] )
		{
			$GLOBALS['the_footer_boxes'] = true;
			echo '<div class="spacer"></div>' . "\n"
				. '<div id="footer_boxes" class="wrapper">' . "\n"
				. '<div id="footer_boxes_top"><div class="hidden"></div></div>' . "\n"
				. '<div id="footer_boxes_bg">' . "\n"
				. '<div class="wrapper_item">' . "\n";
			dynamic_sidebar('the_footer_boxes');
			echo '<div class="spacer"></div>' . "\n"
				. '</div>' . "\n"
				. '</div>' . "\n"
				. '<div id="footer_boxes_bottom"><div class="hidden"></div></div>' . "\n"
				. '</div><!-- footer_boxes -->' . "\n";
			$GLOBALS['the_footer_boxes'] = false;
		}
	} # the_footer_boxes()
	
	
	#
	# the_footer()
	#
	
	function the_footer()
	{
		$GLOBALS['the_footer'] = true;
		
		dynamic_sidebar('the_footer');
		
		$GLOBALS['the_footer'] = false;
	} # the_footer()
	
	
	#
	# the_404()
	#
	
	function the_404()
	{
		$GLOBALS['the_404'] = true;
		dynamic_sidebar('the_404');
		$GLOBALS['the_404'] = false;
	} # the_404()
	
	
	#
	# the_top_sidebar()
	#
	
	function the_top_sidebar()
	{
		dynamic_sidebar('top_sidebar');
	} # the_top_sidebar()
	
	
	#
	# the_bottom_sidebar()
	#
	
	function the_bottom_sidebar()
	{
		dynamic_sidebar('bottom_sidebar');
	} # the_bottom_sidebar()
	
	
	#
	# the_left_sidebar()
	#
	
	function the_left_sidebar()
	{
		dynamic_sidebar('sidebar-1');
	} # the_left_sidebar()
	
	
	#
	# the_right_sidebar()
	#
	
	function the_right_sidebar()
	{
		dynamic_sidebar('sidebar-2');
	} # the_right_sidebar()
} # sem_panels

sem_panels::init();
?>