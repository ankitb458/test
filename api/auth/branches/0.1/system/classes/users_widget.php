<?php
#
# MC Users Widget
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class users_widget extends widget
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
	# wire()
	#

	public function wire(&$args)
	{
	} # wire()


	#
	# html()
	#

	public function html(&$args)
	{
		if ( !active_user::can('manage_users') )
		{
			return;
		}

		echo $this->begin();

		echo $this->begin_title()
			. self::$captions->get('users')
			. $this->end_title();

		echo $this->begin_content();

		echo '<ul>'
			. '<li>'
			. '<a href="' . str::attr(permalink::get(array('cmd' => 'users'))) . '">'
				. self::$captions->get('users')
				. '</a>'
			. '</li>'
			. '<li>'
			. ( $args['cmd'] != 'new_user'
				? ( '<a href="' . str::attr(permalink::get(array('cmd' => 'new_user'))) . '">'
					. self::$captions->get('new_user')
					. '</a>'
					)
				: self::$captions->get('new_user')
				)
			. '</li>'
			. '</ul>';

		echo $this->end_content();

		echo $this->end();
	} # html()
} # users_widget

users_widget::init();
?>