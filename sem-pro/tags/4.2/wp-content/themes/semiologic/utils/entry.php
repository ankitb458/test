<?php
#
# display_entry_header()
#

function display_entry_header()
{
?>	<div class="entry_header">
<?php
		do_action('display_entry_date');
		do_action('display_entry_title');
		do_action('display_entry_title_meta');
?>	</div>
<?php
} # end display_entry_header()

add_action('display_entry_header', 'display_entry_header');


#
# display_entry_date()
#

function display_entry_date()
{
	$show_entry_date = !is_page()
		&& !( is_home()
			&& defined('sem_home_page_id')
			&& sem_home_page_id
			);

	$show_entry_date = apply_filters('show_entry_date', $show_entry_date);

	if ( $show_entry_date )
	{
		the_date('', '<h2>', '</h2>');
	}
} # end display_entry_date()

add_action('display_entry_date', 'display_entry_date');


#
# display_entry_title()
#

function display_entry_title()
{
	$show_entry_title_link = !( is_single() || is_page() )
		&& !( is_home()
			&& defined('sem_home_page_id')
			&& sem_home_page_id
			);

	$show_entry_title_link = apply_filters('show_entry_title_link', $show_entry_title_link);

	echo '<h1>';

	if ( $show_entry_title_link )
	{
		echo '<a href="';
		the_permalink();
		echo '">';
		the_title();
		echo '</a>';
	}
	else
	{
		the_title();
	}

	edit_post_link(get_caption('edit'), ' <span class="admin_link">&bull;&nbsp;', '</span>');

	echo '</h1>';
} # end display_entry_title()

add_action('display_entry_title', 'display_entry_title');


#
# display_entry_by_on()
#

function display_entry_by_on()
{
	if ( apply_filters('show_entry_by_on', false) )
	{
		echo '<div class="entry_author">';

			echo get_caption('by') . ' ';

			if ( trim(get_the_author_url()) != 'http://' )
			{
				echo '<a href="';
				the_author_url();
				echo '">';
				the_author();
				echo '</a>';
			}
			else
			{
				the_author();
			}

		echo '</div>';

		echo '<div class="entry_date">';

			the_time(get_settings('date_format'));

		echo '</div>';
	}
} # end display_entry_by_on()

add_action('display_entry_title_meta', 'display_entry_by_on');


#
# display_entry_body()
#

function display_entry_body()
{
	echo '<div class="entry_body">';
	the_content(get_caption('more'));
	echo '</div>';
} # end display_entry_body()

add_action('display_entry_body', 'display_entry_body');


#
# display_entry_nav()
#

function display_entry_nav()
{
	link_pages('<div class="entry_nav"> ' . get_caption('page') . ': ', '</div>', 'number');
} # end display_entry_nav()

add_action('display_entry_body', 'display_entry_nav', 100);


#
# display_entry_filed_under_by()
#

function display_entry_filed_under_by()
{
	$show_entry_filed_under_by = !is_page()
		&& !( is_home()
			&& defined('sem_home_page_id')
			&& sem_home_page_id
			);

	$show_entry_filed_under_by = apply_filters('show_entry_filed_under_by', $show_entry_filed_under_by);

	if ( $show_entry_filed_under_by )
	{
		echo '<div class="entry_meta">';

		echo '<p>';

		echo get_caption('filed_under') . ' '
			. '<span class="entry_tags">';

		the_category(', ');

		echo '</span>'
			. ' ' . strtolower(get_caption('by')) . ' ';

		echo '<span class="entry_author">';

		if ( trim(get_the_author_url()) != 'http://' )
		{
			echo '<a href="';
			the_author_url();
			echo '">';
			the_author();
			echo '</a>';
		}
		else
		{
			the_author();
		}

		echo '</span>';

		echo '</p>';

		echo '</div>';
	}
} # end display_entry_filed_under_by()

add_action('display_entry_meta', 'display_entry_filed_under_by');


#
# display_entry_filed_under()
#

