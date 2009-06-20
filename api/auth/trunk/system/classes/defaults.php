<?php
#
# MC Defaults Service
# -------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All right reserved
#

class defaults
{
	#
	# load()
	#

	function load()
	{
		# System Commands
		cmd::attach('login', 'login');
		cmd::attach('logout', 'logout');
		cmd::attach('remind', 'remind');

		cmd::attach('register', 'user_editor');
		cmd::attach('profile', 'user_editor');

		foreach ( array('user', 'profile', 'domain') as $data )
		{
			cmd::attach('new_' . $data, $data . '_editor');
			cmd::attach('edit_' . $data, $data . '_editor');
			cmd::attach('delete_' . $data, $data . '_editor');
			cmd::attach($data . 's', $data . '_list');
		}

		event::attach('register_caps', array('user', 'register_caps'));
		event::attach('register_caps', array('domain', 'register_caps'));

		event::attach('admin_menu', array('users_menu', 'admin_menu'));

		event::attach('preprocess', array('active_user', 'autologin'));
	} # load()
} # defaults
?>