<?php
#
# MC Error Object
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class error extends message
{
	#
	# exists()
	#

	public static function exists()
	{
		$messages =& session::get('messages');

		return isset($messages[__CLASS__]);
	} # exists()
} # error
?>