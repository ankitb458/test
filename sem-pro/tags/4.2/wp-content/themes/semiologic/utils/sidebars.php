<?php
#
# sidebar_is_empty()
#

function sidebar_is_empty($sidebar_id)
{
	$sidebars = get_option('sidebars_widgets');

	return ( !isset($sidebars['sidebar-' . $sidebar_id]) || empty($sidebars['sidebar-' . $sidebar_id]) );
} # end sidebar_is_empty()


#
# display_sidebar()
#

function display_sidebar()
{
	$sidebar_id = 0;
	$default_layout = get_active_layout(false);

	if ( function_exists('dynamic_sidebar') )
	{
		$active_layout = get_active_layout(true);

		$setup = array($default_layout, $active_layout);

		switch ( $setup )
		{
		case array('essm', 'essm'):
			$sidebar_id = 3;
			break;

		case array('esms', 'esms'):
			$sidebar_id = 2;
			break;

		case array('emss', 'emss'):
			$sidebar_id = 2;
			break;


		case array('ssme', 'ssme'):
			$sidebar_id = 2;
			break;

		case array('smse', 'smse'):
			$sidebar_id = 1;
			break;

		case array('msse', 'msse'):
			$sidebar_id = 1;
			break;


		case array('ssm', 'ssm'):
			$sidebar_id = 2;
			break;

		case array('sms', 'sms'):
			$sidebar_id = 1;
			break;

		case array('mss', 'mss'):
			$sidebar_id = 1;
			break;


		case array('esm', 'esm'):
			$sidebar_id = 2;
			break;

		case array('ems', 'ems'):
			$sidebar_id = 2;
			break;


		case array('sme', 'sme'):
			$sidebar_id = 1;
			break;

		case array('mse', 'mse'):
			$sidebar_id = 1;
			break;


		case array('sm', 'sm'):
			$sidebar_id = 1;
			break;

		case array('ms', 'ms'):
			$sidebar_id = 1;
			break;


		case array('essm', 'esm'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 3;
			}
			break;

		case array('esms', 'esm'):
			$sidebar_id = 2;
			break;

		case array('esms', 'ems'):
			$sidebar_id = 3;
			break;

		case array('emss', 'ems'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 3;
			}
			break;


		case array('ssme', 'ssm'):
			$sidebar_id = 2;
			break;

		case array('smse', 'sms'):
			$sidebar_id = 1;
			break;

		case array('msse', 'mss'):
			$sidebar_id = 1;
			break;


		case array('ssme', 'sme'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 1;
			}
			break;

		case array('smse', 'sme'):
			$sidebar_id = 1;
			break;

		case array('msse', 'mse'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 1;
			}
			break;


		case array('essm', 'ssm'):
			$sidebar_id = 3;
			break;

		case array('esms', 'sms'):
			$sidebar_id = 2;
			break;

		case array('emss', 'mss'):
			$sidebar_id = 2;
			break;


		case array('smse', 'mse'):
			$sidebar_id = 2;
			break;


		case array('essm', 'sm'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 3;
			}
			break;

		case array('esms', 'ms'):
			$sidebar_id = 3;
			break;

		case array('emss', 'ms'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 3;
			}
			break;


		case array('esms', 'sm'):
			$sidebar_id = 2;
			break;

		case array('ssme', 'sm'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 1;
			}
			break;

		case array('smse', 'ms'):
			$sidebar_id = 2;
			break;

		case array('msse', 'ms'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 1;
			}
			break;


		case array('smse', 'sm'):
			$sidebar_id = 1;
			break;


		case array('ssm', 'sm'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 1;
			}
			break;

		case array('sms', 'sm'):
			$sidebar_id = 1;
			break;

		case array('sms', 'ms'):
			$sidebar_id = 2;
			break;

		case array('mss', 'ms'):
			if ( !sidebar_is_empty(2) )
			{
				$sidebar_id = 2;
			}
			else
			{
				$sidebar_id = 1;
			}
			break;


		case array('sme', 'sm'):
			$sidebar_id = 1;
			break;

		case array('mse', 'ms'):
			$sidebar_id = 1;
			break;


		case array('esm', 'sm'):
			$sidebar_id = 2;
			break;

		case array('ems', 'ms'):
			$sidebar_id = 2;
			break;
		}
	}
	else
	{
		if ( preg_match("/ssm/", $default_layout) )
		{
			$sidebar_id = 2;
		}
		elseif ( preg_match("/sm|ms/", $default_layout) )
		{
			$sidebar_id = 1;
		}
	}

	if ( $sidebar_id )
	{
		include_once TEMPLATEPATH . '/sidebar.php';
	}
} # end display_sidebar()

