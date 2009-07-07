<?php
/**
 * sem_panels
 *
 * @package Semiologic Theme
 **/

sem_panels::register();

if ( !defined('DOING_CRON') && intval(get_option('init_sem_panels')) )
	add_action('init', array('sem_panels', 'init_widgets'), 2000);

class sem_panels {
	/**
	 * register()
	 *
	 * @return void
	 **/

	function register() {
		global $sem_options;
		
		switch ( substr_count($sem_options['active_layout'], 's') ) {
		case 2:
			register_sidebar(
				array(
					'id' => 'sidebar-1',
					'name' => __('Left Sidebar', 'sem-theme'),
					)
				);
			register_sidebar(
				array(
					'id' => 'sidebar-2',
					'name' => __('Right Sidebar', 'sem-theme'),
					)
				);
			break;

		case 1:
			register_sidebar(
				array(
					'id' => 'sidebar-1',
					'name' => __('Sidebar', 'sem-theme'),
					)
				);
			break;
		}
		
		if ( strpos($sem_options['active_layout'], 'e') !== false ) {
			register_sidebar(
				array(
					'id' => 'ext_sidebar',
					'name' => __('Ext Sidebar', 'sem-theme'),
					)
				);
		}
		
		foreach ( array('sidebar', 'sidebar2', 'ext_sidebar') as $sidebar ) {
			add_action('display_' . $sidebar, array('sem_panels', $sidebar));
		}
		
		foreach ( array(
			'the_header' => __('Header Area', 'sem-theme'),
			'the_header_boxes' => __('Header Boxes Bar', 'sem-theme'),
			'before_the_entries' => __('Before the Entries', 'sem-theme'),
			'the_entry' => __('Each Entry', 'sem-theme'),
			'after_the_entries' => __('After the Entries', 'sem-theme'),
			'the_footer_boxes' => __('Footer Boxes Bar', 'sem-theme'),
			'the_footer' => __('Footer Area', 'sem-theme'),
			'the_404' => __('Not Found Error (404)', 'sem-theme'),
		) as $panel_id => $panel_label ) {
			if ( $panel_id != 'the_404' )
				add_action($panel_id, array('sem_panels', $panel_id));
			else
				add_action('404_error', array('sem_panels', $panel_id));
			
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
		
		switch ( $panel_id ) {
		case 'left_sidebar':
			dynamic_sidebar('sidebar-1');
			break;
		case 'right_sidebar':
			dynamic_sidebar('sidebar-2');
			break;
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

			echo '<div id="' . $class . '" class="' . $class . '">';
			dynamic_sidebar($panel_id);
			echo '<div class="spacer"></div>' . "\n";
			echo '</div><!-- ' . $class . ' -->' . "\n";
			break;
		}
	} # display()
	
	
	/**
	 * init_widgets()
	 *
	 * @return void
	 **/

	function init_widgets() {
		if ( is_admin() ) {
			global $wp_filter;
			$filter_backup = $wp_filter['sidebars_widgets'];
			unset($wp_filter['sidebars_widgets']);
			$sidebars_widgets = wp_get_sidebars_widgets(false);
			$wp_filter['sidebars_widgets'] = $filter_backup;
			$sidebars_widgets = sem_panels::install($sidebars_widgets);
			wp_set_sidebars_widgets($sidebars_widgets);
			$sidebars_widgets = sem_panels::upgrade($sidebars_widgets);
			wp_set_sidebars_widgets($sidebars_widgets);
			update_option('init_sem_panels', '0');
		} else {
			global $_wp_sidebars_widgets;
			if ( empty($_wp_sidebars_widgets) )
				$_wp_sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
			$_wp_sidebars_widgets = sem_panels::install($_wp_sidebars_widgets);
			$_wp_sidebars_widgets = sem_panels::upgrade($_wp_sidebars_widgets);
		}
	} # init_widgets()
	
	
	/**
	 * install()
	 *
	 * @param array $sidebars_widgets
	 * @return array $sidebars_widgets
	 **/

	function install($sidebars_widgets) {
		if ( !empty($sidebars_widgets['the_entry']) )
			return $sidebars_widgets;
		
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
				'footer_boxes',
				'footer',
				),
			'sidebar-1' => array(
				'newsletter_manager',
				'subscribe_me',
				class_exists('nav_menu') ? 'nav_menu' : null,
				class_exists('fuzzy_widget') ? 'fuzzy_widget' : null,
				),
			'the_404' => array(
				),
			);
		
		$registered_sidebars = array_keys($wp_registered_sidebars);
		$registered_sidebars = array_diff($registered_sidebars, array('wp_inactive_widgets'));
		foreach ( $registered_sidebars as $sidebar )
			$sidebars_widgets[$sidebar] = (array) $sidebars_widgets[$sidebar];
		$sidebars_widgets['wp_inactive_widgets'] = (array) $sidebars_widgets['wp_inactive_widgets'];
		
		# convert left/right sidebars into sidebar-1/-2
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
						if ( !is_array($sidebars_widgets[$sidebar]) )
							continue;
						
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
		
		if ( isset($sidebars_widgets['array_version']) && $sidebars_widgets['array_version'] == 3 )
			$sidebars_widgets['wp_inactive_widgets'] = array_merge($sidebars_widgets['wp_inactive_widgets']);
		else
			unset($sidebars_widgets['wp_inactive_widgets']);
		
