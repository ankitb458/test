<?php


#
# get_active_layout()
#

function get_active_layout($strip_sidebars = true)
{
	if ( $strip_sidebars )
	{
		return apply_filters('active_layout', $GLOBALS['semiologic']['active_layout']);
	}
	else
	{
		if ( !isset($GLOBALS['wp_filter']['active_layout_full']) )
		{
			$filters = $GLOBALS['wp_filter']['active_layout'];

			if ( $filters )
			{
				foreach ( array_keys((array) $filters) as $key )
				{
					foreach ( array_keys((array) $filters[$key]) as $priority )
					{
						if ( $filters[$key][$priority]['function'] == 'active_sidebars' )
						{
							unset($filters[$key][$priority]);
						}
					}
				}
			}

			$GLOBALS['wp_filter']['active_layout_full'] = $filters;
		}

		return apply_filters('active_layout_full', $GLOBALS['semiologic']['active_layout']);
	}
} # end get_active_layout()


#
# get_active_width()
#

function get_active_width()
{
	return apply_filters('active_width', $GLOBALS['semiologic']['active_width']);
} # end get_active_width()


#
# strip_s()
#

function strip_s($in)
{
	return str_replace('s', '', $in);
} # end strip_s()


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
	$font_size = get_active_font_size();
	$skin = get_active_skin();

	$width_layout = $width . '_' . $layout;

	switch ( strlen($layout) )
	{
		case 1:
			$old_width = $width . 1;
			break;
		case 2:
			$old_width = $width . 2 . str_replace('m', '', $layout);
			break;
		case 3:
		default:
			$old_width = $width . 3;
			break;
	}

	$template = '';

	if ( is_page() )
	{
		$template = get_post_meta($GLOBALS['posts'][0]->ID, '_wp_page_template', true);

		$template = preg_replace("/\.[^\.]+$/", "", $template);
	}

	$header_class = header::get_class();

	$page_class = $layout
		. ' ' . $width
		. ' ' . $width_layout
		. ' ' . $old_width
		. ' ' . $font
		. ' ' . $font_size
		. ' ' . $header_class
		. ' ' . $skin
		. ' ' . $template
		. ' skin'
		. ' custom';

	$page_class = preg_replace("/\s+/", " ", $page_class);

	$page_class = preg_replace("/[^0-9a-z ]/", "_", strtolower($page_class));

	echo $page_class;
} # end display_page_class()

add_action('display_page_class', 'display_page_class');


#
# display_spacer()
#

function display_spacer()
{
?><div class="spacer"></div>
<?php
} # end display_spacer()

add_action('display_header_spacer', 'display_spacer');
add_action('display_entry_spacer', 'display_spacer');
add_action('display_body_spacer', 'display_spacer');
add_action('display_canvas_spacer', 'display_spacer');
add_action('display_navbar_spacer', 'display_spacer');
add_action('display_footer_spacer', 'display_spacer');
?>