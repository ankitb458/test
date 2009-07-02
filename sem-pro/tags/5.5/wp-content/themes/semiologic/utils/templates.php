<?php
#
# setup_template()
#

function setup_template($template)
{
	switch ($template)
	{
		case 'monocolumn':
			add_filter('active_layout', 'strip_s');
			break;
		case 'sell_page':
			add_filter('active_layout', 'force_m');
			add_filter('active_width', 'force_sell');
			if ( sem_pro ) add_filter('show_credits', 'false');
			reset_plugin_hook('wp_footer');
			add_action('wp_footer', 'display_theme_onload_scripts', 1000000);
			add_action('wp_footer', 'display_entry_footer', 50);
			break;
	}
} # setup_template()

add_action('setup_template', 'setup_template');


#
# archive_page_permalink()
#

function archive_page_permalink($permalink, $mon = null)
{
	if ( !isset($mon) )
	{
		$mon = ( isset($_GET['mon']) && preg_match("/\d{4}-\d{2}/", $_GET['mon']) )
		? $_GET['mon']
		: false;
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
				term_id
			FROM
				$wpdb->terms
			WHERE
				slug = 'highlights'
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
				OR sem_post2cat.term_taxonomy_id = " . highlights_cat_id )
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
				LEFT JOIN $wpdb->term_relationships AS sem_post2cat
				ON sem_post2cat.object_id = posts.ID
				LEFT JOIN $wpdb->term_taxonomy as sem_cats
				ON sem_cats.term_taxonomy_id = sem_post2cat.term_taxonomy_id
				AND taxonomy = 'category'
				"
				)
			: ""
			)
		. "
		WHERE
			posts.post_date_gmt <= '" . $now . "'
			AND posts.post_password = ''
			AND (
				( post_status = 'publish' AND post_type = 'post' )
				OR
				( post_status = 'publish' AND post_type = 'page' AND postmeta.meta_value = 'article.php' )
				)
			"
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
			AND (
				( post_status = 'publish' AND post_type = 'post' )
				OR
				( post_status = 'publish' AND post_type = 'page' AND postmeta.meta_value = 'article.php' )
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
		update_post_cache($cur_posts);
		update_page_cache($cur_posts);

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
} # end display_archives_template()


#
# links_page_permalink()
#

function links_page_permalink($permalink, $cat_id = null)
{
	if ( !isset($cat_id) )
	{
		$cat_id = intval($_GET['cat_id']);
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

	$link_cats = $wpdb->get_results("
		SELECT
			terms.term_id as cat_id,
			terms.name as cat_name,
			term_taxonomy.count as num_links
		FROM
			$wpdb->terms as terms
		INNER JOIN
			$wpdb->term_taxonomy as term_taxonomy
		ON
			terms.term_id = term_taxonomy.term_id
		AND
			term_taxonomy.taxonomy = 'link_category'
		INNER JOIN
			$wpdb->term_relationships as term_relationships
		ON
			term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
		INNER JOIN
			$wpdb->links as links
		ON
			links.link_id = term_relationships.object_id
		WHERE
			links.link_visible = 'Y'
		AND
			term_taxonomy.count > 0
		GROUP BY
			terms.term_id
		ORDER BY
			terms.name
		");

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
false, -1, false);
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
false, 10, false);
			echo "</ul>\n";
		}

		echo "</div>\n";
	}

	echo '</div>';
} # end display_links_template()


#
# display_archive_archive_header
#

function display_archive_header()
{
	global $sem_options;

	$show_archive_listing = $sem_options['theme_archives']
		&& is_archive()
		&& !( is_category() && is_home() );

	if ( apply_filters('show_archive_listing', $show_archive_listing) )
	{
		echo '<div class="entry">'
			. '<div class="entry_header">'
			. '<h1>';

		if ( is_category() )
		{
			single_cat_title();
		}
		elseif ( is_tag() )
		{
			single_tag_title();
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
					term_id
				FROM
					$wpdb->terms
				WHERE
					slug = 'highlights'
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

			edit_post_link(__('Edit'), ' <span class="action admin_link">&bull;&nbsp;', '</span>');

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