		return $sidebars_widgets;
	} # install()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $sidebars_widgets
	 * @return array $sidebars_widgets
	 **/

	function upgrade($sidebars_widgets) {
		global $wp_widget_factory;
		
		if ( !is_active_widget(false, false, 'blog_header') ) {
			$sidebars_widgets['before_the_entries'] = (array) $sidebars_widgets['before_the_entries'];
			$key = array_search('archives_header', $sidebars_widgets['before_the_entries']);
			$widget_id = $wp_widget_factory->widgets['blog_header']->id;
			if ( $key !== false )
				$sidebars_widgets['before_the_entries'][$key] = $widget_id;
			else
				array_unshift($sidebars_widgets['before_the_entries'], $widget_id);
		}
		
		if ( !is_active_widget(false, false, 'blog_footer') ) {
			$sidebars_widgets['after_the_entries'] = (array) $sidebars_widgets['after_the_entries'];
			$key = array_search('next_prev_posts', $sidebars_widgets['after_the_entries']);
			if ( $key === false )
				$key = array_search('nextprev-posts', $sidebars_widgets['after_the_entries']);
			$widget_id = $wp_widget_factory->widgets['blog_footer']->id;
			if ( $key !== false )
				$sidebars_widgets['after_the_entries'][$key] = $widget_id;
			else
				array_push($sidebars_widgets['after_the_entries'], $widget_id);
		}
		
		if ( !is_active_widget(false, false, 'navbar') ) {
			$sidebars_widgets['the_header'] = (array) $sidebars_widgets['the_header'];
			$key = array_search('header-nav-menu', $sidebars_widgets['the_header']);
			$widget_id = $wp_widget_factory->widgets['navbar']->id;
			if ( $key !== false )
				$sidebars_widgets['the_header'][$key] = $widget_id;
		}
		
		if ( !is_active_widget(false, false, 'footer') ) {
			$sidebars_widgets['the_footer'] = (array) $sidebars_widgets['the_footer'];
			$key = array_search('footer-nav-menu', $sidebars_widgets['the_footer']);
			$widget_id = $wp_widget_factory->widgets['footer']->id;
			if ( $key !== false )
				$sidebars_widgets['the_footer'][$key] = $widget_id;
		}
		
		foreach ( array(
			'entry-header' => 'entry_header',
			'entry-content' => 'entry_content',
			'entry-tags' => 'entry_tags',
			'entry-categories' => 'entry_categories',
			'entry-comments' => 'entry_comments',
			) as $old_id => $new_id )
		if ( !is_active_widget(false, false, $new_id) ) {
			$sidebars_widgets['the_entry'] = (array) $sidebars_widgets['the_entry'];
			$key = array_search($old_id, $sidebars_widgets['the_entry']);
			$widget_id = $wp_widget_factory->widgets[$new_id]->id;
			if ( $key !== false )
				$sidebars_widgets['the_entry'][$key] = $widget_id;
		}
		
		if ( !is_active_widget(false, false, 'header_boxes') ) {
			$sidebars_widgets['the_header'] = (array) $sidebars_widgets['the_header'];
			$widget_id = $wp_widget_factory->widgets['header_boxes']->id;
			array_push($sidebars_widgets['the_header'], $widget_id);
		}
		
		if ( !is_active_widget(false, false, 'footer_boxes') ) {
			$sidebars_widgets['the_footer'] = (array) $sidebars_widgets['the_footer'];
			$widget_id = $wp_widget_factory->widgets['footer_boxes']->id;
			array_unshift($sidebars_widgets['the_footer'], $widget_id);
		}
		
		return $sidebars_widgets;
	} # upgrade()
	
	
	/**
	 * sidebar()
	 *
	 * @return void
	 **/

	function sidebar() {
		global $sem_options;
		if ( strpos(apply_filters('active_layout', $sem_options['active_layout']), 's') !== false ) {
			include sem_path . '/sidebar.php';
		}
	} # sidebar()
	
	
	/**
	 * sidebar2()
	 *
	 * @return void
	 **/

	function sidebar2() {
		global $sem_options;
		if ( substr_count(apply_filters('active_layout', $sem_options['active_layout']), 's') == 2 ) {
			include sem_path . '/sidebar2.php';
		}
	} # sidebar2()
	
	
	/**
	 * ext_sidebar()
	 *
	 * @return void
	 **/

	function ext_sidebar() {
		global $sem_options;
		if ( strpos(apply_filters('active_layout', $sem_options['active_layout']), 'e') !== false ) {
			include sem_path . '/sidebar-ext.php';
		}
	} # ext_sidebar()
	
	
	/**
	 * the_header()
	 *
	 * @return void
	 **/

	function the_header() {
		return sem_panels::display('the_header');
	} # the_header()
	
	
	/**
	 * the_header_boxes()
	 *
	 * @return void
	 **/

	function the_header_boxes() {
		return sem_panels::display('the_header_boxes');
	} # the_header_boxes()
	
	
	/**
	 * before_the_entries()
	 *
	 * @return void
	 **/

	function before_the_entries() {
		return sem_panels::display('before_the_entries');
	} # before_the_entries()
	
	
	/**
	 * the_entry()
	 *
	 * @return void
	 **/

	function the_entry() {
		return sem_panels::display('the_entry');
	} # the_entry()
	
	
	/**
	 * after_the_entries()
	 *
	 * @return void
	 **/

	function after_the_entries() {
		return sem_panels::display('after_the_entries');
	} # after_the_entries()
	
	
	/**
	 * the_footer_boxes()
	 *
	 * @return void
	 **/

	function the_footer_boxes() {
		return sem_panels::display('the_footer_boxes');
	} # the_footer_boxes()
	
	
	/**
	 * the_footer()
	 *
	 * @return void
	 **/

	function the_footer() {
		return sem_panels::display('the_footer');
	} # the_footer()
} # sem_panels
?>