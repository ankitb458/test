<?php
#
# Text Field Object
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class textfield extends field
{
	#
	# __toString()
	#

	public function __toString()
	{
		return ( $this->label
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
					. '<div class="field">'
					)
				: ''
				)
			. '<input type="' . ( ( $this->type != 'password' ) ? 'text' : 'password' ) . '"'
				. ( $this->id
					? ( ' id="' . $this->id . '"' )
					: ''
					)
				. ( $this->name
					? ( ' name="' . $this->name . '"' )
					: ''
					)
				. ' value="' . str::attr( ( $this->type != 'password' ) ? $this->value : '' ) . '"'
				. ( $this->disabled
					? ' disabled="disabled"'
					: ''
					)
				. ( $this->readonly
					? ' readonly="readonly"'
					: ''
					)
				. ' />'
			. ( $this->label
				? ( '</div>'
					. '<div class="spacer"></div>'
					)
				: ''
				);
	} # __toString()
} # textfield
?>