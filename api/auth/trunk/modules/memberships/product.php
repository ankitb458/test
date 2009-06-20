<?php
#
# MC Product Data
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class product extends data
{
	public static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('products');
	} # init()


	#
	# register_caps()
	#

	public static function register_caps(&$caps)
	{
		args::merge($caps, array('manage_products' => self::$captions->get('manage_products')));
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
				FROM	products
				WHERE	product_id = :product_id
				",
				array(
					'product_id' => intval($sql)
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
		$field = $this->fields['product_id'] =& new field($this->product_id);
		$field->type = 'id';

		$this->fields['product_key'] =& new textfield($this->product_key);

		$field = $this->fields['product_name'] =& new textfield($this->product_name, '');
		$field->required = true;

		$field = $this->fields['product_price'] =& new textfield($this->product_price, 0);
		$field->type = 'float';
		$field->required = true;

		$this->fields['product_desc'] =& new textarea($this->product_desc, '');


		new event(__CLASS__ . '_map', $this);
	} # map()


	#
	# save()
	#

	public function save()
	{
		if ( !$this->product_id )
		{
			$this->product_id = db::get_var(
				"SELECT	next_node(:node_key);",
				array(
					'node_key' => @ $this->node_key,
					)
				);

			db::query("
				INSERT INTO products (
						product_id,
						product_key,
						product_name,
						product_price
						product_desc,
						)
				VALUES (
						:product_id,
						:product_key,
						:product_name,
						:product_price
						:product_desc,
						);
				",
				array(
					'product_id' => $this->product_id,
					'product_key' => $this->product_key,
					'product_name' => $this->product_name,
					'product_price' => $this->product_price,
					'product_desc' => $this->product_desc,
					)
				);
		}
		else
		{
			db::query("
				UPDATE	products
				SET		product_key = :product_key,
						product_name = :product_name,
						product_price = :product_price
						product_desc = :product_desc,
				WHERE	product_id = :product_id
				",
				array(
					'product_id' => $this->product_id,
					'cat_id' => $this->cat_id,
					'product_key' => $this->product_key,
					'product_name' => $this->product_name,
					'product_price' => $this->product_price,
					'product_desc' => $this->product_desc,
					)
				);
		}

		new event(__CLASS__ . '_save', $this);
	} # save()
} # product

product::init();
?>