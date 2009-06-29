<?php
#
# Affiliate Editor
# ----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class affiliate_editor implements request_addon
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('affiliates');
	} # init()


	#
	# map()
	#

	public static function map(&$request)
	{
		# contextual fields
		if ( $request->cmd != 'register' )
		{
			$request->data->fields['user_paypal']->id = 'user_paypal';
			$request->data->fields['user_paypal']->name = 'user_paypal';
			$request->data->fields['user_paypal']->label = self::$captions->get('user_paypal');
			$request->data->fields['user_paypal']->bind($request->args['user_paypal']);

			if ( active_user::can('manage_affiliates') )
			{
				$request->data->fields['aff_is_gold']->id = 'aff_is_gold';
				$request->data->fields['aff_is_gold']->name = 'aff_is_gold';
				$request->data->fields['aff_is_gold']->label = self::$captions->get('aff_is_gold');
				$request->data->fields['aff_is_gold']->bind($request->args['aff_is_gold']);

				$request->data->fields['aff_is_reseller']->id = 'aff_is_reseller';
				$request->data->fields['aff_is_reseller']->name = 'aff_is_reseller';
				$request->data->fields['aff_is_reseller']->label = self::$captions->get('aff_is_reseller');
				$request->data->fields['aff_is_reseller']->bind($request->args['aff_is_reseller']);
			}
		}
	} # map()


	#
	# wire()
	#

	public static function wire(&$request)
	{
	} # wire()


	#
	# html()
	#

	public static function html(&$request)
	{
		if ( $request->cmd != 'register' )
		{
			echo '<div class="fieldset">'
				. '<h3>' . self::$captions->get('affiliate_details') . '</h3>'
				. '<div class="text">' . $request->data->fields['user_paypal'] . '</div>';

			if ( active_user::can('manage_affiliates') )
			{
				echo  '<div class="checkbox">' . $request->data->fields['aff_is_gold'] . '</div>'
					. '<div class="checkbox">' . $request->data->fields['aff_is_reseller'] . '</div>';

				$row = db::get_row("
					SELECT	campaign_key,
							user_name
					FROM	campaigns
					LEFT JOIN users
					ON users.user_id = campaigns.aff_id
					WHERE	campaign_id = :ref_id
					",
					array(
						'ref_id' => $request->data->ref_id,
						)
					);

				$field =& new textfield;
				$field->readonly = true;
				$field->label = self::$captions->get('ref_id');

				if ( $row )
				{
					$field->value = $row['campaign_key']
						. ( $row['user_name']
							? ( ' (' . $row['user_name'] . ')' )
							: ''
							);
				}
				else
				{
					$field->value = '';
				}

				echo '<div class="text">' . $field . '</div>';
			}

			echo '</div>';
		}
	} # html()
} # affiliate_editor

affiliate_editor::init();
?>