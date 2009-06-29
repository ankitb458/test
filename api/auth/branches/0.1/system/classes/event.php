<?php
#
# MC Event Object
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class event
{
	#
	# Events service
	#

	protected static $handlers = array();


	#
	# attach()
	# --------
	# Attach event handler
	#

	public static function attach($event, $handler)
	{
		if ( @ !in_array($handler, (array) self::$handlers[$event]) )
		{
			self::$handlers[$event][] = $handler;
		}
	} # attach()


	#
	# detach()
	# --------
	# Detach event handler
	#

	public function detach($event, $handler)
	{
		$key = @ array_search($handler, (array) self::$handlers[$event]);

		if ( $key !== false )
		{
			unset(self::$handlers[$event][$key]);
		}
	} # detach()


	#
	# reset()
	# -------
	# Reset an event
	#

	public function reset($event)
	{
		unset(self::$handlers[$event]);
	} # reset()


	#
	# dump()
	#

	public static function dump($event = null)
	{
		if ( isset($event) )
		{
			@ debug::dump(self::$handlers[$event]);
		}
		else
		{
			debug::dump(self::$handlers);
		}
	} # dump()


	#
	# Event object
	#


	#
	# __construct()
	#

	public function __construct($event, &$sender = null)
	{
		foreach ( @ (array) self::$handlers[$event] as $handler )
		{
			call_user_func_array($handler, array(&$sender));
		}
	} # __construct()
} # event
?>