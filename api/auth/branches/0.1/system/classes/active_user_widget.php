<?php
#
# MC Active User Widget
# ---------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class active_user_widget extends widget
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
		echo $this->begin();

		echo $this->begin_title()
			. self::$captions->get('welcome', array('user_name' => active_user::get('user_name') ))
			. $this->end_title();

		echo $this->begin_content()
			. '<ul>';

		if ( !active_user::is_guest() )
		{
			if ( $args['cmd'] == 'profile' )
			{
				echo '<li>'
					. self::$captions->get('profile')
					. '</li>';
			}
			else
			{
				echo '<li>'
					. '<a href="'
						. str::attr(
							permalink::get(
								array(
									'cmd' => 'profile'
									)
								)
							)
						. '">'
					. self::$captions->get('profile')
					. '</a>'
					. '</li>';
			}

			echo '<li>'
				. '<a href="'
						. str::attr(
							permalink::get(
								array(
									'cmd' => 'logout'
									)
								)
							)
						. '">'
				. self::$captions->get('logout')
				. '</a>'
				. '</li>';
		}
		else
		{
			if ( options::get('allow_registrations') )
			{
				if ( $args['cmd'] == 'register' )
				{
					echo '<li>'
						. self::$captions->get('register')
						. '</li>';
				}
				else
				{
					echo '<li>'
						. '<a href="'
							. str::attr(
								permalink::get(
									array(
										'cmd' => 'register',
										'redirect' => ( isset($args['redirect']) ? $args['redirect'] : null ),
										)
									)
								)
							. '">'
						. self::$captions->get('register')
						. '</a>'
						. '</li>';
				}
			}

			echo '<li>'
				. '<a href="'
						. str::attr(
							permalink::get(
								array(
									'cmd' => 'login',
									'redirect' => ( isset($args['redirect']) ? $args['redirect'] : null ),
									)
								)
							)
						. '">'
				. self::$captions->get('login')
				. '</a>'
				. '</li>';
		}

		echo '</ul>'
			. $this->end_content();

		echo $this->end();
	} # html()
} # active_user_widget

active_user_widget::init();
?>