<?php
#
# MC Doc Cat Data
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class doc_cat extends data
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
	# __construct()
	#

	public function __construct($sql = null, $args = null)
	{
		if ( is_numeric($sql) )
		{
			$row = db::get_row("
				SELECT	*
				FROM	doc_cats
				WHERE	cat_id = :cat_id
				",
				array(
					'cat_id' => intval($sql)
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
		$field = $this->fields['cat_id'] =& new field($this->cat_id);
		$field->type = 'id';

		$field = $this->fields['cat_name'] =& new textfield($this->cat_name, '');
		$field->required = true;

		$this->fields['cat_key'] =& new textfield($this->cat_key);

		$field = $this->fields['cat_version'] =& new textfield($this->cat_version, '');
		$field->required = true;

		$this->fields['cat_desc'] =& new textarea($this->cat_desc, '');


		new event(__CLASS__ . '_map', $this);
	} # map()


	#
	# save()
	#

	public function save()
	{
		if ( !$this->cat_id )
		{
			$this->cat_id = db::get_var(
				"SELECT	next_node(:node_key);",
				array(
					'node_key' => @ $this->node_key,
					)
				);

			if ( $this->cat_id === false )
			{
				$this->cat_id = db::get_var("
					SELECT	cat_id
					FROM	doc_cats
					INNER JOIN nodes
					ON nodes.node_id = doc_cats.cat_id
					WHERE	node_key = :node_key
					;",
					array(
						'node_key' => $this->node_key,
						)
					);

				$message = self::$captions->get("request_timeout");
				new error($message);

				db::rollback();

				if ( isset($this->cat_id) )
				{
					permalink::redirect(
						array(
							'cmd' => 'edit_doc_cat',
							'cat_id' => $this->cat_id,
							)
						);
				}
				else
				{
					permalink::redirect(
						array(
							'cmd' => 'new_doc_cat',
							)
						);
				}
			}

			db::query("
				INSERT INTO doc_cats (
						cat_id,
						cat_key,
						cat_name,
						cat_version,
						cat_desc
						)
				VALUES (
						:cat_id,
						:cat_key,
						:cat_name,
						:cat_version,
						:cat_desc
						);
				",
				array(
					'cat_id' => $this->cat_id,
					'cat_key' => $this->cat_key,
					'cat_name' => $this->cat_name,
					'cat_version' => $this->cat_version,
					'cat_desc' => $this->cat_desc,
					)
				);
		}
		else
		{
			db::query("
				UPDATE	doc_cats
				SET		cat_key = :cat_key,
						cat_name = :cat_name,
						cat_version = :cat_version,
						cat_desc = :cat_desc
				WHERE	cat_id = :cat_id
				",
				array(
					'cat_id' => $this->cat_id,
					'cat_key' => $this->cat_key,
					'cat_name' => $this->cat_name,
					'cat_version' => $this->cat_version,
					'cat_desc' => $this->cat_desc,
					)
				);
			
			# version change will result in an id change
			$cat_id = db::get_var("
				SELECT	cat_id
				FROM	doc_cats
				WHERE	cat_key = :cat_key
				AND		cat_version = :cat_version
				", array(
					'cat_key' => $this->cat_key,
					'cat_version' => $this->cat_version,
					)
				);
			
			if ( $cat_id && $cat_id != $this->cat_id )
			{
				$this->cat_id = $cat_id;
			}
		}

		new event(__CLASS__ . '_save', $this);
	} # save()


	#
	# __toString()
	#

	public function __toString()
	{
		return $this->cat_name . ' ' . $this->cat_version;
	} # __toString()
} # doc

doc::init();
?>