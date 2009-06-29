<?php
#
# Field Object
# ------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class field
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
	} # init()


	public $type = 'text';
	public $required = false;


	public $id;
	public $value;
	public $name;
	public $label;

	public $disabled = false;
	public $readonly = false;

	protected $match;
	public $values;


	#
	# __construct()
	#

	public function __construct(&$arg = null, $default = null)
	{
		$this->value =& $arg;

		if ( !isset($this->value) )
		{
			$this->value = $default;
		}
	} # __construct()


	#
	# bind()
	#

	final public function bind(&$arg)
	{
		if ( request::is_post() )
		{
			$this->value = $arg;
		}
		elseif ( $arg && (string) $this->value === '' )
		{
			$this->value = $arg;
		}
	} # bind()


	#
	# match()
	#

	final public function match(&$field1, &$field2)
	{
		if ( request::is_post() )
		{
			$this->match = array(&$field1, &$field2);
		}
	} # match()


	#
	# sanitize()
	#

	public function sanitize()
	{
		switch ( $this->type )
		{
		case 'bool':
			$this->value = intval((bool) $this->value);
			break;

		case 'id':
			$this->value = intval($this->value);

			if ( !$this->value )
			{
				$this->value = null;
			}
			break;

		case 'date':
			$this->value = strtotime($this->value);

			if ( $this->value === false )
			{
				$this->value = null;
			}
			else
			{
				$this->value = date(DATE_RFC822, $this->value);
			}
			break;

		case 'textarea':
			$this->value = trim($this->value, "\n\r");
			if ( !$this->allow_tags )
			{
				$this->value = strip_tags($this->value);
			}
			break;

		case 'int':
		default:
			$this->value = intval($this->value);
			break;

		case 'float':
		default:
			$this->value = round(float($this->value), 2);
			break;

		case 'text':
		case 'password':
		case 'email':
		default:
			$this->value = trim($this->value);
			$this->value = strip_tags($this->value);
			break;
		}

	} # sanitize()


	#
	# validate()
	#

	public function validate()
	{
		if ( $this->required && (string) $this->value === '' )
		{
			$message = self::$captions->get('required_field', array('field' => $this->label));
			new error($message);
		}

		if ( is_array($this->match) )
		{
			list ($field1, $field2) = $this->match;

			if ( $field1->value == $field2->value )
			{
				$this->value = $field1->value;
			}
			else
			{
				$this->value = '';

				$message = self::$captions->get('field_mismatch', array('field' => $field1->label));
				new error($message);
			}
		}

		switch ( $this->type )
		{
		case 'email':
			if ( (string) $this->value !== ''
				&& !preg_match("/
						^[a-z0-9_-]+
						(?:\.[a-z0-9_-]+)*
						@
						[a-z0-9_-]+
						(?:\.[a-z0-9_-]+)+
						$
						/ix", $this->value)
				)
			{
				$message = self::$captions->get('invalid_email', array('field' => $this->label));
				new error($message);
			}
			break;
		}

		if ( isset($this->values) )
		{
			if ( !in_array($this->value, $this->values) )
			{
				$message = self::$captions->get('invalid_value', array('field' => $this->label));
				new error($message);
			}
		}
	} # validate()


	#
	# __toString()
	#

	public function __toString()
	{
		return '<input type="hidden"'
			. ( $this->id
				? ( ' id="' . $this->id . '"' )
				: ''
				)
			. ( $this->name
				? ( ' name="' . $this->name . '"' )
				: ''
				)
			. ' value="' . str::attr($this->value) . '"'
			. ' />';

	} # __toString()
} # field

field::init();
?>