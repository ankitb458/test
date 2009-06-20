<?php
#
# MC Session Service
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class session
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
		
		if ( $_SERVER['HTTP_HOST'] != 'localhost' )
			ini_set('session.cookie_domain', '.semiologic.com');
		else
			ini_set('session.cookie_domain', 'localhost');
		ini_set('session.cookie_path', '/');

		@ session_start();

		event::attach('exit', array('session', 'on_exit'));
	} # init()


	#
	# clean()
	#

	public static function clean()
	{
		foreach ( array_keys((array) $_SESSION) as $key )
		{
			if ( !isset($_SESSION[$key]) )
			{
				unset($_SESSION[$key]);
			}
		}
	} # clean()


	#
	# on_exit()
	#

	public static function on_exit()
	{
		self::clean();
	} # on_exit()


	#
	# get()
	#

	public static function &get($key)
	{
		$var =& $_SESSION[$key];

		return $var;
	} # get()


	#
	# dump()
	#

	function dump($key = null)
	{
		if ( isset($key) )
		{
			debug::dump($_SESSION[$key]);
		}
		else
		{
			debug::dump($_SESSION);
		}
	} # dump()
} # session

# start service
session::init();
?>