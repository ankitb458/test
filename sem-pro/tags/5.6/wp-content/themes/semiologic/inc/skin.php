<?php
#
# get_active_skin()
#

function get_active_skin()
{
	global $sem_options;

	return apply_filters('active_skin', $sem_options['active_skin']);
} # end get_active_skin()


#
# get_active_font()
#

function get_active_font()
{
	global $sem_options;

	return apply_filters('active_font', $sem_options['active_font']);
} # end get_active_font()


#
# get_active_font()
#

function get_active_font_size()
{
	global $sem_options;

	$active_font_size = apply_filters('active_font_size', $sem_options['active_font_size']);

	return $active_font_size ? $active_font_size : 'small';
} # end get_active_font()


#
# display_theme_css
#

function display_theme_css()
{
	foreach ( array(
		'/style.css',
		'/font.css',
		'/css/layout.css',
		) as $file )
	{
		echo '<link rel="stylesheet" type="text/css" href="' . sem_url . $file . '?ver=' . rawurlencode(sem_version) . '" />' . "\n";
	}
} # end display_theme_css()

add_action('wp_head', 'display_theme_css', 20);


#
# display_skin_css()
#

function display_skin_css()
{
	if ( $_GET['action'] == 'print' )
	{
		$file = '/css/print.css';
	}
	elseif ( get_active_width() == 'letter' )
	{
		$file = '/css/letter.css';
	}
	else
	{
		$file = '/skins/' . get_active_skin() . '/skin.css';
	}

	echo '<link rel="stylesheet" type="text/css" href="' . sem_url . $file . '?ver=' . rawurlencode(sem_version) . '" />' . "\n";
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
		if ( file_exists(sem_path . '/custom.css') )
		{
?>
<link rel="stylesheet" type="text/css" href="<?php echo sem_url . '/custom.css'; ?>" />
<?php
		}
		if ( file_exists(sem_path . '/skins/' . $active_skin . '/custom.css') )
		{
?>
<link rel="stylesheet" type="text/css" href="<?php echo sem_url . '/skins/' . $active_skin . '/custom.css'; ?>" />
<?php
		}
	}
} # end display_custom_css()

add_action('wp_head', 'display_custom_css', 10000);


#
# display_theme_icon_css()
#

function display_theme_icon_css()
{
	echo '<link rel="stylesheet" type="text/css" href="' . sem_url . '/icons/icons.css?ver=' . rawurlencode(sem_version) . '" />' . "\n";
} # display_theme_icon_css()

add_action('wp_head', 'display_theme_icon_css', 20);
?>