<?php
include_once s_path . '/inc/data.php';


/**
 * campaign_type
 *
 * @package Semiologic Backend
 **/

interface campaign_type {
	const type = 'campaign';
	const types = 'campaigns';
	const active_status = 'active';
} # campaign_type


/**
 * campaign()
 *
 * @param mixed $key
 * @return campaign $campaign
 **/

function campaign($key = null) {
	return $key instanceof campaign
		? $key
		: new campaign($key);
} # campaign()


/**
 * campaign_set()
 *
 * @param array $args
 * @return campaign_set $campaign_set
 **/

function campaign_set($args = null) {
	return $args instanceof campaign_set
		? $args
		: new campaign_set($args);
} # campaign_set()


/**
 * campaign
 *
 * @package Semiologic Backend
 **/

class campaign extends s_data_base implements campaign_type, s_data {
	protected	$id,
				$uuid,
				$ukey,
				$status = 'draft',
				$name = '',
				$aff_id,
				$product_id,
				$init_discount = 0,
				$rec_discount = 0,
				$min_date,
				$max_date,
				$max_orders,
				$firesale = false,
				$memo = '',
				$created_date,
				$modified_date,
				$product;
	
	
	/**
	 * __set_state()
	 *
	 * @param mixed $key
	 * @return object $object
	 **/

	static function __set_state($key = null) {
		return campaign($key);
	} # __set_state()
	
	
	/**
	 * fields()
	 *
	 * @return array $fields
	 **/

	function fields() {
		return array(
			'id',
			'uuid',
			'ukey',
			'status',
			'name',
			'aff_id',
			'product_id',
			'init_discount',
			'rec_discount',
			'min_date',
			'max_date',
			'max_orders',
			'firesale',
			'memo',
			'created_date',
			'modified_date',
			);
	} # fields()
	
	
	/**
	 * coupon_fields()
	 *
	 * @return array $fields
	 **/

	function coupon_fields() {
		return array(
			'product_id',
			'init_discount',
			'rec_discount',
			'min_date',
			'max_date',
			'max_orders',
			'firesale',
			);
	} # coupon_fields()
	
	
	/**
	 * aff_id()
	 *
	 * @param id $aff_id
	 * @return id $aff_id
	 **/

	final function aff_id($aff_id = null) {
		return isset($aff_id)
			? $this->aff_id = $this->set_id($aff_id, true)
			: $this->aff_id;
	} # aff_id()
	
	
	/**
	 * product_id()
	 *
	 * @param id $product_id
	 * @return id $product_id
	 **/

	final function product_id($product_id = null) {
		return isset($product_id)
			? $this->product_id = $this->set_id($product_id, true)
			: $this->product_id;
	} # product_id()
	
	
	/**
	 * init_discount()
	 *
	 * @param float $init_discount
	 * @return float $init_discount
	 **/

	function init_discount($init_discount = null) {
		return isset($init_discount)
			? $this->init_discount = $this->set_price($init_discount)
			: $this->init_discount;
	} # init_discount()
	
	
	/**
	 * rec_discount()
	 *
	 * @param float $rec_discount
	 * @return float $rec_discount
	 **/

	function rec_discount($rec_discount = null) {
		return isset($rec_discount)
			? $this->rec_discount = $this->set_price($rec_discount)
			: $this->rec_discount;
	} # rec_discount()
	
	
	/**
	 * min_date()
	 *
	 * @param date $min_date
	 * @return date $min_date
	 **/

	function min_date($min_date = null) {
		return isset($min_date)
			? $this->min_date = $this->set_date($min_date, true)
			: $this->min_date;
	} # min_date()
	
	
	/**
	 * max_date()
	 *
	 * @param date $max_date
	 * @return date $max_date
	 **/

	function max_date($max_date = null) {
		return isset($max_date)
			? $this->max_date = $this->set_date($max_date, true)
			: $this->max_date;
	} # max_date()
	
	
	/**
	 * max_orders()
	 *
	 * @param quantity $max_orders
	 * @return quantity $max_orders
	 **/

	function max_orders($max_orders = null) {
		return isset($max_orders)
			? $this->max_orders = $this->set_quantity($max_orders, true)
			: $this->max_orders;
	} # max_orders()
	
	
	/**
	 * firesale()
	 *
	 * @param bool $firesale
	 * @return bool $firesale
	 **/

	function firesale($firesale = null) {
		return isset($firesale)
			? $this->firesale = $this->set_bool($firesale)
			: $this->firesale;
	} # firesale()
	
	
	/**
	 * dates()
	 *
	 * @return array $dates
	 **/

