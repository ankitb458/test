<?php
/**
 * sem_template
 *
 * @package Semiologic Reloaded
 **/

class sem_template {
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/

	function admin_menu() {
		add_theme_page(
			__('Manage Custom', 'sem-reloaded'),
			__('Custom CSS', 'sem-reloaded'),
			'switch_themes',
			'custom',
			array('sem_custom', 'edit_options')
			);
		add_theme_page(
			__('Manage Header', 'sem-reloaded'),
			__('Header', 'sem-reloaded'),
			'switch_themes',
			'header',
			array('sem_header', 'edit_options')
			);
		add_theme_page(
			__('Manage Layout', 'sem-reloaded'),
			__('Layout', 'sem-reloaded'),
			'switch_themes',
			'layout',
			array('sem_layout', 'edit_options')
			);
		add_theme_page(
			__('Manage Skin', 'sem-reloaded'),
			__('Skin', 'sem-reloaded'),
			'switch_themes',
			'skin',
			array('sem_skin', 'edit_options')
			);
	} # admin_menu()
	
	
	/**
	 * meta_boxes()
	 *
	 * @return void
	 **/

	function meta_boxes() {
		if ( current_user_can('switch_themes') ) {
			add_meta_box('sem_header', __('Post-Specific Header', 'sem-reloaded'), array('sem_header', 'edit_entry'), 'post');
			add_meta_box('sem_header', __('Page-Specific Header', 'sem-reloaded'), array('sem_header', 'edit_entry'), 'page');
		}
	} # meta_boxes()
	
	
	/**
	 * body_class()
	 *
	 * @param array $classes
	 * @return array $classes
	 **/

