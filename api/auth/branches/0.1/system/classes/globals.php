<?php
#
# Globals Manipulation Library
# ----------------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class globals
{
	#
	# unregister()
	# ------------
	# Unregister global variables
	#

	public static function unregister()
	{
		if ( isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) )
		{
			new threat();
		}

		$no_unset = array(
			'GLOBALS',
			'_GET',
			'_POST',
			'_COOKIE',
			'_REQUEST',
			'_SERVER',
			'_ENV',
			'_FILES'
			);

		$vars = array_merge(
			$_GET,
			$_POST,
			$_COOKIE,
			$_SERVER,
			$_ENV,
			$_FILES,
			isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array()
			);

		foreach ( array_keys($vars) as $key )
		{
			if ( isset($GLOBALS[$key])
				&& !in_array($key, $no_unset)
				)
			{
				unset($GLOBALS[$key]);
			}
		}
	} # unregister()
} # globals
?>