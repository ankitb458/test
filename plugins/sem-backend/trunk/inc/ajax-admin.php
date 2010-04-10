<?php
/**
 * s_ajax_admin
 *
 * @package Semiologic Backend
 **/

class s_ajax_admin extends s_base {
	/**
	 * suggest()
	 *
	 * @param $data
	 * @return void
	 **/

	static function suggest($data = array()) {
		#header("Content-type: application/json; Charset: " . get_option('blog_charset'));
		nocache_headers();
		echo json_encode($data);
		die;
	} # suggest()
	
	
	/**
	 * sanitize()
	 *
	 * @return string $s
	 **/

	static function sanitize() {
		if ( empty($_GET['q']) )
			self::suggest();
		
		$s = trim(stripslashes($_GET['q']));
		
		# catch possible hack to scan emails
		if ( preg_match("/([ge]?mail|google|yahoo|live|facebook)/", $s)
		&& ( strpos($s, '@') === false || preg_match("/^.{0,2}@/", $s) ) )
			self::suggest();
		
		return $s;
	} # sanitize()
	
	
	/**
	 * suggest_user()
	 *
	 * @return void
	 **/

	static function suggest_user() {
		global $wpdb;
		$data = array();
		$s = self::sanitize();
		$eq_s = $wpdb->_real_escape($s);
		$like_s = $wpdb->_real_escape(addcslashes($s, '_%\\'));
		$san_s = sanitize_title($s);
		$show_email = current_user_can('edit_users');
		
		if ( self::is_id($s) ) {
			$s = (int) $s;
			$rows = array();
			$row = new WP_User($s);
			if ( $row->ID )
				$rows[] = $row;
		} elseif ( preg_match("/^([^<]+) *<([^<>]+)>?$/", $s, $match) ) {
			$like_name = $wpdb->_real_escape(addcslashes(trim($match[1]), '_%\\'));
			$like_email = $wpdb->_real_escape(addcslashes($match[2], '_%\\'));
			$san_s = sanitize_title($match[1]);
			$sql = "
				SELECT	ID, display_name, user_email, user_login, user_nicename
				FROM	$wpdb->users
				WHERE	user_email LIKE '$like_email%'
				AND		( display_name LIKE '%$like_name%'
						OR user_nicename LIKE '%$san_s%'
						OR user_login LIKE '%$san_s%' )
				ORDER BY LENGTH(display_name), display_name
				LIMIT 10
				";
			$rows = $wpdb->get_results($sql);
			if ( !$rows )
				echo $sql;
		} elseif ( strpos($s, '@') !== false && is_email(trim($s, '.') . 'foo.com') ) {
			if ( $len <= 6 ) {
				$str_match = "LIKE '$like_s%'";
				$san_match = "LIKE '$san_s%'";
			} else {
				$str_match = "LIKE '%$like_s%'";
				$san_match = "LIKE '%$san_s%'";
			}
			$sql = "
				SELECT	ID, display_name, user_email, user_login, user_nicename
				FROM	$wpdb->users
				WHERE	user_email $str_match
				ORDER BY CASE
				WHEN	user_email = '$eq_s'
				THEN	2
				WHEN	user_email LIKE '$like_s%'
				THEN	1
				ELSE	0
				END DESC, LENGTH(display_name), display_name
				LIMIT 10
				";
			$rows = $wpdb->get_results($sql);
		} else {
			$len = strlen($s);
			
			if ( $len <= 2 && !current_user_can('edit_users') ) {
				$str_match = "= '$eq_s'";
				$san_match = "= '$san_s'";
			} elseif ( $len < 4 ) {
				$str_match = "LIKE '$like_s%'";
				$san_match = "LIKE '$san_s%'";
			} else {
				$str_match = "LIKE '%$like_s%'";
				$san_match = "LIKE '%$san_s%'";
			}
			
			$sql = "
				SELECT	ID, display_name, user_email, user_login, user_nicename
				FROM	$wpdb->users
				WHERE	display_name $str_match
				OR		user_email $str_match
				OR		user_login $san_match
				OR		user_nicename $san_match
				ORDER BY CASE
				WHEN	display_name = '$eq_s'
				OR		user_email = '$eq_s'
				OR		user_login = '$san_s'
				OR		user_nicename = '$san_s'
				THEN	2
				WHEN	display_name LIKE '$like_s%'
				OR		user_email LIKE '$like_s%'
				OR		user_login LIKE '$san_s%'
				OR		user_nicename LIKE '$san_s%'
				THEN	1
				ELSE	0
				END DESC, LENGTH(display_name), display_name
				LIMIT 10
				";
			$rows = $wpdb->get_results($sql);
		}
		
		foreach ( $rows as $row ) {
			$email = $show_email
					? $row->user_email
					: preg_replace('/@.*/', '@&#133;', $row->user_email);
			$data[] = array(
				'id' => (int) $row->ID,
				'name' => $row->display_name,
				'preview' => "$row->display_name &lt;$email&gt;",
				'display' => $row->display_name,
				);
		}
		
		return self::suggest($data);
	} # suggest_user()
	
	
	/**
	 * suggest_product()
	 *
	 * @return void
	 **/

	function suggest_product() {
		$data = array();
		$s = self::sanitize();
		
		$dataset = new product_set(array('s' => $s));
		foreach ( $dataset as $row ) {
			$data[] = array(
				'id' => $row->id(),
				'name' => $row->name(),
				'preview' => $row->name() . ' - ' . $row->format_price($row->init_price(), $row->rec_price(), $row->rec_interval()),
				'display' => $row->name(),
				'init_price' => $row->init_price(),
				'init_comm' => $row->init_comm(),
				'rec_price' => $row->rec_price(),
				'rec_comm' => $row->rec_comm(),
				'rec_interval' => $row->rec_interval(),
				);
		}
		
		return self::suggest($data);
	} # suggest_product()
} # s_ajax_admin

foreach ( array(
	'user',
	'product',
	) as $hook ) {
	add_action('wp_ajax_suggest_' . $hook, array('s_ajax_admin', 'suggest_' . $hook));
}
?>