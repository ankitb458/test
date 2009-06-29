<?php
#
# MC Data Object
# --------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class data
{
	#
	# Data service
	#

	protected static $addons = array();


	#
	# attach()
	# --------
	# Attach addon
	#

	public static function attach($handler, $addon)
	{
		if ( @ !in_array($addon, (array) self::$addons[$handler]) )
		{
			self::$addons[$handler][] = $addon;
		}
	} # attach()


	#
	# detach()
	# --------
	# Detach addon
	#

	public function detach($handler, $addon)
	{
		$key = @ array_search($addon, (array) self::$addons[$handler]);

		if ( $key !== false )
		{
			unset(self::$addons[$handler][$key]);
		}
	} # detach()


	#
	# Data Object
	#


	public $fields = array();


	#
	# __construct()
	#

	public function __construct($row = null)
	{
		$this->__wakeup();

		$this->map();

		if ( is_array($row) )
		{
			foreach ( $row as $key => $val )
			{
				if ( isset($this->fields[$key]) )
				{
					$this->{$key} = $val;
				}
			}
		}
	} # __construct()


	#
	# __wakeup();
	#

	public function __wakeup()
	{
		foreach ( @ (array) self::$addons[get_class($this)] as $addon )
		{
			foreach ( array(
				'map',
				'save',
				) as $method )
			{
				event::attach(get_class($this) . '_' . $method, array($addon, $method));
			}
		}
	} # __wakeup()


	#
	# __call()
	#

	final public function __call($method, $args)
	{
		new event(get_class($this) . '_' . $method, $this);
	} # __call()


	#
	# validate()
	#

	final public function validate()
	{
		$this->_validate($this->fields);
	} # validate()


	#
	# _validate()
	#

	final protected function _validate(&$fieldset)
	{
		foreach ( $fieldset as &$field )
		{
			if ( is_array($field) )
			{
				$this->_validate($field);
			}
			else
			{
				$field->sanitize();
				$field->validate();
			}
		}
	} # _validate()
} # data
?>