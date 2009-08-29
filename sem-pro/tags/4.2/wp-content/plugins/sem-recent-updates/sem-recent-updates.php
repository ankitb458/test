<?php
/*
Plugin Name: Fuzzy Recent Updates
Plugin URI: http://www.semiologic.com/software/recent-updates/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/recent-updates/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; A WordPress widget that lists a fuzzy number of recently updated entries. To use, call the_recent_updates(); where you want the tile to appear. Alternatively, do nothing and the tile will display when wp_meta(); is called.
Author: Denis de Bernardy
Version: 4.8
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


load_plugin_textdomain('sem-recent-updates');

if ( !defined('sem_cache_path') )
{
	define('sem_cache_path', ABSPATH . 'wp-content/cache/'); # same as wp-cache
}
if ( !defined('sem_cache_timeout') )
{
	define('sem_cache_timeout', 3600); # one hour
}


class sem_recent_updates
{
	#
	# Variables
	#

	var $params = array(
			'min_num' => false,			# integer, set to false to disable
			'max_num' => false,			# integer, set to false to disable
			'min_days' => false,		# integer, set to false to disable
			'max_days' => false,		# integer, set to false to disable
			'num_days' => 3,			# integer, set to false to disable
			'inc_posts' => false,		# boolean
			'inc_pages' => true,		# boolean
			'show_date' => false,		# boolean
			'max_len' => false,			# integer, set to false to disable
			'exclude' => false			# array, set to false to disable
			);

	var $captions = array(
			'recently_updated' => 'Recently Updated'
			);


	#
	# Constructor
	#

	function sem_recent_updates()
	{
		$this->cache_file = sem_cache_path . 'sem-recent-updates'
			. isset($GLOBALS['site_id']) ? ( '-' . $GLOBALS['site_id'] ) : '';

		$params = get_settings('sem_recent_updates_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			update_option('sem_recent_updates_params', $this->params);
		}

		if ( isset($_GET['action'])
			&& in_array($_GET['action'], array('flush', 'flush_cache'))
			)
		{
			$this->flush_cache();
		}

		add_action('admin_menu', array(&$this, 'add2admin_menu'));

		add_action('publish_post', array(&$this, 'flush_cache'), 0);
		add_action('save_post', array(&$this, 'flush_cache'), 0);
		add_action('edit_post', array(&$this, 'flush_cache'), 0);
		add_action('delete_post', array(&$this, 'flush_cache'), 0);
		add_action('publish_phone', array(&$this, 'flush_cache'), 0);
		#add_action('generate_rewrite_rules', array(&$this, 'flush_cache'), 0);
		add_action('init', array(&$this, 'init'));

		add_action('plugins_loaded', array(&$this, 'widgetize'));

		$this->displayed = false;
		add_action('wp_meta', array(&$this, 'auto_display'));
	} # end sem_recent_updates()


	#
	# init()
	#

	function init()
	{
		global $sem_theme_captions;

		if ( isset($sem_theme_captions) )
		{
			$this->captions = $sem_theme_captions->register($this->captions);
		}
		else
		{
			array_walk($this->captions, '__');
		}
	} # end init()


	#
	# flush_cache()
	#

	function flush_cache()
	{
		if ( is_writable(sem_cache_path) )
		{
			$cache_files = glob(sem_cache_path . "*");

			if ( $cache_files )
			{
				foreach ( $cache_files as $cache_file )
				{
					if ( is_file($cache_file) && is_writable($cache_file) )
					{
						unlink( $cache_file );
					}
				}
			}
		}
	} # end flush_cache()


	#
	# add2admin_menu()
	#

	function add2admin_menu()
	{
		add_options_page(
				__('Recent&nbsp;Updates', 'sem-recent-updates'),
				__('Recent&nbsp;Updates', 'sem-recent-updates'),
				8,
				str_replace("\\", "/", __FILE__),
				array(&$this, 'display_admin_page')
				);
	} # end add2admin_menu()


	#
	# update()
	#

	function update()
	{
		$this->params = array();

		#echo '<pre>';
		#var_dump($_POST);
		#echo '</pre>';

		switch ( $_POST['config_type'] )
		{
		case 'fuzzy':
			$this->params['min_num'] = intval($_POST['min_num']);
			$this->params['max_num'] = intval($_POST['max_num']);
			$this->params['min_days'] = intval($_POST['min_days']);
			$this->params['max_days'] = intval($_POST['max_days']);
			$this->params['num_days'] = false;
			break;
		case 'fixed_entries':
			if ( !intval($_POST['num_entries']) )
			{
				$_POST['num_entries'] = 10;
			}
			$this->params['min_num'] = intval($_POST['num_entries']);
			$this->params['max_num'] = intval($_POST['num_entries']);
			$this->params['min_days'] = false;
			$this->params['max_days'] = false;
			$this->params['num_days'] = false;
			break;
		case 'fixed_days':
			if ( !intval($_POST['num_days']) )
			{
				$_POST['num_days'] = 3;
			}
			$this->params['min_num'] = false;
			$this->params['max_num'] = false;
			$this->params['min_days'] = false;
			$this->params['max_days'] = false;
			$this->params['num_days'] = intval($_POST['num_days']);
			break;
		case 'fixed_days_ago':
			if ( !intval($_POST['num_days_ago']) )
			{
				$_POST['num_days_ago'] = 7;
			}
			$this->params['min_num'] = false;
			$this->params['max_num'] = false;
			$this->params['min_days'] = intval($_POST['num_days_ago']);
			$this->params['max_days'] = intval($_POST['num_days_ago']);
			$this->params['num_days'] = false;
			break;
		}

		switch ( $_POST['recent_type'] )
		{
		case 'inc_posts':
			$this->params['inc_posts'] = true;
			$this->params['inc_pages'] = false;
			break;
		case 'inc_pages':
			$this->params['inc_posts'] = false;
			$this->params['inc_pages'] = true;
			break;
		case 'inc_entries':
			$this->params['inc_posts'] = true;
			$this->params['inc_pages'] = true;
			break;
		}

		if ( isset($_POST['show_date']) )
		{
			$this->params['show_date'] = true;
		}
		else
		{
			$this->params['show_date'] = false;
		}

		$this->params['max_len'] = $_POST['max_len'];

		preg_match_all("/\d+/", $_POST['exclude'], $exclude, PREG_PATTERN_ORDER);

		if ( $exclude )
		{
			$this->params['exclude'] = end($exclude);
		}
		else
		{
			$this->params['exclude'] = false;
		}

		foreach ( $this->params as $key => $val )
		{
			switch ( $key )
			{
			case 'inc_posts':
			case 'inc_pages':
				break;
			default:
				if ( $val == 0 )
				{
					$this->params[$key] = false;
				}
				break;
			}
		}

		if ( !$this->params['min_num'] && !$this->params['max_num']
			&& !$this->params['min_days'] && !$this->params['max_days']
			&& !$this->params['num_days']
			)
		{
			$this->params['num_days'] = 3;
		}

		if ( !$this->params['inc_posts'] && !$this->params['inc_pages'] )
		{
			$this->params['inc_pages'] = true;
		}

		update_option('sem_recent_updates_params', $this->params);

		$this->flush_cache();
	} # end update()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		# Process updates, if any

		if ( isset($_POST['action'])
			&& ( $_POST['action'] == 'update_sem_recent_updates' )
			)
		{
			$this->update();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.', 'sem-recent-updates')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		# Display admin page

		echo "<div class=\"wrap\">\n"
			. "<h2>" . __('Recent Updates Options', 'sem-recent-updates') . "</h2>\n"
			. "<form method=\"post\" action=\"\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"update_sem_recent_updates\" />\n";

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Display Options', 'sem-recent-updates') . "</legend>\n";

		echo "<p style=\"padding-bottom: 6px;\">"
				. "<label for=\"show_date\">"
				. "<input type=\"checkbox\" id=\"show_date\""
				. " name=\"show_date\""
				. ( $this->params['show_date']
					? " checked=\"checked\""
					: ""
					)
				. " /> " . __('Show date', 'sem-recent-updates')
				. "</label>"
				. "</p>\n";

		echo "<p style=\"padding-bottom: 6px;\">" . __('Show recently updated', 'sem-recent-updates')
				. ":"
				. " <label for=\"show_recent_posts\">"
				. "<input type=\"radio\" id=\"show_recent_posts\""
					. " name=\"recent_type\" value=\"inc_posts\""
					. ( ( $this->params['inc_posts'] && !$this->params['inc_pages'] )
						? " checked=\"checked\""
						: ""
						)
					. " /> " . __('Posts', 'sem-recent-updates')
				. "</label>"
				. " <label for=\"show_recent_pages\">"
				. "<input type=\"radio\" id=\"show_recent_pages\""
					. " name=\"recent_type\" value=\"inc_pages\""
					. ( ( !$this->params['inc_posts'] && $this->params['inc_pages'] )
						? " checked=\"checked\""
						: ""
						)
					. " /> " . __('Pages', 'sem-recent-updates')
				. "</label>"
				. " <label for=\"show_recent_entries\">"
				. "<input type=\"radio\" id=\"show_recent_entries\""
					. " name=\"recent_type\" value=\"inc_entries\""
					. ( ( $this->params['inc_posts'] && $this->params['inc_pages'] )
						? " checked=\"checked\""
						: ""
						)
					. " /> " . __('Both', 'sem-recent-updates')
				. "</label>"
			. "</p>\n";

		echo "</fieldset>\n";

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Fuzziness Options', 'sem-recent-updates') . "</legend>\n";

		echo "<table>\n"
			. "<tr valign=\"top\">\n"
			. "<td style=\"width: 40%;\">"
				. "<p><label for=\"config_type_fixed_days\">"
					. "<input type=\"radio\" id=\"config_type_fixed_days\""
					. " name=\"config_type\" value=\"fixed_days\""
					. ( ( $this->params['num_days'] )
						? " checked=\"checked\""
						: ""
						)
					. " /> " . __('Fixed number of days', 'sem-recent-updates') . "</label></p>\n"
			. "</td>\n"
			. "<td>"
				. "<p><label for=\"mum_days\">"
					. "<input type=\"text\" size=\"2\""
					. " id=\"num_days\" name=\"num_days\""
					. " value=\""
						. ( $this->params['num_days']
							? $this->params['num_days']
							: 3
							)
						. "\""
						. " onchange=\"if ( this.value ) this.form.config_type[0].checked = true;\""
						. "> " . __('Days', 'sem-recent-updates') . "</label></p>\n"
			. "</td>\n"
			. "</tr>\n"
			. "<tr valign=\"top\">\n"
			. "<td style=\"width: 40%;\">"
				. "<p><label for=\"config_type_fixed_days_ago\">"
					. "<input type=\"radio\" id=\"config_type_fixed_days_ago\""
					. " name=\"config_type\" value=\"fixed_days_ago\""
					. ( ( $this->params['min_days']
							&& ( $this->params['min_days'] == $this->params['max_days'] )
							)
						? " checked=\"checked\""
						: ""
						)
					. " /> " . __('Fixed number of days ago', 'sem-recent-updates') . "</label></p>\n"
			. "</td>\n"
			. "<td>"
				. "<p><label for=\"num_days_ago\">"
					. "<input type=\"text\" size=\"2\""
					. " id=\"num_days_ago\" name=\"num_days_ago\""
					. " value=\""
						. ( ( $this->params['min_days']
								&& ( $this->params['min_days'] == $this->params['max_days'] )
								)
							? $this->params['min_days']
							: 7
							)
						. "\""
						. " onchange=\"if ( this.value ) this.form.config_type[1].checked = true;\""
						. "> " . __('Days ago', 'sem-recent-updates') . "</label></p>\n"
			. "</td>\n"
			. "</tr>\n"
			. "<tr valign=\"top\">\n"
			. "<td style=\"width: 40%;\">"
				. "<p><label for=\"config_type_fixed_entries\">"
					. "<input type=\"radio\" id=\"config_type_fixed_entries\""
					. " name=\"config_type\" value=\"fixed_entries\""
					. ( ( $this->params['min_num']
							&& ( $this->params['min_num'] == $this->params['max_num'] )
							)
						? " checked=\"checked\""
						: ""
						)
					. " /> " . __('Fixed number of entries', 'sem-recent-updates') . "</label>"
					. "</p>\n"
			. "</td>\n"
			. "<td>"
				. "<p><label for=\"num_entries\">"
					. "<input type=\"text\" size=\"2\""
					. " id=\"num_entries\" name=\"num_entries\""
					. " value=\""
						. ( ( $this->params['min_num']
								&& ( $this->params['min_num'] == $this->params['max_num'] )
								)
							? $this->params['min_num']
							: 10
							)
						. "\""
						. " onchange=\"if ( this.value ) this.form.config_type[2].checked = true;\""
						. "> ". __('Entries', 'sem-recent-updates') . "</label>"
						. "</p>\n"
			. "</td>\n"
			. "</tr>\n"
			. "</table>\n";

		echo "</fieldset>\n";

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Max Length') . "</legend>\n";

		echo '<p>'
			. '<label for="max_len">'
				. '<input type="text"'
				. ' name="max_len" id="max_len"'
				. '" value="'
					. ( $this->params['max_len']
						? $this->params['max_len']
						: ""
						)
					. '" />'
				. ' '
				. __('Characters')
				. '</label>'
			. '</p>';

		echo "</fieldset>\n";


		$exclude = "";

		foreach ( (array) $this->params['exclude'] as $exclude_id )
		{
			$exclude .= ( $exclude ? ', ' : '' ) . $exclude_id;
		}

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . '<label for="exclude">' . __('Exclude') . '</label>' . "</legend>\n";

		echo '<p>'
				. '<label for="exclude">'
				. __('Enter a list of post and page IDs') . ':<br />'
				. '<input type="text"'
				. ' name="exclude" id="exclude"'
				. ' style="width: 350px;"'
				. '" value="' . $exclude . '" />'
				. '</label>'
			. '</p>';

		echo "</fieldset>\n";


		echo "<p class=\"submit\">"
			. "<input type=\"submit\""
				. " value=\"" . __('Update Options', 'sem-recent-updates') . "\""
				. " />"
			. "</p>\n";

		echo "</form>"
			. "</div>\n";
	} # end display_admin_page()


	#
	# display()
	#

	function display($args = null)
	{
		$this->displayed = true;

		global $wpdb;

		# fetch params

		if ( isset($args) )
		{
			if ( is_string($args) )
			{
				parse_str($args, $args);
			}
		}
		else
		{
			$args = array();
		}

		if ( !isset($args['before_widget']) )
		{
			$args['before_widget'] = '';
		}
		if ( !isset($args['after_widget']) )
		{
			$args['after_widget'] = '';
		}
		if ( !isset($args['before_title']) )
		{
			$args['before_title'] = '<h2>';
		}
		if ( !isset($args['after_title']) )
		{
			$args['after_title'] = '</h2>';
		}

		$params = $args;


		# default params

		foreach ( $this->params as $key => $val )
		{
			if ( !isset($params[$key]) )
			{
				$params[$key] = $this->params[$key];
			}
		}

		if ( !isset($params['title']) || !$params['title'] )
		{
			$params['title'] = $this->captions['recently_updated'];
		}

		# process booleans params

		foreach ( $params as $key => $val )
		{
			switch ( $key )
			{
			case 'inc_posts':
			case 'inc_pages':
			case 'show_date':
				if ( $val == 'false' )
				{
					$val = false;
				}
				if ( !$params[$key] )
				{
					$params[$key] = false;
				}
				else
				{
					$params[$key] = intval($params[$key]);
				}
			}
		}

		# autocorrect params

		if ( !($params['inc_posts'] || $params['inc_pages']) )
		{
			$params['inc_posts'] = true;
		}

		if ( $params['max_num'] !== false
			&& $params['max_num'] < $params['min_num']
			)
		{
			$params['max_num'] = false;
		}

		if ( $params['max_days'] !== false
			&& $params['max_days'] < $params['min_days']
			)
		{
			$params['max_days'] = false;
		}

		# additional params

		$exclude = "";

		foreach ( (array) $params['exclude'] as $exclude_id )
		{
			$exclude .= ( $exclude ? ', ' : '' ) . $exclude_id;
		}

		$params['date_format'] = get_settings('date_format');
		$params['encoding'] = get_settings('blog_charset');

		if ( defined('sem_home_page_id') && sem_home_page_id )
		{
			$params['home_page_id'] = intval(sem_home_page_id);
		}
		else
		{
			$params['home_page_id'] = false;
		}

		if ( defined('sem_main_cat_id') && sem_main_cat_id )
		{
			$params['main_cat_id'] = intval(sem_main_cat_id);
		}
		else
		{
			$params['main_cat_id'] = false;
		}

		if ( defined('sem_sidebar_tile_id') && sem_sidebar_tile_id )
		{
			$params['sidebar_tile_id'] = intval(sem_sidebar_tile_id);
		}
		else
		{
			$params['sidebar_tile_id'] = false;
		}

		# return cache if relevant

		if ( is_writable(sem_cache_path) )
		{
			$cache_file = $this->cache_file . "-" . md5(serialize($params));
			if ( file_exists($cache_file) )
			{
				if ( ( filemtime($cache_file) + sem_cache_timeout ) >= time() )
				{
					return file_get_contents($cache_file);
				}
				else
				{
					$this->flush_cache();
				}
			}
		}


		# else display normally

		$o = "";

		$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

		if ( $params['min_days'] !== false )
		{
			$days_ago = gmdate('Y-m-d H:i:00', strtotime("-" . $params['min_days'] . " days"));
		}
		else
		{
			$days_ago = false;
		}

		if ( $params['max_days'] !== false )
		{
			$max_days_ago = gmdate('Y-m-d H:i:00', strtotime("-" . $params['max_days'] . "days"));
		}
		else
		{
			$max_days_ago = false;
		}

		if ( $params['num_days'] )
		{
			$max_date = $wpdb->get_col(
			#var_dump(
				"SELECT
					DATE_FORMAT( posts.post_modified, '%Y-%m-%d 00:00:00' ) AS max_date
				FROM
					$wpdb->posts as posts"
					. ( ( $params['main_cat_id'] && !$params['inc_pages'] )
						? "
				INNER JOIN $wpdb->post2cat as post2cat ON post2cat.post_id = posts.ID"
						: ""
						)
					. "
				WHERE
					posts.post_date_gmt <= '" . $now . "'
					AND posts.post_password = ''"
					. ( $params['home_page_id']
						? ( "
					AND posts.ID <> " . $params['home_page_id'] )
						: ""
						)
					. ( $params['sidebar_tile_id']
						? ( "
					AND posts.ID <> " . $params['sidebar_tile_id'] )
						: ""
						)
					. "
					AND
					("
						. ( $params['inc_posts']
							? "
						(
							" . ( function_exists('get_site_option')
								? "
							( posts.post_status = 'publish' AND posts.post_type = 'post' )"
								: "
							posts.post_status = 'publish'"
								)
							. ( ( $params['main_cat_id'] && !$params['inc_pages'] )
								? ( "
							AND post2cat.category_id = " . $params['main_cat_id'] )
								: ""
								)
							. "
						)"
							: ""
							)
						. ( ( $params['inc_posts'] && $params['inc_pages'] )
							? ( "
						OR" )
							: ""
							)
						. ( $params['inc_pages']
							? ( function_exists('get_site_option')
								? "
						( posts.post_status = 'publish' AND posts.post_type = 'page' )"
								: "
						posts.post_status = 'static'"
								)
							: ""
							)
						. "
					)"
					. ( $exclude
						? "
					AND posts.ID NOT IN ( $exclude )"
						: ''
						)
					. "
				GROUP BY
					max_date DESC
				LIMIT " . $params['num_days']
				);

			if ( isset($max_date) && $max_date )
			{
				$max_date = end($max_date);
			}
		}
		else
		{
			$max_date = false;
		}

		if ( !( $params['min_num']
				&& ( $params['min_num'] == $params['max_num'] )
				)
			)
		{
			$posts = $wpdb->get_results(
			#var_dump(
				"SELECT
					posts.*
				FROM
					$wpdb->posts as posts"
					. ( ( $params['main_cat_id'] && !$params['inc_pages'] )
						? "
				INNER JOIN $wpdb->post2cat as post2cat ON post2cat.post_id = posts.ID"
						: ""
						)
					. "
				WHERE
					posts.post_date_gmt <= '" . $now . "'
					AND posts.post_password = ''"
					. ( $params['home_page_id']
						? ( "
					AND posts.ID <> " . $params['home_page_id'] )
						: ""
						)
					. ( $params['sidebar_tile_id']
						? ( "
					AND posts.ID <> " . $params['sidebar_tile_id'] )
						: ""
						)
					. "
					AND
					("
						. ( $params['inc_posts']
							? "
						(
							" . ( function_exists('get_site_option')
								? "
							( posts.post_status = 'publish' AND posts.post_type = 'post' )"
								: "
							posts.post_status = 'publish'"
								)
							. ( ( $params['main_cat_id'] && !$params['inc_pages'] )
								? ( "
							AND post2cat.category_id = " . $params['main_cat_id'] )
								: ""
								)
							. "
						)"
							: ""
							)
						. ( ( $params['inc_posts'] && $params['inc_pages'] )
							? ( "
						OR" )
							: ""
							)
						. ( $params['inc_pages']
							? ( function_exists('get_site_option')
								? "
						( posts.post_status = 'publish' AND posts.post_type = 'page' )"
								: "
						posts.post_status = 'static'"
								)
							: ""
							)
						. "
					)"
					. ( $days_ago
						? ( "
					AND posts.post_modified_gmt >= '" . $days_ago . "'" )
						: ""
						)
					. ( $max_date
						? ( "
					AND posts.post_modified >= '" . $max_date . "'" )
						: ""
						)
					. ( $exclude
						? "
					AND posts.ID NOT IN ( $exclude )"
						: ''
						)
					. "
				ORDER BY
					posts.post_modified DESC"
				. ( ( ( $params['max_num'] !== false )
						&& ( $params['max_num'] > $params['min_num'] )
						)
					? "
				LIMIT " . intval($params['max_num'])
					: ""
					)
				);
		}

		if ( $params['min_num']
			&& ( !isset($posts)
				|| ( sizeof($posts) < $params['min_num'] )
				)
			)
		{
			$posts = $wpdb->get_results(
			#var_dump(
				"SELECT
					posts.*
				FROM
					$wpdb->posts as posts"
					. ( ( $params['main_cat_id'] && !$params['inc_pages'] )
						? "
				INNER JOIN $wpdb->post2cat as post2cat ON post2cat.post_id = posts.ID"
						: ""
						)
					. "
				WHERE
					posts.post_date_gmt <= '" . $now . "'
					AND posts.post_password = ''"
					. ( $params['home_page_id']
						? ( "
					AND posts.ID <> " . $params['home_page_id'] )
						: ""
						)
					. ( $params['sidebar_tile_id']
						? ( "
					AND posts.ID <> " . $params['sidebar_tile_id'] )
						: ""
						)
					. "
					AND
					("
						. ( $params['inc_posts']
							? "
						(
							" . ( function_exists('get_site_option')
								? "
							( posts.post_status = 'publish' AND posts.post_type = 'post' )"
								: "
							posts.post_status = 'publish'"
								)
							. ( ( $params['main_cat_id'] && !$params['inc_pages'] )
								? ( "
							AND post2cat.category_id = " . $params['main_cat_id'] )
								: ""
								)
							. "
						)"
							: ""
							)
						. ( ( $params['inc_posts'] && $params['inc_pages'] )
							? ( "
						OR" )
							: ""
							)
						. ( $params['inc_pages']
							? ( function_exists('get_site_option')
								? "
						( posts.post_status = 'publish' AND posts.post_type = 'page' )"
								: "
						posts.post_status = 'static'"
								)
							: ""
							)
						. "
					)"
					. ( $max_days_ago
						? ( "
					AND posts.post_modified_gmt >= '" . $max_days_ago . "'" )
						: ""
						)
					. ( $exclude
						? "
					AND posts.ID NOT IN ( $exclude )"
						: ''
						)
					. "
				ORDER BY
					posts.post_modified DESC
				LIMIT " . $params['min_num']
				);
		}

		if ( isset($posts) && $posts )
		{
			if ( function_exists('update_post_cache') )
			{
				update_post_cache($posts);
			}
			if ( function_exists('update_page_cache') )
			{
				update_page_cache($posts);
			}

			$o .= $params['before_widget']
				. '<div class="tile sem_recent">';

			$o .= ( $params['title']
				? ( '<div class="tile_header">'
					. $params['before_title'] . $params['title'] . $params['after_title'] . "\n"
					. '</div>'
					)
				: ""
				);

			$o .= '<div class="tile_body">';

			if ( !$params['show_date'] )
			{
				$o .= "<ul>\n";
			}

			foreach ( $posts as $key => $post )
			{
				$post_title = stripslashes($post->post_title);
				$post_title = $this->chop_str($post_title, $params['max_len']);
				$post_permalink = apply_filters('the_permalink', get_permalink($post->ID));
				$post_modified = date($params['date_format'], strtotime($post->post_modified));

				if ( $params['show_date']
					&& ( !isset($posts[$key-1])
						|| ( $post_modified != date($params['date_format'], strtotime($posts[$key-1]->post_modified)) )
						)
					)
				{
					$o .= "<h3>" . $post_modified . "</h3>\n"
						. "<ul>\n";
				}

				$o .= "<li>"
					. "<a href=\"" . $post_permalink . "\">"
						. $post_title
						. "</a>"
					. "</li>\n";

				if ( $params['show_date']
					&& ( !isset($posts[$key+1])
						|| $post_modified != date($params['date_format'], strtotime($posts[$key+1]->post_modified))
						)
					)
				{
					$o .= "</ul>\n";
				}
			}

			if ( !$params['show_date'] )
			{
				$o .= "</ul>\n";
			}

			$o .= '</div>'
				. '</div>'
				. $params['after_widget'];
		}


		# cache the result

		if ( is_writable(sem_cache_path) && is_writable($this->cache_file) )
		{
			$fp = fopen($cache_file, "w+");
			fwrite($fp, $o);
			fclose($fp);
		}


		# return output

		return $o;
	} # end display()


	#
	# chop_str()
	#

	function chop_str($str, $max_len)
	{
		if ( $max_len )
		{
			$str = trim(strip_tags($str));

			if ( ( strlen($str) > $max_len ) )
			{
				$str = substr($str, 0, $max_len) . '...';
			}
		}

		return $str;
	} # end chop_str()


	#
	# widgetize()
	#

	function widgetize()
	{
		if ( function_exists('register_sidebar_widget') )
		{
			register_sidebar_widget('Fuzzy Updates', array(&$this, 'display_widget'));
			register_widget_control('Fuzzy Updates', array(&$this, 'widget_control'));
		}
	} # end widgetize()


	#
	# display_widget()
	#

	function display_widget($args)
	{
		echo $this->display($args);
	} # end display_widget()


	#
	# widget_control()
	#

	function widget_control()
	{
		$options = get_settings('sem_recent_updates_params');

		if ( $_POST["recent_updates"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["sem_recent_updates_widget_title"])));

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('sem_recent_updates_params', $options);
			}
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<input type="hidden"'
				. ' id="recent_updates"'
				. ' name="recent_updates"'
				. ' value="1"'
				. ' />'
			. '<p>'
			. '<label for="sem_recent_updates_widget_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="sem_recent_updates_widget_title"'
					. ' name="sem_recent_updates_widget_title"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</p>';
	} # end widget_control()


	#
	# auto_display()
	#

	function auto_display()
	{
		if ( !$this->displayed && !function_exists('register_sidebar_widget') )
		{
			echo '</ul></li>'
				. $this->display()
				. '<li><ul>';
		}
	} # end auto_display()
} # end sem_recent_updates

$my_recent_updates =& new sem_recent_updates();


#
# Template tags
#

function the_recent_updates($args = null)
{
	global $my_recent_updates;

	echo $my_recent_updates->display($args);
} # end the_recent_updates()


########################
#
# Backward compatibility
#

function sem_recent_updates($args = null)
{
	the_recent_updates($args);
} # end sem_recent_updates()
?>