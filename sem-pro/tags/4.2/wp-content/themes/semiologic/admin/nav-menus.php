<?php

#
# add_theme_nav_menu_options_admin()
#

function add_theme_nav_menu_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Nav Menus'),
		__('Nav Menus'),
		7,
		str_replace("\\", "/", basename(__FILE__)),
		'display_theme_nav_menu_options_admin'
		);
} # end add_theme_nav_menu_options_admin()

add_action('admin_menu', 'add_theme_nav_menu_options_admin');


#
# update_theme_nav_menu_options()
#

function update_theme_nav_menu_options()
{
	$GLOBALS['semiologic']['nav_menus'] = array();

	foreach ( array('header_nav', 'sidebar_nav', 'footer_nav') as $menu_id )
	{
		$GLOBALS['semiologic']['nav_menus'][$menu_id] = array();

		foreach ( $_POST[$menu_id . '_item'] as $key => $value )
		{
			$key = trim(stripslashes(strip_tags($key)));
			$value = trim(stripslashes(wp_filter_post_kses($value)));

			$_POST[$menu_id . '_ref'][$key] = trim($_POST[$menu_id . '_ref'][$key]);

			if ( $value )
			{
				if ( !preg_match("~
								^\s*				# trailing spaces
									(?:
										mailto:
									)?
									\S+@\S+
								\s*$
							~isx", $_POST[$menu_id . '_ref'][$key]
							)
					&& !preg_match("~
								^\s*				# trailing spaces
									(?:
										#\S+		# '#some_id'
									|
										\S*
										(?:
											/		# something with '/'
										|
											\.\.	# or with '..'
										)
										\S*
									)
								\s*$
							~isx", $_POST[$menu_id . '_ref'][$key]
							)
					)
				{
					$_POST[$menu_id . '_ref'][$key] = sanitize_title($_POST[$menu_id . '_ref'][$key]);
				}

				if ( !$_POST[$menu_id . '_ref'][$key] )
				{
					$_POST[$menu_id . '_ref'][$key] = sanitize_title($_POST[$menu_id . '_ref'][$key]);
				}

				$GLOBALS['semiologic']['nav_menus'][$menu_id][$value] = $_POST[$menu_id . '_ref'][$key];
			}
		}
	}

	#echo '<pre>';
	#var_dump($GLOBALS['semiologic']['nav_menus']);
	#echo '</pre>';

	update_option('semiologic', $GLOBALS['semiologic']);

	regen_theme_nav_menu_cache();
} # end update_theme_nav_menu_options()


#
# flush_theme_nav_menu_cache()
#

