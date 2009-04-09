<?php
#
# sem_version
#

define('sem_version', '5.7.2');

define('sem_path', dirname(dirname(__FILE__)));
define('sem_url', get_stylesheet_directory_uri());

define('sem_debug', false);
#$_GET['add_stops'] = true;


#
# override memory limit: 32M is too low on some sites
#

if ( abs(intval(WP_MEMORY_LIMIT)) < 64
	&& function_exists('memory_get_usage')
	&& ( (int) @ini_get('memory_limit') < 64 )
	) :

@ini_set('memory_limit', '64M');

endif;


#
# true(), false() and reset_plugin_hook()
#

if ( !function_exists('true') ) :
function true($bool = null)
{
	return true;
} # true()
endif;

if ( !function_exists('false') ) :
function false($bool = null)
{
	return false;
} # false()
endif;

function reset_plugin_hook($plugin_hook = null)
{
	if ( isset($plugin_hook) )
	{
		unset($GLOBALS['wp_filter'][$plugin_hook]);
	}
} # reset_plugin_hook()


#
# dump()
#

if ( !function_exists('dump') ) :
function dump()
{
	foreach ( func_get_args() as $var )
	{
		echo '<pre style="padding: 10px; border: solid 1px black; background-color: ghostwhite; color: black;">';
		var_dump($var);
		echo '</pre>';
	}
} # dump()
endif;


#
# stops
#

if ( isset($_COOKIE['add_stops']) || isset($_GET['add_stops']) || isset($_GET['drop_stops']) )
{
	if ( current_user_can('administrator') )
	{
		function add_stop($in = null, $where = null)
		{
			$queries = get_num_queries();
			$seconds = timer_stop();
			$memory = number_format(memory_get_usage() / 1024, 0);
			
			$out =  "$queries queries - {$seconds}s - {$memory}kB";
			
			if ( $where )
			{
				$GLOBALS['sem_stops'][$where] = $out;
			}
			else
			{
				dump($out);
			}

			return $in;
		} # add_stop()

		function dump_stops($in = null)
		{
			echo '<pre style="padding: 10px; border: solid 1px black; background-color: ghostwhite; color: black;">';
			foreach ( $GLOBALS['sem_stops'] as $where => $stop )
			{
				echo "$where: $stop\n";
			}
			echo '</pre>';
			
			if ( defined('SAVEQUERIES') )
			{
				global $wpdb;
				dump($wpdb->queries);
			}

			return $in;
		} # dump_stops()
		
		if ( !isset($_GET['drop_stops']) ) {
			setcookie('add_stops', 1);
			
			add_action('init', create_function('$in', '
				return add_stop($in, "Load");
				'), 10000000);
			
			add_action('template_redirect', create_function('$in', '
				return add_stop($in, "Query");
				'), -10000000);
			
			add_action('wp_footer', create_function('$in', '
				return add_stop($in, "Display");
				'), 10000000);
			
			add_action('admin_footer', create_function('$in', '
				return add_stop($in, "Display");
				'), 10000000);
			
			add_action('wp_footer', 'dump_stops', 10000000);
			add_action('admin_footer', 'dump_stops', 10000000);
		} else {
			setcookie('add_stops', null, time() - 3600);
		}
	}
	else
	{
		add_action('init', create_function('', '
			header("HTTP/1.1 301 Moved Permanently");
	        header("Status: 301 Moved Permanently");
			wp_redirect(get_option("home"));
			'));
	}
}


#
# diagnosis (obsolete)
#

if ( isset($_GET['send_diagnosis']) )
{
	add_action('init', create_function('', '
		header("HTTP/1.1 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
		wp_redirect(get_option("home"));
		'));
}


#
# catch old wizard upgrader
#

if ( is_admin() && isset($_GET['page']) && $_GET['page'] == 'wizards/upgrade.php' )
{
	wp_redirect(trailingslashit(site_url()) . 'wp-admin/');
	die;
}


#
# fix_wp_widgets()
#

function fix_wp_widgets($o)
{
	if ( !isset($o['array_version']) ) $o['array_version'] = 3;
	
	return $o;
} # fix_wp_widgets()

add_filter('pre_update_option_sidebars_widgets', 'fix_wp_widgets');


#
# load options
#

$GLOBALS['sem_options'] = get_option('sem6_options');
$GLOBALS['sem_captions'] = get_option('sem6_captions');
$GLOBALS['sem_nav_menus'] = get_option('sem_nav_menus');

# autoinstall test
#$GLOBALS['sem_options'] = false;


#
# install / upgrade
#

$force_update = false;

if ( !$GLOBALS['sem_options'] )
{
	# try semiologic 5
	
	$GLOBALS['sem_options'] = get_option('sem5_options');
	$GLOBALS['sem_captions'] = get_option('sem5_captions');
	
	if ( $GLOBALS['sem_options'] )
	{
		$force_update = true;
	}
}

if ( !$GLOBALS['sem_options'] )
{
	include sem_path . '/inc/install.php';
}
elseif ( $GLOBALS['sem_options']['version'] != sem_version || $force_update )
{
	include sem_path . '/inc/upgrade.php';
}


#
# override WP 2.7 comment options
#

if ( is_admin() )
	add_action('load-options-discussion.php', array('sem_entry_admin', 'discussion_options'));


#
# kill paged comments
#

if ( !is_admin() )
	add_filter('option_page_comments', 'false');


#
# set content width
#

global $content_width;
$content_width = 480;
?>