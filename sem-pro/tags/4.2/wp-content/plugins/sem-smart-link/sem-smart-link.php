<?php
/*
Plugin Name: Smart Link
Plugin URI: http://www.semiologic.com/software/smart-link/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/smart-link/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Lets you write links as [link text->link url] (explicit link), or as [link text->] (implicit link).
Author: Denis de Bernardy
Version: 3.5
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


if ( !defined('sem_include') )
{
	require_once dirname(__FILE__) . '/sem-include.php';
}

#
# sem_smart_link()
#

function sem_smart_link($buffer)
{
	preg_match("/
			<\s*body
				(?:
					\s[^>]*
				)?
				\s*>
			.*
			<\s*\/\s*body\s*>
		/isx",
		$buffer,
		$body
		);

	if ( $body )
	{
		$body = $body[0];

		$old_body = $body;

		# Step 1: Process raw, hard-coded uri

		$body = preg_replace_callback(
			"/
				(?<!`)					# not escaped
				\[
				\s*
				(						# uri
					(?:[^<>\[\s]*)
					(?:\/|\#)
					(?:[^<>\s]*)
				)
				\s*
				-(?:>|&gt;)				# ->
				\s*
				\]
			/iUsx",
			'sem_smart_link_process_raw_uri',
			$body
			);

		# Step 2: Process hard-coded uri with text

		$body = preg_replace_callback(
			"/
				(?<!`)					# not escaped
				\[
				([^<>\[]+)				# text
				-(?:>|&gt;)
				\s*
				(						# uri
					(?:[^<>\s]*)
					(?:\/|\#)
					(?:[^<>\s]*)
				)
				\s*
				\]
			/iUsx",
			'sem_smart_link_process_uri',
			$body
			);

		# Step 3: Process raw, hard-coded emails

		$body = preg_replace_callback(
			"/
				(?<!`)					# not escaped
				\[
				\s*
				(						# email (good enough...)
					[^<>\[@\s]+
					@
					[^<>\[@\s]+\.[^<>\[@\s]+
				)
				\s*
				-(?:>|&gt;)				# ->
				\s*
				\]
			/iUsx",
			'sem_smart_link_process_raw_email',
			$body
			);

		# Step 4: Process hard-coded emails with text

		$body = preg_replace_callback(
			"/
				(?<!`)					# not escaped
				\[
				([^<>\[]+)
				-(?:>|&gt;)				# ->
				\s*
				(						# email (good enough...)
					[^<>@\s]+
					@
					[^<>@\s]+\.[^<>@\s]+
				)
				\s*
				\]
			/iUsx",
			'sem_smart_link_process_email',
			$body
			);

		# Step 5: Initialize smart link cache

		$GLOBALS['sem_smart_link_cache'] = array();

		# Step 6: Register smart links

		$body = preg_replace_callback(
			"/
				(?<!`)					# not escaped
				\[
				([^<>\[]+)				# text
				-(?:>|&gt;)				# ->
				([^<>]*)				# link
				\]
			/iUsx",
			'sem_smart_link_register_link',
			$body
			);

		# Step 7: Cache smart links

		sem_smart_link_cache_links();

		# Step 8: Process smart links

		$body = preg_replace_callback(
			"/
				(?<!`)					# not escaped
				\[
				([^<>\[]+)				# text
				-(?:>|&gt;)				# ->
				([^<>]+)				# link
				@
				([^<>@]+)				# domain
				\]
			/iUsx",
			'sem_smart_link_process_link',
			$body
			);

		# Step 9: Unescape smart links

		$body = sem_smart_link_unescape($body);


		# Step 10: Replace new version of the body

		$buffer = str_replace($old_body, $body, $buffer);
	}

	return $buffer;
} # end sem_smart_link()

add_action('the_content', 'sem_smart_link');


#
# sem_smart_link_process_raw_uri()
#

function sem_smart_link_process_raw_uri($input)
{
	#echo '<pre>';
	#var_dump($input);
	#echo '</pre>';

	$input[1] = trim($input[1]);

	return '<a href="' . $input[1] . '" title="' . $input[1] . '">'
		. $input[1]
		. '</a>';
} # end sem_smart_link_process_raw_uri()


#
# sem_smart_link_process_uri()
#

function sem_smart_link_process_uri($input)
{
	#echo '<pre>';
	#var_dump($input);
	#echo '</pre>';

	$input[1] = trim($input[1]);
	$input[2] = trim($input[2]);

	return '<a href="' . $input[2] . '" title="' . $input[1] . '">'
		. $input[1]
		. '</a>';
} # end sem_smart_link_process_uri()


#
# sem_smart_link_process_raw_email()
#

function sem_smart_link_process_raw_email($input)
{
	#echo '<pre>';
	#var_dump($input);
	#echo '</pre>';

	$input[1] = trim($input[1]);

	$input[1] = preg_replace("/^\s*mailto:/i", "", $input[1]);
	$input[1] = antispambot($input[1]);

	return '<a href="mailto:' . $input[1] . '" title="' . $input[1] . '">'
		. $input[1]
		. '</a>';
} # end sem_smart_link_process_raw_email()


#
# sem_smart_link_process_email()
#

function sem_smart_link_process_email($input)
{
	#echo '<pre>';
	#var_dump($input);
	#echo '</pre>';

	$input[1] = trim($input[1]);
	$input[2] = trim($input[2]);

	$input[2] = preg_replace("/^\s*mailto:/i", "", $input[2]);
	$input[2] = antispambot($input[2]);

	return '<a href="mailto:' . $input[2] . '" title="' . $input[2] . '">'
		. $input[1]
		. '</a>';
} # end sem_smart_link_process_email()


#
# sem_smart_link_register_link()
#

function sem_smart_link_register_link($input)
{
	#echo '<pre>';
	#var_dump($input);
	#echo '</pre>';

	$input[1] = trim($input[1]);
	$input[2] = trim($input[2]);

	# clean up the mess...

	# no link provided...
	if ( !$input[2] )
	{
		# catch things like [@domain->]
		if (
			preg_match(
				"/
					^
					@
					\s*
					[^@\s]+
					\s*
					$
				/sx",
				$input[1]
				)
			)
		{
			return '\`' . $input[0];
		}
		else
		{
			$input[2] = $input[1];
		}
	}

	# catch things like [text->@domain]
	if (
		preg_match(
			"/
				^
				@
				\s*
				[^@\s]+
				\s*
				$
			/sx",
			$input[2]
			)
		)
	{
		$input[2] = $input[1] . ' ' . $input[2];
	}

	# catch things with no domain
	if (
		!preg_match(
			"/
				@
				\s*
				([^@\s]+)
				\s*
				$
			/sx",
			$input[2]
			)
		)
	{
		$input[2] = $input[2] . ' @ default';
	}

	# match link and domain
	if (
		!preg_match(
			"/
				^
				(.+)
				@
				\s*
				([^@\s]+)
				\s*
				$
			/sx",
			$input[2],
			$match
			)
		)
	{
		return '\`' . $input[0];
	}

	$match[1] = trim($match[1]);
	$match[2] = trim($match[2]);
	$match[2] = strtolower($match[2]);

	# catch any invalid link that may remain
	if ( !$match[1] || !$match[2] )
	{
		return '\`' . $input[0];
	}

	#echo '<pre>';
	#var_dump($match);
	#echo '</pre>';

	# register link

	$GLOBALS['sem_smart_link_cache'][$match[2]][$match[1]] = false;

	# return santize version of smart link

	return '[' . $input[1] . '->' . $match[1] . ' @ ' . $match[2] . ']';
} # end sem_smart_link_register_link()


#
# sem_smart_link_cache_links()
#

function sem_smart_link_cache_links()
{
	foreach ( array_keys((array) $GLOBALS['sem_smart_link_cache']) as $engine )
	{
		if ( isset($GLOBALS['sem_smart_link_engines'][$engine]) )
		{
			$GLOBALS['sem_smart_link_cache'][$engine] = $GLOBALS['sem_smart_link_engines'][$engine]($GLOBALS['sem_smart_link_cache'][$engine]);
		}
	}
} # end sem_smart_link_cache_links()


#
# sem_smart_link_process_link()
#

function sem_smart_link_process_link($input)
{
	$input[1] = trim($input[1]);
	$input[2] = trim($input[2]);
	$input[3] = trim($input[3]);

	#echo '<pre>';
	#var_dump($input);
	#var_dump($GLOBALS['sem_smart_link_cache'][$input[3]][$input[2]]);
	#echo '</pre>';

	if ( isset($GLOBALS['sem_smart_link_cache'][$input[3]][$input[2]]['link']) )
	{
		return '<a href="' . $GLOBALS['sem_smart_link_cache'][$input[3]][$input[2]]['link'] . '" title="' . $GLOBALS['sem_smart_link_cache'][$input[3]][$input[2]]['title'] . '">'
			. $input[1]
			. '</a>';
	}
	else
	{
		return $input[1];
	}
} # end sem_smart_link_process_link()




#
# sem_smart_link_engines()
#

$GLOBALS['sem_smart_link_engines'] = array();


#
# sem_smart_link_set_engine()
#

function sem_smart_link_set_engine($engine = '', $callback = '')
{
	$engine = trim($engine);
	$engine = strtolower($engine);

	if ( $engine && $callback )
	{
		$GLOBALS['sem_smart_link_engines'][$engine] = $callback;
	}
} # end sem_smart_link_set_engine()


#
# sem_smart_link_google_search()
#

function sem_smart_link_google_search($links)
{
    foreach ( (array) $links as $look_for => $found )
	{
		if ( !$links[$look_for] )
		{
			$links[$look_for] = array(
					'link' => (
						"http://www.google.com/search?q=" . rawurlencode($look_for)
						),
					'title' => $look_for
					);
		}
	}

	return $links;
} # end sem_smart_link_google_search()

sem_smart_link_set_engine('google', 'sem_smart_link_google_search');
sem_smart_link_set_engine('evil', 'sem_smart_link_google_search');


#
# sem_smart_link_yahoo_search()
#

function sem_smart_link_yahoo_search($links)
{
    foreach ( $links as $look_for => $found )
	{
		if ( !$links[$look_for] )
		{
			$links[$look_for] = array(
					'link' => ( "http://search.yahoo.com/search?p=" . rawurlencode($look_for) ),
					'title' => $look_for
					);
		}
	}

	return $links;
} # end sem_smart_link_yahoo_search()

sem_smart_link_set_engine('yahoo', 'sem_smart_link_yahoo_search');


#
# sem_smart_link_msn_search()
#

function sem_smart_link_msn_search($links)
{
    foreach ( $links as $look_for => $found )
	{
		if ( !$links[$look_for] )
		{
			$links[$look_for] = array(
					'link' => ( "http://search.msn.com/results.aspx?q=" . rawurlencode($look_for) ),
					'title' => $look_for
					);
		}
	}

	return $links;
} # end sem_smart_link_msn_search()

sem_smart_link_set_engine('msn', 'sem_smart_link_msn_search');


#
# sem_smart_link_wiki_search()
#

function sem_smart_link_wiki_search($links)
{
    foreach ( $links as $look_for => $found )
	{
		if ( !$links[$look_for] )
		{
			$links[$look_for] = array(
					'link' => ( "http://en.wikipedia.org/wiki/Special:Search?search=" . rawurlencode($look_for) ),
					'title' => $look_for
					);
		}
	}

	return $links;
} # end sem_smart_link_wiki_search()

sem_smart_link_set_engine('wiki', 'sem_smart_link_wiki_search');
sem_smart_link_set_engine('wikipedia', 'sem_smart_link_wiki_search');


#
# sem_smart_link_wp_links()
#

function sem_smart_link_wp_links($links)
{
	global $wpdb;

	$link_names = '';
	$replace = array();

    foreach ( $links as $look_for => $found )
	{
		$look_for = trim(strtolower($look_for));

		if ( !$found && $look_for )
		{
			$like_link_names .= ( $like_link_names ? ' OR ' : '' )
				. "TRIM(LOWER(links.link_name)) LIKE '" . mysql_real_escape_string($look_for) . "%'";
		}
	}

	#echo '<pre>';
	#var_dump($link_names);
	#echo '</pre>';

	if ( $like_link_names )
	{
		$sql = "
			SELECT
				links.*
			FROM
				$wpdb->links as links
			WHERE
				( $like_link_names )
			ORDER BY
				links.link_name ASC
			";

		#echo '<pre>';
		#var_dump($sql);
		#echo '</pre>';

		$wp_links = (array) $wpdb->get_results($sql);

		if ( $wp_links )
		{
			foreach ( $wp_links as $link )
			{
				$look_for = trim(strtolower($link->link_name));

				if ( !isset($replace[$look_for]) )
				{
					$replace[$look_for] = array(
							'link' => $link->link_url,
							'title' => $link->link_name
							);
				}
			}

			#echo '<pre>';
			#var_dump($replace);
			#echo '</pre>';

			foreach ( $links as $look_for => $found )
			{
				if ( !$found )
				{
					$look_for_i = trim(strtolower($look_for));

					if ( !$found && isset($replace[$look_for_i]) )
					{
						$links[$look_for] = $replace[$look_for_i];
						continue;
					}
				}
			}
		}
	}

	return $links;
} # end sem_smart_link_wp_links()

sem_smart_link_set_engine('links', 'sem_smart_link_wp_links');
sem_smart_link_set_engine('wp_links', 'sem_smart_link_wp_links');
sem_smart_link_set_engine('wordpress_links', 'sem_smart_link_wp_links');


#
# sem_smart_link_wp_cats()
#

function sem_smart_link_wp_cats($links)
{
	global $wpdb;
	global $wp_rewrite;

	if ( !isset($wp_rewrite) )
	{
		$wp_rewrite = new WP_Rewrite();
	}

	$like_cat_names = '';
	$like_cat_slugs = '';
	$replace = array();

    foreach ( $links as $look_for => $found )
	{
		$look_for = trim(strtolower($look_for));

		if ( !$found && $look_for )
		{
			$like_cat_names .= ( $like_cat_names ? ' OR ' : '' )
				. "TRIM(LOWER(categories.cat_name)) LIKE '" . mysql_real_escape_string($look_for) . "%'";
			$like_cat_slugs .= ( $like_cat_slugs ? ' OR ' : '' )
				. "categories.category_nicename LIKE '" . mysql_real_escape_string(sanitize_title($look_for)) . "%'";
		}
	}

	#echo '<pre>';
	#var_dump($like_cat_names, $like_cat_slugs);
	#echo '</pre>';

	if ( $like_cat_names && $like_cat_slugs )
	{
		$sql = "
			SELECT
				categories.*
			FROM
				$wpdb->categories as categories
			WHERE
				categories.category_count > 0
			AND (
				$like_cat_names
			OR
				$like_cat_slugs
				)
			ORDER BY
				categories.cat_name ASC
			";

		#echo '<pre>';
		#var_dump($sql);
		#echo '</pre>';

		$wp_cats = (array) $wpdb->get_results($sql);

		if ( $wp_cats )
		{
			foreach ( $wp_cats as $cat )
			{
				$look_for = trim(strtolower($cat->cat_name));

				if ( !isset($replace[$look_for]) )
				{
					$replace[$look_for] = array(
							'link' => get_category_link($cat->cat_ID),
							'title' => $cat->cat_name
							);
				}

				$look_for = $cat->category_nicename;

				if ( !isset($replace[$look_for]) )
				{
					$replace[$look_for] = array(
							'link' => get_category_link($cat->cat_ID),
							'title' => $cat->cat_name
							);
				}
			}

			#echo '<pre>';
			#var_dump($replace);
			#echo '</pre>';

			foreach ( $links as $look_for => $found )
			{
				if ( !$found )
				{
					$look_for_i = trim(strtolower($look_for));

					if ( isset($replace[$look_for_i]) )
					{
						$links[$look_for] = $replace[$look_for_i];
						continue;
					}

					$look_for_i = sanitize_title($look_for);

					if ( isset($replace[$look_for_i]) )
					{
						$links[$look_for] = $replace[$look_for_i];
						continue;
					}
				}
			}
		}
	}

	return $links;
} # end sem_smart_link_wp_cats()

sem_smart_link_set_engine('cats', 'sem_smart_link_wp_cats');
sem_smart_link_set_engine('wp_cats', 'sem_smart_link_wp_cats');
sem_smart_link_set_engine('wordpress_cats', 'sem_smart_link_wp_cats');

sem_smart_link_set_engine('categories', 'sem_smart_link_wp_cats');
sem_smart_link_set_engine('wp_categories', 'sem_smart_link_wp_cats');
sem_smart_link_set_engine('wordpress_categories', 'sem_smart_link_wp_cats');

sem_smart_link_set_engine('tags', 'sem_smart_link_wp_cats');
sem_smart_link_set_engine('wp_tags', 'sem_smart_link_wp_cats');
sem_smart_link_set_engine('wordpress_tags', 'sem_smart_link_wp_cats');


#
# sem_smart_link_wp_entries()
#

function sem_smart_link_wp_entries($links)
{
	global $wpdb;
	global $wp_rewrite;

	if ( !isset($wp_rewrite) )
	{
		$wp_rewrite =& new WP_Rewrite();
	}

	$like_entry_titles = '';
	$like_entry_slugs = '';
	$replace = array();

    foreach ( $links as $look_for => $found )
	{
		$look_for = trim(strtolower($look_for));

		if ( !$found && $look_for )
		{
			$like_entry_titles .= ( $like_entry_titles ? ' OR ' : '' )
				. "TRIM(LOWER(posts.post_title)) LIKE '" . mysql_real_escape_string($look_for) . "%'";
			$like_entry_slugs .= ( $like_entry_slugs ? ' OR ' : '' )
				. "posts.post_name LIKE '" . mysql_real_escape_string(sanitize_title($look_for)) . "%'";
		}
	}

	#echo '<pre>';
	#var_dump($like_entry_titles, $like_entry_slugs);
	#echo '</pre>';

	if ( $like_entry_titles && $like_entry_slugs )
	{
		$now = gmdate('Y-m-d H:i:00', strtotime("+1 minute"));

		$sql = "
			SELECT
				posts.*,
				CASE
				WHEN "
					. ( function_exists('get_site_option')
						? "posts.post_type = 'page'"
						: "posts.post_status = 'static'"
						)
					. "
				THEN 1
				ELSE 0
				END as is_static
			FROM
				$wpdb->posts as posts
			WHERE
				posts.post_status IN ('publish', 'static')
				"
				. ( function_exists('get_site_option')
					? "AND posts.post_type IN ('post', 'page')"
					: ""
					)
				. "
			AND
				posts.post_date <= '$now'"
			. ( ( defined('sem_home_page_id') && sem_home_page_id )
						? ( "
					AND posts.ID <> " . intval(sem_home_page_id) )
						: ""
						)
					. ( ( defined('sem_sidebar_tile_id') && sem_sidebar_tile_id )
						? ( "
					AND posts.ID <> " . intval(sem_sidebar_tile_id) )
						: ""
						)
					. ( ( defined('sem_entry_id') && sem_entry_i )
						? ( "
					AND posts.ID <> " . intval(sem_entry_id) )
						: ""
						)
			. "
			AND (
					$like_entry_titles
				OR
					$like_entry_slugs
				)
			ORDER BY
				is_static DESC,
				posts.post_title ASC,
				posts.post_date DESC
			";

		#echo '<pre>';
		#var_dump($sql);
		#echo '</pre>';

		$wp_entries = (array) $wpdb->get_results($sql);

		if ( $wp_entries )
		{
			if ( function_exists('update_post_cache') )
			{
				update_post_cache($wp_entries);
			}
			if ( function_exists('update_page_cache') )
			{
				update_page_cache($wp_entries);
			}

			foreach ( $wp_entries as $post )
			{
				$look_for = trim(strtolower($post->post_title));

				if ( !isset($replace[$look_for]) )
				{
					$replace[$look_for] = array(
							'link' => get_permalink($post->ID),
							'title' => $post->post_title
							);
				}

				$look_for = $post->post_name;

				if ( !isset($replace[$look_for]) )
				{
					$replace[$look_for] = array(
							'link' => get_permalink($post->ID),
							'title' => $post->post_title
							);
				}

				$look_for = preg_replace("/-\d+$/", '', $look_for);

				if ( !isset($replace[$look_for]) )
				{
					$replace[$look_for] = array(
							'link' => get_permalink($post->ID),
							'title' => $post->post_title
							);
				}
			}

			#echo '<pre>';
			#var_dump($replace);
			#echo '</pre>';

			foreach ( $links as $look_for => $found )
			{
				if ( !$found )
				{
					$look_for_i = trim(strtolower($look_for));

					if ( isset($replace[$look_for_i]) )
					{
						$links[$look_for] = $replace[$look_for_i];
						continue;
					}

					$look_for_i = sanitize_title($look_for);

					if ( isset($replace[$look_for_i]) )
					{
						$links[$look_for] = $replace[$look_for_i];
						continue;
					}

					$look_for_i = preg_replace("/-\d+$/", '', $look_for_i);

					if ( isset($replace[$look_for_i]) )
					{
						$links[$look_for] = $replace[$look_for_i];
						continue;
					}
				}
			}
		}
	}

	return $links;
} # end sem_smart_link_wp_entries()

sem_smart_link_set_engine('posts', 'sem_smart_link_wp_entries');
sem_smart_link_set_engine('wp_posts', 'sem_smart_link_wp_entries');
sem_smart_link_set_engine('wordpress_posts', 'sem_smart_link_wp_entries');

sem_smart_link_set_engine('pages', 'sem_smart_link_wp_entries');
sem_smart_link_set_engine('wp_pages', 'sem_smart_link_wp_entries');
sem_smart_link_set_engine('wordpress_pages', 'sem_smart_link_wp_entries');

sem_smart_link_set_engine('entries', 'sem_smart_link_wp_entries');
sem_smart_link_set_engine('wp_entries', 'sem_smart_link_wp_entries');
sem_smart_link_set_engine('wordpress_entries', 'sem_smart_link_wp_entries');


#
# sem_smart_link_wp()
#

function sem_smart_link_wp($links)
{
	$links = sem_smart_link_wp_entries($links);
	$links = sem_smart_link_wp_cats($links);
	$links = sem_smart_link_wp_links($links);

	return $links;
} # end sem_smart_link_wp()

sem_smart_link_set_engine('wp', 'sem_smart_link_wp');
sem_smart_link_set_engine('wordpress', 'sem_smart_link_wp');
sem_smart_link_set_engine('default', 'sem_smart_link_wp');


#
# sem_smart_link_escape()
#

function sem_smart_link_escape($buffer)
{
	$buffer = preg_replace(
		"/
			(?<!`)					# not escaped
			\[
			[^<>]*					# text
			-(?:>|&gt;)				# ->
			[^<>]*					# link
			\]
		/iUsx",
		"`$0",
		$buffer
		);
	return $buffer;
} # end sem_smart_link_escape()


#
# sem_smart_link_unescape()
#

function sem_smart_link_unescape($buffer)
{
	$buffer = preg_replace(
		"/
			`						# escaped
			(
				\[
				[^<>\[]*			# text
				-(?:>|&gt;)			# ->
				[^<>\]]*			# link
				\]
			)
			`						# optional escape symbol
		/iUsx",
		"$1",
		$buffer
		);
	return $buffer;
} # end sem_smart_link_unescape()


#
# sem_smart_link_ob()
#

function sem_smart_link_ob()
{
	remove_action('the_content', 'sem_smart_link');

	add_filter('get_comment_author_link', 'sem_smart_link_escape');
	add_filter('comment_text', 'sem_smart_link_escape');
	add_filter('comment_excerpt', 'sem_smart_link_escape');

	ob_start('sem_smart_link');
} # end sem_smart_link_ob()

add_action('template_redirect', 'sem_smart_link_ob', 2000);
?>