<?php
class sem_nav_menus_admin
{
	#
	# init()
	#
	
	function init()
	{
		if ( strpos($_SERVER['REQUEST_URI'], '/wp-admin/widgets.php') !== false )
		{
			// add_action('admin_head', array('sem_nav_menus_admin', 'css'));
			// add_action('admin_print_scripts', array('sem_nav_menus_admin', 'register_scripts'));
		}

		if ( get_option('sem_nav_menus_cache') === false )
		{
			update_option('sem_nav_menus_cache', array());
		}

		if ( version_compare(mysql_get_server_info(), '4.1', '<') )
		{
			add_action('admin_notices', array('sem_nav_menus_admin', 'mysql_warning'));
		}
	} # init()
	
	
	#
	# mysql_warning()
	#
	
	function mysql_warning()
	{
		echo '<div class="error">'
			. '<p><b style="color: firebrick;">Semiologic Nav Menus Error</b><br /><b>Your MySQL version is lower than 4.1.</b> It\'s time to <a href="http://www.semiologic.com/resources/wp-basics/wordpress-server-requirements/">change hosts</a> if yours doesn\'t want to upgrade.</p>'
			. '</div>';
	} # mysql_warning()
	
	
	#
	# register_scripts()
	#
	
	function register_scripts()
	{
		$plugin_path = sem_url . '/js/';
		
		wp_enqueue_script('jquery-livequery', $plugin_path . 'jquery.livequery.js', array('jquery'),  '1.1', true);
		wp_enqueue_script( 'nav-menus', $plugin_path . 'admin.js', array('jquery-ui-sortable', 'jquery-livequery'),  '20090422', true);
	} # register_scripts()
	
	
	#
	# css()
	#
	
	function css()
	{
		echo '<link rel="stylesheet" type="text/css" href="'
			. sem_url . '/css/admin.css'
			. '">' . "\n";
	} # css()


	#
	# widget_control()
	#

