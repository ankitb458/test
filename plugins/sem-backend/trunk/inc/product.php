<?php
include_once s_path . '/inc/data.php';


/**
 * product_type
 *
 * @package Semiologic Backend
 **/

interface product_type {
	const type = 'product';
	const types = 'products';
	const active_status = 'active';
} # product_type


/**
 * product()
 *
 * @param mixed $key
 * @return product $product
 **/

function product($key = null) {
	return $key instanceof product
		? $key
		: new product($key);
} # product()


/**
 * product_set()
 *
 * @param array $args
 * @return product_set $product_set
 **/

function product_set($args = null) {
	return $args instanceof product_set
		? $args
		: new product_set($args);
} # product_set()


/**
 * product
 *
 * @package Semiologic Backend
 **/

class product extends s_data_base implements product_type, s_data {
	protected	$id,
				$uuid,
				$ukey,
				$status = 'draft',
				$name = '',
				$init_price = 0,
				$init_comm = 0,
				$rec_price = 0,
				$rec_comm = 0,
				$rec_interval = '',
				$min_date,
				$max_date,
				$max_orders,
				$memo = '',
				$created_date,
				$modified_date,
				$coupon;
	
	
	/**
	 * __set_state()
	 *
	 * @param mixed $key
	 * @return object $object
	 **/

	static function __set_state($key = null) {
		return product($key);
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
			'init_price',
			'init_comm',
			'rec_price',
			'rec_comm',
			'rec_interval',
			'min_date',
			'max_date',
			'max_orders',
			'memo',
			'created_date',
			'modified_date',
			);
	} # fields()
	
	
	/**
	 * init_price()
	 *
	 * @param float $init_price
	 * @return float $init_price
	 **/

	function init_price($init_price = null) {
		return isset($init_price)
			? $this->init_price = $this->set_price($init_price)
			: $this->init_price;
	} # init_price()
	
	
	/**
	 * init_comm()
	 *
	 * @param float $init_comm
	 * @return float $init_comm
	 **/

	function init_comm($init_comm = null) {
		return isset($init_comm)
			? $this->init_comm = $this->set_price($init_comm)
			: $this->init_comm;
	} # init_comm()
	
	
	/**
	 * rec_price()
	 *
	 * @param float $rec_price
	 * @return float $rec_price
	 **/

	function rec_price($rec_price = null) {
		return isset($rec_price)
			? $this->rec_price = $this->set_price($rec_price)
			: $this->rec_price;
	} # rec_price()
	
	
	/**
	 * rec_comm()
	 *
	 * @param float $rec_comm
	 * @return float $rec_comm
	 **/

	function rec_comm($rec_comm = null) {
		return isset($rec_comm)
			? $this->rec_comm = $this->set_price($rec_comm)
			: $this->rec_comm;
	} # rec_comm()
	
	
	/**
	 * rec_interval()
	 *
	 * @param interval $rec_interval
	 * @return interval $rec_interval
	 **/

	function rec_interval($rec_interval = null) {
		return isset($rec_interval)
			? $this->rec_interval = $this->set_interval($rec_interval)
			: $this->rec_interval;
	} # rec_interval()
	
	
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
		
		parent::import($row);
		
		if ( isset($row['coupon']) )
			$this->coupon()->import($row['coupon']);
		
		return $this;
	} # import()
	
	
	/**
	 * sanitize()
	 *
	 * @return object $this
	 **/

	function sanitize() {
		# sanity checks on price fields
		$this->init_comm = min($this->init_comm, $this->init_price);
		$this->rec_comm = min($this->rec_comm, $this->rec_price);
		if ( !$this->rec_price )
			$this->rec_interval = '';
		
		# dates
		if ( $this->is_active() || $this->is_scheduled() ) {
			# dates are normalized as Y-m-d H:i:s by now, so we can compare them as strings
			$now = gmdate('Y-m-d H:i:s');
			
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
		if ( !$this->name && !$this->ukey )
			$this->name = __('New Product', 'sem-backend');
		elseif ( !$this->name )
			$this->name = $this->ukey;
		
		$this->sanitize_ukey();
		
		return $this;
	} # sanitize()
	
	
	/**
	 * coupon()
	 *
	 * @param campaign $coupon
	 * @return campaign $coupon
	 **/

	function coupon() {
		if ( isset($this->coupon) )
			return $this->coupon;
		
		$this->coupon = new campaign($this->uuid);
		$this->coupon->product($this);
		
		return $this->coupon;
	} # coupon()
	
	
	/**
	 * save()
	 *
	 * @return object $this
	 **/

	function save() {
		parent::save();
		$this->coupon()->product($this);
		$this->coupon()->save();
		$this->coupon = null;
		return $this;
	} # save()
	
	
	/**
	 * can_delete()
	 *
	 * @return bool $can_delete
	 **/

	function can_delete() {
		if ( !$this->id )
			return false;
		
		$orders = wp_cache_get($this::type . '_orders_' . $this->id, 'counts');
		if ( $orders )
			return false;
		
		$can_delete = wp_cache_get($this::type . '_can_delete_' . $this->id, 'counts');
		if ( $can_delete !== false )
			return (bool) $can_delete;
		
		$id = (int) $this->id;
		
		$can_delete = !sb::db()->query("
			SELECT EXISTS (
				SELECT	1
				FROM	orders as o
				WHERE	o.product_id = $id
				) as has_orders
			")->fetch();
		
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
		case 'delete_product':
			if ( !product($args[0])->can_delete() ) {
				$caps = array('do_not_allow');
				break;
			}
		
		case 'edit_product':
		case 'view_product':
		case 'publish_product':
		
		case 'manage_products':
		case 'view_products':
		case 'edit_products':
		case 'publish_products':
		case 'delete_products':
			$caps = array('manage_products');
			break;
		}
		
		switch ( $cap ) {
		case 'do_not_allow':
		#case 'manage_products':
		#case 'edit_products':
		#case 'edit_product':
		#case 'publish_products':
		#case 'publish_product':
		#case 'delete_products':
		#case 'delete_product':
			$caps = array('do_not_allow');
		}
		
		return $caps;
	} # map_meta_cap()
} # product


