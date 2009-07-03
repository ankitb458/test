<?php
#
# display_sidebar()
#

function display_sidebar()
{
	$active_layout = get_active_layout();

	if ( strpos($active_layout, 's') !== false )
	{
		include_once TEMPLATEPATH . '/sidebar.php';
	}
} # end display_sidebar()

add_action('display_sidebar', 'display_sidebar');


#
# display_ext_sidebar()
#

function display_ext_sidebar()
{
	$active_layout = get_active_layout();

	if ( strpos($active_layout, 'e') !== false )
	{
		include_once TEMPLATEPATH . '/sidebar-ext.php';
	}
} # end display_ext_sidebar()

add_action('display_ext_sidebar', 'display_ext_sidebar');


#
# active_sidebars()
#

function active_sidebars($active_layout = 'mse')
{
	if ( function_exists('dynamic_sidebar') )
	{
		$new_layout = $active_layout;

		$sidebars = get_option('sidebars_widgets');

		if ( !isset($sidebars['sidebar-1']) || empty($sidebars['sidebar-1']) )
		{
			if ( in_array($active_layout, array('ems', 'esm', 'em', 'me')) )
			{
				$new_layout = str_replace('e', '', $new_layout);
			}
			elseif ( in_array($active_layout, array('sme', 'mse', 'sm', 'ms')) )
			{
				$new_layout = str_replace('s', '', $new_layout);
			}
		}

		if ( !isset($sidebars['sidebar-2']) || empty($sidebars['sidebar-2']) )
		{
			if ( in_array($active_layout, array('ems', 'esm')) )
			{
				$new_layout = str_replace('s', '', $new_layout);
			}
			elseif ( in_array($active_layout, array('sme', 'mse')) )
			{
				$new_layout = str_replace('e', '', $new_layout);
			}
		}

		$active_layout = $new_layout;
	}

	return $active_layout;
}

add_filter('active_layout', 'active_sidebars', 20);
?>