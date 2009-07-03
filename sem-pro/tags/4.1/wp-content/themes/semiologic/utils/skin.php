<?php
#
# get_active_skin()
#

function get_active_skin()
{
	return apply_filters('active_skin', $GLOBALS['semiologic']['active_skin']['skin']);
} # end get_active_skin()


#
# get_skin_credits()
#

function get_skin_credits()
{
	$name = $GLOBALS['semiologic']['active_skin']['name'];
	$author = $GLOBALS['semiologic']['active_skin']['author'];
	$author_uri = $GLOBALS['semiologic']['active_skin']['author_uri'];

	return str_replace(
		array('%name%', '%author%', '%author_uri%'),
		array($name, $author, $author_uri),
		__('%name% skin by <a href="%author_uri%">%author%</a>')
		);
} # end get_skin_credits()


#
# get_active_layout()
#

function get_active_layout()
{
	return apply_filters('active_layout', $GLOBALS['semiologic']['active_layout']);
} # end get_active_layout()


#
# get_active_width()
#

function get_active_width()
{
	return apply_filters('active_width', $GLOBALS['semiologic']['active_width']);
} # end get_active_width()


#
# get_active_font()
#

function get_active_font()
{
	return apply_filters('active_font', $GLOBALS['semiologic']['active_font']);
} # end get_active_font()


#
# display_theme_css
#

function display_theme_css()
{
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/style.css'; ?>" />
<?php
} # end display_theme_css()

add_action('wp_head', 'display_theme_css', 20);


#
# display_skin_css()
#

function display_skin_css()
{
	$active_skin = get_active_skin();
	$active_width = get_active_width();

	if ( $active_skin
		&& !( isset($_GET['action']) && $_GET['action'] == 'print' )
		&& $active_width != 'sell'
		)
	{
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/skins/' . $active_skin . '/skin.css'; ?>" />
<?php
	}
} # end display_skin_css()

add_action('wp_head', 'display_skin_css', 25);


#
# display_custom_css
#

function display_custom_css()
{
	$active_skin = get_active_skin();

	if ( $active_skin
		&& file_exists(TEMPLATEPATH . '/skins/' . $active_skin . '/custom.css')
		&& !( isset($_GET['action']) && $_GET['action'] == 'print' )
		&& $active_width != 'sell'
		)
	{
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/skins/' . $active_skin . '/custom.css'; ?>" />
<?php
	}
	elseif ( file_exists(TEMPLATEPATH . '/custom.css') )
	{
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/custom.css'; ?>" />
<?php
	}
} # end display_custom_css()

add_action('wp_head', 'display_custom_css', 30);


#
# force_m()
#

function force_m($in)
{
	return 'm';
} # end force_m()


#
# force_narrow()
#

function force_narrow($in)
{
	return 'narrow';
} # end force_narrow()


#
# display_page_class()
#

function display_page_class()
{
	$layout = get_active_layout();
	$width = get_active_width();
	$font = get_active_font();
	$skin = get_active_skin();

	if ( $width != 'sell' )
	{
		switch ( strlen($layout) )
		{
			case 1:
				$width = $width . 1;
				break;
			case 2:
				$width = $width . 2 . str_replace('m', '', $layout);
				break;
			case 3:
			default:
				$width = $width . 3;
				break;
		}
	}

	$header_bg = ( header_bg || background_header )
		? 'header_bg'
		: '';

	$header_img = ( image_header || flash_header )
		? 'header_img'
		: '';

	$page_class = $layout
		. ' ' . $width
		. ' ' . $font
		. ' ' . $header_img
		. ' ' . $header_bg
		. ' ' . $skin
		. ' skin'
		. ' custom';

	$page_class = preg_replace("/\s+/", " ", $page_class);

	$page_class = preg_replace("/[^0-9a-z ]/", "_", strtolower($page_class));

	echo $page_class;
} # end display_page_class()

add_action('display_page_class', 'display_page_class');


#
# get_active_header()
#

function get_active_header()
{
	return $GLOBALS['semiologic']['active_header'];
} # end get_active_header()


#
# display_spacer()
#

function display_spacer()
{
?>
<div class="spacer"></div>
<?php
} # end display_spacer()

add_action('display_entry_spacer', 'display_spacer');
add_action('display_body_spacer', 'display_spacer');
add_action('display_canvas_spacer', 'display_spacer');
add_action('display_navbar_spacer', 'display_spacer');
add_action('display_footer_spacer', 'display_spacer');
?>