function display_entry_filed_under()
{
	$show_entry_filed_under_by = !is_page()
		&& !( is_home()
			&& defined('sem_home_page_id')
			&& sem_home_page_id
			);

	$show_entry_filed_under_by = apply_filters('show_entry_filed_under_by', $show_entry_filed_under_by);

	if ( $show_entry_filed_under_by )
	{
		echo '<div class="entry_meta">';

		echo '<p>';

		echo get_caption('filed_under') . ' '
			. '<span class="entry_tags">';

		the_category(', ');

		echo '</span>';

		echo '</p>';

		echo '</div>';
	}
} # end display_entry_filed_under()


#
# get_email_link()
#

function get_email_link($url = null, $subject = null)
{
	$url = isset($url) ? $url : apply_filters('the_permalink', get_permalink());
	$subject = isset($subject) ? $subject : the_title(null, null, false);

	return 'mailto:'
		. '?'
		. 'subject=' . $subject
		. '&amp;'
		. 'body=' . rawurlencode(str_replace('&amp;', '&', $url));
} # end get_email_link()


#
# get_print_link()
#

function get_print_link($url = null)
{
	$url = isset($url) ? $url : apply_filters('the_permalink', get_permalink());

	return $url
		. ( ( strpos($url, '?') === false )
			? '?'
			: '&amp;'
			)
		. 'action=print';
} # end get_print_link()


#
# display_entry_actions()
#

function display_entry_actions()
{
	$show_entry_actions = apply_filters('show_entry_actions', true);

	$num_comments = get_comments_number();

	if ( $show_entry_actions )
	{
?><div class="entry_actions">
	<span class="action link_entry"><a href="<?php the_permalink(); ?>"><?php echo get_caption('permalink'); ?></a></span>
	<span class="action print_entry">&bull;&nbsp;<a href="<?php echo get_print_link(); ?>"><?php echo get_caption('print'); ?></a></span>
	<span class="action email_entry">&bull;&nbsp;<a href="<?php echo get_email_link(); ?>"><?php echo get_caption('email'); ?></a></span>
<?php
		if ( $num_comments && ( is_single() || is_page() ) && comments_open() )
		{
?>	<span class="action comment_entry">&bull;&nbsp;<a href="<?php the_permalink(); ?>#postcomment"><?php echo get_caption('comment'); ?></a></span>
<?php
		}
		elseif ( $num_comments && !( is_single() || is_page() ) )
		{
?>	<span class="action entry_comments">&bull;&nbsp;<a href="<?php the_permalink(); ?>#comments"><?php comments_number(get_caption('no_comment'), get_caption('1_comment'), get_caption('n_comments')) ?></a></span>
<?php
		}
		elseif ( !$num_comments && comments_open()
			&& !( is_home()
				&& defined('sem_home_page_id')
				&& sem_home_page_id
				)
			)
		{
?>	<span class="action comment_entry">&bull;&nbsp;<a href="<?php the_permalink(); ?>#postcomment"><?php echo get_caption('comment'); ?></a></span>
<?php
		}

		edit_post_link(get_caption('edit'), ' <span class="action admin_link">&bull;&nbsp;', '</span>');
?></div>
<?php
	}
} # end display_entry_actions()

add_action('display_entry_actions', 'display_entry_actions');


#
# display_entry_actions2()
#

function display_entry_actions2()
{
	$show_entry_actions = apply_filters('show_entry_actions', true);

	$num_comments = get_comments_number();

	if ( $show_entry_actions )
	{
?><div class="entry_actions">
	<span class="action print_entry"><a href="<?php echo get_print_link(); ?>"><?php echo get_caption('print'); ?></a></span>
	<span class="action email_entry">&bull;&nbsp;<a href="<?php echo get_email_link(); ?>"><?php echo get_caption('email'); ?></a></span>
<?php
		if ( $num_comments && ( is_single() || is_page() ) )
		{
?>	<span class="action entry_comments">&bull;&nbsp;<a href="<?php the_permalink(); ?>#comments"><?php comments_number(get_caption('no_comment'), get_caption('1_comment'), get_caption('n_comments')) ?></a></span>
<?php
			if ( comments_open() )
			{
?>	<span class="action comment_entry">&bull;&nbsp;<a href="<?php the_permalink(); ?>#postcomment"><?php echo get_caption('comment'); ?></a></span>
<?php
			}
		}
		elseif ( $num_comments && !( is_single() || is_page() ) )
		{
?>	<span class="action entry_comments">&bull;&nbsp;<a href="<?php the_permalink(); ?>#comments"><?php comments_number(get_caption('no_comment'), get_caption('1_comment'), get_caption('n_comments')) ?></a></span>
<?php
		}
		elseif ( !$num_comments && comments_open()
			&& !( is_home()
				&& defined('sem_home_page_id')
				&& sem_home_page_id
				)
			)
		{
?>	<span class="action comment_entry">&bull;&nbsp;<a href="<?php the_permalink(); ?>#postcomment"><?php echo get_caption('comment'); ?></a></span>
<?php
		}

		edit_post_link(get_caption('edit'), ' <span class="action admin_link">&bull;&nbsp;', '</span>');
?></div>
<?php
	}
} # end display_entry_actions2()


