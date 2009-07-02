<?php
#
# force_sell()
#

function force_sell($in)
{
	return 'sell';
} # end force_sell()


#
# setup_advanced_template()
#

function setup_advanced_template($template)
{
	switch ($template)
	{
		case 'article':
			add_filter('show_entry_by_on', 'true');
			add_filter('show_entry_trackback_uri', 'pings_open');
			add_filter('show_entry_follow_ups', 'true');
			add_filter('show_entry_related_entries', 'true');
			add_filter('show_entry_author_image', 'true');
			break;
		case 'archives':
			remove_action('display_entry_body', 'display_entry_body');
			add_action('display_entry_body', 'display_archives_template');
			break;
		case 'links':
			remove_action('display_entry_body', 'display_entry_body');
			add_action('display_entry_body', 'display_links_template');
			break;
		case 'monocolumn':
			define('monocolumn_template', true);
			add_filter('active_layout', 'strip_s');
			break;
		case 'sell_page':
			add_filter('active_layout', 'force_m');
			add_filter('active_width', 'force_sell');
			add_filter('show_credits', 'false');
			reset_plugin_hook('before_the_wrapper');
			reset_plugin_hook('before_the_header');
			reset_plugin_hook('display_header');
			reset_plugin_hook('display_navbar');
			reset_plugin_hook('after_the_header');
			reset_plugin_hook('before_the_entries');
			reset_plugin_hook('before_the_entry');
			reset_plugin_hook('display_entry_date');
			reset_plugin_hook('display_entry_title');
			reset_plugin_hook('display_entry_title_meta');
			reset_plugin_hook('display_entry_meta');
			reset_plugin_hook('display_entry_actions');
			reset_plugin_hook('after_the_entry');
			reset_plugin_hook('after_the_entries');
			reset_plugin_hook('before_the_footer');
			reset_plugin_hook('display_footer');
			reset_plugin_hook('wp_footer');
			reset_plugin_hook('after_the_footer');
			reset_plugin_hook('after_the_wrapper');
			add_action('before_the_entry', create_function('$in', 'echo "<div class=\"sell\">";'));
			add_action('after_the_entry', create_function('$in', 'echo "</div>"; edit_post_link(get_caption(\'edit\'), \' <p class="admin_link" style="text-align: right;">\', \'</p>\');'));
			add_action('before_the_entry', 'html2wp_kill_formatting');
			break;
	}
} # end setup_advanced_template()

remove_action('setup_default_advanced_template', 'setup_default_advanced_template');
add_action('setup_template', 'setup_advanced_template');


#
# archive_page_permalink()
#

function archive_page_permalink($permalink, $mon = null)
{
	if ( !isset($mon) )
	{
		$mon = $_GET['mon'];
	}

	return $permalink
		. ( $mon
			? ( ( ( strpos($permalink, "?") !== false )
					? "&amp;"
					: "?"
					)
				. "mon=" . $mon
				)
			: ''
			);
} # end archive_page_permalink()


#
# add_mon2permalink()
#

function add_mon2permalink($permalink)
{
	return archive_page_permalink($permalink);
} # end add_mon2permalink()


#
# display_archives_template()
#

