<?php
#
# MC Doc Cat Editer
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class doc_cat_editor extends request
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('docs');

		event::attach('doc_cat_check_exists', array(__CLASS__, 'check_exists'));
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		# node data
		switch ( $args['cmd'] )
		{
		case 'new_doc_cat':
			if ( isset($args['cat_id']) )
			{
				permalink::redirect(array('cmd' => 'edit_doc_cat', 'cat_id' => $args['cat_id']));
			}
			elseif ( !active_user::can('manage_docs') )
			{
				new status_403($args);
			}
			break;

		case 'edit_doc_cat':
			if ( !isset($args['cat_id']) )
			{
				permalink::redirect(array('cmd' => 'new_doc_cat'));
			}
			elseif ( !active_user::can('manage_docs') )
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
		case 'new_doc_cat':
			$this->data =& new doc_cat;
			break;

		case 'edit_doc_cat':
			$this->data =& new doc_cat($this->args['cat_id']);

			if ( !$this->data->cat_id )
			{
				new status_404($this->args);
			}
		}


		# add fields
		foreach ( array('cat_key', 'cat_name', 'cat_version', 'cat_desc') as $key )
		{
			$this->data->fields[$key]->id = $key;
			$this->data->fields[$key]->name = $key;
			$this->data->fields[$key]->label = self::$captions->get($key);
			$this->data->fields[$key]->bind($this->args[$key]);
		}

		$this->data->fields['cat_desc']->style = 'height: 80px';

		if ( $this->cmd == 'new_doc_cat' )
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
		elseif ( $this->cmd == 'edit_doc_cat' )
		{
			$this->data->fields['cat_id']->required = true;
			$this->data->fields['cat_id']->name = 'cat_id';
			$this->data->fields['cat_id']->bind($this->args['cat_id']);
		}


		if ( active_user::can('manage_access') )
		{
			event::attach('doc_cat_save', array(__CLASS__, 'save_profiles'));

			$dbs = db::query("
				SELECT	profiles.profile_id,
						profiles.profile_name,
						CASE
						WHEN node2profile.profile_id IS NOT NULL
						THEN
							true
						ELSE
							false
						END as profile_is_active
				FROM	profiles
				LEFT JOIN node2profile
				ON		node2profile.profile_id = profiles.profile_id
				AND		node_id = :node_id
				ORDER BY lower(profile_name);
				",
				array(
					'node_id' => $this->data->cat_id,
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
		$this->data->sanitize();
		$this->data->validate();
		$this->data->check_exists();

		if ( !error::exists() )
		{
			db::start();
			$this->data->save();
			db::commit();

			$message = self::$captions->get('doc_cat_saved', array('cat_name' => $this->data->cat_name));
			new notice($message);

			permalink::redirect(array('cmd' => 'edit_doc_cat', 'cat_id' => $this->data->cat_id));
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
		case 'edit_doc_cat':
			echo $this->data->cat_name;
			break;

		case 'new_doc_cat':
			echo self::$captions->get('new_doc_cat');
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
		case 'new_doc_cat':
			echo $this->data->fields['node_key'];
			break;

		case 'edit_doc_cat':
			echo $this->data->fields['cat_id'];
			break;
		}

		echo '<h2>';
		$this->title();
		echo '</h2>';


		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('cat_overview') . '</h3>'
			. '<div class="text">' . $this->data->fields['cat_name'] . '</div>'
			. '<div class="text">' . $this->data->fields['cat_key'] . '</div>'
			. '<div class="text">' . $this->data->fields['cat_version'] . '</div>'
			. '</div>';

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('cat_desc') . '</h3>'
			. '<div class="textarea">' . $this->data->fields['cat_desc'] . '</div>'
			. '</div>';


		if ( active_user::can('manage_access') && isset($this->data->fields['profile']) )
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

		echo '<input type="submit" value="' . str::attr(self::$captions->get('save_doc_cat')) . '" />';

		echo '</div>'
			. '</div>';

		echo '</form>';
	} # html()


	#
	# check_exists()
	#

	public static function check_exists(&$doc_cat)
	{
		if ( $doc_cat->cat_id )
		{
			$cat_id = db::get_var("
				SELECT	cat_id
				FROM	doc_cats
				WHERE	( str2key(:cat_key1) IS NOT NULL AND cat_key = str2key(:cat_key2)
						OR str2key(:cat_key3) IS NULL AND cat_key = str2key(:cat_name) )
						AND cat_version = :cat_version
				;",
				array(
					'cat_key1' => $doc_cat->cat_key,
					'cat_key2' => $doc_cat->cat_key,
					'cat_key3' => $doc_cat->cat_key,
					'cat_name' => $doc_cat->cat_name,
					'cat_version' => $doc_cat->cat_version,
					)
				);

			if ( $cat_id && $cat_id != $doc_cat->cat_id )
			{
				$message = self::$captions->get('doc_cat_exists');
				new error($message);
			}
		}
		else
		{
			$dbs = db::query("
				SELECT	node_id,
						node_key
				FROM	nodes
				INNER JOIN doc_cats
				ON		doc_cats.cat_id = nodes.node_id
				WHERE	( str2key(:cat_key1) IS NOT NULL AND cat_key = str2key(:cat_key2)
						OR str2key(:cat_key3) IS NULL AND cat_key = str2key(:cat_name) )
						AND cat_version = :cat_version
				;",
				array(
					'cat_key1' => $doc_cat->cat_key,
					'cat_key2' => $doc_cat->cat_key,
					'cat_key3' => $doc_cat->cat_key,
					'cat_name' => $doc_cat->cat_name,
					'cat_version' => $doc_cat->cat_version,
					)
				);

			$dbs->bind('node_id', $node_id);
			$dbs->bind('node_key', $node_key);

			$dbs->get_row();

			if ( $node_key )
			{
				if ( $node_key != $doc_cat->node_key )
				{
					$message = self::$captions->get('doc_cat_exists');
					new error($message);
				}
				else
				{
					$doc_cat->cat_id = $node_id;
				}
			}
		}
	} # check_exists()


	#
	# save_profiles()
	#

	public static function save_profiles(&$node)
	{
		$dbs = db::query("
			SELECT	node2profile.profile_id
			FROM	node2profile
			WHERE	node_id = :node_id
			;",
			array(
				'node_id' => $node->cat_id,
				)
			);

		$dbs->bind('profile_id', $profile_id);

		$insert = array();
		$delete = array();

		while ( $dbs->get_row() )
		{
			$profiles[$profile_id] = true;

			if ( !$node->profile[$profile_id] )
			{
				$delete[] = $profile_id;
			}

			unset($node->profile[$profile_id]);
		}

		foreach ( array_keys($node->profile) as $profile_id )
		{
			if ( $node->profile[$profile_id] )
			{
				$insert[] = $profile_id;
			}
		}

		if ( !empty($insert) )
		{
			$dbs = db::prepare("
					INSERT INTO node2profile (
						node_id,
						profile_id
						)
					VALUES (
						:node_id,
						:profile_id
						);
					");

			$dbs->bind_var('node_id', $node->cat_id);
			$dbs->bind_var('profile_id', $profile_id);

			foreach ( $insert as $profile_id )
			{
				$dbs->exec();
			}
		}

		# deleted profiles
		if ( !empty($delete) )
		{
			$dbs = db::prepare("
				DELETE FROM node2profile
				WHERE	profile_id = :profile_id
				AND		node_id = :node_id
				;");

			$dbs->bind_var('node_id', $node->cat_id);
			$dbs->bind_var('profile_id', $profile_id);

			foreach ( $delete as $profile_id )
			{
				$dbs->exec();
			}
		}
	} # save_profiles()
} # doc_cat_editor

doc_cat_editor::init();
?>