<?php
#
# MC User Editer
# --------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class user_editor extends request
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;

		event::attach('user_check_exists', array(__CLASS__, 'check_exists'));
		event::attach('user_send_password', array(__CLASS__, 'send_password'));
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		# node data
		switch ( $args['cmd'] )
		{
		case 'register':
			if ( isset($args['user_id']) )
			{
				permalink::redirect(array('cmd' => 'edit_user', 'user_id' => $args['user_id']));
			}
			elseif ( !active_user::is_guest() )
			{
				permalink::redirect(array('cmd' => 'new_user'));
			}
			elseif ( !options::get('allow_registrations') )
			{
				new status_403($args);
			}

			if ( !isset($args['redirect']) )
			{
				if ( isset($_SERVER['HTTP_REFERER']) )
				{
					$args['redirect'] = $_SERVER['HTTP_REFERER'];
				}
				else
				{
					$args['redirect'] = permalink::get(array('cmd' => 'profile'));
				}
			}
			break;

		case 'profile':
			if ( isset($args['user_id']) )
			{
				permalink::redirect(array('cmd' => 'edit_user', 'user_id' => $args['user_id']));
			}
			elseif ( active_user::is_guest() )
			{
				permalink::redirect(array('cmd' => 'login'));
			}
			break;

		case 'new_user':
			if ( isset($args['user_id']) )
			{
				permalink::redirect(array('cmd' => 'edit_user', 'user_id' => $args['user_id']));
			}
			elseif ( active_user::is_guest() && options::get('allow_registrations') )
			{
				permalink::redirect(array('cmd' => 'register'));
			}
			elseif ( !active_user::can('manage_users') )
			{
				new status_403($args);
			}
			break;

		case 'edit_user':
			if ( !isset($args['user_id']) )
			{
				permalink::redirect(array('cmd' => 'new_user'));
			}
			elseif ( active_user::get('user_id') == $args['user_id'] )
			{
				permalink::redirect(array('cmd' => 'profile'));
			}
			elseif ( !active_user::can('manage_users') )
			{
				new status_403($args);
			}
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
		case 'register':
		case 'new_user':
			$this->data =& new user;
			break;

		case 'profile':
			$this->data =& new user(active_user::get('user_id'));
			break;

		case 'edit_user':
			$this->data =& new user($this->args['user_id']);

			if ( !$this->data->user_id )
			{
				new status_404($this->args);
			}
		}


		# add fields
		foreach ( array('user_name', 'user_email', 'user_phone', 'user_email') as $key )
		{
			$this->data->fields[$key]->id = $key;
			$this->data->fields[$key]->name = $key;
			$this->data->fields[$key]->label = self::$captions->get($key);
			$this->data->fields[$key]->bind($this->args[$key]);
		}


		$field = $this->data->fields['user_pass1'] =& new textfield;
		if ( $this->cmd == 'register' || $this->cmd == 'new_user' )
		{
			$field->required = true;
		}
		$field->type = 'password';
		$field->id = 'user_pass1';
		$field->name = 'user_pass1';
		$field->label = self::$captions->get('user_pass');
		$field->bind($this->args['user_pass1']);


		$field = $this->data->fields['user_pass2'] =& new textfield;
		$field->type = 'password';
		$field->id = 'user_pass2';
		$field->name = 'user_pass2';
		$field->label = self::$captions->get('confirm_pass');
		$field->bind($this->args['user_pass2']);

		$this->data->fields['user_pass']->match(
				$this->data->fields['user_pass1'],
				$this->data->fields['user_pass2']
				);


		if ( $this->cmd == 'register' || $this->cmd == 'new_user' )
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
		elseif ( $this->cmd == 'edit_user' )
		{
			$this->data->fields['user_id']->required = true;
			$this->data->fields['user_id']->name = 'user_id';
			$this->data->fields['user_id']->bind($this->args['user_id']);
		}


		if ( active_user::can('manage_users') && $this->cmd != 'profile' )
		{

			$dbs = db::query("
				SELECT	profiles.profile_id,
						profiles.profile_name,
						CASE
						WHEN user2profile.profile_id IS NOT NULL
						THEN
							true
						ELSE
							false
						END as profile_is_active,
						user2profile.profile_expires
				FROM	profiles
				LEFT JOIN user2profile
				ON		user2profile.profile_id = profiles.profile_id
				AND		user_id = :user_id
				ORDER BY lower(profile_name);
				",
				array(
					'user_id' => $this->data->user_id,
					)
				);

			$dbs->bind('profile_id', $profile_id);
			$dbs->bind('profile_name', $profile_name);
			$dbs->bind('profile_is_active', $profile_is_active);
			$dbs->bind('profile_expires', $profile_expires);

			while ( $dbs->get_row() )
			{
				if ( isset($profile_expires) )
				{
					$profile_expires = date('m/d/Y', strtotime($profile_expires));
				}

				$field = $this->data->fields['profile'][$profile_id] =& new checkbox($this->data->profile[$profile_id], $profile_is_active);
				$field->id = 'profile__' . $profile_id;
				$field->name = 'profile[' . $profile_id . ']';
				$field->label = $profile_name;
				if ( active_user::is_admin() )
				{
					$field->bind($this->args['profile'][$profile_id]);
				}
				else
				{
					$field->disabled = true;
				}

				$field = $this->data->fields['profile_expires'][$profile_id] =& new textfield($this->data->profile_expires[$profile_id], $profile_expires);
				$field->type = 'date';
				$field->id = 'profile_expires__' . $profile_id;
				$field->name = 'profile_expires[' . $profile_id . ']';
				if ( active_user::is_admin() )
				{
					$field->bind($this->args['profile_expires'][$profile_id]);
				}
				else
				{
					$field->disabled = true;
				}
			}

			if ( active_user::is_admin() )
			{
				event::attach('user_save', array(__CLASS__, 'save_profiles'));
			}
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
			switch ( $this->cmd )
			{
			case 'profile':
				db::start();
				$this->data->save();
				db::commit();

				$message = self::$captions->get('profile_saved');
				new notice($message);

				permalink::redirect(array('cmd' => 'profile'));
				break;

			case 'edit_user':
				db::start();
				$this->data->save();
				db::commit();

				$message = self::$captions->get('user_saved', array('user_name' => $this->data->user_name));
				new notice($message);

				permalink::redirect(array('cmd' => 'edit_user', 'user_id' => $this->data->user_id));
				break;

			case 'new_user':
				db::start();
				$this->data->save();
				db::commit();

				$this->data->send_password();

				$message = self::$captions->get('user_saved', array('user_name' => $this->data->user_name));
				new notice($message);

				permalink::redirect(array('cmd' => 'edit_user', 'user_id' => $this->data->user_id));
				break;

			case 'register':
				db::start();
				$this->data->save();
				db::commit();

				$this->data->send_password();

				active_user::login(new user($this->data->user_id));

				$message = self::$captions->get('welcome', array('user_name' => $this->data->user_name));
				new notice($message);

				permalink::redirect($this->redirect);
				break;
			}
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
		case 'edit_user':
		case 'profile':
			echo $this->data->user_name;
			break;

		case 'new_user':
			echo self::$captions->get('new_user');
			break;

		case 'register':
			echo self::$captions->get('register');
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
			. ( isset($this->redirect)
				? ( '<input type="hidden" name="redirect" value="' . str::attr($this->redirect) . '" />' )
				: ''
				)
			;

		switch ( $this->cmd )
		{
		case 'register':
		case 'new_user':
			echo $this->data->fields['node_key'];
			break;

		case 'edit_user':
			echo $this->data->fields['user_id'];
			break;
		}

		echo '<h2>';
		$this->title();
		echo '</h2>';


		switch ( $this->cmd )
		{
		case 'profile':
			echo '<div class="fieldset">'
				. '<h3>' . self::$captions->get('api_key') . '</h3>'
				. '<div class="text"><div class="field">' . $this->data->fields['user_key'] . '</div></div>'
				. '</div>';
			break;

		case 'edit_user':
			if ( active_user::is_admin() )
			{
				echo '<div class="fieldset">'
					. '<h3>' . self::$captions->get('api_key') . '</h3>'
					. '<div class="text"><div class="field">' . $this->data->fields['user_key'] . '</div></div>'
					. '</div>';
			}
			break;
		}


		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('contact_details') . '</h3>'
			. '<div class="text">' . $this->data->fields['user_name'] . '</div>'
			. '<div class="text">' . $this->data->fields['user_phone'] . '</div>'
			. '<div class="text">' . $this->data->fields['user_email'] . '</div>'
			. '</div>';


		echo '<div class="fieldset">'
			. '<h3>'
				. ( in_array($this->cmd, array('register', 'new_user') )
					? self::$captions->get('user_pass')
					: self::$captions->get('new_pass')
					)
				. '</h3>'
			. '<div class="text">' . $this->data->fields['user_pass1'] . '</div>'
			. '<div class="text">' . $this->data->fields['user_pass2'] . '</div>'
			. '</div>';


		if ( active_user::can('manage_users') && isset($this->data->fields['profile']) )
		{
			echo '<div class="fieldset">'
				. '<h3>' . self::$captions->get('profiles') . '</h3>'
				. '<table>'
				;

			echo '<tr>'
				. '<th>&nbsp;</th>'
				. '<th>' . self::$captions->get('expires') . '</th>'
				. '</tr>';

			foreach ( array_keys((array) $this->data->fields['profile']) as $key )
			{
				echo '<tr>'
					. '<td class="checkbox">' . $this->data->fields['profile'][$key] . '</td>'
					. '<td class="text">' . $this->data->fields['profile_expires'][$key] . '</td>'
					. '</tr>';
			}

			echo '</table>'
				. '</div>';
		}


		new event(__CLASS__ . '_html', $this);


		echo '<div class="fieldset">'
			. '<div class="button">';

		echo '<input type="submit" value="';
		switch ( $this->cmd )
		{
		case 'edit_user':
		case 'new_user':
			echo str::attr(self::$captions->get('save_user'));
			break;

		case 'profile':
			echo str::attr(self::$captions->get('save_profile'));
			break;

		case 'register':
			echo str::attr(self::$captions->get('register'));
			break;
		}
		echo '" />';

		echo '</div>'
			. '</div>';

		echo '</form>';
	} # html()


	#
	# check_exists()
	#

	public static function check_exists(&$user)
	{
		if ( $user->user_id )
		{
			$user_key = db::get_var("
				SELECT get_user_key(:user_email);",
				array('user_email' => $user->user_email)
				);

			if ( $user_key && $user_key != $user->user_key )
			{
				$message = self::$captions->get('user_exists');
				new error($message);
			}
		}
		else
		{
			$dbs = db::query("
				SELECT	node_id,
						node_key
				FROM	nodes
				INNER JOIN users
				ON		users.user_id = nodes.node_id
				WHERE	node_key = get_user_key(:user_email)
				;",
				array('user_email' => $user->user_email)
				);

			$dbs->bind('node_id', $node_id);
			$dbs->bind('node_key', $node_key);

			$dbs->get_row();

			if ( $node_key )
			{
				if ( $node_key != $user->node_key )
				{
					$message = self::$captions->get('user_exists');
					new error($message);
				}
				else
				{
					$user->user_id = $node_id;
				}
			}
		}
	} # check_exists()


	#
	# send_password()
	#

	public static function send_password(&$user)
	{
		$email = new email;
		$email->to = $user->user_email;

		$email->title = self::$captions->get(
			'send_user_password_title',
			array(
				'site_name' => options::get('site_name')
				)
			);

		$email->message = self::$captions->get(
			'send_user_password_message',
			array(
				'site_name' => options::get('site_name'),
				'site_url' => site_url . '/',
				'site_signature' => options::get('site_signature'),
				'user_name' => $user->user_name,
				'user_email' => $user->user_email,
				'user_pass' => $user->user_pass,
				)
			);

		$email->send();
	} # send_password()


	#
	# save_profiles()
	#

	public static function save_profiles(&$user)
	{
		$dbs = db::query("
			SELECT	user2profile.profile_id,
					user2profile.profile_expires
			FROM	user2profile
			WHERE	user_id = :user_id
			;",
			array(
				'user_id' => $user->user_id,
				)
			);

		$dbs->bind('profile_id', $profile_id);
		$dbs->bind('profile_expires', $profile_expires);

		$insert = array();
		$delete = array();
		$update = array();

		while ( $dbs->get_row() )
		{
			$profiles[$profile_id] = true;
			$profiles_expire[$profile_id] = $profile_expires;

			if ( !$user->profile[$profile_id] )
			{
				$delete[] = $profile_id;
				unset($user->profile[$profile_id]);
			}
			else
			{
				if ( strtotime($profile_expires) != strtotime($user->profile_expires[$profile_id]) )
				{
					$update[] = $profile_id;
				}

				unset($user->profile[$profile_id]);
			}
		}

		foreach ( array_keys($user->profile) as $profile_id )
		{
			if ( $user->profile[$profile_id] )
			{
				$insert[] = $profile_id;
			}
		}

		if ( !empty($insert) )
		{
			$dbs = db::prepare("
				INSERT INTO user2profile (
					user_id,
					profile_id,
					profile_expires
					)
				VALUES (
					:user_id,
					:profile_id,
					:profile_expires
					);
				");

			$dbs->bind_var('user_id', $user->user_id);
			$dbs->bind_var('profile_id', $profile_id);
			$dbs->bind_var('profile_expires', $profile_expires);

			foreach ( $insert as $profile_id )
			{
				$profile_expires = $user->profile_expires[$profile_id];

				$dbs->exec();
			}
		}

		if ( !empty($delete) )
		{
			$dbs = db::prepare("
				DELETE FROM user2profile
				WHERE	profile_id = :profile_id
				AND		user_id = :user_id
				;");

			$dbs->bind_var('user_id', $user->user_id);
			$dbs->bind_var('profile_id', $profile_id);

			foreach ( $delete as $profile_id )
			{
				$dbs->exec();
			}
		}

		if ( !empty($update) )
		{
			$dbs = db::prepare("
				UPDATE	user2profile
				SET		profile_expires = :profile_expires
				WHERE	user_id = :user_id
				AND		profile_id = :profile_id
				;");

			$dbs->bind_var('user_id', $user->user_id);
			$dbs->bind_var('profile_id', $profile_id);
			$dbs->bind_var('profile_expires', $profile_expires);

			foreach ( $update as $profile_id )
			{
				$profile_expires = $user->profile_expires[$profile_id];

				$dbs->exec();
			}
		}
	} # save_profiles()
} # user_editor

user_editor::init();
?>