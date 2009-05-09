<?php
/**
 * sem_template
 *
 * @package Semiologic Reloaded
 **/

if ( !is_admin() ) {
	add_action('wp', array('sem_template', 'wp'), 0);
	add_action('template_redirect', array('sem_template' ,'template_redirect'), 0);
	add_action('wp_print_scripts', array('sem_template', 'scripts'));
	add_action('wp_print_styles', array('sem_template', 'styles'));
	add_action('wp_head', array('sem_template' ,'trackback_rdf'), 100);
	add_filter('body_class', array('sem_template', 'body_class'));
	add_filter('widget_title', array('sem_template', 'widget_title'));
	add_action('wp_footer', array('sem_template', 'display_credits'));
}

class sem_template {
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
		$css_url = sem_url . '/css';
		$skin_dir = sem_path . '/skins/' . $sem_options['active_skin'];
		$skin_url = sem_url . '/skins/' . $sem_options['active_skin'];
		
		wp_enqueue_style('style', sem_url . '/style.css', sem_version);
		wp_enqueue_style('layout', $css_url . '/layout.css', sem_version);
		
		if ( file_exists($skin_dir . '/icons.css') )
			wp_enqueue_style('icons', $skin_url . '/icons.css', sem_version);
		else
			wp_enqueue_style('icons', $css_url . '/icons.css', sem_version);
		
		if ( isset($_GET['action']) && $_GET['action'] == 'print' ) {
			if ( file_exists($skin_dir . '/print.css') )
				wp_enqueue_style('skin', $skin_url . '/print.css', sem_version);
			else
				wp_enqueue_style('skin', $css_url . '/print.css', sem_version);
		} elseif ( apply_filters('active_layout', $sem_options['active_layout']) == 'letter' ) {
			if ( file_exists($skin_dir . '/letter.css') )
				wp_enqueue_style('skin', $skin_url . '/letter.css', sem_version);
			else
				wp_enqueue_style('skin', $css_url . '/letter.css', sem_version);
		} else {
			wp_enqueue_style('skin', $skin_url . '/skin.css', sem_version);
		}
		
		if ( file_exists(sem_path . '/custom.css') )
			wp_enqueue_style('custom', sem_url . '/custom.css', sem_version);
		if ( file_exists($skin_path . '/custom.css') )
			wp_enqueue_style('custom-skin', $skin_url . '/custom.css', sem_version);
	} # styles()
	
	
	/**
	 * strip_sidebars()
	 *
	 * @param string $layout
	 * @return string $layout
	 **/

	function strip_sidebars($layout) {
		return str_replace(array('s', 't'), 'm', $layout);
	} # strip_sidebars()
	
	
	/**
	 * force_letter()
	 *
	 * @param string $layout
	 * @return string $layout
	 **/

	function force_letter($layout) {
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
		if ( is_attachment() ) {
			add_filter('option_blog_public', 'false');
			add_filter('comments_open', 'false');
			add_filter('pings_open', 'false');
		}
		
		if ( is_singular() ) {
			global $post;
			global $wp_the_query;
			$post = $wp_the_query->posts[0];
			setup_postdata($post);
		}

		remove_action('wp', array('sem_template', 'wp'));
	} # wp()
	
	
	/**
	 * template_redirect()
	 *
	 * @return void
	 **/

	function template_redirect() {
		if ( !isset($_GET['action']) || $_GET['action'] != 'print' )
			return;

		add_filter('option_blog_public', 'false');
		add_filter('comments_open', 'false');
		add_filter('pings_open', 'false');
		remove_action('wp_footer', array('sem_footer', 'display_credits'));
		
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
		global $sem_captions;
		
		echo '<div id="credits">' . "\n"
			. '<div id="credits_top"><div class="hidden"></div></div>' . "\n"
			. '<div id="credits_bg">' . "\n";
		
		if ( $sem_captions['credits'] ) {
			$theme_credits = sem_template::get_theme_credits();
			$skin_credits = sem_template::get_skin_credits();
			
			$credits = str_replace(
				array(
					'%wordpress%',
					'%semiologic%',
					'%skin_name%',
					'%skin_author%',
					),
				array(
					'<a href="http://wordpress.org">' . __('WordPress') . '</a>',
					$theme_credits,
					$skin_credits['skin_name'],
					$skin_credits['skin_author'],
					),
				$sem_captions['credits']
				);
			
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
		if ( defined('sem_fixes_path') || defined('sem_docs_path') ) {
			return '<a href="http://www.getsemiologic.com">'
				. __('Semiologic Pro')
				. '</a>';
		} else {
			$theme_descriptions = array(
				'the <a href="http://www.semiologic.com/software/sem-reloaded/">Semiologic Reloaded theme</a>',
				'an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to use WordPress theme</a>',
				'an <a href="http://www.semiologic.com/software/sem-reloaded/">easy to customize WordPress theme</a>',
				);
			
			$i = rand(0, sizeof($theme_descriptions) - 1);

			return '<a href="http://wordpress.org">WordPress</a> and ' . $theme_descriptions[$i];
		}
	} # get_theme_credits()
	
	
	/**
	 * get_skin_credits()
	 *
	 * @return array $credits
	 **/

	function get_skin_credits() {
		global $sem_options;
		
		if ( !isset($sem_options['skin_data']) || !is_array($sem_options['skin_data']) ) {
			$skin_data = sem_template::get_skin_data($sem_options['active_skin']);
			$sem_options['skin_data'] = $skin_data;
			update_option('sem6_options', $sem_options);
		} else {
			$skin_data = $sem_options['skin_data'];
		}
		
		return array(
			'skin_name' => $skin_data['name'],
			'skin_author' => '<a href="' . htmlspecialchars($skin_data['author_uri']) . '">'
				. $skin_data['author']
				. '</a>'
			);
	} # get_skin_credits()
	
	
	/**
	 * get_skin_data()
	 *
	 * @return array $data
	 **/

	function get_skin_data($skin_id) {
		$fields = array( 'name', 'uri', 'version', 'author_name', 'author_uri', 'description' );
		
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

		
		$fp = @fopen($plugin_file, 'r');
		
		if ( !$fp ) {
			foreach ( $fields as $field )
				$$field = '';
			return compact($fields);
		}

		$skin_data = fread( $fp, 4096 );
		
		fclose($fp);
		
		$skin_data = str_replace("\r", "\n", $skin_data);

		preg_match('/Skin(?:\s+name)?\s*:(.*)/i', $skin_data, $name);
		preg_match('/Skin\s+ur[il]\s*:(.*)/i', $skin_data, $uri);
		preg_match('/Version\s*:(.*)/i', $skin_data, $version);
		preg_match('/Author\s*:(.*)/i', $skin_data, $author);
		preg_match('/Author\s+ur[il]\s*:(.*)/i', $skin_data, $author_uri);
		preg_match('/Description\s*:(.*)/i', $skin_data, $description);
		
		foreach ( $fields as $field ) {
			if ( !empty( ${$field} ) )
				${$field} = _cleanup_header_comment(${$field}[1]);
			else
				${$field} = '';
			
			switch ( $field ) {
			case 'uri':
			case 'author_uri':
				$$field = clean_url($$field);
				break;
			case 'description':
				$$field = kses($$field, $allowed_tags);
				break;
			default:
				$$field = kses($$field, array());
				break;
			}
		}
		
		return compact($fields);
	} # get_skin_data()
} # sem_template
?>