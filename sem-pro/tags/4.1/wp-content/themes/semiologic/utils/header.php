<?php
#
# display_header()
#

function display_header()
{
?>
<div id="header" class="header"
	<?php do_action('enhance_header_div'); ?>
	>
<div class="pad">
<?php
do_action('display_tagline');
do_action('display_sitename');
do_action('display_header_spacer');
?>
</div>
</div><!-- #header -->
<?php
} # end display_header()

add_action('display_header', 'display_header');


#
# display_navbar()
#

function display_navbar()
{
?>
<div id="navbar" class="navbar">
<div class="pad">
<?php
do_action('display_header_nav');
do_action('display_search_form');
do_action('display_navbar_spacer');
?>
</div>
</div><!-- #navbar -->
<?php
} # end display_navbar()

add_action('display_navbar', 'display_navbar');


#
# display_header_nav()
#

function display_header_nav()
{
?>
<div id="header_nav" class="inline_menu">
<?php display_nav_menu('header_nav', '|'); ?>
</div><!-- #header_nav -->
<?php
} # end display_header_nav()

add_action('display_header_nav', 'display_header_nav');


#
# display_search_form()
#

function display_search_form()
{
?>
<div id="search_form">
<form method="get" action="<?php bloginfo('url'); ?>"
	id="searchform" name="searchform">
<input type="text"
	id="s" name="s"
	value="<?php echo get_caption('search'); ?>"
	onfocus="if ( this.value == '<?php echo get_caption('search'); ?>' ) this.value = '';"
	onblur="if ( this.value == '' ) this.value = '<?php echo get_caption('search'); ?>';"
	/><input type="submit" value="<?php echo get_caption('go'); ?>" />
</form>
</div><!-- #search_form -->
<?php
} # end display_search_form()

add_action('display_search_form', 'display_search_form');


#
# display_sitename()
#

function display_sitename()
{
?>
<div id="sitename">
<h1><a href="<?php bloginfo('url'); ?>"><?php bloginfo('sitename'); ?></a></h1>
</div><!-- #sitename -->
<?php
} # end display_sitename()


#
# display_tagline()
#

function display_tagline()
{
?>
<div id="tagline">
<h2><?php bloginfo('description'); ?></h2>
</div><!-- #tagline -->
<?php
} # end display_tagline()


#
# display_image_header()
#

function display_image_header()
{
?>
<div id="sitename">
<h1><img src="<?php echo get_template_directory_uri() . '/' . image_header; ?>"
	width="<?php echo image_header_width; ?>" height="<?php echo image_header_height; ?>"
	alt="<?php bloginfo('sitename'); ?>"
	/></h1>
</div><!-- #sitename -->
<?php
} # end display_image_header()


#
# display_background_header_css()
#

function display_background_header_css()
{
?>
<style type="text/css">
.header_bg #header div.pad
{
	background-image: url(<?php echo get_template_directory_uri() . '/' . background_header; ?>);
	height: <?php echo background_header_height; ?>px;
}
</style>
<?php
} # end display_background_header_css()


#
# display_header_bg_css()
#

function display_header_bg_css()
{
?>
<style type="text/css">
.header_bg #header div.pad
{
	background-image: url(<?php echo get_template_directory_uri() . '/' . header_bg; ?>);
	height: <?php echo header_bg_height; ?>px;
}
</style>
<?php
} # end display_header_bg_css()


#
# display_flash_header()
#

function display_flash_header()
{
?>
<object width="<?php echo flash_header_width; ?>" height="<?php echo flash_header_height; ?>">
<param name="movie" value="<?php echo get_template_directory_uri() . '/' . flash_header; ?>">
<embed src="<?php echo get_template_directory_uri() . '/' . flash_header; ?>" width="<?php echo flash_header_width; ?>" height="<?php echo flash_header_height; ?>">
</embed>
</object>
<?php
} # end display_flash_header()


#
# Check for image header
#

foreach ( array('skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header.jpg',
				'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header.jpeg',
				'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header.png',
				'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header.gif',
				'header.jpg',
				'header.jpeg',
				'header.png',
				'header.gif',
				'headers/header.jpg',
				'headers/header.jpeg',
				'headers/header.png',
				'headers/header.gif'
				)
			as $image_file )
{
	if ( file_exists(TEMPLATEPATH . '/' . $image_file) )
	{
		define('image_header', $image_file);

		list($image_width, $image_height)
			= getimagesize(TEMPLATEPATH . '/' . $image_file);

		define('image_header_width', $image_width);
		define('image_header_height', $image_height);

		break;
	}
}

if ( !defined('image_header') )
{
	define('image_header', false);
}


