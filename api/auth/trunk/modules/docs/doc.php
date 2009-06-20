<?php
#
# MC Doc Data
# -----------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class doc extends data
{
	public static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('docs');
	} # init()


	#
	# register_caps()
	#

	public static function register_caps(&$caps)
	{
		args::merge($caps,
			array(
				'manage_docs' => self::$captions->get('manage_docs'),
				'edit_docs' => self::$captions->get('edit_docs'),
				)
			);
	} # register_caps()


	#
	# __construct()
	#

	public function __construct($sql = null, $args = null)
	{
		if ( is_numeric($sql) )
		{
			$row = db::get_row("
				SELECT	*
				FROM	docs
				INNER JOIN doc_cats
				ON		doc_cats.cat_id = docs.cat_id
				WHERE	doc_id = :doc_id
				",
				array(
					'doc_id' => intval($sql)
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

		if ( @ $row['cat_id'] )
		{
			$this->doc_cat =& new doc_cat($row);
		}

		if ( @ $row['author_id'] )
		{
			$this->author =& new user($row);
		}
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		$field = $this->fields['doc_id'] =& new field($this->doc_id);
		$field->type = 'id';

		$field = $this->fields['rev_id'] =& new field($this->rev_id);
		$field->type = 'id';

		$this->fields['doc_key'] =& new textfield($this->doc_key);

		$field = $this->fields['doc_name'] =& new textfield($this->doc_name, '');
		$field->required = true;

		$this->fields['doc_status'] =& new dropdown($this->doc_status);

		$field = $this->fields['doc_modified'] =& new textfield($this->doc_modified);
		$field->type = 'date';

		$this->fields['doc_excerpt'] =& new textarea($this->doc_excerpt, '');

		$this->fields['doc_content'] =& new textarea($this->doc_content, '');

		$field = $this->fields['cat_id'] =& new dropdown($this->cat_id);
		$field->type = 'id';
		$field->required = true;

		$field = $this->fields['author_id'] =& new dropdown($this->author_id);
		$field->type = 'id';


		new event(__CLASS__ . '_map', $this);
	} # map()


	#
	# save()
	#

	public function save()
	{
		if ( isset($this->doc_status) )
		{
			if ( !$this->doc_id )
			{
				$this->doc_id = db::get_var(
					"SELECT	next_node(:node_key);",
					array(
						'node_key' => @ $this->node_key,
						)
					);
			}

			if ( $this->doc_id === false )
			{
				$this->doc_id = db::get_var("
					SELECT	doc_id
					FROM	docs
					INNER JOIN nodes
					ON nodes.node_id = docs.doc_id
					WHERE	node_key = :node_key
					;",
					array(
						'node_key' => $this->node_key,
						)
					);

				$message = self::$captions->get("request_timeout");
				new error($message);

				db::rollback();

				if ( isset($this->doc_id) )
				{
					permalink::redirect(
						array(
							'cmd' => 'edit_doc',
							'doc_id' => $this->doc_id,
							)
						);
				}
				else
				{
					permalink::redirect(
						array(
							'cmd' => 'new_doc',
							)
						);
				}
			}

			db::query("
				INSERT INTO doc_revs (
						doc_id,
						cat_id,
						author_id,
						doc_key,
						doc_name,
						doc_status,
						doc_excerpt,
						doc_content
						)
				VALUES (
						:doc_id,
						:cat_id,
						:author_id,
						:doc_key,
						:doc_name,
						:doc_status,
						:doc_excerpt,
						:doc_content
						)
				",
				array(
					'doc_id' => $this->doc_id,
					'cat_id' => $this->cat_id,
					'author_id' => active_user::get('user_id'),
					'doc_key' => $this->doc_key,
					'doc_name' => $this->doc_name,
					'doc_status' => $this->doc_status,
					'doc_excerpt' => $this->doc_excerpt,
					'doc_content' => $this->doc_content,
					)
				);
		}
		else
		{
			if ( !$this->doc_id )
			{
				$this->doc_id = db::get_var(
					"SELECT	next_node(:node_key);",
					array(
						'node_key' => @ $this->node_key,
						)
					);

				db::query("
					INSERT INTO docs (
							doc_id,
							cat_id,
							doc_key,
							doc_name,
							doc_excerpt,
							doc_content
							)
					VALUES (
							:doc_id,
							:cat_id,
							:doc_key,
							:doc_name,
							:doc_excerpt,
							:doc_content
							);
					",
					array(
						'doc_id' => $this->doc_id,
						'cat_id' => $this->cat_id,
						'doc_key' => $this->doc_key,
						'doc_name' => $this->doc_name,
						'doc_excerpt' => $this->doc_excerpt,
						'doc_content' => $this->doc_content,
						)
					);
			}
			else
			{
				db::query("
					UPDATE	docs
					SET		cat_id = :cat_id,
							doc_key = :doc_key,
							doc_name = :doc_name,
							doc_excerpt = :doc_excerpt,
							doc_content = :doc_content
					WHERE	doc_id = :doc_id
					",
					array(
						'doc_id' => $this->doc_id,
						'cat_id' => $this->cat_id,
						'doc_key' => $this->doc_key,
						'doc_name' => $this->doc_name,
						'doc_excerpt' => $this->doc_excerpt,
						'doc_content' => $this->doc_content,
						)
					);
			}
		}

		new event(__CLASS__ . '_save', $this);
	} # save()
} # doc

doc::init();
?>