/**
 * product_set
 *
 * @package Semiologic Backend
 **/

class product_set extends s_dataset_base implements product_type, s_dataset {
	/**
	 * __set_state()
	 *
	 * @param mixed $key
	 * @return object $object
	 **/

	static function __set_state($key = null) {
		return product_set($key);
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
	 * cache()
	 *
	 * @return object $this
	 **/

	function cache() {
		return $this
			->cache_coupons();
	} # cache()
	
	
	/**
	 * cache_coupons()
	 *
	 * @return object $this
	 **/

	function cache_coupons() {
		$uuids = array();
		
		foreach ( $this as $row ) {
			$uuid = $row->uuid();
			if ( wp_cache_get($uuid, 'campaigns') === false )
				$uuids[] = $uuid;
		}
		
		if ( !$uuids )
			return $this;
		
		$db = sb::db();
		
		$coupons = $db->query("
			SELECT	*
			FROM	campaigns
			WHERE	uuid IN ( '" . implode("', '", $uuids) . "' )
			")->fetchAll(PDO::FETCH_OBJ);
		
		foreach ( $coupons as $coupon )
			campaign($coupon)->cache();
		foreach ( $uuids as $uuid )
			wp_cache_add($uuid, 0, 'campaigns');
		
		return $this;
	} # cache_coupons()
	
	
	/**
	 * cache_counts()
	 *
	 * @return object $this
	 **/

	function cache_counts() {
		$ids = array();
		
		foreach ( $this as $row ) {
			$id = $row->id();
			if ( wp_cache_get($this::type . '_orders_' . $id, 'counts') === false )
				$ids[] = $id;
		}
		
		if ( !$ids )
			return $this;
		
		$ids = array_map('intval', $ids);
		$counts = sb::db()->query("
			SELECT	product_id, COUNT(*) as num_orders, COUNT(NULLIF(status, 'cleared')) as pending_orders
			FROM	orders
			WHERE	product_id IN ( " . implode(', ', $ids) . " )
			GROUP BY product_id
			")->fetchAll(POD::FETCH_OBJ);
		
		foreach ( $counts as $count ) {
			wp_cache_add($this::type . '_orders_' . $count->product_id, $count->num_orders - $count->pending_orders, 'counts');
			wp_cache_add($this::type . '_can_delete_' . $count->product_id, intval(!$count->num_orders), 'counts');
		}
		foreach ( $ids as $id ) {
			wp_cache_add($this::type . '_orders_' . $id, 0, 'counts');
			wp_cache_add($this::type . '_can_delete_' . $id, 1, 'counts');
		}
		
		return $this;
	} # cache_counts()
} # product_set
?>