#
# Check for image header background
#

if ( !image_header )
{
	foreach ( array('skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-background.jpg',
					'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-background.jpeg',
					'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-background.png',
					'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-background.gif',
					'header-background.jpg',
					'header-background.jpeg',
					'header-background.png',
					'header-background.gif',
					'headers/header-background.jpg',
					'headers/header-background.jpeg',
					'headers/header-background.png',
					'headers/header-background.gif'
					)
				as $image_file )
	{
		if ( file_exists(TEMPLATEPATH . '/' .$image_file) )
		{
			define('background_header', $image_file);

			list($image_width, $image_height)
				= getimagesize(TEMPLATEPATH . '/' . $image_file);

			define('background_header_width', $image_width);
			define('background_header_height', $image_height);

			break;
		}
	}
}

if ( !defined('background_header') )
{
	define('background_header', false);
}


#
# Check for flash header
#

if ( !image_header && !background_header )
{
	foreach ( array('skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header.swf',
					'header.swf',
					'headers/header.swf'
					)
				as $movie_file )
	{
		if ( file_exists(TEMPLATEPATH . '/' . $movie_file) )
		{
			define('flash_header', $movie_file);

			list( $movie_width, $movie_height )
				= getimagesize(TEMPLATEPATH . '/' . $movie_file);

			define('flash_header_width', $movie_width);
			define('flash_header_height', $movie_height);

			break;
		}
	}
}

if ( !defined('flash_header') )
{
	define('flash_header', false);
}


#
# Check for header background
#

if ( !image_header && !background_header && !flash_header )
{
	foreach ( array('skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-bg.jpg',
					'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-bg.jpeg',
					'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-bg.png',
					'skins/' . $GLOBALS['semiologic']['active_skin']['skin'] . '/' . 'header-bg.gif',
					'header-bg.jpg',
					'header-bg.jpeg',
					'header-bg.png',
					'header-bg.gif',
					'headers/header-bg.jpg',
					'headers/header-bg.jpeg',
					'headers/header-bg.png',
					'headers/header-bg.gif'
					)
				as $image_file )
	{
		if ( file_exists(TEMPLATEPATH . '/' . $image_file)
			)
		{
			define('header_bg', $image_file);

			list($image_width, $image_height)
				= getimagesize(TEMPLATEPATH . '/' . $image_file);

			define('header_bg_width', $image_width);
			define('header_bg_height', $image_height);

			break;
		}
	}

	if ( !defined('header_bg') )
	{
		if ( isset($GLOBALS['semiologic']['active_header'])
			&& $GLOBALS['semiologic']['active_header']
			&& file_exists(TEMPLATEPATH . '/headers/' . $GLOBALS['semiologic']['active_header'])
			)
		{
			define('header_bg', 'headers/' . $GLOBALS['semiologic']['active_header']);

			list($image_width, $image_height)
				= getimagesize(TEMPLATEPATH . '/headers/' . $GLOBALS['semiologic']['active_header']);

			define('header_bg_width', $image_width);
			define('header_bg_height', $image_height);
		}
	}
}

if ( !defined('header_bg') )
{
	define('header_bg', false);
}


#
# auto_remove_search_form()
#

function auto_remove_search_form()
{
	$show_search_form = true;

	$sidebars = get_option('sidebars_widgets');

	foreach ( (array) $sidebars as $sidebar )
	{
		if ( in_array('Search', (array) $sidebar)
			|| in_array('Google Search', (array) $sidebar)
			)
		{
			$show_search_form = false;
			break;
		}
	}

	if ( !apply_filters('show_search_form', $show_search_form) )
	{
		remove_action('display_search_form', 'display_search_form');
	}
} # end auto_remove_search_form()

add_action('init', 'auto_remove_search_form');


#
# enhance_header_div()
#

function enhance_header_div()
{
?>
	style="cursor: pointer;"
	onclick="top.location.href = '<?php bloginfo('url'); ?>'"
<?php
} # end enhance_header_div()

if ( !flash_header )
{
	add_action('enhance_header_div', 'enhance_header_div');
}


#
# wire_header()
#

if ( image_header )
{
	add_action('display_sitename', 'display_image_header');
	add_action('display_tagline', 'display_tagline');
}
elseif ( background_header )
{
	add_action('wp_head', 'display_background_header_css');
}
elseif ( flash_header )
{
	add_action('display_sitename', 'display_flash_header');
}
else
{
	add_action('display_sitename', 'display_sitename');
	add_action('display_tagline', 'display_tagline');
}

if ( header_bg )
{
	add_action('wp_head', 'display_header_bg_css');
}
?>