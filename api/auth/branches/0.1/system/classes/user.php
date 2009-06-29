<?php
#
# MC User Data
# ------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class user extends data
{
	public static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
	} # init()


	#
	# User Object
	#


	#
	# __construct()
	#

	public function __construct($sql = null, $args = null)
	{
		if ( is_numeric($sql) )
		{
			$row = db::get_row("
				SELECT	*
				FROM	users
				WHERE	user_id = :user_id
				",
				array(
					'user_id' => intval($sql)
					)
				);
		}
		elseif ( is_string($sql) )
		{
			$row = db::get_row($sql, $args);
		}
		else
		{
			$row = $sql;
		}

		parent::__construct($row);

		$this->user_pass = '';
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		$field = $this->fields['user_id'] =& new field($this->user_id);
		$field->type = 'id';

		$field = $this->fields['user_key'] =& new textfield($this->user_key);
		$field->readonly = true;

		$field = $this->fields['user_name'] =& new textfield($this->user_name, '');
		$field->required = true;

		$this->fields['user_phone'] =& new textfield($this->user_phone, '');

		$field = $this->fields['user_email'] =& new textfield($this->user_email, '');
		$field->type = 'email';
		$field->required = true;

		$field = $this->fields['user_pass'] =& new textfield($this->user_pass, '');
		$field->type = 'password';

		$this->fields['is_admin'] =& new checkbox($this->is_admin, false);


		new event(__CLASS__ . '_map', $this);
	} # map()


	#
	# save()
	#

	public function save()
	{
		if ( !$this->user_id )
		{
			$this->user_id = db::get_var(
				"SELECT	next_node(:node_key);",
				array(
					'node_key' => @ $this->node_key,
					)
				);

			if ( $this->user_id === false )
			{
				$this->user_id = db::get_var("
					SELECT	user_id
					FROM	users
					INNER JOIN nodes
					ON nodes.node_id = users.user_id
					WHERE	node_key = :node_key
					;",
					array(
						'node_key' => $this->node_key,
						)
					);

				$message = self::$captions->get("request_timeout");
				new error($message);

				db::rollback();

				if ( isset($this->user_id) )
				{
					permalink::redirect(
						array(
							'cmd' => 'edit_user',
							'user_id' => $this->user_id,
							)
						);
				}
				else
				{
					permalink::redirect(
						array(
							'cmd' => 'new_user',
							)
						);
				}
			}



			db::query("
				INSERT INTO users (
						user_id,
						user_name,
						user_phone,
						user_email
						)
				VALUES (
						:user_id,
						:user_name,
						:user_phone,
						:user_email
						);
				",
				array(
					'user_id' => $this->user_id,
					'user_name' => $this->user_name,
					'user_phone' => $this->user_phone,
					'user_email' => $this->user_email,
					)
				);
		}
		else
		{
			db::query("
				UPDATE	users
				SET		user_name = :user_name,
						user_phone = :user_phone,
						user_email = :user_email
				WHERE	user_id = :user_id
				",
				array(
					'user_id' => $this->user_id,
					'user_name' => $this->user_name,
					'user_phone' => $this->user_phone,
					'user_email' => $this->user_email,
					)
				);
		}

		if ( $this->user_pass )
		{
			db::query("
				UPDATE	users
				SET		user_pass = md5(:user_pass)
				WHERE	user_id = :user_id
				",
				array(
					'user_id' => $this->user_id,
					'user_pass' => $this->user_pass,
					)
				);
		}

		new event(__CLASS__ . '_save', $this);

		if ( $this->user_id == active_user::get('user_id') )
		{
			active_user::refresh();
		}
	} # save()


	#
	# can()
	#

	public function can()
	{
		if ( !isset($this->user_id) )
		{
			return false;
		}
		elseif ( $this->is_admin )
		{
			return true;
		}
		else
		{
			if ( !isset($this->user_profiles) )
			{
				$this->get_permissions();
			}

			foreach ( func_get_args() as $cap )
			{
				if ( isset($this->user_profiles[$cap]) )
				{
					return true;
				}
			}

			foreach ( $this->user_profiles as &$profile )
			{
				foreach ( func_get_args() as $cap )
				{
					if ( $profile->can($cap) )
					{
						return true;
					}
				}
			}

			return false;
		}
	} # can()


	#
	# get_permissions()
	#

	public function get_permissions()
	{
		$this->user_profiles = array();

		$dbs = db::query("
			SELECT	profiles.profile_id,
					profile_key
			FROM	profiles
			INNER JOIN user2profile
			ON		user2profile.profile_id = profiles.profile_id
			WHERE	( profile_expires IS NULL OR profile_expires >= now() )
			AND		user_id = :user_id
			",
			array(
				'user_id' => $this->user_id,
				)
			);

		$dbs->bind('profile_key', $profile_key);

		while ( $row = $dbs->get_row() )
		{
			$this->user_profiles[$profile_key] = new profile($row);
		}
	} # get_permissions()


	#
	# register_caps()
	#

	public static function register_caps(&$caps)
	{
		args::merge(
			$caps,
			array(
				'manage_users' => self::$captions->get('manage_users'),
				)
			);
	} # register_caps()
} # user

user::init();
?>