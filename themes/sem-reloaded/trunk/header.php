<?php
#
# DO NOT EDIT THIS FILE
# ---------------------
# You would lose your changes when you upgrade your site. Use php widgets instead.
#

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>><head><title><?php
if ( $title = trim(wp_title('&#8211;', false)) ) {
	if ( strpos($title, '&#8211;') === 0 )
		$title = trim(substr($title, strlen('&#8211;')));
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
# canvas

do_action('before_the_canvas');

# wrapper

echo '<div id="wrapper">' . "\n";

echo '<div id="wrapper_top"><div class="hidden"></div></div>' . "\n";

echo '<div id="wrapper_bg">' . "\n";

	
	# header
	
	if ( $active_layout != 'letter') :
		
		echo '<div id="header_wrapper" class="wrapper">' . "\n";
		
		sem_panels::display('the_header');
		
		echo '<div id="body_split"><div class="hidden"></div></div>' . "\n";

		echo '</div>' . "\n";
		
	endif;

	
	# body
	
	echo '<div id="body_wrapper" class="wrapper">' . "\n";
	
	echo '<div id="body" class="wrapper">' . "\n";
	
	echo '<div id="body_top"><div class="hidden"></div></div>' . "\n";
	
	echo '<div id="body_bg">' . "\n";
	
	echo '<div class="wrapper_item">' . "\n";
	
		
		switch ( $active_layout) :
		
		case 'sms':

			# sidebar wrapper for sms layout
		
			echo '<div id="sidebar_wrapper">' . "\n";
			
			break;

		endswitch;

			
		# content
		
		echo '<div id="main" class="main">' . "\n";

		echo '<div id="main_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="main_bg">' . "\n";
		
		echo '<div class="pad">' . "\n";
?>