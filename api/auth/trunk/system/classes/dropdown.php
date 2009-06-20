<?php
#
# Text Field Object
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class dropdown extends field
{
	public $options = array();


	#
	# __toString()
	#

	public function __toString()
	{
		$o = ( $this->label
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
			. '<select'
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
				. ' >';

		foreach ( $this->options as $value => $name )
		{
			$o .= '<option'
				. ' value="' . $value . '"'
				. ( (string) $value === (string) $this->value
					? ' selected="selected"'
					: ''
					)
				. ' >'
				. $name
				. ' </option>';
		}

		$o .= '</select>'
			. ( $this->label
				? ( '</div>'
					. '<div class="spacer"></div>'
					)
				: ''
				);

		return $o;
	} # __toString()
} # textfield
?>