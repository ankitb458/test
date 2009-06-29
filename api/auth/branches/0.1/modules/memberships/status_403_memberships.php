<?php
#
# Status 403 Memberships
# ----------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class status_403_memberships implements request_addon
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions('memberships');
	} # init()


	#
	# map()
	#

	public static function map(&$request)
	{
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
		if ( $request->cmd == 'restrict' )
		{
			$campaign_key = isset($_COOKIE['ref_id']) ? $_COOKIE['ref_id'] : null;

			if ( is_null($campaign_key) )
			{
				$campaign_key = db::get_var("
					SELECT	campaign_key
					FROM	campaigns
					WHERE	campaign_id = :ref_id
					",
					array(
						'ref_id' => active_user::get('ref_id'),
						)
					);

				if ( !$campaign_key )
				{
					$campaign_key = null;
				}
			}

			$dbs = db::query("
				SELECT	pricelist.*,
						profiles.profile_name,
						product2profile.membership_duration,
						memberships.membership_expires
				FROM	pricelist
				INNER JOIN product2profile
				ON		product2profile.product_id = pricelist.product_id
				INNER JOIN profiles
				ON		profiles.profile_id = product2profile.profile_id
				INNER JOIN profile2domain
				ON		profile2domain.profile_id = product2profile.profile_id
				INNER JOIN domain2url
				ON		domain2url.domain_id = profile2domain.domain_id
				LEFT JOIN memberships
				ON		( memberships.profile_id IS NULL OR memberships.profile_id = product2profile.profile_id )
				AND		( memberships.user_id IS NULL OR memberships.user_id = pricelist.user_id )
				WHERE	url_handle LIKE :url_handle
				AND		pricelist.user_id = :user_id
				AND		campaign_key IS NULL
				ORDER BY product_name, profile_name
				",
				array(
					'url_handle' => $request->args['url'] . '%',
					'user_id' => active_user::get('user_id'),
					)
				);

			#debug::dump($request->args['url'], active_user::get('user_id'), $campaign_key);
			#$dbs->dump();

			if ( $dbs->num_rows() )
			{
				echo '<p>'
					. self::$captions->get('resolve_403')
					. '</p>';

				echo '<div class="dataset">';

				echo '<table>';

				echo '<tr>'
					. '<th>' . self::$captions->get('membership') . '</th>'
					. '<th>' . self::$captions->get('duration') . '</th>'
					. '<th>' . self::$captions->get('price') . '</th>'
					. '<th>&nbsp;</th>'
					. '</tr>';

				$dbs->bind('product_name', $product_name);
				$dbs->bind('product_key', $product_key);
				$dbs->bind('product_desc', $product_desc);
				$dbs->bind('profile_name', $profile_name);
				$dbs->bind('membership_duration', $membership_duration);
				$dbs->bind('membership_expires', $membership_expires);
				$dbs->bind('product_price', $product_price);

				while ( $dbs->get_row() )
				{
					echo '<tr>'
						. '<td colspan="4">&nbsp;</td>'
						. '</tr>';

					echo '<tr align="center">'
						. '<td align="left">'
							. '<strong>' . $product_name . '</strong>'
							. ( !is_null($membership_expires)
								? ( ' (' . self::$captions->get('expired') . ')' )
								: ''
								)
							. '</td>'
						. '<td>' . $membership_duration . '</td>'
						. '<td align="right">' . number_format($product_price, 2) . '</td>'
						. '<td>'
							. '<a href="http://oldbackend.semiologic.com/order.php?product=' . $product_key . '">'
							. ( !is_null($membership_expires)
								? self::$captions->get('renew')
								: self::$captions->get('order')
								)
							. '</a>'
							. '</td>'
						. '</tr>';

					if ( $product_desc )
					{
						echo '<tr>'
							. '<td colspan="4">'
							. str::markdown($product_desc)
							. '</td>'
							. '</tr>';
					}
				}

				echo '</table>';

				echo '</div>';
			}
		}
	} # html()
} # status_403_memberships

status_403_memberships::init();

/*

#
# start tests
#

$db_host = config::get('db_host');
$db_name = config::get('db_name');
$db_user = config::get('db_user');
$db_pass = config::get('db_pass');

try
{
	$dbh =& new PDO(
		'pgsql:host=' . $db_host . ';dbname=' . $db_name,
		$db_user,
		$db_pass
		);
}
catch ( PDOException $e )
{
	throw new exception('err_db_connect');
}

$dbs = $dbh->prepare(' SELECT	1 WHERE	(?)::int4 IS NOT NULL ');

$dbs->execute(array(1));

$res = $dbs->fetchAll();

debug::dump($res);
*/
?>