<?php
#
# MC Profile Editer
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class profile_editor extends request
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
		# node data
		switch ( $args['cmd'] )
		{
		case 'new_profile':
			if ( isset($args['profile_id']) )
			{
				permalink::redirect(array('cmd' => 'edit_profile', 'profile_id' => $args['profile_id']));
			}
			elseif ( !active_user::is_admin() )
			{
				new status_403($args);
			}

			event::attach('profile_check_exists', array(__CLASS__, 'check_exists'));
			break;

		case 'edit_profile':
			if ( !isset($args['profile_id']) )
			{
				permalink::redirect(array('cmd' => 'new_profile'));
			}
			elseif ( !active_user::is_admin() )
			{
				new status_403($args);
			}

			event::attach('profile_check_exists', array(__CLASS__, 'check_exists'));
			break;

		case 'delete_profile':
			if ( !isset($args['profile_id']) )
			{
				permalink::redirect(array('cmd' => 'new_profile'));
			}
			elseif ( !active_user::is_admin() )
			{
				new status_403($args);
			}

			event::attach('profile_check_exists', array(__CLASS__, 'check_exists'));
			break;
		}

		parent::__construct($args);
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		# create data

		switch ( $this->cmd )
		{
		case 'new_profile':
			$this->data =& new profile;
			break;

		case 'edit_profile':
			$this->data =& new profile($this->args['profile_id']);

			if ( !$this->data->profile_id )
			{
				new status_404($this->args);
			}
		}


		# add fields
		foreach ( array('profile_key', 'profile_name', 'profile_desc') as $key )
		{
			$this->data->fields[$key]->id = $key;
			$this->data->fields[$key]->name = $key;
			$this->data->fields[$key]->label = self::$captions->get($key);
			$this->data->fields[$key]->bind($this->args[$key]);
		}

		$this->data->fields['profile_desc']->style = 'height: 80px';

		if ( $this->cmd == 'new_profile' )
		{
			$field = $this->data->fields['node_key'] =& new field($this->data->node_key);
			$field->required = true;
			$field->name = 'node_key';
			$field->value =& $this->data->node_key;
			$field->bind($this->args['node_key']);

			if ( !self::is_post() )
			{
				$this->data->node_key = db::get_var("SELECT next_hash();");
			}
		}
		elseif ( $this->cmd == 'edit_profile' )
		{
			$this->data->fields['profile_id']->required = true;
			$this->data->fields['profile_id']->name = 'profile_id';
			$this->data->fields['profile_id']->bind($this->args['profile_id']);
		}

		foreach ( $this->data->get_caps() as $cap )
		{
			$field = $this->data->fields['caps'][$cap['key']] =& new checkbox($this->data->caps[$cap['key']], $cap['active']);
			$field->id = 'caps__' . $cap['key'];
			$field->name = 'caps[' . $cap['key'] . ']';
			$field->label = $cap['name'];
			$field->bind($this->args['caps'][$cap['key']]);
		}
	} # map()


	#
	# exec()
	#

	public function exec()
	{
		$this->data->sanitize();
		$this->data->validate();
		$this->data->check_exists();

		if ( !error::exists() )
		{
			db::start();
			$this->data->save();
			db::commit();

			$message = self::$captions->get('profile_saved', array('profile_name' => $this->data->profile_name));
			new notice($message);

			permalink::redirect(array('cmd' => 'edit_profile', 'profile_id' => $this->data->profile_id));
		}
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
		switch ( $this->cmd )
		{
		case 'edit_profile':
			echo $this->data->profile_name;
			break;

		case 'new_profile':
			echo self::$captions->get('new_profile');
			break;
		}
	} # title()


	#
	# html()
	#

	public function html()
	{
		echo '<form method="post"'
				. ' action="' . ( iis ? ( mc_url . '/index.php' ) : '' ) . '"'
				. '>'
			. '<input type="hidden" name="cmd" value="' . str::attr($this->cmd) . '" />'
			;

		switch ( $this->cmd )
		{
		case 'new_profile':
			echo $this->data->fields['node_key'];
			break;

		case 'edit_profile':
			echo $this->data->fields['profile_id'];
			break;
		}

		echo '<h2>';
		$this->title();
		echo '</h2>';


		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('profile_overview') . '</h3>'
			. '<div class="text">' . $this->data->fields['profile_name'] . '</div>'
			. '<div class="text">' . $this->data->fields['profile_key'] . '</div>'
			. '</div>';

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('profile_desc') . '</h3>'
			. '<div class="textarea">' . $this->data->fields['profile_desc'] . '</div>'
			. '</div>';


		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('caps') . '</h3>';

		foreach ( $this->data->fields['caps'] as &$cap  )
		{
			echo '<div class="checkbox">' . $cap . '</div>';
		}

		echo '</div>';


		new event(__CLASS__ . '_html', $this);


		echo '<div class="fieldset">'
			. '<div class="button">';

		echo '<input type="submit" value="' . str::attr(self::$captions->get('save_profile')) . '" />';

		echo '</div>'
			. '</div>';
		
		$active_users = db::get_col("
			SELECT	user_email
			FROM	users
			JOIN	user2profile
			ON		user2profile.user_id = users.user_id
			AND		user2profile.profile_id = :profile_id
			WHERE	profile_expires IS NULL
			OR		date_trunc('day', profile_expires) >= now()::date
			ORDER BY user_email
			", array(
				'profile_id' => $this->data->profile_id,
				)
			);
		
		$expired_users = db::get_col("
			SELECT	user_email
			FROM	users
			JOIN	user2profile
			ON		user2profile.user_id = users.user_id
			AND		user2profile.profile_id = :profile_id
			WHERE	date_trunc('day', profile_expires) < now()::date
			ORDER BY user_email
			", array(
				'profile_id' => $this->data->profile_id,
				)
			);
		
		echo '<div class="fieldset">'
			. '<h3>Current Users (' . sizeof($active_users) . ')</h3>'
			. '<div class="textarea">'
				. '<textarea style="width: 98%; height: 80px; overflow-y: scroll;">'
				. implode("\n", $active_users)
				. '</textarea>'
				. '</div>'
			. '<h3>Expired Users (' . sizeof($expired_users) . ')</h3>'
			. '<div class="textarea">'
				. '<textarea style="width: 98%; height: 80px; overflow-y: scroll;">'
				. implode("\n", $expired_users)
				. '</textarea>'
				. '</div>'
			. '</div>';
		
		echo '</form>';
	} # html()


	#
	# check_exists()
	#

	public static function check_exists(&$profile)
	{
		if ( $profile->profile_id )
		{
			$profile_id = db::get_var("
				SELECT	profile_id
				FROM	profiles
				WHERE	( str2key(:profile_key1) IS NOT NULL AND profile_key = str2key(:profile_key2)
						OR str2key(:profile_key3) IS NULL AND profile_key = str2key(:profile_name1) )
						OR str2field(:profile_name2) = profile_name
				;",
				array(
					'profile_key1' => $profile->profile_key,
					'profile_key2' => $profile->profile_key,
					'profile_key3' => $profile->profile_key,
					'profile_name1' => $profile->profile_name,
					'profile_name2' => $profile->profile_name,
					)
				);

			if ( $profile_id && $profile_id != $profile->profile_id )
			{
				$message = self::$captions->get('profile_exists');
				new error($message);
			}
		}
		else
		{
			$dbs = db::query("
				SELECT	node_id,
						node_key
				FROM	nodes
				INNER JOIN profiles
				ON		profiles.profile_id = nodes.node_id
				WHERE	( str2key(:profile_key1) IS NOT NULL AND profile_key = str2key(:profile_key2)
						OR str2key(:profile_key3) IS NULL AND profile_key = str2key(:profile_name1) )
						OR str2field(:profile_name2) = profile_name
				;",
				array(
					'profile_key1' => $profile->profile_key,
					'profile_key2' => $profile->profile_key,
					'profile_key3' => $profile->profile_key,
					'profile_name1' => $profile->profile_name,
					'profile_name2' => $profile->profile_name,
					)
				);

			$dbs->bind('node_id', $node_id);
			$dbs->bind('node_key', $node_key);

			$dbs->get_row();

			if ( $node_key )
			{
				if ( $node_key != $profile->node_key )
				{
					$message = self::$captions->get('profile_exists');
					new error($message);
				}
				else
				{
					$profile->profile_id = $node_id;
				}
			}
		}
	} # check_exists()
} # profile_editor

profile_editor::init();
?>