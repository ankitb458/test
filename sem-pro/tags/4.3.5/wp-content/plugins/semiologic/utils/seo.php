<?php
#
# display_page_title()
#

function display_page_title()
{
	global $cat;
	global $cache_categories;
	global $posts;
	global $s;
	global $sem_s;
	global $tag;
	global $semiologic;

	$o = "";

	if ( is_search() )
	{
		if ( $sem_s )
		{
			$o .= trim(convert_chars(strip_tags(stripslashes($sem_s))));
		}
		else
		{
			$o .= trim(convert_chars(strip_tags(stripslashes($s))));
		}
	}
	elseif ( $tag )
	{
		$o .= convert_chars(stripslashes($tag));
	}
	elseif ( is_category() )
	{
		$o .= convert_chars(stripslashes(single_cat_title('', false)));
	}
	elseif ( is_archive() )
	{
		$o .= __('Archives');
	}
	elseif ( is_single() || is_page() || ( class_exists('sem_static_front') && sem_static_front::is_home() ) )
	{
		if ( $title = get_post_meta($posts[0]->ID, '_title', true) )
		{
			$o .= convert_chars(strip_tags($title));
		}
		else
		{
			$o .= convert_chars(strip_tags(apply_filters('single_post_title', $posts[0]->post_title)));
		}
	}
	elseif ( preg_match("/\/photos/i", $_SERVER['REQUEST_URI'])
		|| preg_match("/falbum/", $_SERVER['REQUEST_URI'])
		)
	{
		$o .= __('Photos');
	}
	elseif ( is_404() )
	{
		$o .= __('Not Found');
	}
	elseif ( $semiologic['seo']['title'] )
	{
		$o .= convert_chars($semiologic['seo']['title']);
	}
	else
	{
		$o .= convert_chars(apply_filters('bloginfo', get_bloginfo('description'), 'description'));
	}

	if ( $semiologic['seo']['add_site_name'] )
	{
		$o .= ( $o ? " | " : "" )
			. convert_chars(apply_filters('bloginfo', get_bloginfo('name'), 'name'));
	}

	echo $o;
} # end display_page_title()

remove_action('display_page_title', 'default_page_title');
add_action('display_page_title', 'display_page_title');


#
# display_seo_meta()
#

function display_seo_meta()
{
	global $posts;

	if ( is_single() || is_page() || ( class_exists('sem_static_front') && sem_static_front::is_home() ) )
	{
		$GLOBALS['posts'] = $GLOBALS['wp_query']->posts;
		$GLOBALS['post'] = $GLOBALS['wp_query']->posts[0];

		echo "\n"
			. "<meta name=\"keywords\" content=\"";

		if ( $keywords = get_post_meta($posts[0]->ID, '_keywords', true) )
		{
			echo htmlspecialchars($keywords, ENT_QUOTES);
		}
		else
		{
			the_title();

			echo " - ";

			echo strip_tags(get_the_category_list(', '));

		}

			echo "\" />\n";

		echo "<meta name=\"description\" content=\"";

		if ( $description = get_post_meta($posts[0]->ID, '_description', true) )
		{
			echo htmlspecialchars($description, ENT_QUOTES);
		}
		else
		{
			echo preg_replace("/(\n\r?){2,}/",
				"\n\n",
				htmlspecialchars(strip_tags(get_the_excerpt()), ENT_QUOTES)
				);
		}

			echo "\" />\n";
	}
	elseif ( is_category() )
	{
		echo "\n"
			. "<meta name=\"keywords\" content=\"";

			echo convert_chars(stripslashes(single_cat_title('', false)));

			echo "\" />\n";

		echo "<meta name=\"description\" content=\"";

			if ( $description = category_description() )
			{
				echo htmlspecialchars(trim(strip_tags($description)), ENT_QUOTES);
			}
			elseif ( $GLOBALS['semiologic']['seo']['description'] )
			{
				echo htmlspecialchars($GLOBALS['semiologic']['seo']['description'], ENT_QUOTES);
			}
			else
			{
				echo htmlspecialchars(strip_tags(get_bloginfo('description')), ENT_QUOTES);
			}

			echo "\" />\n";
	}
	else
	{
		echo "\n"
			. "<meta name=\"keywords\" content=\"";

			if ( $GLOBALS['semiologic']['seo']['keywords'] )
			{
				echo htmlspecialchars($GLOBALS['semiologic']['seo']['keywords'], ENT_QUOTES);
			}
			else
			{
				echo bloginfo('name');
			}

			echo "\" />\n";

		echo "<meta name=\"description\" content=\"";

			if ( $GLOBALS['semiologic']['seo']['description'] )
			{
				echo htmlspecialchars($GLOBALS['semiologic']['seo']['description'], ENT_QUOTES);
			}
			else
			{
				echo htmlspecialchars(strip_tags(get_bloginfo('description')), ENT_QUOTES);
			}

			echo "\" />\n";
	}
} # end display_seo_meta()