	function dates() {
		return array(
			'min_date' => 'min_time',
			'max_date' => 'max_time',
			);
	} # dates()
	
	
	/**
	 * statuses()
	 *
	 * @return array $statuses
	 **/

	function statuses() {
		return array(
			'active',
			'future',
			'inactive',
			'pending',
			'draft',
			'inherit',
			'trash',
			);
	} # statuses()
	
	
	/**
	 * import()
	 *
	 * @param array|object $row
	 * @return object $this
	 **/
	
	function import($row = null) {
		$row = (array) $row;
		
		# catch checkbox UI
		foreach ( array(
			'firesale',
			) as $var ) {
			$row[$var] = !empty($row[$var]);
		}
		
		# force aff_id
		if ( !current_user_can('manage_campaigns') ) {
			if ( !$this->id )
				$row['aff_id'] = (int) wp_get_current_user()->ID;
			else
				unset($row['aff_id']);
		}
		
		# force coupon fields
		if ( !current_user_can('edit_coupon', $this) ) {
			foreach ( $this->coupon_fields() as $field )
				unset($row[$field]);
		}
		
		# clone before importing, so as to moderate/auto-fix coupons
		$old = clone $this;
		
		parent::import($row);
		
		# post-process coupons
		switch ( $this->is_coupon() && $this->is_active() ) {
		case true:
			# change the launch date if the coupon was altered
			if ( $this->min_date && strtotime($this->min_date . ' GMT') < gmdate('U') - 3600 ) {
				foreach ( $this->coupon_fields() as $var ) {
					switch ( $var ) {
					case 'min_date':
					case 'max_date':
					case 'max_orders':
						continue 2;
				
					default:
						if ( (string) $this->$var === (string) $old->$var )
							continue 2;
						
						$this->min_date = gmdate('Y-m-d H:i:s');
						if ( $this->max_date && $this->max_date < $this->min_date )
							$this->max_date = $this->max_date;
						break 3;
					}
				}
			}
			
			# maybe moderate
			if ( !$old->is_active() && !current_user_can('publish_coupon', $this) ) {
				foreach ( $this->coupon_fields() as $var ) {
					if ( (string) $this->$var === (string) $old->$var )
						continue;
					
					$this->status = 'pending';
					break;
				}
			}
		}
		
		return $this;
	} # import()
	
	
	/**
	 * sanitize()
	 *
	 * @return object $this
	 **/

