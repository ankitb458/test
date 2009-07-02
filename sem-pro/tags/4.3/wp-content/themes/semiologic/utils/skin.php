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
# get_active_font()
#

function get_active_font()
{
	return apply_filters('active_font', $GLOBALS['semiologic']['active_font']);
} # end get_active_font()


#
# get_active_font()
#

function get_active_font_size()
{
	$active_font_size = apply_filters('active_font_size', $GLOBALS['semiologic']['active_font_size']);

	return $active_font_size ? $active_font_size : 'small';
} # end get_active_font()


#
# display_theme_css
#

function display_theme_css()
{
?><link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/style.css'; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/layout.css'; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/font.css'; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/sell.css'; ?>" />
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
?><link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/skins/' . $active_skin . '/skin.css'; ?>" />
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
		&& !( isset($_GET['action']) && $_GET['action'] == 'print' )
		&& $active_width != 'sell'
		)
	{
		if ( file_exists(TEMPLATEPATH . '/custom.css') )
		{
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/custom.css'; ?>" />
<?php
		}
		if ( file_exists(TEMPLATEPATH . '/skins/' . $active_skin . '/custom.css') )
		{
?>
<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri() . '/skins/' . $active_skin . '/custom.css'; ?>" />
<?php
		}
	}
} # end display_custom_css()

add_action('wp_head', 'display_custom_css', 10000);
?>