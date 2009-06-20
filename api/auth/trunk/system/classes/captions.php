<?php
#
# MC Captions Object
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class captions
{
	# context
	protected $context;

	# captions
	protected $captions = array();


	#
	# __construct()
	#

	public function __construct($context = 'system')
	{
		$this->context = $context;

		dict::load($this->context);

		$this->captions =& dict::get($this->context);
	} # __construct()


	#
	# get()
	# -----
	# Get a caption
	#

	public function get($caption, $args = array())
	{
		# Retrieve the raw caption
		$caption = isset($this->captions[$caption]) ? $this->captions[$caption] : $caption;

		# Process arguments
		if ( !empty($args) )
		{
			$find = array();
			$replace = array();

			foreach ( (array) $args as $key => $val )
			{
				$find[] = '{$' . $key . '}';
				$repl[] = $val;
			}

			$caption = str_replace($find, $repl, $caption);
		}

		# Return the raw caption
		return $caption;
	} # get()
} # captions
?>