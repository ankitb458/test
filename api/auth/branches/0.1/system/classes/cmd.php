<?php
#
# MC Command Service
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All right reserved
#

class cmd
{
	# handlers and addons
	public static $handlers = array();


	#
	# exec()
	#

	public static function exec($cmd = null)
	{
		if ( request::is_post() )
		{
			$args = $_POST;
		}
		else
		{
			$args = $_GET;
		}

		if ( !is_null($cmd) && !isset($args['cmd']) )
		{
			$args['cmd'] = $cmd;
		}

		new event('rewrite', $args);
		new event('redirect', $args);

		if ( !isset($args['cmd']) )
		{
			$args['cmd'] = 'profile';
		}
		
		$handler = @ self::$handlers[$args['cmd']];

		if ( $handler )
		{
			$request =& new $handler($args);
		}
		elseif ( config::get('debug') )
		{
			die('cmd not found');
		}
		else
		{
			new status_404($args);
		}
	} # exec()


	#
	# attach()
	#

	public static function attach($cmd, $handler = null)
	{
		self::$handlers[$cmd] = $handler;
	} # attach()


	#
	# detach()
	#

	public static function detach($cmd)
	{
		unset(self::$handlers[$cmd]);
	} # detach()


	#
	# dump()
	#

	public static function dump($cmd = null)
	{
		if ( isset($cmd) )
		{
			debug::dump(self::$handlers[$cmd]);
		}
		else
		{
			debug::dump(self::$handlers);
		}
	} # dump()
} # cmd
?>