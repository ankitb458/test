<?php
#
# Data Add-On Interface
# ---------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

interface data_addon
{
	#
	# map()
	#

	public static function map(&$data);


	#
	# sanitize()
	#

	public static function sanitize(&$data);


	#
	# validate()
	#

	public static function validate(&$data);


	#
	# save()
	#

	public static function save(&$data);
} # data_addon
?>