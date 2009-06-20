<?php
#
# MC Affiliate Module
# -------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class affiliate
{
	protected static $captions;

	protected static $defaults = array(
		'user_paypal' => '',
		'aff_is_gold' => false,
		'aff_is_reseller' => false,
		'ref_id' => null,
		);


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

	public static function map(&$user)
	{
		$field = $user->fields['user_paypal'] =& new textfield($user->user_paypal, '');
		$field->type = 'email';

		$user->fields['aff_is_gold'] =& new checkbox($user->aff_is_gold, false);
		$user->fields['aff_is_reseller'] =& new checkbox($user->aff_is_reseller, false);

		$field = $user->fields['ref_id'] =& new textfield($user->ref_id, null);
		$field->type = 'id';
		$field->readonly = true;
	} # map()


	#
	# save()
	#

	public static function save(&$user)
	{
		db::query("
			UPDATE	users
			SET		user_paypal = :user_paypal,
					aff_is_gold = :aff_is_gold,
					aff_is_reseller = :aff_is_reseller,
					ref_id = :ref_id
			WHERE	user_id = :user_id
			",
			array(
				'user_id' => $user->user_id,
				'user_paypal' => $user->user_paypal,
				'aff_is_gold' => $user->aff_is_gold,
				'aff_is_reseller' => $user->aff_is_reseller,
				'ref_id' => $user->ref_id,
				)
			);
	} # save()
} # affiliate

affiliate::init();
?>