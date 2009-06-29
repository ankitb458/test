<?php
#
# Security Threat Service
# -----------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class threat
{
	#
	# __construct()
	#

	public function __construct()
	{
		$captions =& new captions;
		$message = $captions->get('security_threat');

		if ( config::get('debug') )
		{
			throw new exception($message);
		}
		else
		{
			ob::clean();
			die($message);
		}
	} # __construct()
} # threat
?>