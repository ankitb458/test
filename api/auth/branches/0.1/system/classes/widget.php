<?php
#
# MC Widget Ojbect
# ----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

abstract class widget
{
	protected $id;
	protected $options;


	#
	# __construct()
	#

	public function __construct($id = null)
	{
		$this->id = $id;

		$this->get_options();
	} # construct()


	#
	# get_options()
	#

	public function get_options()
	{
		if ( !isset($this->id) )
		{
			return;
		}

		if ( !isset($this->options) )
		{
			$options =& options::get('widgets');

			$this->options =& $options[$this->id];
		}
	} # get_options()


	#
	# __call()
	#

	public function __call($method, $args = null)
	{
		$captions =& new captions;

		throw new exception($captions->get('undefined_widget_method', array('widget' => get_class($this), 'method' => $method)));
	} # __call()


	#
	# begin()
	#

	protected function begin()
	{
		return '<div class="widget ' . get_class($this) . '">';
	} # begin()


	#
	# end()
	#

	protected function end()
	{
		return '</div>';
	} # end()


	#
	# begin_title()
	#

	public function begin_title()
	{
		return '<div class="widget_title">'
			. '<h3>';
	} # begin_title()


	#
	# end_title()
	#

	public function end_title()
	{
		return '</h3>'
			. '</div>';
	} # end_title()


	#
	# begin_content()
	#

	public function begin_content()
	{
		return '<div class="widget_content">';
	} # begin_content()


	#
	# end_content()
	#

	public function end_content()
	{
		return '</div>';
	} # end_content()
} # widget
?>