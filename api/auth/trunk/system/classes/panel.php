<?php
#
# MC Panel Object
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class panel
{
	public $id;
	public $class;
	public $pad = false;
	public $spacer = false;

	protected $widgets = array();


	#
	# load()
	#

	public function load(&$widget)
	{
		if ( !is_object($widget) )
		{
			@ list ( $widget_class, $widget_id ) = $widget;
			$widget =& new $widget_class($widget_id);
		}

		$this->widgets[] =& $widget;
	} # load()


	#
	# wire()
	#

	public function wire(&$args)
	{
		foreach ( $this->widgets as &$widget )
		{
			$widget->wire($args);
		}
	} # wire()


	#
	# html()
	#

	public function html(&$args)
	{
		if ( $this->widgets )
		{
			if ( $this->id )
			{
				echo '<div id="' . $this->id . '"'
					. ( $this->class
						? ( ' class="' . $this->class . '"' )
						: ''
						)
					. '>';
			}

			if ( $this->pad )
			{
				echo '<div class="pad">';
			}

			foreach ( $this->widgets as &$widget )
			{
				$widget->html($args);
			}

			if ( $this->pad )
			{
				echo '</div>';
			}

			if ( $this->spacer )
			{
				echo '<div class="spacer"></div>';
			}

			if ( $this->id )
			{
				echo '</div><!-- #' . $this->id . ' -->';
			}
		}
	} # html()
} # panel
?>