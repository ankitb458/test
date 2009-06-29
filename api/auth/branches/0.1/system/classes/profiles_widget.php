<?php
#
# MC Profiles Widget
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class profiles_widget extends widget
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
		if ( !active_user::is_admin() )
		{
			return;
		}

		echo $this->begin();

		echo $this->begin_title()
			. self::$captions->get('profiles')
			. $this->end_title();

		echo $this->begin_content();

		echo '<ul>'
			. '<li>'
			. '<a href="' . str::attr(permalink::get(array('cmd' => 'profiles'))) . '">'
				. self::$captions->get('profiles')
				. '</a>'
			. '</li>'
			. '<li>'
			. ( $args['cmd'] != 'new_profile'
				? ( '<a href="' . str::attr(permalink::get(array('cmd' => 'new_profile'))) . '">'
					. self::$captions->get('new_profile')
					. '</a>'
					)
				: self::$captions->get('new_profile')
				)
			. '</li>'
			. '</ul>';

		echo $this->end_content();

		echo $this->end();
	} # html()
} # profiles_widget

profiles_widget::init();
?>