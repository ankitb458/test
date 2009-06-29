<?php
#
# MC Message Service
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

abstract class message
{
	protected $message;


	#
	# __construct()
	#

	public function __construct($message)
	{
		$this->message = $message;

		$messages =& session::get('messages');

		$messages[get_class($this)][] =& $this;
	} # __construct()


	#
	# __toString()
	#

	public function __toString()
	{
		return $this->message;
	} # __toString()


	#
	# exists()
	#

	public static function exists()
	{
		$messages =& session::get('messages');

		return isset($messages);
	} # exists()


	#
	# clear()
	#

	public static function clear()
	{
		$messages =& session::get('messages');

		$messages = null;
	} # clear()


	#
	# on_exit()
	#

	public static function on_exit()
	{
		self::clear();
	} # on_exit()


	#
	# dump()
	#

	public static function dump()
	{
		debug::dump(session::get('messages'));
	} # dump()
} # message
?>