function display_archives_template()
{
	global $wpdb;

	$params = array(
		'date_format' => get_option('date_format'),
		'sep' => '&bull;'
		);

	$the_archive_permalink = get_permalink();

	# latest posts

	$mon = ( isset($_GET['mon']) && preg_match("/\d{4}-\d{2}/", $_GET['mon']) )
		? $_GET['mon']
		: false;

	$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

	if ( !defined('highlights_cat_id') )
	{
		$highlights_cat_id = $wpdb->get_var("
			SELECT
				categories.cat_ID
			FROM
				$wpdb->categories as categories
			WHERE
				categories.category_nicename = 'highlights'
			");

		if ( isset($highlights_cat_id) )
		{
			define('highlights_cat_id', intval($highlights_cat_id));
		}
		else
		{
			define('highlights_cat_id', false);
		}
	}

	$sql = "
		SELECT
			posts.*,
			DATE_FORMAT( posts.post_date, '%Y-%m' ) AS post_month,
			MAX( CASE
				WHEN postmeta.meta_value = 'article.php'"
				. ( highlights_cat_id
					? ( "
				OR post2cat.category_id = " . highlights_cat_id )
					: ''
					)
				. "
					THEN 1
					ELSE 0
				END ) AS highlight_post
		FROM
			$wpdb->posts as posts
		LEFT JOIN $wpdb->postmeta as postmeta
			ON postmeta.post_id = posts.ID"
		. ( highlights_cat_id
			? ( "
		LEFT JOIN
			$wpdb->post2cat as post2cat
				ON post2cat.post_id = posts.ID" )
			: ""
			)
		. "
		WHERE
			posts.post_date_gmt <= '" . $now . "'
			AND posts.post_password = ''
			AND ( "
			. ( use_post_type_fixed
				? "( post_status = 'publish' AND post_type = 'post' )"
				: "( post_status = 'publish' )"
				)
			. "
				OR
				"
			. ( use_post_type_fixed
				? "( post_status = 'publish' AND post_type = 'page' AND postmeta.meta_value = 'article.php' )"
				: "( post_status = 'static' AND postmeta.meta_value = 'article.php' )"
				)
			. "
				)"
			. ( $mon
				? "
			AND DATE_FORMAT( posts.post_date, '%Y-%m' ) = '$mon'"
				: ""
				)
			. "
		GROUP BY
			posts.ID"
			. ( $mon
				? "
		ORDER BY
			posts.post_date ASC"
				: "
		ORDER BY
			posts.post_date DESC
		LIMIT 10"
				);

	#echo '<pre>';
	#var_dump($sql);
	#echo '</pre>';

	$cur_posts = $wpdb->get_results($sql);



	$monthly_archives = $wpdb->get_results("
		SELECT
			DATE_FORMAT( posts.post_date, '%Y-%m' ) AS post_month,
			COUNT( DISTINCT posts.ID ) AS num_posts
		FROM
			$wpdb->posts as posts
		LEFT JOIN $wpdb->postmeta as postmeta
			ON postmeta.post_id = posts.ID
		WHERE
			posts.post_date_gmt <= '" . $now . "'
			AND posts.post_password = ''
			AND ( "
			. ( use_post_type_fixed
				? "( post_status = 'publish' AND post_type = 'post' )"
				: "( post_status = 'publish' )"
				)
			. "
				OR
				"
			. ( use_post_type_fixed
				? "( post_status = 'publish' AND post_type = 'page' AND postmeta.meta_value = 'article.php' )"
				: "( post_status = 'static' AND postmeta.meta_value = 'article.php' )"
				)
			. "
				)
		GROUP BY
			post_month
		HAVING
			num_posts > 0
		ORDER BY
			posts.post_date ASC
		");

	echo '<div class="entry_body">';

	if ( isset($monthly_archives) && $monthly_archives )
	{
		echo "<div class=\"sem_post_archive\">\n";

		echo "<h2>"
			. __('Monthly Archives')
			. "</h2>\n";

		echo "<ul>\n"
			. "<li>"
			. ( $mon
				? ( "<a href=\""
					. $the_archive_permalink
					. "\">"
					. __('Latest Posts')
					. "</a>"
					)
				: __('Latest Posts')
				)
			. "</li>";

		foreach ( $monthly_archives as $key => $monthly_archive )
		{
			echo "<li>"
				. ( ( $monthly_archive->post_month != $mon )
					? ( "<a href=\""
						. archive_page_permalink($the_archive_permalink, $monthly_archive->post_month)
						. "\">"
						. date('F Y', strtotime($monthly_archive->post_month . "-01"))
						. "</a>"
						)
					: date('F Y', strtotime($monthly_archive->post_month . "-01"))
					)
				. " (" . $monthly_archive->num_posts . ")\n"
				. "</li>\n";
		}

		echo "</ul>\n";

		echo "</div>\n";
	}

	if ( isset($cur_posts) && $cur_posts )
	{
		if ( function_exists('update_post_cache') )
		{
			update_post_cache($cur_posts);
		}
		if ( function_exists('update_page_cache') )
		{
			update_page_cache($cur_posts);
		}

		echo "<div class=\"sem_post_list\" id=\"sem_post_list\">\n";

		echo '<h2>'
			. ( $mon
				? date('F Y', strtotime($mon . "-01"))
				: __('Latest Posts')
				)
			. "</h2>\n";

		foreach ( $cur_posts as $key => $cur_post )
		{
			$cur_post_title = $cur_post->post_title;
			$cur_post_permalink = apply_filters('the_permalink', get_permalink($cur_post->ID));
			$cur_post_date = date($params['date_format'], strtotime($cur_post->post_date));
			$user_can_edit_cur_post = user_can_edit_post($user_ID, $cur_post->ID);

			if ( !isset($cur_posts[$key-1])
				|| ( $cur_post_date != date($params['date_format'], strtotime($cur_posts[$key-1]->post_date)) )
				)
			{
				echo "<h3>" . $cur_post_date . "</h3>\n"
					. "<ul>\n";
			}

			echo "<li>"
				. "<a href=\"" . $cur_post_permalink . "\">"
					. ( $cur_post->highlight_post
						? ( "<em>" . $cur_post_title . "</em>" )
						: $cur_post_title
						)
					. "</a>"
				. ( $user_can_edit_cur_post
					? ( "<span class=\"post_meta\">"
						. " " . $params['sep'] . " "
						. "<a href=\""
							. site_url . "wp-admin/post.php?action=edit&amp;post=" . $cur_post->ID
							. "\" class=\"admin_link edit_entry\">" . __('Edit') . "</a>"
						. "</span>" )
					: ""
					)
				. "</li>\n";

			if ( !isset($cur_posts[$key+1])
				|| $cur_post_date != date($params['date_format'], strtotime($cur_posts[$key+1]->post_date))
				)
			{
				echo "</ul>\n";
			}
		}

		echo "</div>\n";
	}

	echo '</div>';

	add_action('display_entry_actions', create_function('', "add_filter('the_permalink', 'add_mon2permalink');"), 0);
	add_action('display_entry_actions', create_function('', "remove_filter('the_permalink', 'add_mon2permalink');"), 1000);
} # end display_archives_template()


#
# links_page_permalink()
#

function links_page_permalink($permalink, $cat_id = null)
{
	if ( !isset($cat_id) )
	{
		$cat_id = $_GET['cat_id'];
	}

	return $permalink
		. ( $cat_id
			? ( ( ( strpos($permalink, "?") !== false )
					? "&amp;"
					: "?"
					)
				. "cat_id=" . $cat_id
				)
			: ''
			);
} # end links_page_permalink()


#
# add_cat_id2permalink()
#

function add_cat_id2permalink($permalink)
{
	return links_page_permalink($permalink);
} # end add_cat_id2permalink()


#
# display_links_template()
#

function display_links_template()
{
	$cat_id = ( ( isset($_GET['cat_id']) )
		? intval($_GET['cat_id'])
		: false
		);

	$the_links_permalink = get_permalink();

	global $wpdb;

	if ( use_post_type_fixed )
	{
		$link_cats = $wpdb->get_results("
			SELECT
				cat_ID as cat_id, cat_name, COUNT( $wpdb->links.link_id ) as num_links
			FROM
				$wpdb->categories
			INNER JOIN
				$wpdb->link2cat ON $wpdb->link2cat.category_id = $wpdb->categories.cat_ID
			INNER JOIN
				$wpdb->links ON $wpdb->links.link_id = $wpdb->link2cat.link_id
			WHERE
				link_visible = 'Y'
			GROUP BY
				cat_ID
			HAVING
				num_links > 0
			ORDER BY
				category_nicename
			");
	}
	else
	{
		$link_cats = $wpdb->get_results("
			SELECT
				cat_id, cat_name, COUNT( link_id ) as num_links
			FROM
				$wpdb->linkcategories
			INNER JOIN
				$wpdb->links ON link_category = cat_id
			WHERE
				link_visible = 'Y'
			GROUP BY
				cat_id
			HAVING
				num_links > 0
			ORDER BY
				cat_name
			");
	}

	echo '<div class="entry_body">';

	if ( isset($link_cats) && $link_cats )
	{

		echo "<div class=\"sem_link_archive\">\n";

		echo "<h2>" . __('Link Categories') . "</h2>";

		echo "<ul>\n";

		echo "<li>"
			. ( $cat_id
				? ( "<a href=\"" . get_permalink() . "\">"
					. __('Latest links')
					. "</a>"
					)
				: __('Latest links')
				)
			. "</li>\n";

		foreach ( $link_cats as $link_cat )
		{
			echo "<li>"
				. ( ( $cat_id != $link_cat->cat_id )
					? ( "<a href=\""
						. links_page_permalink($the_links_permalink, $link_cat->cat_id)
						. "\">"
						. stripslashes($link_cat->cat_name)
						. "</a>"
						)
					: ( stripslashes($link_cat->cat_name) )
					)
				. " (" . $link_cat->num_links . ")\n"
				. "</li>\n";
		}
		echo "</ul>\n";

		echo "</div>\n";

		echo "<div class=\"sem_link_list\" id=\"sem_link_list\">\n";

		if ( $cat_id )
		{
		    foreach ( $link_cats as $link_cat )
			{
				if ( $link_cat->cat_id == $cat_id )
				{
					echo "<h2>" . stripslashes($link_cat->cat_name) . "</h2>\n"
						. "<ul>\n";
					get_links($cat_id, '<li>', '</li>', '<br />', false, 'name', true,
true, -1, false);
					echo "</ul>\n";
					break;
				}
			}
		}
		else
		{
			echo "<h2>" .  __('Latest links') . "</h2>\n"
						. "<ul>\n";
			get_links(-1, '<li>', '</li>', '<br />', false, '_id', true,
true, 10, false);
			echo "</ul>\n";
		}

		echo "</div>\n";
	}

	echo '</div>';

	add_action('display_entry_actions', create_function('', "add_filter('the_permalink', 'add_cat_id2permalink');"), 0);
	add_action('display_entry_actions', create_function('', "remove_filter('the_permalink', 'add_cat_id2permalink');"), 1000);
} # end display_links_template()


#
# display_archive_archive_header
#

function display_archive_header()
{
	if ( isset($GLOBALS['sem_opt_in_front']) && !defined('sem_main_cat_id') )
	{
		$GLOBALS['sem_opt_in_front']->init();
	}

	$show_archive_listing = isset($GLOBALS['semiologic']['theme_archives'])
		&& $GLOBALS['semiologic']['theme_archives']
		&& is_archive()
		&& !( defined('sem_main_cat_id')
			&& is_category()
			&& sem_main_cat_id == $GLOBALS['cat']
			);

	if ( apply_filters('show_archive_listing', $show_archive_listing) )
	{
		echo '<div class="entry">'
			. '<div class="entry_header">'
			. '<h1>';

		if ( is_category() )
		{
			single_cat_title();
		}
		else
		{
			echo __('Archive');
		}

		echo '</h1>'
			. '</div>';

		echo '<div class="entry_body">';

		echo category_description();

		echo '</div>'
			. '</div>';

		do_action('display_archive_listing');
	}
} # end display_archive_header()

add_action('before_the_entries', 'display_archive_header');


#
# display_archive_listing()
#

function display_archive_listing()
{
	global $wpdb;

	if ( have_posts() )
	{
		$i = 0;

		echo '<div class="archive_listing">';

		if ( !defined('highlights_cat_id') )
		{
			$highlights_cat_id = $wpdb->get_var("
				SELECT
					categories.cat_ID
				FROM
					$wpdb->categories as categories
				WHERE
					categories.category_nicename = 'highlights'
				");

			if ( isset($highlights_cat_id) )
			{
				define('highlights_cat_id', intval($highlights_cat_id));
			}
			else
			{
				define('highlights_cat_id', false);
			}
		}

		while ( have_posts() )
		{
			the_post();

			$the_date = the_date('', '', '', false);

			if ( $the_date )
			{
				if ( $i != 0 )
				{
					echo '</ul>';
				}

				echo '<h3>' . $the_date . '</h3>';

				echo '<ul>';
			}

			$i++;

			echo '<li>'
				. '<a href="';

			the_permalink();

			echo '">';

			if ( highlights_cat_id
				&& in_category(highlights_cat_id)
				)
			{
				echo '<em>';
				the_title();
				echo '</em>';
			}
			else
			{
				the_title();
			}

			echo '</a>';

			edit_post_link(get_caption('edit'), ' <span class="action admin_link">&bull;&nbsp;', '</span>');

			echo '</li>';

		}

		echo '</ul>'
			. '</div>';
	}

	# hijack WP loop
	$GLOBALS['wp_query']->current_post = $GLOBALS['wp_query']->post_count;
	reset_plugin_hook('display_404');
} # end display_archive_listing()

add_action('display_archive_listing', 'display_archive_listing');
?>