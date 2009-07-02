<?php
#
# remove_nextlink_junk()
#

function remove_nextlink_junk($content)
{
	return preg_replace(
		"~
			<p>\s*&lt;\s*</p>
			\s*
			<p>p>
		~isx",
		"<p>",
		$content
		);
} # end remove_nextlink_junk()

add_filter('the_content', 'remove_nextlink_junk', 900);


#
# remove_wysiwyg_junk()
#

function remove_wysiwyg_junk($content)
{
	$content = preg_replace(
		"~
			<\s*(?:p|div|noscript)	# p, div or noscript tag
			(?:\s[^>]*)?			# optional attributes
			/\s*>					# />
		~ix",
		"",
		$content
		);

	$content = preg_replace(
		"~
			<p></p>					# empty paragraph
		~ix",
		"",
		$content
		);

	return $content;
} # end remove_wysiwyg_junk()

add_filter('the_content', 'remove_wysiwyg_junk', 1000);


#
# fix_pagenav_align()
#

function fix_wysiwyg_align($content)
{
	$content = preg_replace("~
		<p>&lt;</p>
		\s*
		<p>div
		\s+
		align=(?:&\#034;|&\#8221;)right(?:&\#034;|&\#8221;)>
		\s*
		(.*)
		\s*
		</p>
		~isx",
		"<p style=\"text-align: right;\">$1</p>",
		$content
		);

	$content = preg_replace("~
		<div\s+align=\"right\">(<!--(?:more|nextpage)-->)</div>
		~isx",
		"<p style=\"text-align: right;\">$1</p>",
		$content
		);

	return $content;
} # fix_wysiwyg_align()

add_filter('the_content', 'fix_wysiwyg_align');
add_filter('the_excerpt', 'fix_wysiwyg_align');


#
# kill_template_host()
#

function kill_template_host($dir)
{
	$dir = preg_replace("/^https?:\/\/" . $_SERVER['HTTP_HOST'] . "/", "", $dir);

	return $dir;
} # end kill_host()

add_filter('template_directory_uri', 'kill_template_host');


#
# fix_br()
#

function fix_br($buffer)
{
	return preg_replace("/<br\s*\/?>/i", "<br />", $buffer);
} # end fix_br()


#
# start_fix_br()
#

function start_fix_br()
{
	ob_start('fix_br');
} # end start_fix_br()

add_action('template_redirect', 'start_fix_br', -10000);


#
# prev_next_page_link()
#

function prev_next_page_link()
{
	global $sem_captions;

	echo '<div class="prev_next_page">';
	posts_nav_link(
		' &bull; ',
		'&laquo;&nbsp;' . $sem_captions['prev_page'],
		$sem_captions['next_page'] . '&nbsp;&raquo;'
		);
	echo '</div>';
} # prev_next_page_link()


#
# sem_postnav_widget()
#

function sem_postnav_widget($args)
{
	if ( !is_single() && !is_page() && !is_404() && !$GLOBALS['disable_next_prev_page_link'] )
	{
		echo $args['before_widget'];
		prev_next_page_link();
		echo $args['after_widget'];
	}
} # sem_postnav_widget()


#
# sem_postnav_widgetize()
#

function sem_postnav_widgetize()
{
	register_sidebar_widget(
		'Next/Prev Posts',
		'sem_postnav_widget',
		'sem_postnav_widget'
		);
	register_widget_control(
		'Next/Prev Posts',
		'sem_postnav_widget_control',
		450,
		300
		);
} # sem_postnav_widgetize()

add_action('widgets_init', 'sem_postnav_widgetize');


#
# disable_next_prev_page_link()
#

function disable_next_prev_page_link($data)
{
	$GLOBALS['disable_next_prev_page_link'] = true;

	return $data;
} # disable_next_prev_page_link()

add_action('get_books', 'disable_next_prev_page_link');
add_action('get_single_book', 'disable_next_prev_page_link');


remove_filter('pre_category_description', 'wp_filter_kses');
add_filter('pre_category_description', 'wp_filter_post_kses');



#
# enable_easy_auctionads()
#

function enable_easy_auctionads()
{
	if ( function_exists('wp_easy_auctionads_start') )
	{
		add_action('before_the_wrapper', 'wp_easy_auctionads_start');
		add_action('after_the_wrapper', 'wp_easy_auctionads_end');
	}
} # enable_easy_auctionads()

add_action('init', 'enable_easy_auctionads');


if ( function_exists('wp_cache_force_update') )
{
	foreach ( array(
				'sem5_options',
				'sem5_captions',
				'sem5_nav'
				) as $o )
	{
		add_action('update_option_' . $o, 'wp_cache_force_update');
	}
}

?>