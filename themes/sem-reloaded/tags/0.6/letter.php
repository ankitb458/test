<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# You would lose your changes when you upgrade your site. Use php widgets instead.
#


/*
Template Name: Sales Letter
*/

add_filter('active_layout', 'force_letter');
remove_action('wp_footer', array('sem_footer', 'display_credits'));

# show header
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>><head><title><?php
if ( $title = wp_title('&raquo;', false) )
{
	echo $title;
}
else
{
	bloginfo('description');
}
?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
<link rel="alternate" type="application/rss+xml" title="<?php _e('RSS feed'); ?>" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php do_action('wp_head'); ?>
</head>
<body class="<?php echo implode(' ', get_body_class(array('skin', 'custom'))); ?>">

<div id="wrapper">
<div id="wrapper_top"><div class="hidden"></div></div>
<div id="wrapper_bg">
<?php
# show header

sem_header::letter();
?>
<div class="pad">
<?php
if ( class_exists('widget_contexts') )
{
	do_action('before_the_entries');
}
?>
<div class="entry" id="entry-<?php the_ID(); ?>">
<?php
		# start loop
		the_post();
		
		# show post
		do_action('the_entry');
		
		# reset in_the_loop
		have_posts();
?>
</div>
<?php
if ( class_exists('widget_contexts') )
{
	do_action('after_the_entries');
}
?>
</div>
</div>
<div id="wrapper_bottom"><div class="hidden"></div></div>
</div><!-- wrapper -->
<?php

# show footer
do_action('wp_footer');
?>
</body>
</html>