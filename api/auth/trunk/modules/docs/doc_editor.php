<?php
#
# MC Doc Editer
# -------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class doc_editor extends request
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('docs');

		event::attach('doc_check_exists', array(__CLASS__, 'check_exists'));
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		# node data
		switch ( $args['cmd'] )
		{
		case 'new_doc':
			if ( isset($args['doc_id']) || isset($args['rev_id']) )
			{
				@ permalink::redirect(array('cmd' => 'edit_doc', 'doc_id' => $args['doc_id'], 'rev_id' => $args['rev_id']));
			}
			elseif ( !active_user::can('manage_docs') )
			{
				new status_403($args);
			}
			break;

		case 'edit_doc':
			if ( !isset($args['doc_id']) && !isset($args['rev_id']) )
			{
				permalink::redirect(array('cmd' => 'new_doc'));
			}
			elseif ( !active_user::can('manage_docs', 'edit_docs') )
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
		case 'new_doc':
			$this->data =& new doc;
			break;

		case 'edit_doc':
			if ( isset($this->args['rev_id']) )
			{
				$this->data =& new doc("
					SELECT	*
					FROM	doc_revs
					INNER JOIN doc_cats
					ON		doc_cats.cat_id = doc_revs.cat_id
					LEFT JOIN users
					ON		users.user_id = doc_revs.author_id
					WHERE	rev_id = :rev_id
					",
					array(
						'rev_id' => $this->args['rev_id'],
						)
					);
			}
			elseif ( isset($this->args['doc_id']) )
			{
				$this->data =& new doc("
					SELECT	*
					FROM	doc_revs
					INNER JOIN doc_cats
					ON		doc_cats.cat_id = doc_revs.cat_id
					LEFT JOIN users
					ON		users.user_id = doc_revs.author_id
					WHERE	doc_id = :doc_id
					ORDER BY rev_id DESC
					LIMIT 1
					",
					array(
						'doc_id' => $this->args['doc_id'],
						)
					);
			}

			if ( !$this->data->doc_id )
			{
				new status_404($this->args);
			}
		}


		# add fields
		foreach ( array('doc_key', 'doc_name', 'doc_status', 'doc_excerpt', 'doc_content') as $key )
		{
			$this->data->fields[$key]->id = $key;
			$this->data->fields[$key]->name = $key;
			$this->data->fields[$key]->label = self::$captions->get($key);
			$this->data->fields[$key]->bind($this->args[$key]);
		}

		$this->data->fields['cat_id']->id = 'cat_id';
		$this->data->fields['cat_id']->label = self::$captions->get('doc_cat');

		$this->data->fields['doc_excerpt']->style = 'height: 80px';
		$this->data->fields['doc_content']->style = 'height: 240px';
		$this->data->fields['doc_content']->allow_tags = true;

		if ( $this->cmd == 'new_doc' )
		{
			$field = $this->data->fields['node_key'] =& new field($this->data->node_key);
			$field->required = true;
			$field->name = 'node_key';
			$field->value =& $this->data->node_key;
			$field->bind($this->args['node_key']);

			$this->data->fields['cat_id']->name = 'cat_id';
			$this->data->fields['cat_id']->bind($this->args['cat_id']);

			$this->data->fields['cat_id']->options[''] = self::$captions->get('select_doc_cat');

			if ( !self::is_post() )
			{
				$this->data->node_key = db::get_var("SELECT next_hash();");
			}

			$dbs = db::query("
				SELECT	cat_id,
						cat_version,
						cat_name
				FROM	doc_cats
				ORDER BY cat_name, cat_version DESC
				;"
				);

			$dbs->bind('cat_id', $cat_id);
			$dbs->bind('cat_version', $cat_version);
			$dbs->bind('cat_name', $cat_name);

			while ( $dbs->get_row() )
			{
				$this->data->fields['cat_id']->options[$cat_id] = $cat_name . ' ' . $cat_version;
			}
		}
		elseif ( $this->cmd == 'edit_doc' )
		{
			$this->data->fields['doc_id']->required = true;
			$this->data->fields['doc_id']->name = 'doc_id';
			$this->data->fields['doc_id']->bind($this->args['doc_id']);
		}

		$this->data->fields['doc_status']->label = null;

		if ( active_user::can('manage_docs') )
		{
			$this->data->fields['doc_status']->values[] = 'publish';
		}
		$this->data->fields['doc_status']->values[] = 'pending';
		$this->data->fields['doc_status']->values[] = 'draft';

		if ( !request::is_post() )
		{
			if ( isset($this->data->doc_id) )
			{
				$last_rev = db::get_var("
					SELECT	MAX(rev_id)
					FROM	doc_revs
					WHERE	doc_id = :doc_id
					",
					array(
						'doc_id' => $this->data->doc_id,
						)
					);
			}

			if ( isset($last_rev) && $last_rev == $this->data->rev_id )
			{
				if ( active_user::can('manage_docs')
					&& $this->data->doc_status == 'pending'
					)
				{
					$this->data->fields['doc_status']->value = 'publish';
				}
				elseif ( $this->data->fields['doc_status']->value == 'publish' )
				{
					$this->data->fields['doc_status']->value = 'draft';
				}
			}
			else
			{
				$this->data->fields['doc_status']->value = 'draft';
			}
		}

		foreach ( $this->data->fields['doc_status']->values as $key )
		{
			$this->data->fields['doc_status']->options[$key] = self::$captions->get($key);
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

			$message = self::$captions->get('doc_saved', array('doc_name' => $this->data->doc_name));
			new notice($message);

			permalink::redirect(array('cmd' => 'edit_doc', 'doc_id' => $this->data->doc_id));
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
		case 'edit_doc':
			echo $this->data->doc_name;
			break;

		case 'new_doc':
			echo self::$captions->get('new_doc');
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
		case 'new_doc':
			echo $this->data->fields['node_key'];
			break;

		case 'edit_doc':
			echo $this->data->fields['doc_id'];
			break;
		}

		echo '<h2>';
		$this->title();
		echo '</h2>';

		$this->list_revisions();

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('doc_overview') . '</h3>'
			. '<div class="text">' . $this->data->fields['doc_name'] . '</div>'
			. '<div class="text">' . $this->data->fields['doc_key'] . '</div>';

		if ( $this->cmd == 'new_doc' )
		{
			echo '<div class="dropdown">' . $this->data->fields['cat_id'] . '</div>';
		}
		else
		{
			$field =& new textfield;
			$field->id = 'doc_cat';
			$field->readonly = true;
			$field->value = (string) $this->data->doc_cat;
			$field->label = self::$captions->get('doc_cat');

			echo '<div class="text">' . $field . '</div>';
		}

		echo '</div>';

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('doc_excerpt') . '</h3>'
			. '<div class="textarea">' . $this->data->fields['doc_excerpt'] . '</div>'
			. '</div>';

		echo '<div class="fieldset">'
			. '<h3>' . self::$captions->get('doc_content') . '</h3>'
			. '<div class="textarea">' . $this->data->fields['doc_content'] . '</div>'
			. '</div>';


		new event(__CLASS__ . '_html', $this);


		echo '<div class="fieldset">'
			. '<div class="button">';

		echo self::$captions->get('new_status') . ':'
			. '&nbsp;'
			. $this->data->fields['doc_status']
			. '&nbsp;';

		echo '<input type="submit" value="' . str::attr(self::$captions->get('save_doc')) . '" />';

		echo '</div>'
			. '</div>';

		echo '</form>';
	} # html()


	#
	# check_exists()
	#

	public static function check_exists(&$doc)
	{
		if ( $doc->doc_id )
		{
			$doc_id = db::get_var("
					SELECT	doc_id
					FROM	docs
					WHERE	cat_id = :cat_id
					AND		( str2key(:doc_key1) IS NOT NULL AND doc_key = str2key(:doc_key2)
							OR str2key(:doc_key3) IS NULL AND doc_key = str2key(:doc_name) )
					;",
					array(
						'cat_id' => $doc->cat_id,
						'doc_key1' => $doc->doc_key,
						'doc_key2' => $doc->doc_key,
						'doc_key3' => $doc->doc_key,
						'doc_name' => $doc->doc_name,
						)
					);

			if ( $doc_id && $doc_id != $doc->doc_id )
			{
				$message = self::$captions->get('doc_exists');
				new error($message);
			}
		}
		else
		{
			$dbs = db::query("
				SELECT	node_id,
						node_key
				FROM	nodes
				INNER JOIN docs
				ON		docs.doc_id = nodes.node_id
				WHERE	cat_id = :cat_id
				AND		( str2key(:doc_key1) IS NOT NULL AND doc_key = str2key(:doc_key2)
						OR str2key(:doc_key3) IS NULL AND doc_key = str2key(:doc_name) )
				;",
				array(
					'cat_id' => $doc->cat_id,
					'doc_key1' => $doc->doc_key,
					'doc_key2' => $doc->doc_key,
					'doc_key3' => $doc->doc_key,
					'doc_name' => $doc->doc_name,
					)
				);

			$dbs->bind('node_id', $node_id);
			$dbs->bind('node_key', $node_key);

			$dbs->get_row();

			if ( $node_key )
			{
				if ( $node_key != $doc->node_key )
				{
					$message = self::$captions->get('doc_exists');
					new error($message);
				}
				else
				{
					$doc->doc_id = $node_id;
				}
			}
		}
	} # check_exists()


	#
	# list_revisions()
	#

	protected function list_revisions()
	{
		if ( isset($this->data->doc_id) )
		{
			$dbs = db::query("
				SELECT	rev_id,
						user_name,
						doc_modified,
						doc_status
				FROM	doc_revs
				LEFT JOIN users
				ON users.user_id = doc_revs.author_id
				WHERE	doc_id = :doc_id
				ORDER BY rev_id DESC
				",
				array(
					'doc_id' => $this->data->doc_id,
					)
				);

			$dbs->bind('rev_id', $rev_id);
			$dbs->bind('user_name', $user_name);
			$dbs->bind('doc_modified', $doc_modified);
			$dbs->bind('doc_status', $doc_status);

			echo '<div style="text-align: right">'
				. self::$captions->get('revision')
				. '&nbsp;'
				. '<select onchange="if (this.value) document.location = this.value;">';

			while ( $dbs->get_row() )
			{
				echo '<option value="'
					. ( $rev_id != $this->data->rev_id
						? str::attr(
							permalink::get(
								array(
									'cmd' => 'edit_doc',
									'rev_id' => $rev_id
									)
								)
							)
						: ''
						)
						. '"'
					. ( $rev_id == $this->data->rev_id
						? ' selected="selected"'
						: ''
						)
					. '>'
					. self::$captions->get(
						'rev_author_date_status',
						array(
							'rev_id' => $rev_id,
							'doc_modified' => date('m/d/Y @ H:i', strtotime($doc_modified)),
							'doc_author' => $user_name,
							'doc_status' => self::$captions->get($doc_status),
							)
						)
					. '</option>';
			}

			echo '</select>'
				. '</div>';
		}
	} # list_revisions()
} # doc_editor

doc_editor::init();
?>