	function sanitize() {
		$product = $this->product();
		
		# products can't have a key or an owner
		if ( $this->is_product() ) {
			$this->aff_id = null;
			$this->ukey = null;
			$this->name = sprintf(__('Promo: %s', 'sem-backend'), $product->name());
			if ( $product->is_trash() )
				$this->status = 'inherit';
			elseif ( !( $product->is_active() || $product->is_scheduled() ) )
				$this->status = $product->status();
			elseif ( !( $this->is_active() || $this->is_scheduled() ) )
				$this->status = 'inactive';
		}
		
		# validate user IDs
		foreach ( array(
			'aff_id' => 'aff',
			) as $var => $obj ) {
			if ( (string) $this->$var === '' )
				continue;
			$$obj = new WP_User($this->$var);
			if ( $$obj->ID )
				continue;
			$this->$var = null;
		}
		
		# force a launch date update when coupons are altered
		if ( $this->is_coupon() && $this->is_active() &&
			$this->min_date && strtotime($this->min_date . ' GMT') < gmdate('U') - 3600 ) {
			$old = new campaign($this->id);
			foreach ( $this->coupon_fields() as $var ) {
				switch ( $var ) {
				case 'min_date':
				case 'max_date':
				case 'max_orders':
					continue 2;
			
				default:
					if ( (string) $this->$var === (string) $old->$var )
						continue 2;
					
					$this->min_date = gmdate('Y-m-d H:i:s');
					if ( $this->max_date && $this->max_date < $this->min_date )
						$this->max_date = $this->max_date;
					break 2;
				}
			}
		}
		
		# sanity checks on coupon fields
		if ( $this->is_product()
			|| $this->is_coupon() && ( $product->is_active() || $product->is_scheduled() ) ) {
			# dates are normalized as Y-m-d H:i:s by now, so we can compare them as strings
			$now = gmdate('Y-m-d H:i:s');
			
			extract($product->get('init_price', 'init_comm', 'rec_price', 'rec_comm'));
			
			if ( $this->aff_id ) {
				$this->init_discount = min($this->init_discount, $init_comm);
				$this->rec_discount = min($this->rec_discount, $rec_comm);
			} else {
				$this->init_discount = min($this->init_discount, $init_price - $init_comm);
				$this->rec_discount = min($this->rec_discount, $rec_price - $rec_comm);
			}
			
			if ( ( $this->is_active() || $this->is_scheduled() ) &&
				!( $this->init_discount || $this->rec_discount ) )
				$this->status = 'inactive';
			
			if ( $this->is_active() || $this->is_scheduled() ) {
				if ( !$this->min_date )
					$this->min_date = $now;

				if ( $this->min_date && $this->max_date
					&& $this->min_date != $this->max_date
					&& $this->min_date > $this->max_date ) {
					$this->max_date = $this->min_date;
				}

				$this->status = $this->min_date && $this->min_date > $now
					? 'future'
					: 'active';
			}
			
			if ( $this->firesale && ( $this->is_active() || $this->is_scheduled() ) ) {
				$this->firesale = $this->min_date &&
					( $this->max_date || self::is_quantity($this->max_orders) ) &&
					( $this->init_discount || $this->rec_discount );
			}
		} else {
			if ( !( $this->is_trash() || $this->is_draft() ) )
				$this->status = 'active';
			$this->product_id = null;
			$this->init_discount = 0;
			$this->rec_discount = 0;
			$this->min_date = null;
			$this->max_date = null;
			$this->max_orders = null;
			$this->firesale = false;
		}
		
		# string fields
		foreach ( array(
			'ukey',
			'name',
			'memo',
			) as $var ) {
			if ( (string) $this->$var === '' )
				continue;
			
			switch ( $var ) {
			case 'ukey':
				$this->$var = sanitize_title($this->$var);
				break;
			
			case 'name':
				$this->$var = trim($this->$var);
			case 'memo':
				# esc_html() should prevent double escaping problems
				$this->$var = esc_html($this->$var);
				break;
			}
		}
		
		# name and ukey
		if ( !$this->is_product() ) {
			if ( !$this->ukey ) {
				if ( $this->aff_id ) {
					if ( $this->name )
						$this->ukey = sanitize_title($this->name);
					if ( !$this->ukey )
						$this->ukey = sanitize_title($aff->user_login);
				}
				if ( !$this->ukey )
					$this->ukey = 'campaign';
			}
			
			$this->sanitize_ukey();
			
			if ( !$this->name )
				$this->name = $this->ukey;
		}
		
		return $this;
	} # sanitize()
	
	
	/**
	 * is_coupon()
	 *
	 * @return bool $is_coupon
	 **/

	function is_coupon() {
		return $this->product_id
			&& ( $this->init_discount || $this->rec_discount );
	} # is_coupon()
	
	
	/**
	 * is_product()
	 *
	 * @return bool $is_product
	 **/

	function is_product() {
		$product = $this->product();
		return $this->id && $product->id() && $product->uuid() == $this->uuid ||
			!$this->id && product($this->uuid())->id();
	} # is_product()
	
	
	/**
	 * product()
	 *
	 * @param product $product
	 * @return product $product
	 **/

	function product(product $product = null) {
		if ( isset($product) ) {
			$this->product = $product;
			$this->product_id = $product->id();
		}
		
		if ( !isset($this->product) )
			$this->product = new product($this->product_id);
		
		return $this->product;
	} # product()
	
	
	/**
	 * admin_url()
	 *
	 * @param array $args
	 * @return string $url
	 **/
	
	function admin_url($args = null) {
		return $this->is_product()
			? $this->product()->admin_url($args)
			: parent::admin_url($args);
	} # admin_url()
	
	
	/**
	 * save()
	 *
	 * @return object $this
	 **/

	function save() {
		parent::save();
		$this->product = null;
		return $this;
	} # save()
	
	
	/**
	 * can_delete()
	 *
	 * @return bool $can_delete
	 **/

