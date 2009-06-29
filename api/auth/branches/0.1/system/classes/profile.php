<?php
#
# MC Profile Object
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class profile extends data
{
	public static $captions;
	public static $caps;
	public static $all_caps;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
		self::$caps =& options::get('caps');
		self::$all_caps =& options::get('all_caps');

		if ( !isset(self::$caps) )
		{
			self::$caps = array();
		}
	} # init()


	#
	# Profile Object
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
				FROM	profiles
				WHERE	profile_id = :profile_id
				",
				array(
					'profile_id' => intval($sql)
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
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		$field = $this->fields['profile_id'] =& new field($this->profile_id);
		$field->type = 'id';

		$field = $this->fields['profile_key'] =& new textfield($this->profile_key);

		$field = $this->fields['profile_name'] =& new textfield($this->profile_name, '');
		$field->required = true;

		$field = $this->fields['profile_desc'] =& new textarea($this->profile_desc);


		new event(__CLASS__ . '_map', $this);
	} # map()


	#
	# save()
	#

	public function save()
	{
		if ( !$this->profile_id )
		{
			$this->profile_id = db::get_var(
				"SELECT	next_node(:node_key);",
				array(
					'node_key' => @ $this->node_key,
					)
				);

			if ( $this->profile_id === false )
			{
				$this->profile_id = db::get_var("
					SELECT	profile_id
					FROM	profiles
					INNER JOIN nodes
					ON nodes.node_id = profiles.profile_id
					WHERE	node_key = :node_key
					;",
					array(
						'node_key' => $this->node_key,
						)
					);

				$message = self::$captions->get("request_timeout");
				new error($message);

				db::rollback();

				if ( isset($this->profile_id) )
				{
					permalink::redirect(
						array(
							'cmd' => 'edit_profile',
							'profile_id' => $this->profile_id,
							)
						);
				}
				else
				{
					permalink::redirect(
						array(
							'cmd' => 'new_profile',
							)
						);
				}
			}

			db::query("
				INSERT INTO profiles (
						profile_id,
						profile_key,
						profile_name,
						profile_desc
						)
				VALUES (
						:profile_id,
						:profile_key,
						:profile_name,
						:profile_desc
						);
				",
				array(
					'profile_id' => $this->profile_id,
					'profile_key' => $this->profile_key,
					'profile_name' => $this->profile_name,
					'profile_desc' => $this->profile_desc,
					)
				);
		}
		else
		{
			db::query("
				UPDATE	profiles
				SET		profile_key = :profile_key,
						profile_name = :profile_name,
						profile_desc = :profile_desc
				WHERE	profile_id = :profile_id
				",
				array(
					'profile_id' => $this->profile_id,
					'profile_key' => $this->profile_key,
					'profile_name' => $this->profile_name,
					'profile_desc' => $this->profile_desc,
					)
				);
		}

		$this->save_caps();

		new event(__CLASS__ . '_save', $this);
	} # save()


	#
	# can()
	#

	public function can()
	{
		if ( !isset($this->profile_id) )
		{
			return false;
		}
		else
		{
			foreach ( func_get_args() as $cap )
			{
				if ( @ self::$caps[$this->profile_id][$cap] )
				{
					return true;
				}
			}

			return false;
		}
	} # can()


	#
	# get_all_caps()
	#

	protected static function get_all_caps()
	{
		self::$all_caps = array();

		new event('register_caps', self::$all_caps);

		natcasesort(self::$all_caps);
	} # get_all_caps()


	#
	# get_caps()
	#

	public function get_caps()
	{
		self::get_all_caps();

		$caps = array();

		if ( isset($this->profile_id) )
		{
			foreach ( self::$all_caps as $cap => $name )
			{
				$caps[$cap]['key'] = $cap;
				$caps[$cap]['name'] = $name;
				$caps[$cap]['active'] = @ (bool) self::$caps[$this->profile_id][$cap];
			}
		}
		else
		{
			foreach ( self::$all_caps as $cap => $name )
			{
				$caps[$cap]['key'] = $cap;
				$caps[$cap]['name'] = $name;
				$caps[$cap]['active'] = false;
			}
		}

		return $caps;
	} # get_caps()


	#
	# save_caps()
	#

	public function save_caps()
	{
		if ( isset($this->caps) && isset($this->profile_id) )
		{
			@ self::$caps[$this->profile_id] = array_merge((array) self::$caps[$this->profile_id], $this->caps);
		}
	} # save_caps()
} # profile

profile::init();
?>