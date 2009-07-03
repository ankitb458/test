<?php
#
# get_nav_menu()
#

function get_nav_menu($id)
{
	return isset($GLOBALS['semiologic']['nav_menu_cache'][$id])
		? $GLOBALS['semiologic']['nav_menu_cache'][$id]
		: array();
} # end get_nav_menu()


#
# display_nav_menu()
#

function display_nav_menu($menu_id, $sep = '')
{
	$nav_menu = get_nav_menu($menu_id);

	if ( $nav_menu )
	{
		echo '<ul>';

		foreach ( $nav_menu as $name => $url )
		{
			if ( $sep && $i++ )
			{
				echo '<li>' . $sep . '</li>';
			}

			echo '<li>'
				. '<a href="' . $url . '">'
				. $name
				. '</a>'
				. '</li>';
		}

		echo '</ul>';
	}
} # end display_nav_menu()


#
# auto remove navbar if header_nav is empty
#

function auto_remove_navbar()
{
	$header_nav = get_nav_menu('header_nav');
	$show_navbar = !empty($header_nav);

	if ( !apply_filters('show_navbar', $show_navbar) )
	{
		remove_action('display_search_form', 'display_search_form');
		remove_action('wp_head', 'hide_search_form');
		add_action('wp_head', 'hide_navbar');
	}
} # end auto_remove_navbar()

function hide_navbar()
{
?>
<style>
#navbar {display: none;}
</style>
<?php
} # end hide_header_nav()

add_action('init', 'auto_remove_navbar');


#
# widgetize sidebar nav
#

if ( function_exists('register_sidebar_widget') )
{
	register_sidebar_widget('Sidebar Nav', 'display_sidebar_nav_widget');
	register_widget_control('Sidebar Nav', 'sidebar_nav_widget_control');
}


#
# display_sidebar_nav_widget()
#

function display_sidebar_nav_widget($args)
{
	$options = get_settings('sidebar_nav_widget_params');

	echo $args['before_widget']
		. ( $options['title']
			? ( $args['before_title']
				. $options['title']
				. $args['after_title']
				)
			: ''
			);

	display_nav_menu('sidebar_nav');

	echo $args['after_widget'];
} # end display_widget()
?>