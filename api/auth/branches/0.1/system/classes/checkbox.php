<?php
#
# Checkbox Object
# ---------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class checkbox extends field
{
	public $type = 'bool';


	#
	# __toString()
	#

	public function __toString()
	{
		return '<div class="field">'
			. '<input type="checkbox"'
				. ( $this->id
					? ( ' id="' . $this->id . '"' )
					: ''
					)
				. ( $this->name
					? ( ' name="' . $this->name . '"' )
					: ''
					)
				. ( $this->value
					? ' checked="checked"'
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
				. ' />'
				. '</div>'
			. ( $this->label
				? ( '<div class="label">'
					. '<label'
					. ( $this->id
						? ( ' for="' . $this->id . '"' )
						: ''
						)
					. '>'
					. $this->label
					. '</label>'
					. '</div>'
					)
				: ''
				)
			. '<div class="spacer"></div>';
	} # __toString()
} # checkbox

checkbox::init();
?>