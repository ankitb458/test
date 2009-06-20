<?php
#
# Magic Quote Manipulation Library
# --------------------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class gpc
{
	#
	# strip_all()
	# -----------
	# Strip gpc quotes from all request variables
	#

	public static function strip_all()
	{
		$_GET = self::strip($_GET);
		$_POST = self::strip($_POST);
		$_COOKIE = self::strip($_COOKIE);
		$_REQUEST = self::strip($_REQUEST);
	} # strip_all()


	#
	# strip()
	# -------
	# Strip gpc quotes from an array
	#

	public static function strip($args = null)
	{
		if ( is_array($args) )
		{
			foreach ( $args as $key => $arg )
			{
				unset($args[$key]);

				$args[stripslashes($key)] = self::strip($arg);
			}
		}
		elseif ( is_string($args) )
		{
			$args = stripslashes($args);
		}

		return $args;
	} # strip()
} # gpc
?>