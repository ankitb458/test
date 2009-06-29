<?php
#
# Request Add-On Interface
# ------------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

interface request_addon
{
	#
	# map()
	#

	public static function map(&$request);


	#
	# wire()
	#

	public static function wire(&$request);


	#
	# html()
	#

	public static function html(&$request);
} # request_addon
?>