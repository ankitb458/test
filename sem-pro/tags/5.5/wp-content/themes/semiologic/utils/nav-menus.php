<?php
class sem_nav
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('sem_nav', 'widgetize'));
	} # init()


	#
	# get_areas()
	#

	function get_areas()
	{
		return array(
			'header' => 'Header',
			'footer' => 'Footer',
			);
	} # get_areas()


	#
	# widgetize()
	#

	function widgetize()
	{
		foreach ( sem_nav::get_areas() as $area => $label )
		{
			register_sidebar_widget(
				$label . ': Nav Menu',
				create_function('$args', 'sem_nav::widget(\'' . $area . '\', $args);'),
				$area . '_nav_widget'
				);
			register_widget_control(
				$label . ': Nav Menu',
				create_function('', 'sem_nav_admin::widget_control(\'' . $area . '\');'),
				450,
				300
				);
		}
	} # widgetize()


	#
	# widget()
	#

	function widget($area, $args)
	{
		global $sem_options;

		switch ( $area )
		{
		case 'header':
			if ( $GLOBALS['the_header'] )
			{
				if ( $sem_options['show_search_form'] )
				{
					add_action('display_search_form', 'display_search_form');
				}

				do_action('display_navbar');
			}
			else
			{
				echo $args['before_widget']
					. __('The Header Nav Menu widget will only work in the header area.')
					. $args['after_widget'];
			}
			break;

		case 'footer':
			if ( $GLOBALS['the_footer'] )
			{
				if ( $sem_options['show_copyright'] )
				{
					add_action('display_copyright_notice', 'display_copyright_notice');
				}

				do_action('display_copyright_notice');
				do_action('display_footer_nav');
			}
			else
			{
				echo $args['before_widget']
					. __('The Footer Nav Menu widget will only work in the header area.')
					. $args['after_widget'];
			}
			break;
		}
	} # widget()
} # sem_nav

sem_nav::init();


#
# display_nav_menu()
#

function display_nav_menu($menu_id, $sep = '')
{
	global $sem_nav_cache;

	$nav_menu = $sem_nav_cache[$menu_id];

	if ( $nav_menu )
	{
		echo '<div>';

		foreach ( $nav_menu as $name => $url )
		{
			if ( $sep && $i++ )
			{
				echo '<span>' . $sep . '</span>';
			}

			$class = sanitize_title($name);
			$class = preg_replace("/[_-]+/", "_", $class);

			echo '<span class="' . $class . '">'
				. '<a href="' . $url . '">'
				. $name
				. '</a>'
				. '</span>';
		}

		echo '</div>';
	}
} # end display_nav_menu()




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
	global $sem_nav_cache;
	global $sem_captions;

	if ( $sem_nav_cache['sidebar_nav'] )
	{
		echo $args['before_widget']
			. ( $sem_captions['sidebar_nav_title']
				? ( $args['before_title']
					. $sem_captions['sidebar_nav_title']
					. $args['after_title']
					)
				: ''
				);

		display_nav_menu('sidebar_nav');

		echo $args['after_widget'];
	}
} # end display_sidebar_nav_widget()
?>