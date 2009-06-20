<?php
#
# MC Core
# -------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#


if ( !function_exists('__autoload') ) :


# Application factory

if ( !defined('mc_path') )
{
	define('mc_path', dirname(__FILE__));
}

function __autoload($class)
{
	#echo '<pre>' . $class . '</pre>';

	if ( $file = current((array) glob(mc_path . '/{modules/*,system/classes}/' . $class . '.php', GLOB_BRACE)) )
	{
		require $file;
	}
} # __autoload()


# Check server settings

define('iis', strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

if ( iis )
{
	dev::start();
}

if ( get_magic_quotes_gpc() )
{
	gpc::strip_all();
}

if ( ini_get('register_globals') )
{
	globals::unregister();
}

if ( get_magic_quotes_runtime() )
{
	set_magic_quotes_runtime(0);
}

if ( !ini_get('auto_detect_line_endings') )
{
	ini_set('auto_detect_line_endings', 1);
}

ini_set('default_charset', config::get('charset'));


# Load defaults

defaults::load();


# Load modules

modules::load();


define('mc_url', config::get('mc_url'));
define('site_url', config::get('site_url'));


# Register shutdown hook

function on_shutdown()
{
	ob::flush();

	new event('shutdown');
	new event('exit');
} # do_shutdown()

register_shutdown_function('on_shutdown');


# Start buffer

ob::start();


# Init hook

new event('init');


# Preprocess Request

new event('preprocess');

endif;
?>