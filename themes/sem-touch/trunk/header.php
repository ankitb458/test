<!DOCTYPE HTML><html <?php language_attributes(); ?>>
<head>
<title><?php if ( $title = trim(wp_title('&#8211;', false)) ) : echo ( strpos($title, '&#8211;') === 0 ) ? trim(substr($title, strlen('&#8211;'))) : $title; else : bloginfo('description'); endif; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>"> 
<meta name="viewport" content="width=device-height" >
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="wrapper" class="wrapper">
<div id="wrapper_bg">

<div id="header" class="header">
<div id="header_bg">

<div id="search" class="search">
<div id="search_bg" class="pad">
<form id="sform" method="get" action="">
<input type="text" id="sfield" class="sfield" value="Search"><input type="button" id="sbutton" class="sbutton" value="Go">
</form>
</div>
</div><!-- #search -->

<div id="sitename" class="sitename">
<div id="sitename_bg" class="pad">
<?php if ( is_front_page() ) : ?><?php bloginfo('name'); ?><?php else : ?><a href="<?php bloginfo('url'); ?>/"><?php bloginfo('name'); ?></a><?php endif; ?>
</div>
</div><!-- #sitename -->

<div class="clear"></div>
</div>
</div><!-- #header -->


<div id="header_nav" class="header_nav navbar">
<div id="header_nav_bg" class="pad">

<?php wp_nav_menu(array(
	'theme_location' => 'header',
	'container' => false,
	'depth' => 1,
	)); ?>

<ul id="meta_nav" class="meta_nav">
<li><a href="<?php echo bloginfo('rss2_url'); ?>"><img src="<?php echo sem_url; ?>/icons/feed.png" alt="Subscribe to our Feed" /></a></li>
<li><a href="http://www.facebook.com/semiologic/"><img src="<?php echo sem_url; ?>/icons/facebook.png" alt="Follow us on Facebook" /></a></li>
<li><a href="http://twitter.com/ddebernardy"><img src="<?php echo sem_url; ?>/icons/twitter.png" alt="Follow us on Twitter" /></a></li>
</ul>

<div class="clear"></div>
</div>
</div><!-- #header_nav -->


<div id="body" class="body">
<div id="body_bg">
