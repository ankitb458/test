<?php
#
# MC 403 Error
# ------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class status_403 extends request
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

		$this->template = 'login';

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
		css::load('/system/css/system.css');
	} # wire()


	#
	# title()
	#

	public function title()
	{
		echo self::$captions->get('permission_denied');
	} # title()


	#
	# html_headers()
	#

	public function html_headers()
	{
		if ( active_user::is_guest() )
		{
			permalink::redirect(array('cmd' => 'login', 'redirect' => request::self()));
		}
		elseif ( $this->args['cmd'] != 'restrict' )
		{
			$protocol = $_SERVER['SERVER_PROTOCOL'];

			if ( ( $protocol != 'HTTP/1.1' ) && ( $protocol != 'HTTP/1.0' ) )
			{
				$protocol = 'HTTP/1.0';
			}

			$status_header = "$protocol 403 Forbidden";

			header($status_header);

			die;
		}
		else
		{
			request::html_headers();
		}
	} # html_headers()


	#
	# html()
	#

	public function html()
	{
		echo '<h2>'
			. self::$captions->get('permission_denied')
			. '</h2>';

		echo '<p>'
			. self::$captions->get('permission_denied_details')
			. '</p>';

		new event(get_class($this) . '_html', $this);
	} # html()


	#
	# xml()
	#

	public function xml()
	{
		$message = self::$captions->get('permission_denied');
		new error($message);

		messages::xml();
	} # html()
} # status_403

status_403::init();
?>