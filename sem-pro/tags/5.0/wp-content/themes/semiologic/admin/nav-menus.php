<?php
class sem_nav_admin
{
	#
	# widget_control()
	#

	function widget_control($area)
	{
		global $sem_options;
		global $sem_captions;
		global $sem_nav;

		switch ( $area )
		{
		case 'header':
			if ( $_POST['update_sem_header']['nav_menu'] )
			{
				$new_options = $sem_options;
				$new_captions = $sem_captions;

				$new_options['show_search_form'] = isset($_POST['sem_header']['show_search_form']);

				$new_captions['search_field'] = strip_tags(stripslashes($_POST['sem_header']['label_search_field']));
				$new_captions['search_button'] = strip_tags(stripslashes($_POST['sem_header']['label_search_button']));

				if ( $new_options != $sem_options )
				{
					$sem_options = $new_options;

					update_option('sem5_options', $sem_options);
				}
				if ( $new_captions != $sem_captions )
				{
					$sem_captions = $new_captions;

					update_option('sem5_captions', $sem_captions);
				}
			}

			echo '<input type="hidden" name="update_sem_header[nav_menu]" value="1" />';

			echo '<h3>'
				. __('Config')
				. '</h3>';

			echo '<div style="margin-bottom: .2em;">'
				. '<label>'
				. '<input type="checkbox"'
					. ' name="sem_header[show_search_form]"'
					. ( $sem_options['show_search_form']
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. ' '
				. __('Insert Search Form')
				. '</label>'
				. '</div>';

			echo '<h3>'
				. __('Captions')
				. '</h3>';

			echo '<div style="margin-bottom: .2em;">'
				. '<label>'
				. __('Search Field, e.g. Search')
				. '<br />'
				. '<input type="text" style="width: 95%"'
					. ' name="sem_header[label_search_field]"'
					. ' value="' . htmlspecialchars($sem_captions['search_field']) . '"'
					. ' />'
				. '</label>'
				. '</div>';

			echo '<div style="margin-bottom: .2em;">'
				. '<label>'
				. __('Search Button, e.g. Go')
				. '<br />'
				. '<input type="text" style="width: 95%"'
					. ' name="sem_header[label_search_button]"'
					. ' value="' . htmlspecialchars($sem_captions['search_button']) . '"'
					. ' />'
				. '</label>'
				. '</div>';

			echo '<div>'
				. '<br />'
				. __('You can configure this navigation menu under Presentation / Nav Menus')
				. '</div>';
			break;

		case 'footer':
			if ( $_POST['update_sem_footer']['nav_menu'] )
			{
				$new_options = $sem_options;
				$new_captions = $sem_captions;

				$new_options['show_copyright'] = isset($_POST['sem_footer']['show_copyright']);
				$new_options['float_footer'] = isset($_POST['sem_footer']['float_footer']);

				$new_captions['copyright'] = strip_tags(stripslashes($_POST['sem_footer']['label_copyright']));

				if ( $new_options != $sem_options )
				{
					$sem_options = $new_options;

					update_option('sem5_options', $sem_options);
				}
				if ( $new_captions != $sem_captions )
				{
					$sem_captions = $new_captions;

					update_option('sem5_captions', $sem_captions);
				}
			}

			echo '<input type="hidden" name="update_sem_footer[nav_menu]" value="1" />';

			echo '<h3>'
				. __('Config')
				. '</h3>';

			echo '<div style="margin-bottom: .2em;">'
				. '<label>'
				. '<input type="checkbox"'
					. ' name="sem_footer[show_copyright]"'
					. ( $sem_options['show_copyright']
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. ' '
				. __('Show Copyright Notice')
				. '</label>'
				. '</div>';

			echo '<div style="margin-bottom: .2em;">'
				. '<label>'
				. '<input type="checkbox"'
					. ' name="sem_footer[float_footer]"'
					. ( $sem_options['float_footer']
						? ' checked="checked"'
						: ''
						)
					. ' />'
				. ' '
				. __('Show copyright and menu as a single line')
				. '</label>'
				. '</div>';

			echo '<h3>'
				. __('Captions')
				. '</h3>';

			echo '<div style="margin-bottom: .2em;">'
				. '<label>'
				. __('Copyright Notice, e.g. Copyright %year%')
				. '<br />'
				. '<input type="text" style="width: 95%"'
					. ' name="sem_footer[label_copyright]"'
					. ' value="' . htmlspecialchars($sem_captions['copyright']) . '"'
					. ' />'
				. '</label>'
				. '</div>';

			echo '<div>'
				. '<br />'
				. __('You can configure this navigation menu under Presentation / Nav Menus')
				. '</div>';
			break;
		}
	} # widget_control()
} # sem_nav_admin



#
# add_theme_nav_menu_options_admin()
#

function add_theme_nav_menu_options_admin()
{
	add_submenu_page(
		'themes.php',
		__('Nav&nbsp;Menus'),
		__('Nav&nbsp;Menus'),
		'switch_themes',
		basename(__FILE__),
		'display_theme_nav_menu_options_admin'
		);
} # end add_theme_nav_menu_options_admin()

add_action('admin_menu', 'add_theme_nav_menu_options_admin');


#
# update_theme_nav_menu_options()
#

function update_theme_nav_menu_options()
{
	check_admin_referer('sem_nav_menus');

	global $sem_nav;

	$sem_nav = array();

	foreach ( array('header_nav', 'sidebar_nav', 'footer_nav') as $menu_id )
	{
		$sem_nav[$menu_id] = array();

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

				$sem_nav[$menu_id][$value] = $_POST[$menu_id . '_ref'][$key];
			}
		}
	}