#
# display_entry_trackback_uri()
#

function display_entry_trackback_uri()
{
	$show_entry_trackback_uri = pings_open() && is_single();

	$show_entry_trackback_uri = apply_filters('show_entry_trackback_uri', $show_entry_trackback_uri);

	if ( $show_entry_trackback_uri )
	{
?><div class="entry_trackback_uri">
<!--
<?php trackback_rdf(); ?>-->
<h2><?php echo get_caption('trackback_uri'); ?></h2>
<p><a href="<?php trackback_url(); ?>" rel="trackback nofollow"><?php trackback_url(); ?></a></p>
</div>
<?php
	}
} # end display_entry_trackback_uri()

add_action('after_the_entry', 'display_entry_trackback_uri', 5);


#
# display_entry_follow_ups()
#

function display_entry_follow_ups()
{
	$show_entry_follow_ups = is_single();

	if ( ( function_exists('the_blogpulse_link') || function_exists('the_cosmos_link') )
		&& apply_filters('show_entry_follow_ups', $show_entry_follow_ups)
		)
	{
?><div class="entry_follow_ups">
<h2><?php echo get_caption('track_this_entry'); ?></h2>
<?php

if ( function_exists('the_blogpulse_link') ) :
?><p><a href="<?php the_blogpulse_feed(); ?>" style="border: none; background-image: none; padding: 0px;"><img src="<?php echo get_stylesheet_directory_uri() . '/img/rss.png'; ?>" alt="<?php echo __('RSS'); ?>" width="14" height="14" align="absmiddle" /></a>
	<a href="<?php the_blogpulse_link(); ?>"><?php echo __('BlogPulse'); ?></a></p>
<?php
endif;

if ( function_exists('the_cosmos_link') ) :
?><p><a href="<?php the_cosmos_feed(); ?>" style="border: none; background-image: none; padding: 0px;"><img src="<?php echo get_stylesheet_directory_uri() . '/img/rss.png'; ?>" alt="<?php echo __('RSS'); ?>" width="14" height="14" align="absmiddle" /></a>
	<a href="<?php the_cosmos_link(); ?>"><?php echo __('Technorati Cosmos'); ?></a></p>
<?php
endif;

?></div>
<?php
	}
} # end display_entry_follow_ups()

add_action('after_the_entry', 'display_entry_follow_ups', 6);


#
# display_entry_related_entries()
#

function display_entry_related_entries()
{
	$show_entry_related_entries = is_single();

	if ( function_exists('the_terms2posts')
		&& apply_filters('show_entry_related_entries', $show_entry_related_entries)
		&& get_the_post_terms()
		)
	{
		echo '<div class="entry_related_entries">'
			. '<h2>'
			. get_caption('related_entries')
			. '</h2>'
			. '<ul>';

		the_terms2posts();

		echo '</ul>'
			. '</div>';
	}
} # end display_entry_related_entries()

add_action('after_the_entry', 'display_entry_related_entries', 8);


#
# display_404()
#

function display_404()
{
	do_action('before_the_entry');
	echo get_caption('no_entries_found');
	do_action('after_the_entry');
} # end display_404()

add_action('display_404', 'display_404');


#
# display_entry_author_image
#

function display_entry_author_image()
{
	$show_author_image = !is_page() && !is_search();

	if ( function_exists('the_author_image')
		&& apply_filters('show_entry_author_image', $show_author_image) )
	{
		the_author_image();
	}
} # end display_author_image()

add_action('display_entry_title_meta', 'display_entry_author_image', 5);
?>