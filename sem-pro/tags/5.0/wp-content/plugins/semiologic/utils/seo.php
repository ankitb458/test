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
	global $sem_options;

	$o = "";

	if ( is_search() )
	{
		if ( $sem_s )
		{
			$o .= trim(strip_tags(stripslashes($sem_s)));
		}
		else
		{
			$o .= trim(strip_tags(stripslashes($s)));
		}
	}
	elseif ( is_tag() )
	{
		$o .= strip_tags(single_tag_title('', false));
	}
	elseif ( is_category() && !is_home() )
	{
		$o .= strip_tags(single_cat_title('', false));
	}
	elseif ( is_archive() )
	{
		$o .= __('Archives');
	}
	elseif ( is_singular() )
	{
		if ( $title = get_post_meta($posts[0]->ID, '_title', true) )
		{
			$o .= strip_tags($title);
		}
		elseif ( is_page() && is_home() && $sem_options['seo']['title'] )
		{
			$o .= strip_tags($sem_options['seo']['title']);
		}
		else
		{
			$o .= strip_tags(apply_filters('single_post_title', $posts[0]->post_title));
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
	elseif ( $sem_options['seo']['title'] )
	{
		$o .= strip_tags($sem_options['seo']['title']);
	}
	else
	{
		$o .= strip_tags(get_option('blogdescription'));
	}

	if ( $sem_options['seo']['add_site_name'] )
	{
		$o .= ( $o ? " | " : "" )
			. strip_tags(get_option('blogname'));
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
	global $sem_options;

	if ( is_singular() )
	{
		$GLOBALS['posts'] = $GLOBALS['wp_query']->posts;
		$GLOBALS['post'] = $GLOBALS['wp_query']->posts[0];

		echo "\n"
			. "<meta name=\"keywords\" content=\"";

		if ( $keywords = get_post_meta($GLOBALS['post']->ID, '_keywords', true) )
		{
			echo htmlspecialchars($keywords);
		}
		elseif ( is_page() && is_home() && $sem_options['seo']['keywords'] )
		{
			echo htmlspecialchars($sem_options['seo']['keywords']);
		}
		else
		{
			the_title();

			$keywords = array();

			if ( is_single() && ( $cats = get_the_category($GLOBALS['post']->ID) ) )
			{
				foreach ( $cats as $cat )
				{
					$keywords[] = $cat->name;
				}
			}

			if ( $tags = get_the_tags($GLOBALS['post']->ID) )
			{
				foreach ( $tags as $tag )
				{
					$keywords[] = $tag->name;
				}
			}

			foreach ( array_unique($keywords) as $keyword )
			{
				echo ', ' . htmlspecialchars($keyword);
			}
		}

		echo "\" />\n";

		if ( $description = get_post_meta($GLOBALS['post']->ID, '_description', true) )
		{
			echo "<meta name=\"description\" content=\"";
			echo htmlspecialchars($description);
			echo "\" />\n";
		}
		elseif ( is_page() && is_home() && $sem_options['seo']['description'] )
		{
			echo "<meta name=\"description\" content=\"";
			echo htmlspecialchars($sem_options['seo']['description']);
			echo "\" />\n";
		}
	}
	elseif ( is_tag() )
	{
		echo "\n"
			. "<meta name=\"keywords\" content=\"";

			echo htmlspecialchars(single_tag_title('', false));

			echo "\" />\n";

		echo "<meta name=\"description\" content=\"";

		if ( $sem_options['seo']['description'] )
		{
			echo htmlspecialchars($sem_options['seo']['description']);
		}
		else
		{
			echo htmlspecialchars(strip_tags(get_bloginfo('description')));
		}

		echo "\" />\n";
	}
	elseif ( is_category() && !is_home() )
	{
		echo "\n"
			. "<meta name=\"keywords\" content=\"";

			echo htmlspecialchars(single_cat_title('', false));

			echo "\" />\n";

		echo "<meta name=\"description\" content=\"";

			if ( $description = category_description() )
			{
				echo htmlspecialchars(trim(strip_tags($description)));
			}
			elseif ( $sem_options['seo']['description'] )
			{
				echo htmlspecialchars($sem_options['seo']['description']);
			}
			else
			{
				echo htmlspecialchars(strip_tags(get_bloginfo('description')));
			}

			echo "\" />\n";
	}
	else
	{
		echo "\n"
			. "<meta name=\"keywords\" content=\"";

		if ( $sem_options['seo']['keywords'] )
		{
			echo htmlspecialchars($sem_options['seo']['keywords']);
		}
		else
		{
			echo bloginfo('name');
		}

		echo "\" />\n";

		echo "<meta name=\"description\" content=\"";

		if ( $sem_options['seo']['description'] )
		{
			echo htmlspecialchars($sem_options['seo']['description']);
		}
		else
		{
			echo htmlspecialchars(strip_tags(get_bloginfo('description')));
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

add_action('display_sidebar2', 'start_ad_ignore_section', -1000);
add_action('display_sidebar2', 'end_ad_section', 1000);

add_action('display_ext_sidebar', 'start_ad_ignore_section', -1000);
add_action('display_ext_sidebar', 'end_ad_section', 1000);

add_action('before_the_entries', 'start_ad_section');
add_action('after_the_entries', 'end_ad_section');
?>