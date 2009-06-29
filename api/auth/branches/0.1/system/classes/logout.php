<?php
#
# MC Logout Request
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class logout extends request
{
	#
	# __construct()
	#

	public function __construct(&$args)
	{
		active_user::logout($args);
	} # __construct()
} # logout
?>