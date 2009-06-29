<?php
#
# Text Field Object
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class textarea extends textfield
{
	public $style;
	public $type = 'textarea';
	public $allow_tags = false;

	#
	# __toString()
	#

	public function __toString()
	{
		return ( $this->label
				? '<div class="field">'
				: ''
				)
			. '<textarea'
				. ( $this->id
					? ( ' id="' . $this->id . '"' )
					: ''
					)
				. ( $this->name
					? ( ' name="' . $this->name . '"' )
					: ''
					)
				. ( $this->disabled
					? ' disabled="disabled"'
					: ''
					)
				. ( $this->readonly
					? ' readonly="readonly"'
					: ''
					)
				. ( $this->style
					? ' style="' . str::attr($this->style) . '"'
					: ''
					)
				. ' >'
				. $this->value
			. '</textarea>'
			. ( $this->label
				? ( '</div>'
					. '<div class="spacer"></div>'
					)
				: ''
				);
	} # __toString()
} # textfield
?>