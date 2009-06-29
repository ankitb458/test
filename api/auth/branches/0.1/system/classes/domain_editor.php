<?php
#
# MC Domain Editer
# ----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class domain_editor extends request
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
		case 'new_domain':
			if ( isset($args['domain_id']) )
			{
				permalink::redirect(array('cmd' => 'edit_domain', 'domain_id' => $args['domain_id']));
			}
			elseif ( !active_user::can('manage_access') )
			{
				new status_403($args);
			}

			event::attach('domain_check_exists', array(__CLASS__, 'check_exists'));
			event::attach('domain_save', array(__CLASS__, 'save_domain'));
			event::attach('domain_save', array(__CLASS__, 'save_profiles'));
			break;

		case 'edit_domain':
			if ( !isset($args['domain_id']) )
			{
				permalink::redirect(array('cmd' => 'new_domain'));
			}
			elseif ( !active_user::can('manage_access') )
			{
				new status_403($args);
			}

			event::attach('domain_check_exists', array(__CLASS__, 'check_exists'));
			event::attach('domain_save', array(__CLASS__, 'save_domain'));
			event::attach('domain_save', array(__CLASS__, 'save_profiles'));
			break;

		case 'delete_domain':
			if ( !isset($args['domain_id']) )
			{
				permalink::redirect(array('cmd' => 'new_domain'));
			}
			elseif ( !active_user::can('manage_access') )
			{
				new status_403($args);
			}

			event::attach('domain_delete', array(__CLASS__, 'delete_domain'));
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
		case 'new_domain':
			$this->data =& new domain;
			break;

		case 'edit_domain':
		case 'delete_domain':
			$this->data =& new domain($this->args['domain_id']);

			if ( !$this->data->domain_id )
			{
				new status_404($this->args);
			}
		}


		if ( $this->cmd == 'new_domain' )
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
		else
		{
			$this->data->fields['domain_id']->required = true;
			$this->data->fields['domain_id']->name = 'domain_id';
			$this->data->fields['domain_id']->bind($this->args['domain_id']);
		}


		if ( $this->cmd != 'delete_domain' )
		{
			# add fields
			foreach ( array('domain_name', 'domain_desc', 'domain_urls') as $key )
			{
				$this->data->fields[$key]->id = $key;
				$this->data->fields[$key]->name = $key;
				$this->data->fields[$key]->label = self::$captions->get($key);
				$this->data->fields[$key]->bind($this->args[$key]);
			}

			$this->data->fields['domain_desc']->style = 'height: 80px';
			$this->data->fields['domain_urls']->style = 'height: 120px';

			$dbs = db::query("
				SELECT	profiles.profile_id,
						profiles.profile_name,
						CASE
						WHEN profile2domain.profile_id IS NOT NULL
						THEN
							true
						ELSE
							false
						END as profile_is_active
				FROM	profiles
				LEFT JOIN profile2domain
				ON		profile2domain.profile_id = profiles.profile_id
				AND		domain_id = :domain_id
				ORDER BY lower(profile_name);
				",
				array(
					'domain_id' => $this->data->domain_id,
					)
				);

			$dbs->bind('profile_id', $profile_id);
			$dbs->bind('profile_name', $profile_name);
			$dbs->bind('profile_is_active', $profile_is_active);

			while ( $row = $dbs->get_row() )
			{
				$field = $this->data->fields['profile'][$profile_id] =& new checkbox($this->data->profile[$profile_id], $profile_is_active);
				$field->id = 'profile__' . $profile_id;
				$field->label = $profile_name;

				$profile =& new profile($row);

				if ( $profile->can('manage_access') )
				{
					$field->value = true;
					$field->disabled = true;
				}
				else
				{
					$field->name = 'profile[' . $profile_id . ']';
					$field->bind($this->args['profile'][$profile_id]);
				}
			}
		}
	} # map()


	#
	# exec()
	#

	public function exec()
	{
		if ( $this->cmd == 'delete_domain' )
		{
			$this->data->delete();

			permalink::redirect(array('cmd' => 'domains'));
		}

		$this->data->sanitize();
		$this->data->validate();
		$this->data->check_exists();

		if ( !error::exists() )
		{
			db::start();
			$this->data->save();
			db::commit();

			$message = self::$captions->get('domain_saved', array('domain_name' => $this->data->domain_name));
			new notice($message);

			permalink::redirect(array('cmd' => 'edit_domain', 'domain_id' => $this->data->domain_id));
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
		case 'edit_domain':
			echo $this->data->domain_name;
			break;

		case 'new_domain':
			echo self::$captions->get('new_domain');
			break;
		}
	} # title()


	#
	# html()
	#

	public function html()
	{
		if ( $this->cmd == 'edit_domain' )
		{
			echo '<form method="post"'
					. ' action="' . ( iis ? ( mc_url . '/index.php' ) : '' ) . '"'
					. '>'
				;

			echo $this->data->fields['domain_id'];

			echo '<div class="delete">';

			echo '<button'
				. ' type="submit"'
				. ' name="cmd"'
				. ' value="delete_domain"'
				. ' tabindex="-1"'
				. '>'
				. '<b>' . self::$captions->get('delete') . '</b>'
				. '</button>';

			echo '</div>';
			echo '</form>';
		}

		echo '<form method="post"'
				. ' action="' . ( iis ? ( mc_url . '/index.php' ) : '' ) . '"'
				. '>'
			#. '<input type="hidden" name="cmd" value="' . str::attr($this->cmd) . '" />'
			;

		switch ( $this->cmd )
		{
		case 'new_domain':
			echo $this->data->fields['node_key'];
			break;

		case 'edit_domain':
			echo $this->data->fields['domain_id'];
			break;
		}

		echo '<h2>';
		$this->title();
		echo '</h2>';


		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('domain_overview') . '</h3>'
			. '<div class="text">' . $this->data->fields['domain_name'] . '</div>'
			. '</div>';

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('domain_desc') . '</h3>'
			. '<div class="textarea">' . $this->data->fields['domain_desc'] . '</div>'
			. '</div>';

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('domain_urls') . '</h3>'
			. '<div class="textarea">' . $this->data->fields['domain_urls'] . '</div>'
			. '</div>';


		if ( isset($this->data->fields['profile']) )
		{
			echo '<div class="fieldset">'
				. '<h3>' . self::$captions->get('restrict_access') . '</h3>'
				;

			foreach ( @ array_keys((array) $this->data->fields['profile']) as $key )
			{
				echo '<div class="checkbox">' . $this->data->fields['profile'][$key] . '</div>';
			}

			echo '</div>';
		}


		new event(__CLASS__ . '_html', $this);


		echo '<div class="fieldset">'
			. '<div class="button">';

		echo '<button type="submit"'
			. ' name="cmd"'
			. ' value="' . str::attr($this->cmd) . '"'
			. '>'
			. self::$captions->get('save_domain')
			. '</button>';

		echo '</div>'
			. '</div>';

		echo '</form>';
	} # html()


	#
	# check_exists()
	#

	public static function check_exists(&$domain)
	{
		if ( $domain->domain_id )
		{
			$domain_id = db::get_var("
				SELECT	domain_id
				FROM	domains
				WHERE	domain_name = str2field(:domain_name)
				;",
				array(
					'domain_name' => $domain->domain_name,
					)
				);

			if ( $domain_id && $domain_id != $domain->domain_id )
			{
				$message = self::$captions->get('domain_exists');
				new error($message);
			}
		}
		else
		{
			$dbs = db::query("
				SELECT	node_id,
						node_key
				FROM	nodes
				INNER JOIN domains
				ON		domains.domain_id = nodes.node_id
				WHERE	domain_name = str2field(:domain_name)
				;",
				array(
					'domain_name' => $domain->domain_name,
					)
				);

			$dbs->bind('node_id', $node_id);
			$dbs->bind('node_key', $node_key);

			$dbs->get_row();

			if ( $node_key )
			{
				if ( $node_key != $domain->node_key )
				{
					$message = self::$captions->get('domain_exists');
					new error($message);
				}
				else
				{
					$domain->domain_id = $node_id;
				}
			}
		}
	} # check_exists()


	#
	# save_domain()
	#

	public static function save_domain(&$domain)
	{
		if ( !$domain->domain_id )
		{
			$domain->domain_id = db::get_var(
				"SELECT	next_node(:node_key);",
				array(
					'node_key' => $domain->node_key,
					)
				);

			if ( $domain->domain_id === false )
			{
				$domain->domain_id = db::get_var("
					SELECT	domain_id
					FROM	domains
					INNER JOIN nodes
					ON nodes.node_id = domains.domain_id
					WHERE	node_key = :node_key
					;",
					array(
						'node_key' => $domain->node_key,
						)
					);

				$message = self::$captions->get("request_timeout");
				new error($message);

				db::rollback();

				if ( isset($domain->domain_id) )
				{
					permalink::redirect(
						array(
							'cmd' => 'edit_domain',
							'domain_id' => $domain->domain_id,
							)
						);
				}
				else
				{
					permalink::redirect(
						array(
							'cmd' => 'new_domain',
							)
						);
				}
			}

			db::query("
				INSERT INTO domains (
						domain_id,
						domain_name,
						domain_desc,
						domain_urls
						)
				VALUES (
						:domain_id,
						:domain_name,
						:domain_desc,
						:domain_urls
						);
				",
				array(
					'domain_id' => $domain->domain_id,
					'domain_name' => $domain->domain_name,
					'domain_desc' => $domain->domain_desc,
					'domain_urls' => $domain->domain_urls,
					)
				);
		}
		else
		{
			db::query("
				UPDATE	domains
				SET		domain_name = :domain_name,
						domain_desc = :domain_desc,
						domain_urls = :domain_urls
				WHERE	domain_id = :domain_id
				",
				array(
					'domain_id' => $domain->domain_id,
					'domain_name' => $domain->domain_name,
					'domain_desc' => $domain->domain_desc,
					'domain_urls' => $domain->domain_urls,
					)
				);
		}

		event::attach('shutdown', array('domain', 'cache_restricted_urls'));
	} # save_domain()


	#
	# save_profiles()
	#

	public static function save_profiles(&$domain)
	{
		$dbs = db::query("
			SELECT	profile2domain.profile_id
			FROM	profile2domain
			WHERE	domain_id = :domain_id
			;",
			array(
				'domain_id' => $domain->domain_id,
				)
			);

		$dbs->bind('profile_id', $profile_id);

		$insert = array();
		$delete = array();

		while ( $dbs->get_row() )
		{
			$profiles[$profile_id] = true;

			if ( !$domain->profile[$profile_id] )
			{
				$delete[] = $profile_id;
			}

			unset($domain->profile[$profile_id]);
		}

		foreach ( array_keys($domain->profile) as $profile_id )
		{
			if ( $domain->profile[$profile_id] )
			{
				$insert[] = $profile_id;
			}
		}

		if ( !empty($insert) )
		{
			$dbs = db::prepare("
				INSERT INTO profile2domain (
					domain_id,
					profile_id
					)
				VALUES (
					:domain_id,
					:profile_id
					);
				");

			$dbs->bind_var('domain_id', $domain->domain_id);
			$dbs->bind_var('profile_id', $profile_id);

			foreach ( $insert as $profile_id )
			{
				$dbs->exec();
			}
		}

		if ( !empty($delete) )
		{
			$dbs = db::prepare("
				DELETE FROM profile2domain
				WHERE	domain_id = :domain_id
				AND		profile_id = :profile_id
				;");

			$dbs->bind_var('domain_id', $domain->domain_id);
			$dbs->bind_var('profile_id', $profile_id);

			foreach ( $delete as $profile_id )
			{
				$dbs->exec();
			}
		}
	} # save_profiles()


	#
	# delete()
	#

	public static function delete_domain(&$domain)
	{
		db::start();
		db::query("
			DELETE FROM domains
			WHERE	domain_id = :domain_id
			",
			array(
				'domain_id' => $domain->domain_id,
				)
			);
		db::commit();

		$message = self::$captions->get('node_deleted', array('node_name' => $domain->domain_name));
		new notice($message);
	} # delete()
} # domain_editor

domain_editor::init();
?>