	update_option('sem5_nav', $sem_nav);

	regen_theme_nav_menu_cache();
} # end update_theme_nav_menu_options()


#
# regen_theme_nav_menu_cache()
#

function regen_theme_nav_menu_cache()
{
	global $wpdb;

	global $sem_nav;
	global $sem_nav_cache;

	$old_nav_cache = $sem_nav_cache;
	$sem_nav_cache = array();

	foreach ( array('header_nav', 'sidebar_nav', 'footer_nav') as $menu_id )
	{
		$sem_nav_cache[$menu_id] = array();

		$found = array();
		$seek = array();
		$refs = "";

		foreach ( (array) $sem_nav[$menu_id] as $item => $ref )
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
			$refs .= ( $refs ? ", " : "" ) . "'" . $wpdb->escape(sanitize_title($ref)) . "'";
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
					posts.post_status = 'publish'
					AND posts.post_type = 'page'
					AND posts.post_name IN ( $refs )
					AND posts.post_parent = 0
					AND posts.post_date_gmt <= '" . $now . "'
				");

			if ( isset($pages) )
			{
				update_post_cache($pages);
				update_page_cache($pages);

				foreach ( $pages as $page )
				{
					$found[$page->post_name] = apply_filters('the_permalink', get_permalink($page->ID));
				}
			}
		}

#		echo '<pre>';
#		var_dump($found);
#		echo '</pre>';

		foreach ( (array) $sem_nav[$menu_id] as $item => $ref )
		{
			if ( !$ref )
			{
				$ref = sanitize_title($item);
			}

			if ( isset($found[$ref]) )
			{
				$sem_nav_cache[$menu_id][$item] = $found[$ref];
			}
		}
	}

	if ( $old_nav_cache != $sem_nav_cache )
	{
		update_option('sem5_nav_cache', $sem_nav_cache);
	}
} # end regen_theme_nav_menu_cache()

add_action('save_post', 'regen_theme_nav_menu_cache', 0);
add_action('delete_post', 'regen_theme_nav_menu_cache', 0);
add_action('publish_phone', 'regen_theme_nav_menu_cache', 0);

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false
		&& $_SERVER['REQUEST_METHOD'] == 'POST'
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

	global $sem_nav;
	global $sem_nav_cache;

	# Display admin page

	echo "<div class=\"wrap\">\n"
		. "<h2>" . __('Theme Navigation Menus', 'sem-theme') . "</h2>\n"
		. "<form method=\"post\" action=\"\">\n"
		. "<input type=\"hidden\" name=\"action\" value=\"update_sem_theme_nav_menu\" />\n";

	if ( function_exists('wp_nonce_field') ) wp_nonce_field('sem_nav_menus');

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

		foreach ( (array) $sem_nav[$menu_id] as $item => $ref )
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
							? htmlspecialchars($ref)
							: ""
							)
						. "\""
					. " />"
					. "</td>\n"
				. "<td>"
					. ( isset($sem_nav_cache[$menu_id][$item])
						? ( "<a href=\""
								. $sem_nav_cache[$menu_id][$item]
								. "\">"
								. str_replace(" ", "&nbsp;", $item)
								. "</a>"
							)
						: "&nbsp;"
						)
					. "</td>\n"
				. "</tr>\n";
		}

		while ( $i++ < 12 )
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
	global $sem_captions;

	if ( $_POST["sidebar_nav_widget_update"] )
	{
		$new_captions = $sem_captions;

		$new_captions['sidebar_nav_title'] = strip_tags(stripslashes($_POST["sidebar_nav_widget_title"]));

		if ( $captions != $new_captions )
		{
			$captions = $new_captions;

			update_option('sem5_captions', $captions);
		}
	}

	$title = htmlspecialchars($captions['sidebar_nav_title']);

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