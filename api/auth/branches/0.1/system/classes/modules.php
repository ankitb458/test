<?php
#
# MC Modules Service
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class modules
{
	#
	# load()
	#

	public static function load()
	{

		if ( $active_modules = config::get('active_modules') )
		{
			$modules = '';

			foreach ( (array) $active_modules as $module )
			{
				$modules .= ( $module ? "," : '' ) . $module;
			}

			if ( $modules )
			{
				foreach ( glob(mc_path . '/modules/{' . $modules . '}/autoload.php', GLOB_BRACE) as $file )
				{
					include_once $file;
				}
			}
		}
	} # load()
} # modules
?>