	function widget_control($area)
	{
		global $wpdb;
		static $pages;
		static $page_labels;
		static $i = 1;

		global $sem_nav_menus;

		if ( $_POST['update_sem_' . $area]['nav_menu'] )
		{
			$opt = (array) $_POST['nav-menu'][$area];
			
			$display_sep = isset($opt['display_sep']);
			
			$_items = (array) $opt['items'];
		
			$items = array();
		
			foreach ( $_items as $_item )
			{
				$item = array();

				$item['type'] = $_item['type'];
			
				if ( !in_array($item['type'], array('home', 'url', 'page')) )
				{
					continue;
				}
			
				$label = trim(strip_tags(stripslashes($_item['label'])));
			
				switch ( $item['type'] )
				{
					case 'home':
						$item['label'] = $label;
						break;
					case 'url':
						$item['ref'] = trim(strip_tags(stripslashes($_item['ref'])));
						$item['label'] = $label;
						break;
					case 'page':
						$item['ref'] = intval($_item['ref']);
						$item['label'] = $label;
						break;
				}
			
				$items[] = $item;
			}
			
			$sem_nav_menus[$area] = compact( 'items', 'display_sep' );

			update_option('sem_nav_menus', $sem_nav_menus);
			sem_nav_menus::clear_cache();
		}
		
		
		if ( !isset($pages) )
		{
			$pages = (array) $wpdb->get_results("
				SELECT	posts.*,
						post_title as post_label
				FROM	$wpdb->posts as posts
				WHERE	post_type = 'page'
				AND		post_status = 'publish'
				AND		post_parent = 0
				ORDER BY menu_order, post_title
				");
			
			update_post_cache($pages);
			
			$page_labels = array();
			
			foreach ( $pages as $page )
			{
				$page_labels[$page->ID] = $page->post_label;
			}
		}
		
		$number = $area;
		$ops = (array) $sem_nav_menus[$number];

		extract($ops);

		echo '<h3>Menu Items</h3>';
		
		echo '<div style="margin: 0px 0px 6px 0px;">' . "\n"
			. '<div style="width: 100px; float: left; padding-top: 2px;">'
			. '<label for="nav-menu-items-' . $number . '-select">'
			. __('Items', 'nav-menus')
			. '</label>'
			. '</div>' . "\n"
			. '<div style="width: 350px; float: right;">' . "\n";
		
		echo '<select id="nav-menu-items-' . $number . '-select" class="nav_menu_item_select"'
			. '>';
		
		echo '<optgroup label="' . attribute_escape('Url') . '">';
		
		$value = 'type=home&amp;url=' . urlencode(user_trailingslashit(get_option('home')));
		
		echo '<option value="' . $value . '"'
			. ' class="nav_menu_item_home"'
			. '>'
			. 'Home'
			. '</option>';
		
		$value = 'type=url';
		
		echo '<option value="' . $value . '"'
			. ' class="nav_menu_item_url"'
			. '>'
			. 'Url'
			. '</option>';
		
		echo '</optgroup>';
		
		echo '<optgroup label="' . attribute_escape('Page') . '">';
		
		foreach ( $pages as $page )
		{
			$value = 'type=page&amp;ref=' . $page->ID . '&amp;url=' . urlencode(get_permalink($page->ID));

			echo '<option value="' . $value . '"'
				. ' class="nav_menu_item_page"'
				. '>'
				. attribute_escape($page->post_title)
				. '</option>';
		}
		
		echo '</optgroup>';

		echo '</select>';
		
		echo '&nbsp;<input type="button" id="nav-menu-items-' . $number . '-add"'
			. ' class="nav_menu_item_button nav_menu_item_button_add"'
			. ' value="+" />';
		
		echo '<div id="nav-menu-items-' . $number . '" class="nav_menu_items"'
			. '>' . "\n";
		
		if ( !empty($items) )
		{
			foreach ( $items as $item )
			{
				$item_id = md5(serialize($item) . '-' . $area . '-' . $i++);
				
				if ( $item['type'] == 'page' )
				{
					if ( !isset($page_labels[$item['ref']]) ) continue;
				}
				
				echo '<div class="button nav_menu_item" id="' . $item_id . '">' . "\n"
						. '<div class="nav_menu_item_header">' . "\n"
							. '<input type="text" class="nav_menu_item_label"'
								. ' name="nav-menu[' . $number . '][items][' . $item_id . '][label]"'
								. ' value="' . attribute_escape($item['label']) . '"'
								. ' />'
							. '<input type="hidden" name="nav-menu[' . $number . '][items][' . $item_id . '][type]"'
								. ' value="' . attribute_escape($item['type']) . '"'
								. ' />'
							. '&nbsp;<input type="button" id="' . $item_id . '-remove-' . $number . '"'
								. ' class="nav_menu_item_button nav_menu_item_button_remove"'
								. ' tabindex="-1" value="-" />'
							. '</div>' . "\n"
						. '<div class="nav_menu_item_content">' . "\n";

				switch ( $item['type'] )
				{
				case 'url':
					echo '<input type="text" name="nav-menu[' . $number . '][items][' . $item_id . '][ref]"'
						. ' class="nav_menu_item_ref"'
						. ' value="' . attribute_escape($item['ref']) . '"'
						. ' />';
					break;

				case 'home':
					$url = user_trailingslashit(get_option('home'));
					break;
				case 'page':
					echo '<input type="hidden" name="nav-menu[' . $number . '][items][' . $item_id . '][ref]"'
						. ' value="' . intval($item['ref']) . '"'
						. ' />';
					$url = get_permalink($item['ref']);
					break;
				}

				switch ( $item['type'] )
				{
				case 'home':
				case 'page':
					echo '&rarr;&nbsp;<a href="' . $url . '" class="nav_menu_item_preview" onclick="window.open(this.href); return false;">'
						. attribute_escape($item['label'])
						. '</a>';
					break;

				}

				echo '</div>' . "\n"
					. '</div>' . "\n";
			}
		}
		else
		{
			echo '<div class="nav_menu_item_empty">'
				. 'Empty Nav Menu'
				. '</div>';
		}

		echo '</div>' . "\n";
		
		echo '</div>' . "\n"
			. '<div style="clear: both;"></div>' . "\n"
			. '</div>' . "\n";
			
		echo '<div style="margin: 0px 0px 6px 0px;">' . "\n"
			. '<div style="width: 100px; float: left; padding-top: 2px;">' . "\n"
			. '&nbsp;'
			. '</div>' . "\n"
			. '<div style="width: 350px; float: right;">' . "\n"
			. '<label>'
			. '<input type="checkbox"'
			. ' id="nav-menu-display_sep-' . $number . '" name="nav-menu[' . $number . '][display_sep]"'
			. ( $display_sep
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. 'Display Menu Seperator (|)'
			. '</label>'
			. '</div>' . "\n"
			. '<div style="clear: both;"></div>' . "\n"
			. '</div>' . "\n";				
	} # widget_control()
} # sem_nav_menus_admin

sem_nav_menus_admin::init();
?>