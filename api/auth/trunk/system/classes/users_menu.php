<?php
#
# MC Users Admin Menu
# -------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class users_menu
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
	# admin_menu()
	#

	public static function admin_menu()
	{
		admin_menu::add_item(
			'new',
			'user',
			self::$captions->get('user'),
			'new_user',
			'manage_users'
			);

		admin_menu::add_item(
			'manage',
			'users',
			self::$captions->get('users'),
			'users',
			'manage_users'
			);

		admin_menu::add_item(
			'new',
			'profile',
			self::$captions->get('profile'),
			'new_profile',
			'manage_profiles'
			);

		admin_menu::add_item(
			'manage',
			'profiles',
			self::$captions->get('profiles'),
			'profiles',
			'manage_profiles'
			);

		admin_menu::add_item(
			'new',
			'domain',
			self::$captions->get('domain'),
			'new_domain',
			'manage_domains'
			);

		admin_menu::add_item(
			'manage',
			'domains',
			self::$captions->get('domains'),
			'domains',
			'manage_domains'
			);
	} # admin_menu()
} # users_menu

users_menu::init();
?>