	function can_delete() {
		if ( !$this->id || $this->is_product() )
			return false;
		
		$orders = wp_cache_get($this::type . '_orders_' . $this->id, 'counts');
		if ( $orders )
			return false;
		
		$can_delete = wp_cache_get($this::type . '_can_delete_' . $this->id, 'counts');
		if ( $can_delete !== false )
			return (bool) $can_delete;
		
		$id = (int) $this->id;
		
		$can_delete = sb::db()->query("
			SELECT EXISTS (
				SELECT	1
				FROM	orders as o
				WHERE	o.campaign_id = $id
				) as has_orders
			")->fetchObject()->has_orders;
		
		wp_cache_add($this::type . '_can_delete_' . $this->id, (int) $can_delete, 'counts');
		
		return (bool) $can_delete;
	} # can_delete()
	
	
	/**
	 * map_meta_cap()
	 *
	 * @param array $caps
	 * @param string $cap
	 * @param int $user_id
	 * @param array $args
	 * @return array $caps
	 **/
	
	static function map_meta_cap($caps, $cap, $user_id, $args) {
		switch ( $cap ) {
		case 'delete_campaign':
			if ( !campaign($args[0])->can_delete() ) {
				$caps = array('do_not_allow');
				break;
			}
			
		case 'view_campaign':
		case 'edit_campaign':
		case 'publish_campaign':
		case 'publish_coupon':
		case 'edit_coupon':
		
		case 'manage_campaigns':
		case 'view_campaigns':
		case 'edit_campaigns':
		case 'publish_campaigns':
		case 'delete_campaigns':
		case 'publish_coupons':
		case 'edit_coupons':
			$caps = array('manage_campaigns');
			break;
		}
		
		switch ( $cap ) {
		case 'do_not_allow':
		#case 'manage_campaigns':
		#case 'edit_coupons':
		#case 'edit_coupon':
		#case 'publish_coupons':
		#case 'publish_coupon':
		#case 'edit_campaigns':
		#case 'edit_campaign':
		#case 'publish_campaigns':
		#case 'publish_campaign':
		#case 'delete_campaigns':
		#case 'delete_campaign':
			$caps = array('do_not_allow');
		}
		
		return $caps;
	} # map_meta_cap()
} # campaign


/**
 * campaign_set
 *
 * @package Semiologic Backend
 **/

class campaign_set extends s_dataset_base implements campaign_type, s_dataset {
	/**
	 * __set_state()
	 *
	 * @param mixed $key
	 * @return object $object
	 **/

	static function __set_state($key = null) {
		return campaign_set($key);
	} # __set_state()
	
	
	/**
	 * statuses()
	 *
	 * @return array $statuses
	 **/

	function statuses() {
		return array(
			'all',
			'active',
			'future',
			'inactive',
			'pending',
			'draft',
			'trash',
			);
	} # statuses()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/

	function defaults() {
		$defaults = parent::defaults();
		
		$defaults['product_id'] = null;
		$defaults['aff_id'] = !current_user_can('manage_campaigns')
			? (int) wp_get_current_user()->ID
			: null;
		
		return $defaults;
	} # defaults()
	
	
	/**
	 * sorts()
	 *
	 * @return array $sorts
	 **/

	function sorts() {
		return array(
			'name',
			);
	} # sorts()
	
	
	/**
	 * parse()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function parse($args = null) {
		parent::parse($args);
		
		$args = (array) $args;
		
		foreach ( $this->defaults() as $k => $v ) {
			switch ( $k ) {
			case 'aff_id':
				$this->args[$k] = isset($args[$k]) && current_user_can('mamange_campaigns')
					? $this->set_id($args[$k], true)
					: $v;
			
			case 'product_id':
				$this->args[$k] = isset($args[$k])
					? $this->set_id($args[$k], true)
					: $v;
				break;
			}
		}
		
		return $this;
	} # parse()
	
	
	/**
	 * sql_filters()
	 *
	 * @param array $args
	 * @return array $sql
	 **/

	function sql_filters($args = null) {
		if ( $this->is_singular )
			return array();
		
		$args = $this->filters($args);
		$sql = parent::sql_filters($args);
		
		$sql['aff_id'] = $this->aff_sql($args);
		$sql['product_id'] = $this->product_sql($args);
		
		if ( $args['status'] == 'all' )
			$sql['status']['where'] = "c.status NOT IN ('trash', 'inherit')";
		
		return $sql;
	} # sql_filters()
	
	
	/**
	 * search_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function search_sql($args) {
		return array();
		/*
		$s = $args['s'];
		$filter = parent::search_sql($args);
		
		if ( !$s || self::is_id($s) || self::is_uuid($s) || !current_user_can('manage_campaigns') )
			return $filter;
		
		$filter['join'] = array();
		
		$db = sb::db();
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		$s_sql = $db->escape(addcslashes($s, '_%\\'));
		if ( strlen($s) < 5 )
			$match = "ILIKE '$s_sql%'";
		else
			$match = "ILIKE '%$s_sql%'";
		
		if ( !$args['aff_id'] ) {
			$filter['join'][] = "
			LEFT JOIN	users as u
			ON			u.ID = $t.aff_id
			AND			( u.display_name $match OR u.user_email $match OR u.user_login $match )";
			$filter['where'][] = "u.ID IS NOT NULL";
		}
		
		if ( !$args['product_id'] ) {
			$filter['join'][] = "
			LEFT JOIN	products as p
			ON			( p.name $match OR p.ukey $match )";
			$filter['where'][] = "p.id IS NOT NULL";
		}
		*/
		return $filter;
	} # search_sql()
	
	
	/**
	 * aff_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function aff_sql($args) {
		$aff_id = current_user_can('manage_campaigns')
			? (int) $args['aff_id']
			: (int) wp_get_current_user()->ID;
		$filter = array();
		
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		if ( $aff_id )
			$filter['where'] = "$t.aff_id = $aff_id";
		
		return $filter;
	} # aff_sql()
	
	
	/**
	 * product_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function product_sql($args) {
		$product_id = $args['product_id'];
		$filter = array();
		
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		if ( $product_id )
			$filter['where'] = "$t.product_id = $product_id";
		
		return $filter;
	} # product_sql()
	
	
	/**
	 * cache()
	 *
	 * @return object $this;
	 **/