function flush_theme_nav_menu_cache()
{
	$GLOBALS['semiologic']['nav_menu_cache'] = '';

	if ( is_writable(sem_cache_path) )
	{
		$cache_files = glob(sem_cache_path ."*");

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
} # end flush_theme_nav_menu_cache()()


#
# regen_theme_nav_menu_cache()
#

function regen_theme_nav_menu_cache()
{
	global $wpdb;
	global $cache_categories;

	$GLOBALS['semiologic']['nav_menu_cache'] = array();

	foreach ( array('header_nav', 'sidebar_nav', 'footer_nav') as $menu_id )
	{
		$GLOBALS['semiologic']['nav_menu_cache'][$menu_id] = array();

		$found = array();
		$seek = array();
		$refs = "";

#		echo '<pre>';
#		var_dump($GLOBALS['semiologic']['nav_menus']);
#		echo '</pre>';

		foreach ( (array) $GLOBALS['semiologic']['nav_menus'][$menu_id] as $item => $ref )
		{
#			echo '<pre>';
#			var_dump($item, $ref);
#			echo '</pre>';

			if ( !$ref )
			{
				$ref = sanitize_title($item);
			}

			#echo '<pre>';
			#var_dump($item, $ref);
			#echo '</pre>';

			if ( preg_match(
					"~
						^\s*				# trailing spaces
							(?:
								mailto:
							)?
							(\S+@\S+)
						\s*$
					~imx",
					$ref,
					$email
					)
				)
			{
				$found[$ref] = 'mailto:' . antispambot($email[1]);
			}
			if ( preg_match(
					"~
						^\s*				# trailing spaces
							(?:
								#\S+		# '#some_id'
							|
								\S*
								(?:
									/		# something with '/'
								|
									\.\.	# or with '..'
								)
								\S*
							)
						\s*$
					~imx",
					$ref
					)
				)
			{
				$found[$ref] = $ref;
			}
			else
			{
				$seek[$ref] = $ref;
			}
		}

#		echo '<pre>';
#		var_dump($seek);
#		echo '</pre>';

		foreach ( $seek as $ref )
		{
			$refs .= ( $refs ? ", " : "" ) . "'" . mysql_real_escape_string(sanitize_title($ref)) . "'";
		}

		if ( $refs )
		{
			$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

			$pages = $wpdb->get_results("
				SELECT
					posts.*
				FROM
					$wpdb->posts as posts
				WHERE
					"
					. ( function_exists('get_site_option')
						? "( posts.post_status = 'publish' AND posts.post_type = 'page' )"
						: "posts.post_status = 'static'"
						)
					. "
					AND posts.post_name IN ( $refs )
					AND posts.post_parent = 0
					AND posts.post_date_gmt <= '" . $now . "'
				");

			if ( isset($pages) )
			{
				if ( function_exists('update_post_cache') )
				{
					update_post_cache($pages);
				}
				if ( function_exists('update_page_cache') )
				{
					update_page_cache($pages);
				}
			}

			$cats = $wpdb->get_results("
				SELECT
					categories.*
				FROM
					$wpdb->categories as categories
				INNER JOIN
					$wpdb->post2cat as post2cat
						ON post2cat.category_id = categories.cat_ID
				INNER JOIN
					$wpdb->posts as posts
						ON posts.ID = post2cat.post_id
				WHERE
					categories.category_nicename IN ( $refs )
					AND categories.category_parent = 0
					AND posts.post_status = 'publish'
					AND posts.post_date_gmt <= '" . $now . "'
				");

			if ( isset($pages) )
			{
				foreach ( $pages as $page )
				{
					$found[$page->post_name] = apply_filters('the_permalink', get_permalink($page->ID));
				}
			}

			if ( isset($cats) )
			{
				foreach ( $cats as $cat )
				{
					if ( !isset($found[$cat->category_nicename]) )
					{
						$found[$cat->category_nicename] = get_category_link($cat->cat_ID);
					}
				}
			}
		}

#		echo '<pre>';
#		var_dump($found);
#		echo '</pre>';

		foreach ( (array) $GLOBALS['semiologic']['nav_menus'][$menu_id] as $item => $ref )
		{
			if ( !$ref )
			{
				$ref = sanitize_title($item);
			}

			if ( isset($found[$ref]) )
			{
				$GLOBALS['semiologic']['nav_menu_cache'][$menu_id][$item] = $found[$ref];
			}
		}
	}

	update_option('semiologic', $GLOBALS['semiologic']);
} # end regen_theme_nav_menu_cache()

add_action('publish_post', 'regen_theme_nav_menu_cache', 0);
add_action('save_post', 'regen_theme_nav_menu_cache', 0);
add_action('edit_post', 'regen_theme_nav_menu_cache', 0);
add_action('delete_post', 'regen_theme_nav_menu_cache', 0);
add_action('publish_phone', 'regen_theme_nav_menu_cache', 0);
add_action('add_category', 'regen_theme_nav_menu_cache', 0);
add_action('delete_category', 'regen_theme_nav_menu_cache', 0);

if ( !isset($GLOBALS['semiologic']['nav_menu_cache'])
	|| ( ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
		&& ( $_SERVER['REQUEST_METHOD'] == 'POST' )
		)
	)
{
	add_action('init', 'regen_theme_nav_menu_cache');
}


#
# display_theme_nav_menu_options_admin()
#

function display_theme_nav_menu_options_admin()
{
	if ( isset($_POST['action'])
		&& ( $_POST['action'] == 'update_sem_theme_nav_menu' )
		)
	{
		update_theme_nav_menu_options();

		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.', 'sem-theme')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}

	regen_theme_nav_menu_cache();

	# Display admin page

	echo "<div class=\"wrap\">\n"
		. "<h2>" . __('Theme Navigation Menus', 'sem-theme') . "</h2>\n"
		. "<form method=\"post\" action=\"\">\n"
		. "<input type=\"hidden\" name=\"action\" value=\"update_sem_theme_nav_menu\" />\n";

	foreach ( array('header_nav' => 'Header Navigation Menu',
					'sidebar_nav' => 'Sidebar Navigation Menu',
					'footer_nav' => 'Footer Navigation Menu'
					)
				as $menu_id => $fieldset_legend )
	{
		echo "<fieldset class=\"options\">\n"
			. "<legend>" . __($fieldset_legend, 'sem-theme') . "</legend>\n";

		echo "<table>\n"
			. "<tr>\n"
			. "<th>" . __('Menu Item', 'sem-theme') . "</th>\n"
			. "<th>" . __('Smart Reference', 'sem-theme') . "</th>\n"
			. "<th>" . __('Preview', 'sem-theme') . "</th>\n"
			. "</tr>\n";

		$i = 0;

		foreach ( (array) $GLOBALS['semiologic']['nav_menus'][$menu_id] as $item => $ref )
		{
			$i++;

			echo "<tr>\n"
				. "<td style=\"width: 160px;\"><input style=\"width: 150px;\" type=\"text\""
					. " name=\"" . $menu_id . "_item[]\""
					. " value=\""
						. $item
						. "\""
					. " /></td>\n"
				. "<td style=\"width: 160px;\"><input style=\"width: 150px;\" type=\"text\""
					. " name=\"" . $menu_id . "_ref[]\""
					. " value=\""
						. ( ( $ref != sanitize_title($item)
								|| strlen($ref) != strlen($item)
								)
							? htmlspecialchars($ref, ENT_QUOTES)
							: ""
							)
						. "\""
					. " />"
					. "</td>\n"
				. "<td>"
					. ( isset($GLOBALS['semiologic']['nav_menu_cache'][$menu_id][$item])
						? ( "<a href=\""
								. $GLOBALS['semiologic']['nav_menu_cache'][$menu_id][$item]
								. "\">"
								. str_replace(" ", "&nbsp;", $item)
								. "</a>"
							)
						: "&nbsp;"
						)
					. "</td>\n"
				. "</tr>\n";
		}

		while ( $i++ < 8 )
		{
			echo "<tr>\n"
				. "<td style=\"width: 160px;\"><input style=\"width: 150px;\" type=\"text\""
					. " name=\"" . $menu_id . "_item[]\""
					. " value=\"\""
					. " /></td>\n"
				. "<td style=\"width: 160px;\"><input style=\"width: 150px;\" type=\"text\""
					. " name=\"" . $menu_id . "_ref[]\""
					. " value=\"\""
					. " />"
					. "</td>\n"
				. "<td>&nbsp;</td>\n"
				. "</tr>\n";
		}

		echo "</table>\n"
			. "</fieldset>\n";
	}

	echo "<p class=\"submit\">"
		. "<input type=\"submit\""
			. " value=\"" . __('Update Options', 'sem-theme') . "\""
			. " />"
		. "</p>\n";

	echo "</form>"
		. "</div>\n";
} # end display_theme_nav_menu_options_admin()


#
# sidebar_nav_widget_control()
#

function sidebar_nav_widget_control()
{
	$options = get_settings('sidebar_nav_widget_params');

	if ( $_POST["sidebar_nav_widget_update"] )
	{
		$new_options = $options;

		$new_options['title'] = strip_tags(stripslashes($_POST["sidebar_nav_widget_title"]));

		if ( $options != $new_options )
		{
			$options = $new_options;

			update_option('sidebar_nav_widget_params', $options);
		}
	}

	$title = htmlspecialchars($options['title'], ENT_QUOTES);

	echo '<input type="hidden"'
			. ' id="sidebar_nav_widget_update"'
			. ' name="sidebar_nav_widget_update"'
			. ' value="1"'
			. ' />'
		. '<p>'
		. '<label for="sidebar_nav_widget_title">'
			. __('Title:')
			. '&nbsp;'
			. '<input style="width: 250px;"'
				. ' id="sidebar_nav_widget_title"'
				. ' name="sidebar_nav_widget_title"'
				. ' type="text" value="' . $title . '" />'
			. '</label>'
			. '</p>';
} # end sidebar_nav_widget_control()
?>