	function body_class($classes) {
		global $sem_options;
		
		$active_layout = apply_filters('active_layout', $sem_options['active_layout']);
		
		$classes[] = $active_layout;
		
		if ( $active_layout != 'letter' ) {
			$extra_layout = str_replace(array('s', 't'), 'm', $active_layout);
			
			if ( $extra_layout != $active_layout) {
				$classes[] = $extra_layout;
				$classes[] = str_replace(array('s', 't'), '', $active_layout)
					. ( substr_count(str_replace('t', 's', $active_layout), 's')) . 's';
			}
		}
		
		$classes[] = preg_replace("/[^a-z]+/", '_', $sem_options['active_skin']);
		
		if ( $sem_options['active_font'] )
			$classes[] = preg_replace("/[^a-z]+/", '_', $sem_options['active_font']);
		
		if ( is_page() ) {
			global $wp_the_query;
			
			$template = get_post_meta(intval($wp_the_query->get_queried_object_id()), '_wp_page_template', true);
			
			if ( $template != 'default' ) {
				$template = preg_replace("/\.[^\.]+$/", "", $template);

				$classes[] = $template;
			}
		}
		
		return $classes;
	} # body_class()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		global $wp_the_query;
		if ( is_singular() && comments_open($wp_the_query->get_queried_object_id()) ) {
			wp_enqueue_script('comment-reply');
			wp_enqueue_script('jquery');
		}
	} # scripts()
	
	
	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		global $sem_options;
		$skin_path = sem_path . '/skins/' . $sem_options['active_skin'];
		$skin_url = sem_url . '/skins/' . $sem_options['active_skin'];
		
		wp_enqueue_style('style', sem_url . '/style.css', null, sem_last_mod);
		wp_enqueue_style('layout', sem_url . '/css/layout.css', null, sem_last_mod);
		
		if ( file_exists($skin_path . '/icons.css') )
			wp_enqueue_style('custom-icons', $skin_url . '/icons.css', null, filemtime($skin_path . '/icons.css'));
		else
			wp_enqueue_style('icons', sem_url . '/css/icons.css', null, sem_last_mod);
		
		if ( isset($_GET['action']) && $_GET['action'] == 'print' ) {
			wp_enqueue_style('print', sem_url . '/css/print.css', null, sem_last_mod);
			if ( file_exists($skin_path . '/print.css') )
				wp_enqueue_style('custom-print', $skin_url . '/print.css', null, filemtime($skin_path . '/print.css'));
		} elseif ( apply_filters('active_layout', $sem_options['active_layout']) == 'letter' ) {
			wp_enqueue_style('letter', sem_url . '/css/letter.css', null, sem_last_mod);
			if ( file_exists($skin_path . '/letter.css') )
				wp_enqueue_style('custom-letter', $skin_url . '/letter.css', null, filemtime($skin_path . '/letter.css'));
		} else {
			wp_enqueue_style('skin', $skin_url . '/skin.css', null, sem_last_mod);
			if ( file_exists(sem_path . '/custom.css') )
				wp_enqueue_style('custom-theme', sem_url . '/custom.css', null, filemtime(sem_path . '/custom.css'));
			if ( file_exists($skin_path . '/custom.css') )
				wp_enqueue_style('custom-skin', $skin_url . '/custom.css', null, filemtime($skin_path . '/custom.css'));
		}
	} # styles()
	
	
	/**
	 * strip_sidebars()
	 *
	 * @param string $layout
	 * @return string $layout
	 **/

	function strip_sidebars($layout) {
		global $content_width;
		
		
		return str_replace(array('s', 't'), 'm', $layout);
	} # strip_sidebars()
	
	
	/**
	 * force_letter()
	 *
	 * @param string $layout
	 * @return string $layout
	 **/

	function force_letter($layout) {
		global $content_width;
		$content_width = 620;
		
		return 'letter';
	} # force_letter()
	
	
	/**
	 * trackback_rdf()
	 *
	 * @return void
	 **/
	
	function trackback_rdf() {
		global $wp_the_query;
		if ( is_singular() && comments_open($wp_the_query->get_queried_object_id()) ) {
			echo '<!--' . "\n";
			trackback_rdf();
			echo "\n" . '-->' . "\n";
		}
	} # trackback_rdf()
	
	
	/**
	 * wp()
	 *
	 * @param object &$wp
	 * @return void
	 **/

	function wp(&$wp) {
		static $done = false;
		
		if ( $done )
			return;
		
		if ( is_attachment() ) {
			add_filter('option_blog_public', 'false');
			add_filter('comments_open', 'false');
			add_filter('pings_open', 'false');
		} elseif ( is_404() || is_search() ) {
			add_filter('option_blog_public', 'false');
		} elseif ( !is_admin() && !current_user_can('manage_options') ) {
			# avoid cap-related issues
			if ( preg_match("|://[^/]*[Ss]e[Mm]io[Ll]o[Gg]ic[^/]+\.|i", get_option('home')) )
				add_filter('option_blog_public', 'false', 1000);
		}
		
		if ( is_singular() ) {
			global $post;
			global $wp_the_query;
			$post = $wp_the_query->posts[0];
			setup_postdata($post);
		}
		
		$done = true;
	} # wp()
	
	
	/**
	 * template_redirect()
	 *
	 * @return void
	 **/
	
	function template_redirect() {
		if ( !isset($_GET['action']) || $_GET['action'] != 'print' )
			return;

		if ( has_filter('template_redirect', 'redirect_canonical') )
			redirect_canonical();
		
		add_filter('option_blog_public', 'false');
		add_filter('comments_open', 'false');
		add_filter('pings_open', 'false');
		add_filter('active_layout', array('sem_template', 'strip_sidebars'));
		remove_action('wp_footer', array('sem_template', 'display_credits'), 5);
		
		include_once sem_path . '/print.php';
		die;
	} # template_redirect()
	
	
	/**
	 * widget_title()
	 *
	 * @param string $title
	 * @return string $title
	 **/

	function widget_title($title) {
		return $title == '&nbsp;' ? '' : $title;
	} # widget_title()
	
	
	/**
	 * display_credits()
	 *
	 * @return void
	 **/

	function display_credits() {
		global $sem_options;
		
		echo '<div id="credits">' . "\n"
			. '<div id="credits_top"><div class="hidden"></div></div>' . "\n"
			. '<div id="credits_bg">' . "\n";
		
		if ( $sem_options['credits'] ) {
			$theme_credits = sem_template::get_theme_credits();
			$skin_credits = sem_template::get_skin_credits();
			
			$credits = sprintf($sem_options['credits'], $theme_credits, $skin_credits['skin_name'], $skin_credits['skin_author']);
			
			if ( current_user_can('manage_options') ) {
				$credits .= ' - '
					. '<a href="' . esc_url(admin_url() . 'themes.php?page=skin#sem_credits') . '">'
					. __('Edit', 'sem-reloaded')
					. '</a>';
			}
			
			echo '<div class="pad">'
				. $credits
				. '</div>' . "\n";
		}
		
		echo '</div>' . "\n"
			. '<div id="credits_bottom"><div class="hidden"></div></div>' . "\n"
			. '</div><!-- credits -->' . "\n";
	} # display_credits()
	
	
	/**
	 * get_theme_credits()
	 *
	 * @return string $credits
	 **/

	function get_theme_credits() {
		if ( get_option('sem_api_key') ) {
			return '<a href="http://www.getsemiologic.com">'
				. __('Semiologic Pro', 'sem-reloaded')
				. '</a>';
		} else {
			$theme_descriptions = array(
				__('the <a href="http://www.semiologic.com/software/sem-reloaded/">Semiologic theme</a>', 'sem-reloaded'),
				__('an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to use WordPress theme</a>', 'sem-reloaded'),
				__('an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to customize WordPress theme</a>', 'sem-reloaded'),
				);
			
			$i = rand(0, sizeof($theme_descriptions) - 1);

			return $theme_descriptions[$i];
		}
	} # get_theme_credits()
	
	
	/**
	 * get_skin_credits()
	 *
	 * @return array $credits
	 **/

	function get_skin_credits() {
		global $sem_options;
		
		if ( is_admin() || !is_array($sem_options['skin_data']) ) {
			$details = sem_template::get_skin_data($sem_options['active_skin']);
			$sem_options['skin_data'] = $details;
			if ( !defined('sem_install_test') )
				update_option('sem6_options', $sem_options);
		} else {
			$details = $sem_options['skin_data'];
		}
		
		$name = $details['uri']
			? ( '<a href="' . esc_url($details['uri']) . '">'
				. $details['name']
				. '</a>' )
			: $details['name'];
		$author = $details['author_uri']
			? ( '<a href="' . esc_url($details['author_uri']) . '">'
				. $details['author_name']
				. '</a>' )
			: $details['author_name'];
		
		return array(
			'skin_name' => $name,
			'skin_author' => $author,
			);
	} # get_skin_credits()
	
	
	/**
	 * get_skin_data()
	 *
	 * @return array $data
	 **/

	function get_skin_data($skin_id) {
		$fields = array( 'name', 'uri', 'version', 'author_name', 'author_uri', 'description', 'tags' );
		
		$allowed_tags = array(
			'a' => array(
				'href' => array(),'title' => array()
				),
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array()
		);

		$fp = @fopen(sem_path . '/skins/' . $skin_id . '/skin.css', 'r');
		
		if ( !$fp ) {
			foreach ( $fields as $field )
				$$field = '';
			$tags = array();
			return compact($fields);
		}

		$skin_data = fread( $fp, 4096 );
		
		fclose($fp);
		
		$skin_data = str_replace("\r", "\n", $skin_data);

		preg_match('/Skin(?:\s+name)?\s*:(.*)/i', $skin_data, $name);
		preg_match('/Skin\s+ur[il]\s*:(.*)/i', $skin_data, $uri);
		preg_match('/Version\s*:(.*)/i', $skin_data, $version);
		preg_match('/Author\s*:(.*)/i', $skin_data, $author_name);
		preg_match('/Author\s+ur[il]\s*:(.*)/i', $skin_data, $author_uri);
		preg_match('/Description\s*:(.*)/i', $skin_data, $description);
		preg_match('/Tags\s*:(.*)/i', $skin_data, $tags);
		
		foreach ( $fields as $field ) {
			if ( !empty( ${$field} ) )
				${$field} = _cleanup_header_comment(${$field}[1]);
			else
				${$field} = '';
			
			switch ( $field ) {
			case 'uri':
			case 'author_uri':
				$$field = esc_url_raw($$field);
				break;
			case 'tags':
				$$field = strip_tags($$field);
				if ( $$field ) {
					$$field = explode(',', $$field);
					$$field = array_map('trim', $$field);
				} else {
					$$field = array();
				}
			case 'description':
				$$field = wp_kses($$field, $allowed_tags);
				break;
			default:
				$$field = strip_tags($$field);
				break;
			}
		}
		
		return compact($fields);
	} # get_skin_data()
	
	
	/**
	 * archive_query_string()
	 *
	 * @param array $query_string
	 * @return array $query_string
	 **/

	function archive_query_string($query_string) {
		parse_str($query_string, $qv);
		unset($qv['paged'], $qv['debug']);
		
		if ( empty($qv) )
			return $query_string;
		
		foreach ( array(
			'order',
			'pagename',
			'feed',
			'p',
			'page_id',
			'attachment_id',
			's',
			) as $bail ) {
			if ( !empty($qv[$bail]) )
				return $query_string;
		}
		
		global $wp_the_query;
		
		$wp_the_query->parse_query($query_string);
		
		if ( is_feed() || !is_date() )
			return $query_string;
		
		parse_str($query_string, $args);
		
		if ( !isset($args['order']) )
			$args['order'] = 'asc';
		
		$query_string = http_build_query($args);
		
		return $query_string;
	} # archive_query_string()
	
	
	/**
	 * the_header_sidebar_params()
	 *
	 * @param array $params
	 * @return array $params
	 **/

	function the_header_sidebar_params($params) {
		if ( !is_array($params) || !is_array($params[0]) || $params[0]['id'] != 'the_header' )
			return $params;
		
		global $did_header;
		global $did_navbar;
		global $did_top_widgets;
		global $did_middle_widgets;
		global $did_bottom_widgets;
		
		global $wp_registered_widgets;
		$widget_id = $params[0]['widget_id'];
		if ( is_array($wp_registered_widgets[$widget_id]['callback']) ) {
			$type = get_class($wp_registered_widgets[$widget_id]['callback'][0]);
			if ( is_a($wp_registered_widgets[$widget_id]['callback'][0], 'WP_Widget') ) {
				$instance = $wp_registered_widgets[$widget_id]['callback'][0]->get_settings();
				$instance = $instance[$wp_registered_widgets[$widget_id]['callback'][0]->number];
				if ( apply_filters('widget_display_callback', $instance, $wp_registered_widgets[$widget_id]['callback'][0], $params) === false )
					return $params;
				
				if ( is_a($wp_registered_widgets[$widget_id]['callback'][0], 'header_boxes') ) {
					if ( !is_active_sidebar('the_header_boxes') )
						return $params;
				}
			}
		} else {
			$type = $wp_registered_widgets[$widget_id]['callback'];
		}
		
		switch ( $type ) {
		case 'header':
			if ( $did_navbar ) {
				if ( $did_middle_widgets )
					echo '</div></div>' . "\n";
			} else {
				if ( $did_top_widgets )
					echo '</div></div>' . "\n";
			}
			break;
		
		case 'navbar':
			if ( $did_header ) {
				if ( $did_middle_widgets )
					echo '</div></div>' . "\n";
			} else {
				if ( $did_top_widgets )
					echo '</div></div>' . "\n";
			}
			break;
		
		default:
			if ( !$did_header && !$did_navbar ) {
				if ( !$did_top_widgets ) {
					echo '<div id="header_top_wrapper"><div id="header_top_wrapper_bg">' . "\n";
					$did_top_widgets = true;
				}
			} elseif ( $did_header && $did_navbar ) {
				if ( !$did_bottom_widgets ) {
					echo '<div id="header_bottom_wrapper"><div id="header_bottom_wrapper_bg">' . "\n";
					$did_bottom_widgets = true;
				}
			} else {
				if ( !$did_middle_widgets ) {
					echo '<div id="header_middle_wrapper"><div id="header_middle_wrapper_bg">' . "\n";
					$did_middle_widgets = true;
				}
			}
			break;
		}
		
		return $params;
	} # the_header_sidebar_params()
	
	
	/**
	 * the_footer_sidebar_params()
	 *
	 * @param array $params
	 * @return array $params
	 **/

	function the_footer_sidebar_params($params) {
		if ( !is_array($params) || !is_array($params[0]) || $params[0]['id'] != 'the_footer' )
			return $params;
		
		global $did_footer;
		global $did_top_widgets;
		global $did_bottom_widgets;
		
		global $wp_registered_widgets;
		$widget_id = $params[0]['widget_id'];
		if ( is_array($wp_registered_widgets[$widget_id]['callback']) ) {
			$type = get_class($wp_registered_widgets[$widget_id]['callback'][0]);
			if ( is_a($wp_registered_widgets[$widget_id]['callback'][0], 'WP_Widget') ) {
				$instance = $wp_registered_widgets[$widget_id]['callback'][0]->get_settings();
				$instance = $instance[$wp_registered_widgets[$widget_id]['callback'][0]->number];
				if ( apply_filters('widget_display_callback', $instance, $wp_registered_widgets[$widget_id]['callback'][0], $params) === false )
					return $params;
				
				if ( is_a($wp_registered_widgets[$widget_id]['callback'][0], 'footer_boxes') ) {
					if ( !is_active_sidebar('the_footer_boxes') )
						return $params;
				}
			}
		} else {
			$type = $wp_registered_widgets[$widget_id]['callback'];
		}
		
		switch ( $type ) {
		case 'footer':
			if ( $did_top_widgets ) {
				echo '</div></div>' . "\n";
			}
			break;
		
		default:
			if ( !$did_footer ) {
				if ( !$did_top_widgets ) {
					echo '<div id="footer_top_wrapper"><div id="footer_top_wrapper_bg">' . "\n";
					$did_top_widgets = true;
				}
			} else {
				if ( !$did_bottom_widgets ) {
					echo '<div id="footer_bottom_wrapper"><div id="footer_bottom_wrapper_bg">' . "\n";
					$did_bottom_widgets = true;
				}
			}
			break;
		}
		
		return $params;
	} # the_footer_sidebar_params()
} # sem_template

if ( !is_admin() ) {
	add_action('wp', array('sem_template', 'wp'), 0);
	add_action('template_redirect', array('sem_template' ,'template_redirect'), 0);
	add_action('wp_print_scripts', array('sem_template', 'scripts'));
	add_action('wp_print_styles', array('sem_template', 'styles'));
	add_action('wp_head', array('sem_template' ,'trackback_rdf'), 100);
	add_filter('body_class', array('sem_template', 'body_class'));
	add_filter('widget_title', array('sem_template', 'widget_title'));
	add_action('wp_footer', array('sem_template', 'display_credits'), 5);
	add_filter('query_string', array('sem_template', 'archive_query_string'), 20);
	add_filter('dynamic_sidebar_params', array('sem_template', 'the_header_sidebar_params'), 15);
	add_filter('dynamic_sidebar_params', array('sem_template', 'the_footer_sidebar_params'), 15);
	remove_action('wp_print_styles', array('external_links', 'styles'), 5);
} else {
	add_action('admin_menu', array('sem_template', 'admin_menu'));
	add_action('admin_menu', array('sem_template', 'meta_boxes'));
}
?>