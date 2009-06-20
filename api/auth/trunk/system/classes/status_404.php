<?php
#
# MC 404 Error
# ------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class status_404 extends request
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		message::clear();

		parent::__construct($args);
	} # __construct()


	#
	# map()
	#

	public function map()
	{
	} # map()


	#
	# exec()
	#

	public function exec()
	{
	} # exec()


	#
	# wire()
	#

	public function wire()
	{
	} # wire()


	#
	# title()
	#

	public function title()
	{
	} # title()


	#
	# html_headers()
	#

	public function html_headers()
	{
		ob_clean();

		$protocol = $_SERVER['SERVER_PROTOCOL'];

		if ( ( $protocol != 'HTTP/1.1' ) && ( $protocol != 'HTTP/1.0' ) )
		{
			$protocol = 'HTTP/1.0';
		}

		$status_header = "$protocol 404 Not Found";

		header($status_header);

		die;
	} # html_headers()


	#
	# html()
	#

	public function html()
	{
	} # html()


	#
	# xml()
	#

	public function xml()
	{
		$message = self::$captions->get('not_found');
		new error($message);

		messages::xml();
	} # html()
} # status_404

status_404::init();
?>