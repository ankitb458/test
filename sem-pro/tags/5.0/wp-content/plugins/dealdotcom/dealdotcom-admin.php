<?php
class dealdotcom_admin
{
	#
	# widget_control()
	#

	function widget_control()
	{
		$options = $newoptions = get_option('dealdotcom');

		if ( $_POST["dealdotcom-submit"] )
		{
			$newoptions = array();
			$newoptions['aff_id'] = strip_tags(stripslashes($_POST["dealdotcom-aff_id"]));
			$newoptions['nofollow'] = isset($_POST["dealdotcom-nofollow"]);
		}

		if ( $options != $newoptions )
		{
			$options = $newoptions;
			update_option('dealdotcom', $options);
		}


		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div style="width: 100px; float: left; padding-top: 2px;">'
			. '<label for="dealdotcom-aff_id">'
			. '<a href="http://www.semiologic.com/go/dealdotcom" target="_blank">'
				. __('Affiliate ID', 'dealdotcom')
				. '</a>'
				. ':'
			. '</label>'
			. '</div>'
			. '<div style="width: 150px; float: right;">'
			. '<input style="width: 140px;"'
			. ' id="dealdotcom-aff_id" name="dealdotcom-aff_id"'
			. ' type="text" value="' . attribute_escape($options['aff_id']) . '"'
			. ' />'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';

		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<label for="dealdotcom-aff_id">'
			. '<input'
			. ' id="dealdotcom-nofollow" name="dealdotcom-nofollow"'
			. ' type="checkbox" value="' . attribute_escape($options['nofollow']) . '"'
			. ' />'
			. '&nbsp;'
			. __('Add nofollow', 'dealdotcom')
			. '</label>'
			. '</div>';


		echo '<input type="hidden"'
			. ' id="dealdotcom-submit" name="dealdotcom-submit"'
			. ' value="1"'
			. ' />';
	} # widget_control()
} # dealdotcom_admin
?>