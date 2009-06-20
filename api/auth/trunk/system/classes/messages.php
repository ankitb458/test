<?php
#
# MC Messages Widget
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class messages extends widget
{
	#
	# wire()
	#

	public function wire(&$args)
	{
		if ( message::exists() )
		{
			css::load('/system/css/system.css');
		}
	} # wire()


	#
	# html()
	#

	public function html(&$args)
	{
		if ( message::exists() )
		{
			foreach ( session::get('messages') as $type => $messages )
			{
				echo '<div class="system messages widget ' . $type . '">';

				foreach ( $messages as $message )
				{
					echo '<div class="message">'
						. $message
						. '</div>';
				}

				echo '</div>';
			}
		}

		event::attach('exit', array('message', 'on_exit'));
	} # html()


	#
	# xml()
	#

	public function xml()
	{
		if ( message::exists() )
		{
			foreach ( session::get('messages') as $type => $messages )
			{
				echo '<messages>';

				foreach ( $messages as $message )
				{
					echo '<' . $type . '>'
						. str::cdata($message)
						. '</' . $type . '>';
				}

				echo '</messages>';
			}
		}

		event::attach('exit', array('message', 'on_exit'));
	} # xml()
} # messages
?>