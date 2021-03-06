<?php
/**
 * URL/mod_rewrite functions
 * @package now-reading
 */

/**
 * Handles our URLs, depending on what menu layout we're using
 * @package now-reading
 */
class nr_url {
	/**
	 * The current URL scheme.
	 * @access public
	 * @var array
	 */
	var $urls;
	
	/**
	 * The scheme for a multiple menu layout.
	 * @access private
	 * @var array
	 */
	var $multiple;
	/**
	 * The scheme for a single menu layout.
	 * @access private
	 * @var array
	 */
	var $single;
	
	/**
	 * Constructor. Populates {@link $multiple} and {@link $single}.
	 */
	function nr_url() {
		$this->multiple = array(
			'add'		=> '',
			'manage'	=> get_option('siteurl') . '/wp-admin/edit.php?page=manage_books',
			'options'	=> get_option('siteurl') . '/wp-admin/options-general.php?page=nr_options'
		);
		$this->single = array(
			'add'		=> get_option('siteurl') . '/wp-admin/admin.php?page=add_book',
			'manage'	=> get_option('siteurl') . '/wp-admin/admin.php?page=manage_books',
			'options'	=> get_option('siteurl') . '/wp-admin/admin.php?page=nr_options'
		);
	}
	
	/**
	 * Loads the given scheme, populating {@link $urls}
	 * @param integer $scheme The scheme to use, either NR_MENU_SINGLE or NR_MENU_MULTIPLE
	 */
	function load_scheme( $option ) {
		if ( file_exists( ABSPATH . '/wp-admin/post-new.php' ) )
			$this->multiple['add'] = get_option('siteurl') . '/wp-admin/post-new.php?page=add_book';
		else
			$this->multiple['add'] = get_option('siteurl') . '/wp-admin/post.php?page=add_book';
		
		if ( $option == NR_MENU_SINGLE )
			$this->urls = $this->single;
		else
			$this->urls = $this->multiple;
	}
}
/**
 * Global singleton to access our current scheme.
 * @global nr_url $GLOBALS['nr_url']
 * @name $nr_url
 */
$nr_url		= new nr_url();
$options	= get_option('nowReadingOptions');
$nr_url->load_scheme($options['menuLayout']);

/**
 * Registers our query vars so we can redirect to the library and book permalinks.
 * @param array $vars The existing array of query vars
 * @return array The modified array of query vars with our additions.
 */
function nr_query_vars( $vars ) {
	$vars[] = 'now_reading_library';
	$vars[] = 'now_reading_id';
	$vars[] = 'now_reading_tag';
	$vars[] = 'now_reading_search';
	$vars[] = 'now_reading_title';
	$vars[] = 'now_reading_author';
	return $vars;
}
add_filter('query_vars', 'nr_query_vars');

/**
 * Adds our rewrite rules for the library and book permalinks to the regular WordPress ones.
 * @param array $rules The existing array of rewrite rules we're filtering
 * @return array The modified rewrite rules with our additions.
 */
function nr_mod_rewrite( $rules ) {
	global $wp_rewrite;
	$rules['^library/([0-9]+)/?$']			= 'index.php?now_reading_id=' . $wp_rewrite->preg_index(1);
	$rules['^library/tag/([^/]+)/?$']		= 'index.php?now_reading_tag=' . $wp_rewrite->preg_index(1);
	$rules['^library/search/?$']			= 'index.php?now_reading_search=true';
	$rules['^library/([^/]+)/([^/]+)/?$']	= 'index.php?now_reading_author=' . $wp_rewrite->preg_index(1) . '&now_reading_title=' . $wp_rewrite->preg_index(2);
	$rules['^library/([^/]+)/?$']			= 'index.php?now_reading_author=' . $wp_rewrite->preg_index(1);
	$rules['^library/?$']					= 'index.php?now_reading_library=true';
	return $rules;
}
add_filter('rewrite_rules_array', 'nr_mod_rewrite');

/**
 * Returns true if we're on a Now Reading page.
 */
function is_now_reading_page() {
	global $wp;
	$wp->parse_request();
	
	return (
		!empty($wp->query_vars['now_reading_library'])	||
		!empty($wp->query_vars['now_reading_search'])	||
		!empty($wp->query_vars['now_reading_id'])		||
		!empty($wp->query_vars['now_reading_tag'])		||
		!empty($wp->query_vars['now_reading_title'])	||
		!empty($wp->query_vars['now_reading_author'])
	);
}

?>