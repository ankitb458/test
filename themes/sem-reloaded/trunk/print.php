<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# You would lose your changes when you upgrade your site. Use php widgets instead.
#

add_filter('option_blog_public', create_function('$in', 'return "0";'));
remove_action('wp_footer', array('sem_footer', 'display_credits'));

# show header
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>><head><title><?php
if ( $title = trim(wp_title('&rarr;', false)) ) {
	if ( strpos($title, '&rarr;') === 0 )
		$title = trim(substr($title, strlen('&rarr;'), strlen($title)));
	echo $title;
} else {
	bloginfo('description');
}
?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
<link rel="alternate" type="application/rss+xml" title="<?php _e('RSS feed'); ?>" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php do_action('wp_head'); ?>
</head>
<body class="<?php echo implode(' ', get_body_class(array('skin', 'custom'))); ?>">
<?php
do_action('before_the_entries');

# show posts
if ( have_posts() )
{
	while ( have_posts() )
	{
		the_post();

?>
<div class="entry" id="entry-<?php the_ID(); ?>">
<?php
		do_action('the_entry');
?>
</div>
<?php
	}

}
# or fallback
elseif ( is_404() )
{
	do_action('404_error');
}

do_action('after_the_entries');

# show footer
do_action('wp_footer');
?>
</body>
</html>