add_action('display_sidebar', 'display_sidebar');


#
# display_sidebar2()
#

function display_sidebar2()
{
	$sidebar_id = 0;
	$default_layout = get_active_layout(false);

	if ( function_exists('dynamic_sidebar') )
	{
		$active_layout = get_active_layout(true);

		$setup = array($default_layout, $active_layout);

		switch ( $setup )
		{
		case array('essm', 'essm'):
			$sidebar_id = 2;
			break;

		case array('esms', 'esms'):
			$sidebar_id = 3;
			break;

		case array('emss', 'emss'):
			$sidebar_id = 3;
			break;


		case array('ssme', 'ssme'):
			$sidebar_id = 1;
			break;

		case array('smse', 'smse'):
			$sidebar_id = 2;
			break;

		case array('msse', 'msse'):
			$sidebar_id = 2;
			break;


		case array('ssm', 'ssm'):
			$sidebar_id = 1;
			break;

		case array('sms', 'sms'):
			$sidebar_id = 2;
			break;

		case array('mss', 'mss'):
			$sidebar_id = 2;
			break;


		case array('ssme', 'ssm'):
			$sidebar_id = 1;
			break;

		case array('smse', 'sms'):
			$sidebar_id = 2;
			break;

		case array('msse', 'mss'):
			$sidebar_id = 2;
			break;


		case array('essm', 'ssm'):
			$sidebar_id = 2;
			break;

		case array('esms', 'sms'):
			$sidebar_id = 3;
			break;

		case array('emss', 'mss'):
			$sidebar_id = 3;
			break;
		}
	}
	else
	{
		if ( preg_match("/ssm/", $default_layout) )
		{
			$sidebar_id = 1;
		}
		elseif ( preg_match("/sms|mss/", $default_layout) )
		{
			$sidebar_id = 2;
		}
	}


	if ( $sidebar_id )
	{
		include_once TEMPLATEPATH . '/sidebar2.php';
	}
} # end display_sidebar2()

add_action('display_sidebar2', 'display_sidebar2');


#
# display_ext_sidebar()
#

