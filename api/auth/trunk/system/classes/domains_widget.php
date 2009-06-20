<?php
#
# MC Domains Widget
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class domains_widget extends widget
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
		if ( !active_user::can('manage_access') )
		{
			return;
		}

		echo $this->begin();

		echo $this->begin_title()
			. self::$captions->get('domains')
			. $this->end_title();

		echo $this->begin_content();

		echo '<ul>'
			. '<li>'
			. ( $args['cmd'] != 'domains'
				? ( '<a href="' . str::attr(permalink::get(array('cmd' => 'domains'))) . '">'
					. self::$captions->get('domains')
					. '</a>'
					)
				: self::$captions->get('domains')
				)
			. '</li>'
			. '<li>'
			. ( $args['cmd'] != 'new_domain'
				? ( '<a href="' . str::attr(permalink::get(array('cmd' => 'new_domain'))) . '">'
					. self::$captions->get('new_domain')
					. '</a>'
					)
				: self::$captions->get('new_domain')
				)
			. '</li>'
			. '</ul>';

		echo $this->end_content();

		echo $this->end();
	} # html()
} # domains_widget

domains_widget::init();
?>