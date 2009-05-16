<?php
/**
 * sem_panels
 *
 * @package Semiologic Reloaded
 **/

sem_panels::register();

add_action('init', array('sem_panels', 'autofill'), 0);

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
		if ( $panel_id != 'the_entry' && !class_exists('widget_contexts') && is_letter() )
			return;
		
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
			if ( !is_active_sidebar($panel_id) )
			 	break;
			
			$class = ( $panel_id == 'the_header_boxes' ) ? 'header_boxes' : 'footer_boxes';

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
			break;
		}
		$$panel_id = false;
	} # display()
	
	
	/**
	 * autofill()
	 *
	 * @return void
	 **/

	function autofill() {
		if ( is_active_sidebar('the_entry') )
			return;
		
		add_filter('sidebars_widgets', array('sem_panels', 'sidebars_widgets'));
	} # autofill()
	
	
	/**
	 * sidebars_widgets()
	 *
	 * @param array $sidebars_widgets
	 * @return array $sidebars_widgets
	 **/

	function sidebars_widgets($sidebars_widgets) {
		#dump($sidebars_widgets);die;
		
		global $wp_widget_factory;
		global $wp_registered_sidebars;
		
		$default_widgets = array(
			'the_header' => array(
				'header',
				'navbar',
				'header_boxes',
				),
			'before_the_entries' => array(
				'blog_header',
				),
			'the_entry' => array(
				'entry_header',
				'entry_content',
				'entry_tags',
				'entry_categories',
				'bookmark_me',
				'related_widget',
				'entry_comments',
				),
			'after_the_entries' => array(
				'blog_footer',
				),
			'the_footer' => array(
				'footer',
				),
			'sidebar-1' => array(
				class_exists('nav_menu') ? 'nav_menu' : 'WP_Widget_Pages',
				class_exists('fuzzy_widget') ? 'fuzzy_widget' : null,
				'WP_Widget_Categories',
				'WP_Widget_Archives',
				),
			'sidebar-2' => array(
				'newsletter_manager',
				'subscribe_me',
				!class_exists('sem_admin_menu') ? 'WP_Widget_Meta' : null,
				),
			'the_404' => array(
				'WP_Widget_Tag_Cloud',
				class_exists('fuzzy_widget') ? 'fuzzy_widget' : 'WP_Widget_Recent_Posts',
				'WP_Widget_Categories',
				'WP_Widget_Archives',
				class_exists('silo_map') ? 'silo_map' : 'WP_Widget_Pages',
				),
			);
		
		$registered_sidebars = array_keys($wp_registered_sidebars);
		$registered_sidebars = array_diff($registered_sidebars, array('wp_inactive_widgets'));
		foreach ( $registered_sidebars as $sidebar )
			$sidebars_widgets[$sidebar] = (array) $sidebars_widgets[$sidebar];
		$sidebars_widgets['wp_inactive_widgets'] = (array) $sidebars_widgets['wp_inactive_widgets'];
		
		# convert left/right sidebars into sidebar-1/-2 if needed
		foreach ( array(
			'sidebar-1' => array(
				'left',
				'left-sidebar',
				'left_sidebar',
				),
			'sidebar-2' => array(
				'right',
				'right-sidebar',
				'right_sidebar',
				),
			) as $sidebar_id => $old_sidebar_ids ) {
			if ( !empty($sidebars_widgets[$sidebar_id]) )
				continue;
			foreach ( $old_sidebar_ids as $old_sidebar_id ) {
				if ( !empty($sidebars_widgets[$old_sidebar_id]) ) {
					$sidebars_widgets[$sidebar_id] = $sidebars_widgets[$old_sidebar_ids];
					unset($sidebars_widgets[$old_sidebar_ids]);
					break;
				}
			}
		}
		
		foreach ( $default_widgets as $panel => $widgets ) {
			if ( empty($sidebars_widgets[$panel]) )
				$sidebars_widgets[$panel] = (array) $sidebars_widgets[$panel];
			else
				continue;
			
			foreach ( $widgets as $widget ) {
				if ( !is_a($wp_widget_factory->widgets[$widget], 'WP_Widget') )
					continue;
				
				$widget_ids = array_keys((array) $wp_widget_factory->widgets[$widget]->get_settings());
				$widget_id_base = $wp_widget_factory->widgets[$widget]->id_base;
				$new_widget_number = $widget_ids ? max($widget_ids) + 1 : 2;
				foreach ( $widget_ids as $key => $widget_id )
					$widget_ids[$key] = $widget_id_base . '-' . $widget_id;
				
				# check if active already
				foreach ( $widget_ids as $widget_id ) {
					if ( in_array($widget_id, $sidebars_widgets[$panel]) )
						continue 2;
				}

				# use an inactive widget if available
				foreach ( $widget_ids as $widget_id ) {
					foreach ( array_keys($sidebars_widgets) as $sidebar ) {
						$key = array_search($widget_id, $sidebars_widgets[$sidebar]);
						
						if ( $key === false )
							continue;
						elseif ( in_array($sidebar, $registered_sidebars) ) {
							continue 2;
						}
						
						unset($sidebars_widgets[$sidebar][$key]);
						$sidebars_widgets[$panel][] = $widget_id;
						continue 3;
					}
					
					$sidebars_widgets[$panel][] = $widget_id;
					continue 2;
				}
				
				# create a widget on the fly
				$new_settings = $wp_widget_factory->widgets[$widget]->get_settings();
				
				$new_settings[$new_widget_number] = array();
				$wp_widget_factory->widgets[$widget]->_set($new_widget_number);
				$wp_widget_factory->widgets[$widget]->_register_one($new_widget_number);
				
				$widget_id = "$widget_id_base-$new_widget_number";
				$sidebars_widgets[$panel][] = $widget_id;
				
				$wp_widget_factory->widgets[$widget]->save_settings($new_settings);
			}
		}
		
		$sidebars_widgets['wp_inactive_widgets'] = array_merge($sidebars_widgets['wp_inactive_widgets']);
		
		#dump($sidebars_widgets);die;
		
		return $sidebars_widgets;
	} # sidebars_widgets()
} # sem_panels
?>