	function cache() {
		return $this
			->cache_users()
			->cache_products();
	} # cache()
	
	
	/**
	 * cache_users()
	 *
	 * @return object $this
	 **/

	function cache_users() {
		$user_ids = array();
		
		foreach ( $this as $row ) {
			if ( !$row->id() )
				continue;
			$aff_id = $row->aff_id();
			if ( $aff_id && wp_cache_get($aff_id, 'users') === false )
				$user_ids[] = $aff_id;
		}
		
		if ( !$user_ids )
			return $this;
		
		global $wpdb;
		$user_ids = array_map('intval', $user_ids);
		
		$users = $wpdb->get_results("
			SELECT	*
			FROM	$wpdb->users
			WHERE	ID IN ( " . implode(', ', $user_ids) . " )
			");
		
		foreach ( $users as $user ) {
			wp_cache_add($user->ID, $user, 'users');
			wp_cache_add($user->user_login, $user->ID, 'userlogins');
			wp_cache_add($user->user_email, $user->ID, 'useremail');
			wp_cache_add($user->user_nicename, $user->ID, 'userslugs');
		}
		
		return $this;
	} # cache_users()
	
	
	/**
	 * cache_products()
	 *
	 * @return object $this
	 **/

	function cache_products() {
		$product_ids = array();
		$uuids = array();
		
		foreach ( $this as $row ) {
			if ( !$row->id() )
				continue;
			$product_id = $row->product_id();
			if ( $product_id && wp_cache_get($product_id, 'products') === false )
				$product_ids[] = $product_id;
		}
		
		if ( !$product_ids )
			return $this;
		
		$product_ids = array_unique(array_map('intval', $product_ids));
		
		$products = sb::db()->query("
			SELECT	*
			FROM	products
			WHERE	id IN ( " . implode(', ', $product_ids) . " )
			")->fetchAll(PDO::FETCH_OBJ);
		
		foreach ( $products as $product )
			product($product)->cache();
		
		return $this;
	} # cache_products()
	
	
	/**
	 * cache_counts()
	 *
	 * @return object $this
	 **/

	function cache_counts() {
		$ids = array();
		
		foreach ( $this as $row ) {
			$id = $row->id();
			if ( !$id )
				continue;
			if ( wp_cache_get($this::type . '_orders_' . $id, 'counts') === false )
				$ids[] = $id;
		}
		
		if ( !$ids )
			return $this;
		
		$ids = array_map('intval', $ids);
		$counts = sb::db()->query("
			SELECT	campaign_id, COUNT(*) as num_orders, COUNT(NULLIF(status, 'cleared')) as pending_orders
			FROM	orders
			WHERE	campaign_id IN ( " . implode(', ', $ids) . " )
			GROUP BY campaign_id
			")->fetchAll(PDO::FETCH_OBJ);
		
		foreach ( $counts as $count ) {
			wp_cache_add($this::type . '_orders_' . $count->campaign_id, $count->num_orders - $count->pending_orders, 'counts');
			wp_cache_add($this::type . '_can_delete_' . $count->campaign_id, intval(!$count->num_orders), 'counts');
		}
		foreach ( $ids as $id ) {
			wp_cache_add($this::type . '_orders_' . $id, 0, 'counts');
			wp_cache_add($this::type . '_can_delete_' . $id, 1, 'counts');
		}
		
		return $this;
	} # cache_counts()
} # campaign_set

#add_action('save_product', array('campaign', 'save_product'), 5, 3);
#add_action('trash_product', array('campaign', 'trash_product'), 5, 3);
?>