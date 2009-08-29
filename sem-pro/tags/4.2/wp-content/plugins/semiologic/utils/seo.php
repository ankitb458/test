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
	elseif ( is_single() || is_page() )
	{
		$o .= convert_chars(strip_tags(apply_filters('single_post_title', $posts[0]->post_title)));
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
	else
	{
		$o .= convert_chars(apply_filters('bloginfo', get_bloginfo('description'), 'description'));
	}

	$o .= ( $o ? " | " : "" )
		. convert_chars(apply_filters('bloginfo', get_bloginfo('name'), 'name'));

	echo $o;
} # end display_page_title()

remove_action('display_page_title', 'default_page_title');
add_action('display_page_title', 'display_page_title');


#
# display_seo_meta()
#

function display_seo_meta()
{
	if ( !isset($GLOBALS['semiologic']['theme_seo'])
		|| $GLOBALS['semiologic']['theme_seo']
		)
	{
		if ( is_single() || is_page() )
		{
			$GLOBALS['posts'] = $GLOBALS['wp_query']->posts;
			$GLOBALS['post'] = $GLOBALS['wp_query']->posts[0];

			echo "<meta name=\"keywords\" content=\"";

				the_title();

				echo " - ";

				echo strip_tags(get_the_category_list(', '));

				echo "\" />\n";

			echo "<meta name=\"description\" content=\"";

				echo preg_replace("/(\n\r?){2,}/",
					"\n\n",
					htmlspecialchars(strip_tags(get_the_excerpt()), ENT_QUOTES)
					);

				echo "\" />\n";
		}
		else
		{
			echo "<meta name=\"keywords\" content=\"";

				bloginfo('name');

				echo "\" />\n";

			echo "<meta name=\"description\" content=\"";

				echo htmlspecialchars(strip_tags(get_bloginfo('description')), ENT_QUOTES);

				echo "\" />\n";
		}
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

	if ( strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false )
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
# display_translator()
#

function display_translator()
{
	if ( function_exists('create_translator_bar') )
	{
		echo '<div id="translator_bar">';
		create_translator_bar();
		echo '</div>';
	}
} # end display_translator()

add_action('before_the_wrapper', 'display_translator', 0);


#
# add_more_keywords()
#

function add_more_keywords($cats)
{
	return function_exists('get_the_post_keytags')
		? get_the_post_keytags(true)
		: $cats;
} # end add_more_keywords()

add_filter('the_category', 'add_more_keywords', -1000);


#
# display_entry_related_searches()()
#

function display_entry_related_searches()
{
	if ( function_exists('the_terms2search')
		&& apply_filters('show_entry_related_searches', is_single())
		&& get_the_post_terms()
		)
	{
		echo '<div class="entry_related_searches">'
			. '<h2>'
			. __('Related Searches')
			. '</h2>'
			. '<p>';

		the_terms2search();

		echo '</p>'
			. '</div>';
	}
} # end display_entry_related_searches()

add_filter('after_the_entry', 'display_entry_related_searches', 9);


#
# display_entry_related_tags()()
#

function display_entry_related_tags()
{
	if ( function_exists('the_terms2tags')
		&& apply_filters('show_entry_related_tags', is_single())
		&& get_the_post_terms()
		)
	{
		echo '<div class="entry_related_tags">'
			. '<h2>'
			. __('Related Tags')
			. '</h2>'
			. '<p>';

		the_terms2tags();

		echo '</p>'
			. '</div>';
	}
} # end display_entry_related_tags()

add_filter('after_the_entry', 'display_entry_related_tags', 9);


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