add_action('wp_head', 'display_seo_meta');



#
# disable obsolete plugin
#

if ( class_exists('sem_theme_seo') )
{
	remove_action('wp_head', array(&$GLOBALS['sem_theme_seo'], 'display_auto_meta'));
}


#
# fix_permalink_redirect()
#

function fix_permalink_redirect($no_redirect)
{
	$skip = array(
			'wp-',
			'/shop/',
			'/survey/',
			'/library/',
			'aff=',
			'print=',
			'cat_id=',
			'ar_mon=',
			'mon=',
			'action=',
			'/index.php',
			'/update-feeds.php',
			'/sitemap.xml',
			'/sitemap.xml.gz'
			);

	if ( strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false )
	{
		$skip[] = $_SERVER['SCRIPT_NAME'];
		$skip[] = preg_replace("/index\.php\??$/i", "", $_SERVER['SCRIPT_NAME']);
		$skip[] = preg_replace("/index\.php\/?$/i", "", $_SERVER['SCRIPT_NAME']);
		#var_dump($skip);
	}

	return array_merge(
		(array) $no_redirect,
		$skip
		);

} # end fix_permalink_redirect()

add_filter('permalink_redirect_skip', 'fix_permalink_redirect');


#
# add_more_keywords()
#

function add_more_keywords($cats)
{
	$tags = $cats;
	if (!is_admin()) {
		// check for Jerome's keywords
		if (function_exists('get_the_post_keytags'))
			$tags = get_the_post_keytags(true);
		// Simple Tagging
		elseif (function_exists('STP_GetPostTags'))
			$tags = STP_GetPostTags(null, true);
	}
	return ($tags);
} # end add_more_keywords()

add_filter('the_category', 'add_more_keywords', -1000);


#
# start_ad_section()
#

function start_ad_section()
{
?><!-- google_ad_section_start -->
<?php
} # end start_ad_section()


#
# start_ad_ignore_section()
#

function start_ad_ignore_section()
{
?><!-- google_ad_section_start(weight=ignore) -->
<?php
} # end start_ad_ignore_section()


#
# end_ad_section()
#

function end_ad_section()
{
?><!-- google_ad_section_end -->
<?php
} # end end_ad_section()


add_action('before_the_header', 'start_ad_ignore_section');
add_action('after_the_header', 'end_ad_section');

add_action('before_the_footer', 'start_ad_ignore_section');
add_action('after_the_footer', 'end_ad_section');

add_action('display_sidebar', 'start_ad_ignore_section', -1000);
add_action('display_sidebar', 'end_ad_section', 1000);

add_action('display_ext_sidebar', 'start_ad_ignore_section', -1000);
add_action('display_ext_sidebar', 'end_ad_section', 1000);

add_action('before_the_entries', 'start_ad_section');
add_action('after_the_entries', 'end_ad_section');
?>