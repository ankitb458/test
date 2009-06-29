<?php
#
# Output Buffer Service
# ---------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class ob
{
	#
	# start()
	#

	public static function start()
	{
		ob_start(array('ob', 'on_flush'));

		# Start debug mode

		if ( config::get('debug') )
		{
			error_reporting(E_ALL);
			ob_start(array('debug', 'reformat'));
		}
	} # start()


	#
	# flush()
	#

	public static function flush()
	{
		@ob_end_flush();
	} # flush()


	#
	# clean()
	#

	public static function clean()
	{
		@ob_end_clean();
	} # clean()


	#
	# restart()
	#

	public static function restart()
	{
		ob::clean();

		ob::start();
	} # restart()


	#
	# on_flush()
	#

	# Register ob_flush hook

	function on_ob_flush($buffer)
	{
		new event('ob_flush', $buffer);

		return $buffer;
	} # on_flush()
} # ob
?>