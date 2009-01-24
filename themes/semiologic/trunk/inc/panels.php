<?php
class sem_panels
{
	#
	# init()
	#
	
	function init()
	{
		switch ( substr_count($GLOBALS['sem_options']['active_layout'], 's') )
		{
		case 2:
			register_sidebar(
				array(
					'id' => 'sidebar-1',
					'name' => 'Left Sidebar',
					)
				);
			register_sidebar(
				array(
					'id' => 'sidebar-2',
					'name' => 'Right Sidebar',
					)
				);
			break;

		case 1:
			register_sidebar(
				array(
					'id' => 'sidebar-1',
					'name' => 'Sidebar',
					)
				);
			break;
		}

		if ( strpos($GLOBALS['sem_options']['active_layout'], 'e') !== false )
		{
			register_sidebar(
				array(
					'id' => 'ext_sidebar',
					'name' => 'Ext Sidebar',
					)
				);
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
			
			switch ( $panel_id )
			{
			case 'the_header':
			case 'the_footer':
				$before_widget = '<div class="%2$s"><div class="pad">' . "\n";
				$after_widget = '</div></div>' . "\n";
				break;
			
			case 'the_header_boxes':
			case 'the_footer_boxes':
				$before_widget = '<div class="inline_box %2$s"><div class="pad">' . "\n";
				$after_widget = '</div></div>' . "\n";
				break;

			default:
				$before_widget = '<div class="%2$s">' . "\n";
				$after_widget = '</div>' . "\n";
				break;
			}
			
			register_sidebar(
				array(
					'id' => $panel_id,
					'name' => $panel_label,
					'before_widget' => $before_widget,
					'after_widget' => $after_widget,
					'before_title' => '<h2>',
					'after_title' => '</h2>' . "\n",
					)
				);
		}
		
		if ( is_admin() )
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
			if ( method_exists('author_image', 'new_widget') )
			{
				$sidebars_widgets['the_entry'][] = author_image::new_widget();
			}
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
			echo '<div id="header_boxes" class="header_boxes">';
			dynamic_sidebar('the_header_boxes');
			echo '<div class="spacer"></div>' . "\n";
			echo '</div>' . "\n";
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
			echo '<div id="footer_boxes" class="footer_boxes">';
			dynamic_sidebar('the_footer_boxes');
			echo '<div class="spacer"></div>' . "\n";
			echo '</div>' . "\n";
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
} # sem_panels

sem_panels::init();






#
# these are kept around in case anyone hooks into it using custom.php files
#


#
# display_sidebar()
#

function display_sidebar()
{
	if ( strpos(get_active_layout(true), 's') !== false )
	{
		include_once sem_path . '/sidebar.php';
	}
} # end display_sidebar()

add_action('display_sidebar', 'display_sidebar');


#
# display_sidebar2()
#

function display_sidebar2()
{
	if ( substr_count(get_active_layout(true), 's') == 2 )
	{
		include_once sem_path . '/sidebar2.php';
	}
} # end display_sidebar2()

add_action('display_sidebar2', 'display_sidebar2');


#
# display_ext_sidebar()
#

function display_ext_sidebar()
{
	if ( strpos(get_active_layout(true), 'e') !== false )
	{
		include_once sem_path . '/sidebar-ext.php';
	}
} # end display_ext_sidebar()

add_action('display_ext_sidebar', 'display_ext_sidebar');
?>