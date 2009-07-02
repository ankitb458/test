<?php
/*
Plugin Name: Fuzzy Recent Links
Plugin URI: http://www.semiologic.com/software/widgets/recent-links/
Description: A WordPress widget that lists a fuzzy number of recently bookmarked links.
Author: Denis de Bernardy
Version: 1.8
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat Tips
--------

	* Mike Koepke <http://www.mikekoepke.com>
**/


load_plugin_textdomain('sem-recent-links');

if ( !defined('sem_cache_path') )
{
	define('sem_cache_path', ABSPATH . 'wp-content/cache/'); # same as wp-cache

	if ( !get_option('sem_cache_created') )
	{
		@mkdir(sem_cache_path, 0777);

		update_option('sem_cache_created', 1);
	}
}
if ( !defined('sem_cache_timeout') )
{
	define('sem_cache_timeout', 3600); # one hour
}


class sem_recent_links
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
			'show_date' => false,		# boolean
			'show_description' => false,		# boolean
			'max_len' => false			# integer, set to false to disable
			);

	var $captions = array(
			'recently_bookmarked' => 'Recently Bookmarked'
			);


	#
	# Constructor
	#

	function sem_recent_links()
	{
		$this->cache_file = sem_cache_path . 'sem-recent-links'
			. isset($GLOBALS['site_id']) ? ( '-' . $GLOBALS['site_id'] ) : '';

		$params = get_settings('sem_recent_links_params');

		if ( $params )
		{
			foreach ( $params as $key => $value )
			{
				$this->params[$key] = $value;
			}
		}
		else
		{
			update_option('sem_recent_links_params', $this->params);
		}

		if ( !get_settings('sem_links_db_changed') )
		{
			global $wpdb;

			/*
			$wpdb->query("
				ALTER TABLE `$wpdb->links`
				DROP `link_added`
				");
			*/

			$wpdb->query("
				ALTER TABLE `$wpdb->links`
				ADD `link_added` DATETIME
					NOT NULL
					AFTER `link_name`
				");

			$wpdb->query("
				ALTER TABLE `$wpdb->links`
				ADD INDEX ( `link_added` )
				");

			update_option('sem_links_db_changed', 1);
		}

		if ( isset($_GET['action'])
			&& in_array($_GET['action'], array('flush', 'flush_cache'))
			)
		{
			$this->flush_cache();
		}

		if ( !empty($_POST) )
		{
			$this->flush_cache();
		}

		add_action('admin_menu', array(&$this, 'add2admin_menu'));

		add_action('init', array(&$this, 'init'));

		add_action('plugins_loaded', array(&$this, 'widgetize'));

		$this->displayed = false;
		add_action('wp_meta', array(&$this, 'auto_display'));
	} # end sem_recent_links()


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
			$cache_files = glob(sem_cache_path . "sem-recent-links*");

			foreach ( (array) $cache_files as $cache_file )
			{
				@unlink( $cache_file );
			}
		}
	} # end flush_cache()


	#
	# add2admin_menu()
	#

	function add2admin_menu()
	{
		add_options_page(
				__('Fuzzy&nbsp;Links', 'sem-recent-links'),
				__('Fuzzy&nbsp;Links', 'sem-recent-links'),
				'manage_options',
				str_replace("\\", "/", __FILE__),
				array(&$this, 'display_admin_page')
				);
	} # end add2admin_menu()


	#
	# update()
	#

	function update()
	{
		check_admin_referer('fuzzy_links');

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

		if ( isset($_POST['show_date']) )
		{
			$this->params['show_date'] = true;
		}
		else
		{
			$this->params['show_date'] = false;
		}

		if ( isset($_POST['show_description']) )
		{
			$this->params['show_description'] = true;
		}
		else
		{
			$this->params['show_description'] = false;
		}

		$this->params['max_len'] = $_POST['max_len'];

		foreach ( $this->params as $key => $val )
		{
			switch ( $key )
			{
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

		update_option('sem_recent_links_params', $this->params);

		$this->flush_cache();
	} # end update()


	#
	# display_admin_page()
	#

	function display_admin_page()
	{
		# Process updates, if any

		if ( isset($_POST['action'])
			&& ( $_POST['action'] == 'update_sem_recent_links' )
			)
		{
			$this->update();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Options saved.', 'sem-recent-links')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		# Display admin page

		echo "<div class=\"wrap\">\n"
			. "<h2>" . __('Recent Links Options', 'sem-recent-links') . "</h2>\n"
			. "<form method=\"post\" action=\"\">\n"
			. "<input type=\"hidden\" name=\"action\" value=\"update_sem_recent_links\" />\n";

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('fuzzy_links');

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Display Options', 'sem-recent-links') . "</legend>\n";

		echo "<p style=\"padding-bottom: 6px;\">"
				. "<label for=\"show_date\">"
				. "<input type=\"checkbox\" id=\"show_date\""
				. " name=\"show_date\""
				. ( $this->params['show_date']
					? " checked=\"checked\""
					: ""
					)
				. " /> " . __('Show date', 'sem-recent-links')
				. "</label>"
				. "</p>\n";

		echo "<p style=\"padding-bottom: 6px;\">"
				. "<label for=\"show_description\">"
				. "<input type=\"checkbox\" id=\"show_description\""
				. " name=\"show_description\""
				. ( $this->params['show_description']
					? " checked=\"checked\""
					: ""
					)
				. " /> " . __('Show description', 'sem-recent-links')
				. "</label>"
				. "</p>\n";

		echo "</fieldset>\n";

		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __('Fuzziness Options', 'sem-recent-links') . "</legend>\n";

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
					. " /> " . __('Fixed number of days', 'sem-recent-links') . "</label></p>\n"
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
						. "> " . __('Days', 'sem-recent-links') . "</label></p>\n"
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
					. " /> " . __('Fixed number of days ago', 'sem-recent-links') . "</label></p>\n"
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
						. "> " . __('Days ago', 'sem-recent-links') . "</label></p>\n"
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
					. " /> " . __('Fixed number of links', 'sem-recent-links') . "</label>"
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
						. "> ". __('Links', 'sem-recent-links') . "</label>"
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


		echo "<p class=\"submit\">"
			. "<input type=\"submit\""
				. " value=\"" . __('Update Options', 'sem-recent-links') . "\""
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
			$params['title'] = $this->captions['recently_bookmarked'];
		}

		# process booleans params

		foreach ( $params as $key => $val )
		{
			switch ( $key )
			{
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

		$params['date_format'] = get_settings('date_format');
		$params['encoding'] = get_settings('blog_charset');

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

		$now = date('Y-m-d H:i:00', strtotime("+1 minute"));

		# fix piece of shit software

		$wpdb->query("
			UPDATE $wpdb->links
			SET link_added = '" . $now . "'
			WHERE link_added = '0000-00-00 00:00:00'
			");

		if ( $params['min_days'] !== false )
		{
			$days_ago = date('Y-m-d H:i:00', strtotime("-" . $params['min_days'] . " days"));
		}
		else
		{
			$days_ago = false;
		}

		if ( $params['max_days'] !== false )
		{
			$max_days_ago = date('Y-m-d H:i:00', strtotime("-" . $params['max_days'] . "days"));
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
					DATE_FORMAT( links.link_added, '%Y-%m-%d 00:00:00' ) AS max_date
				FROM
					$wpdb->links as links
				WHERE
					links.link_added <= '" . $now . "'
					AND
					links.link_visible = 'Y'
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
			$links = $wpdb->get_results(
			#var_dump(
				"SELECT
					links.*
				FROM
					$wpdb->links as links
				WHERE
					links.link_added <= '" . $now . "'
					AND
					links.link_visible = 'Y'"
					. ( $days_ago
						? ( "
					AND links.link_added >= '" . $days_ago . "'" )
						: ""
						)
					. ( $max_date
						? ( "
					AND links.link_added >= '" . $max_date . "'" )
						: ""
						)
					. "
				ORDER BY
					links.link_added DESC, links.link_id DESC"
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
			&& ( !isset($links)
				|| ( sizeof($links) < $params['min_num'] )
				)
			)
		{
			$links = $wpdb->get_results(
			#var_dump(
				"SELECT
					links.*
				FROM
					$wpdb->links as links
				WHERE
					links.link_added <= '" . $now . "'
					AND
					links.link_visible = 'Y'"
					. ( $max_days_ago
						? ( "
					AND posts.post_date_gmt >= '" . $max_days_ago . "'" )
						: ""
						)
					. "
				ORDER BY
					links.link_added DESC, links.link_id DESC
				LIMIT " . $params['min_num']
				);
		}

		if ( isset($links) && $links )
		{
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

			foreach ( $links as $key => $link )
			{
				$link_name = htmlspecialchars(stripslashes($link->link_name), ENT_QUOTES);
				$link_name = $this->chop_str($link_name, $params['max_len']);
				$link_description = stripslashes($link->link_description);
				$link_permalink = stripslashes($link->link_url);
				$link_date = date($params['date_format'], strtotime($link->link_added));

				if ( $params['show_date']
					&& ( !isset($links[$key-1])
						|| ( $link_date != date($params['date_format'], strtotime($links[$key-1]->link_added)) )
						)
					)
				{
					$o .= "<h3>" . $link_date . "</h3>\n"
						. "<ul>\n";
				}

				$o .= "<li>"
					. "<a href=\"" . $link_permalink . "\">"
						. $link_name
						. "</a>"
						. ( ( $params['show_description'] && $link_description )
							? ( "<br />\n" . $link_description )
							: ""
							)
					. "</li>\n";

				if ( $params['show_date']
					&& ( !isset($links[$key+1])
						|| $link_date != date($params['date_format'], strtotime($links[$key+1]->link_added))
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
			register_sidebar_widget('Fuzzy Links', array(&$this, 'display_widget'));
			register_widget_control('Fuzzy Links', array(&$this, 'widget_control'));
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
		$options = get_settings('sem_recent_links_params');

		if ( $_POST["sem_recent_links_widget_update"] )
		{
			$new_options = $options;

			$new_options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST["sem_recent_links_widget_title"])));

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('sem_recent_links_params', $options);
			}
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<input type="hidden"'
				. ' id="sem_recent_links_widget_update"'
				. ' name="sem_recent_links_widget_update"'
				. ' value="1"'
				. ' />'
			. '<p>'
			. '<label for="sem_recent_links_widget_title">'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' id="sem_recent_links_widget_title"'
					. ' name="sem_recent_links_widget_title"'
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
} # end sem_recent_links

$sem_recent_links =& new sem_recent_links();


#
# Template tags
#

function the_recent_links($args = null)
{
	global $sem_recent_links;

	echo $sem_recent_links->display($args);
} # end the_recent_links()
?>