function display_ext_sidebar()
{
	$sidebar_id = 0;
	$default_layout = get_active_layout(false);

	if ( function_exists('dynamic_sidebar') )
	{
		$active_layout = get_active_layout(true);

		$setup = array($default_layout, $active_layout);

		switch ( $setup )
		{
		case array('essm', 'essm'):
			$sidebar_id = 1;
			break;

		case array('esms', 'esms'):
			$sidebar_id = 1;
			break;

		case array('emss', 'emss'):
			$sidebar_id = 1;
			break;


		case array('ssme', 'ssme'):
			$sidebar_id = 3;
			break;

		case array('smse', 'smse'):
			$sidebar_id = 3;
			break;

		case array('msse', 'msse'):
			$sidebar_id = 3;
			break;


		case array('esm', 'esm'):
			$sidebar_id = 1;
			break;

		case array('ems', 'ems'):
			$sidebar_id = 1;
			break;


		case array('sme', 'sme'):
			$sidebar_id = 2;
			break;

		case array('mse', 'mse'):
			$sidebar_id = 2;
			break;


		case array('em', 'em'):
			$sidebar_id = 1;
			break;

		case array('me', 'me'):
			$sidebar_id = 1;
			break;


		case array('essm', 'esm'):
			$sidebar_id = 1;
			break;

		case array('esms', 'esm'):
			$sidebar_id = 1;
			break;

		case array('esms', 'ems'):
			$sidebar_id = 1;
			break;

		case array('emss', 'ems'):
			$sidebar_id = 1;
			break;


		case array('ssme', 'sme'):
			$sidebar_id = 3;
			break;

		case array('smse', 'sme'):
			$sidebar_id = 3;
			break;

		case array('msse', 'mse'):
			$sidebar_id = 3;
			break;


		case array('smse', 'mse'):
			$sidebar_id = 3;
			break;


		case array('ssme', 'me'):
			$sidebar_id = 3;
			break;

		case array('smse', 'me'):
			$sidebar_id = 3;
			break;

		case array('msse', 'me'):
			$sidebar_id = 3;
			break;


		case array('essm', 'em'):
			$sidebar_id = 1;
			break;

		case array('esms', 'em'):
			$sidebar_id = 1;
			break;

		case array('emss', 'em'):
			$sidebar_id = 1;
			break;


		case array('esm', 'em'):
			$sidebar_id = 1;
			break;

		case array('ems', 'em'):
			$sidebar_id = 1;
			break;


		case array('sme', 'me'):
			$sidebar_id = 2;
			break;

		case array('mse', 'me'):
			$sidebar_id = 2;
			break;
		}
	}
	else
	{
		if ( preg_match("/e/", $default_layout) )
		{
			$sidebar_id = 1;
		}
	}

	if ( $sidebar_id )
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
	$new_layout = $active_layout;

	if ( function_exists('dynamic_sidebar') )
	{
		$num_sidebars = strlen($active_layout) - 1;

		$sidebars = get_option('sidebars_widgets');

		$sidebar_setup = array(
			$active_layout,
			!isset($sidebars['sidebar-1']) || empty($sidebars['sidebar-1']) || $num_sidebars < 1,
			!isset($sidebars['sidebar-2']) || empty($sidebars['sidebar-2']) || $num_sidebars < 2,
			!isset($sidebars['sidebar-3']) || empty($sidebars['sidebar-3']) || $num_sidebars < 3
			);

		#echo '<pre>';
		#var_dump($sidebars, $sidebar_setup);
		#echo '</pre>';

		switch($sidebar_setup)
		{
			case array('essm', false, false, true):
				$new_layout = 'esm';
				break;

			case array('essm', false, true, true):
				$new_layout = 'em';
				break;

			case array('essm', true, false, true):
				$new_layout = 'sm';
				break;

			case array('essm', true, true, true):
				$new_layout = 'm';
				break;

			case array('essm', false, true, false):
				$new_layout = 'esm';
				break;

			case array('essm', true, true, false):
				$new_layout = 'sm';
				break;

			case array('essm', true, false, false):
				$new_layout = 'ssm';
				break;


			case array('esms', false, false, true):
				$new_layout = 'esm';
				break;

			case array('esms', false, true, true):
				$new_layout = 'em';
				break;

			case array('esms', true, false, true):
				$new_layout = 'sm';
				break;

			case array('esms', true, true, true):
				$new_layout = 'm';
				break;

			case array('esms', false, true, false):
				$new_layout = 'ems';
				break;

			case array('esms', true, true, false):
				$new_layout = 'ms';
				break;

			case array('esms', true, false, false):
				$new_layout = 'sms';
				break;


			case array('emss', false, false, true):
				$new_layout = 'ems';
				break;

			case array('emss', false, true, true):
				$new_layout = 'em';
				break;

			case array('emss', true, false, true):
				$new_layout = 'ms';
				break;

			case array('emss', true, true, true):
				$new_layout = 'm';
				break;

			case array('emss', false, true, false):
				$new_layout = 'ems';
				break;

			case array('emss', true, true, false):
				$new_layout = 'ms';
				break;

			case array('emss', true, false, false):
				$new_layout = 'mss';
				break;


			case array('smse', false, false, true):
				$new_layout = 'sms';
				break;

			case array('smse', false, true, true):
				$new_layout = 'sm';
				break;

			case array('smse', true, false, true):
				$new_layout = 'ms';
				break;

			case array('smse', true, true, true):
				$new_layout = 'm';
				break;

			case array('smse', false, true, false):
				$new_layout = 'sme';
				break;

			case array('smse', true, true, false):
				$new_layout = 'me';
				break;

			case array('smse', true, false, false):
				$new_layout = 'mse';
				break;


			case array('ssme', false, false, true):
				$new_layout = 'ssm';
				break;

			case array('ssme', false, true, true):
				$new_layout = 'sm';
				break;

			case array('ssme', true, false, true):
				$new_layout = 'sm';
				break;

			case array('ssme', true, true, true):
				$new_layout = 'm';
				break;

			case array('ssme', false, true, false):
				$new_layout = 'sme';
				break;

			case array('ssme', true, true, false):
				$new_layout = 'me';
				break;

			case array('ssme', true, false, false):
				$new_layout = 'sme';
				break;


			case array('msse', false, false, true):
				$new_layout = 'mss';
				break;

			case array('msse', false, true, true):
				$new_layout = 'ms';
				break;

			case array('msse', true, false, true):
				$new_layout = 'ms';
				break;

			case array('msse', true, true, true):
				$new_layout = 'm';
				break;

			case array('msse', false, true, false):
				$new_layout = 'mse';
				break;

			case array('msse', true, true, false):
				$new_layout = 'me';
				break;

			case array('msse', true, false, false):
				$new_layout = 'mse';
				break;


			case array('esm', false, true, true):
				$new_layout = 'em';
				break;

			case array('esm', true, false, true):
				$new_layout = 'sm';
				break;

			case array('esm', true, true, true):
				$new_layout = 'm';
				break;


			case array('ems', false, true, true):
				$new_layout = 'em';
				break;

			case array('ems', true, false, true):
				$new_layout = 'ms';
				break;

			case array('ems', true, true, true):
				$new_layout = 'm';
				break;


			case array('sme', false, true, true):
				$new_layout = 'sm';
				break;

			case array('sme', true, false, true):
				$new_layout = 'me';
				break;

			case array('sme', true, true, true):
				$new_layout = 'm';
				break;


			case array('mse', false, true, true):
				$new_layout = 'ms';
				break;

			case array('mse', true, false, true):
				$new_layout = 'me';
				break;

			case array('mse', true, true, true):
				$new_layout = 'm';
				break;


			case array('ssm', false, true, true):
				$new_layout = 'sm';
				break;

			case array('ssm', true, false, true):
				$new_layout = 'sm';
				break;

			case array('ssm', true, true, true):
				$new_layout = 'm';
				break;


			case array('sms', false, true, true):
				$new_layout = 'sm';
				break;

			case array('sms', true, false, true):
				$new_layout = 'ms';
				break;

			case array('sms', true, true, true):
				$new_layout = 'm';
				break;


			case array('mss', false, true, true):
				$new_layout = 'ms';
				break;

			case array('mss', true, false, true):
				$new_layout = 'ms';
				break;
			case array('mss', true, true, true):
				$new_layout = 'm';
				break;


			case array('em', true, true, true):
				$new_layout = 'm';
				break;


			case array('me', true, true, true):
				$new_layout = 'm';
				break;


			case array('sm', true, true, true):
				$new_layout = 'm';
				break;


			case array('ms', true, true, true):
				$new_layout = 'm';
				break;
		}
	}

	return $new_layout;
}

add_filter('active_layout', 'active_sidebars', 20);
?>