<?php
#
# sem_version
#

define('sem_version', '5.0');

define('sem_path', dirname(dirname(__FILE__)));
define('sem_pro_path', ABSPATH . PLUGINDIR . '/semiologic');

define('sem_pro', file_exists(sem_pro_path));
define('sem_debug', true);


#
# true() and false()
# these are useful to override theme defaults in the custom.php file
#

if ( !function_exists('true') ) :
function true($bool = null)
{
	return true;
} # end true()
endif;

if ( !function_exists('false') ) :
function false($bool = null)
{
	return false;
} # end false()
endif;


#
# dump() and dump_time()
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

if ( !function_exists('dump_time') ) :
function dump_time($where = '')
{
	echo '<div style="margin: 10px auto; text-align: center;">';
	echo ( $where ? ( $where . ': ' ) : '' ) . get_num_queries() . " queries - " . timer_stop() . " seconds";
	echo '</div>';
} # dump_time()
endif;

if ( !function_exists('add_stop') ) :
function add_stop($in = null, $where = null)
{
	dump_time($where);

	return $in;
} # add_stop()
endif;


#
# reset_plugin_hook()
#

function reset_plugin_hook($plugin_hook = null)
{
	if ( isset($plugin_hook) )
	{
		unset($GLOBALS['wp_filter'][$plugin_hook]);
	}
} # end reset_plugin_hook()


$GLOBALS['sem_options'] = get_option('sem5_options');
$GLOBALS['sem_captions'] = get_option('sem5_captions');
$GLOBALS['sem_nav'] = get_option('sem5_nav');
$GLOBALS['sem_nav_cache'] = get_option('sem5_nav_cache');

# autoinstall test
#$GLOBALS['sem_options'] = false;


#
# autoinstall / upgrade
#

if ( !$GLOBALS['sem_options'] )
{
	include_once sem_path . '/inc/autoinstall.php';
}
elseif ( version_compare($GLOBALS['sem_options']['version'], sem_version, '<') )
{
	require_once sem_path . '/inc/upgrade.php';
}


#
# register panels
#

register_sidebar(
	array(
		'id' => 'the_header',
		'name' => 'Header',
		'before_widget' => '<div class="%2$s">',
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		)
	);

register_sidebar(
	array(
		'id' => 'before_the_entries',
		'name' => 'Before All Entries',
		'before_widget' => '<div class="%2$s">',
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		)
	);

register_sidebar(
	array(
		'id' => 'the_entry',
		'name' => 'Each Entry',
		'before_widget' => '<div class="%2$s">',
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		)
	);

register_sidebar(
	array(
		'id' => 'after_the_entries',
		'name' => 'After All Entries',
		'before_widget' => '<div class="%2$s">',
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		)
	);

if ( substr($GLOBALS['sem_options']['active_layout'], 0, 1) == 'e' )
{
	register_sidebar(
		array(
			'id' => 'ext_sidebar',
			'name' => 'Ext Sidebar',
			)
		);
}

switch ( substr_count($GLOBALS['sem_options']['active_layout'], 's') )
{
case 2:
	register_sidebar(
		array(
			'id' => 'sidebar-1',
			'name' => 'Left Sidebar',
			)
		);
	register_sidebar(
		array(
			'id' => 'sidebar-2',
			'name' => 'Right Sidebar',
			)
		);
	break;

case 1:
	register_sidebar(
		array(
			'id' => 'sidebar-1',
			'name' => 'Sidebar',
			)
		);
	break;
}

if ( substr($GLOBALS['sem_options']['active_layout'], strlen($GLOBALS['sem_options']['active_layout']) - 1, 1) == 'e' )
{
	register_sidebar(
		array(
			'id' => 'ext_sidebar',
			'name' => 'Ext Sidebar',
			)
		);
}

register_sidebar(
	array(
		'id' => 'the_footer',
		'name' => 'Footer',
		'before_widget' => '<div class="%2$s">',
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		)
	);

?>