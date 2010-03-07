<?php
include_once s_path . '/inc/data-admin.php';


/**
 * edit_product()
 *
 * @param mixed $args
 * @return edit_product $edit_product
 **/

function edit_product($args = null) {
	return $args instanceof edit_product
		? $args
		: new edit_product($args);
} # edit_product()


/**
 * edit_product
 *
 * @package Semiologic Backend
 **/

class edit_product extends s_edit_base implements product_type, s_screen {
	/**
	 * init_screen()
	 *
	 * @return void
	 **/

	static function init_screen() {	
		parent::init_screen(__CLASS__);
	} # init_screen()
	
	
	/**
	 * __construct()
	 *
	 * @param array $args
	 * @return void
	 **/

	function __construct($args = null) {
		parent::__construct($args);
		$this->init();
	} # __construct()
	
	
	/**
	 * captions()
	 *
	 * @return array $captions
	 **/

	function captions() {
		static $captions;
		if ( isset($captions) )
			return $captions;
		
		$captions = array_merge(parent::captions(), array(
			'new_product' => __('New Product', 'sem-backend'),
			'edit_product' => __('Edit Product', 'sem-backend'),
			
			'product_updated' => sprintf(
				__('Product Saved. <a href="%1$s">View Products</a>. <a href="%2$s">View Campaigns</a>.', 'sem-backend'),
				esc_url(product_set(array('status' => $this->row()->status()))->admin_url()),
				esc_url(campaign_set(array('status' => $this->row()->coupon()->status()))->admin_url())
				),
			
			'publish_product' => __('Release', 'sem-backend'),
			
			'product_status' => __('Status:', 'sem-backend'),
			'coupon_status' => __('Promo:', 'sem-backend'),
			
			'publish_product_now' => __('Release:', 'sem-backend'),
			'publish_product_on' => __('Release on:', 'sem-backend'),
			'publish_product_on_past' => __('Released on:', 'sem-backend'),
			
			'publish_coupon_now' => __('Launch:', 'sem-backend'),
			'publish_coupon_on' => __('Launch on:', 'sem-backend'),
			'publish_coupon_on_past' => __('Launched on:', 'sem-backend'),
			
			'product_unit' => __('Product left', 'sem-backend'),
			'product_units' => __('Products left', 'sem-backend'),
			
			'coupon_unit' => __('Coupon left', 'sem-backend'),
			'coupon_units' => __('Coupons left', 'sem-backend'),
			
			'ukey' => __('Unique Key (SKU)', 'sem-backend'),
			'ukey_tip' => sprintf(__('The above field is optional, but it comes in handy to create buy buttons: %s', 'sem-backend'), '<code>[buy product=&quot;sku&quot;/]</code>'),
			
			'price' => __('Pricing Information', 'sem-backend'),
			'list_price' => __('List Price', 'sem-backend'),
			'discount_price' => __('Promo Price', 'sem-backend'),
			'comm' => __('Affiliate Commission', 'sem-backend'),
			'month' => __('Month', 'sem-backend'),
			'quarter' => __('Quarter', 'sem-backend'),
			'year' => __('Year', 'sem-backend'),
			
			'firesale' => __('Firesale', 'sem-backend'),
			'firesale_tip' => __('Start a firesale at the promo price, and reach the list price as the campaign expires in time or availability.', 'sem-backend'),
			
			'memo' => __('Memo / Description', 'sem-backend'),
			
			'active' => __('Active', 'sem-backend'),
			'inactive' => __('Inactive', 'sem-backend'),
			));
		
		return $captions;
	} # captions()
	
	
	/**
	 * init()
	 *
	 * @return object $this
	 **/

	function init() {
		$captions =& $this->captions();
		$row = $this->row();
		$coupon = $row->coupon();
		
		extract($row->get('status'));
		extract($coupon->get('status', 'min_date', 'max_date', 'max_orders'), EXTR_PREFIX_ALL, 'coupon');
		
		foreach ( array(
			'ukey',
			'price',
			'firesale',
			'memo',
			) as $box_id ) {
			$title = $captions[$box_id];
			$context = 'body';
			$args = array(
				'group' => $this::type,
				);
			$priority = 5;
			
			switch ( $box_id ) {
			case 'firesale':
				$args['group'] = 'coupon';
				break;
			
			case 'memo':
				$priority = 20;
				break;
			}
			
			$this->add_box(
				$context,
				$box_id,
				$title,
				$box_id . '_box',
				$args,
				$priority
				);
		}
		
		foreach ( array(
			'status',
			'min_date',
			'max_date',
			'max_orders',
			) as $meta_id ) {
			$args = array(
				'field' => $meta_id,
				'group' => $this::type,
				);
			$priority = 5;
			
			switch ( $meta_id ) {
			case 'status':
				$callback = 'status_meta';
				$values = array();
				$statuses = $row->statuses();
				foreach ( $statuses as $key ) {
					switch ( $key ) {
					case 'draft':
					case 'pending':
						if ( $row->is_draft() || $row->is_pending() )
							$values[$key] = $captions[$key];
						break;

					case 'future':
						if ( $row->is_scheduled() )
							$values[$key] = $captions[$key];
						break;

					case 'active':
						if ( $row->is_scheduled() )
							break;
					case 'inactive':
						if ( !$row->is_draft() && !$row->is_pending() )
							$values[$key] = $captions[$key];
						break;
					}
				}
				
				$args['readonly'] = ( count($values) <= 1 );
				$args['values'] = $values;
				break;
			
			case 'min_date':
				$callback = 'date_meta';
				$args['date_type'] = 'timestamp';
				break;
				
			case 'max_date':
				$callback = 'date_meta';
				$args['date_type'] = 'expires';
				break;
				
			case 'max_orders':
				$callback = 'availability_meta';
				break;
			}
			
			self::add_meta(
				$meta_id,
				$callback,
				$args,
				$priority
				);
		}
		
		foreach ( array(
			'status',
			'min_date',
			'max_date',
			'max_orders',
			) as $meta_id ) {
			$args = array(
				'id' => "coupon-$meta_id",
				'name' => "coupon[$meta_id]",
				'value' => ${'coupon_' . $meta_id},
				'group' => 'coupon',
				'main' => false,
				'row' => $coupon,
				);
			$priority = 15;
			
			switch ( $meta_id ) {
			case 'status':
				$callback = 'status_meta';
				$values = array();
				$statuses = $coupon->statuses();
				foreach ( $statuses as $key ) {
					switch ( $key ) {
					case 'draft':
					case 'pending':
						if ( !$coupon->is_draft() && !$coupon->is_pending() )
							break;
						$args['value'] = 'inactive';
						break;
					
					case 'future':
						if ( $coupon->is_scheduled() )
							$values[$key] = $captions[$key];
						break;
					
					case 'active':
						if ( $coupon->is_scheduled() )
							break;
					case 'inactive':
						$values[$key] = $captions[$key];
						break;
					}
				}
				
				$args['readonly'] = ( count($values) == 1 );
				$args['values'] = $values;
				break;
			
			case 'min_date':
				$callback = 'date_meta';
				$args['date_type'] = 'timestamp';
				break;
				
			case 'max_date':
				$callback = 'date_meta';
				$args['date_type'] = 'expires';
				break;
				
			case 'max_orders':
				$callback = 'availability_meta';
				break;
			}
			
			$this->add_meta(
				'coupon_' . $meta_id,
				$callback,
				$args,
				$priority
				);
		}
		
		return $this;
	} # init()
	
	
	/**
	 * ukey_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function ukey_box(array $args) {
		$captions =& $this->captions();
		
		extract($this->get('ukey'));
		
		$ukey = esc_attr($ukey);
		
		echo <<<EOS
<p><input type="text" name="ukey" id="ukey" value="$ukey" class="widefat" autocomplete="off" /></p>
<p>{$captions['ukey_tip']}</p>

EOS;
		
		return $this;
	} # ukey_box()
	
	
	/**
	 * price_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function price_box(array $args) {
		$captions =& $this->captions();
		
		extract($this->get('init_price', 'init_comm', 'rec_price', 'rec_comm', 'rec_interval'));
		extract($this->row()->coupon()->get('init_discount', 'rec_discount'));
		
		$init_discount_price = $init_price - $init_discount;
		$rec_discount_price = $rec_price - $rec_discount;
		
		foreach ( array('init', 'rec') as $type ) {
			foreach ( array('price', 'comm', 'discount', 'discount_price') as $var ) {
				$price = $type . '_price';
				$var = $type . '_' . $var;
				if ( !$$price && !$$var )
					$$var = '';
			}
		}
		
		if ( !$rec_interval )
			$rec_interval = 'month';
		
		$group = esc_attr($this::type . '-group');
		
		echo <<<EOS
<table class="form-table pricelist">
<tr>
<th style="width: 38%">{$captions['list_price']}</th>
<th>{$captions['discount_price']}</th>
<th>{$captions['comm']}</th>
</tr>
<tr valign="top">
<td class="price">
<input type="hidden" id="hidden_init_price" name="init_price" value="$init_price" />
<input type="hidden" id="hidden_rec_price" name="rec_price" value="$rec_price" />
<input type="text" size="5" id="init_price" value="$init_price" class="number $group" autocomplete="off" />
+&nbsp;<input type="text" size="5" id="rec_price" value="$rec_price" class="number $group" autocomplete="off" />
/&nbsp;<select id="rec_interval" name="rec_interval">

EOS;
	
		foreach ( array('month', 'quarter', 'year') as $interval ) {
			echo '<option value="' . $interval. '"'
				. selected($rec_interval, $interval, false)
				. '>' . $captions[$interval] . '</option>' . "\n";
		}
	
		echo <<<EOS
</select>
</td>
<td class="price">
<input type="hidden" id="init_discount" name="coupon[init_discount]" value="$init_discount" />
<input type="hidden" id="rec_discount" name="coupon[rec_discount]" value="$rec_discount" />
<input type="text" size="5" id="init_discount_price" value="$init_discount_price" class="number $group" autocomplete="off"$all_disabled />
+&nbsp;<input type="text" size="5" id="rec_discount_price" value="$rec_discount_price" class="number $group" autocomplete="off"$all_disabled />
/&nbsp;<span class="rec_interval">{$captions[$rec_interval]}</span>
</td>
<td class="price">
<input type="hidden" id="hidden_init_comm" name="init_comm" value="$init_comm" />
<input type="hidden" id="hidden_rec_comm" name="rec_comm" value="$rec_comm" />
<input type="text" size="5" id="init_comm" value="$init_comm" $disabled class="number $group" autocomplete="off" />
+&nbsp;<input type="text" size="5" id="rec_comm" value="$rec_comm" $disabled class="number $group" autocomplete="off" />
/&nbsp;<span class="rec_interval">{$captions[$rec_interval]}</span>
</td>
</tr>
</table>

EOS;
		
		return $this;
	} # price_box()
	
	
	/**
	 * firesale_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function firesale_box(array $args) {
		$captions =& $this->captions();
		
		$checked = checked($this->row()->coupon()->firesale(), true, false);
		
		echo <<<EOS
<p><label><input type="checkbox" id="coupon-firesale" name="coupon[firesale]" $checked />&nbsp;{$captions['firesale_tip']}</label></p>

EOS;
		
		return $this;
	} # firesale_box()
} # edit_product


/**
 * manage_products
 *
 * @package Semiologic Backend
 **/

class manage_products extends s_manage_base implements product_type, s_screen {
	/**
	 * init_screen()
	 *
	 * @return void
	 **/

	static function init_screen() {	
		parent::init_screen(__CLASS__);
	} # init_screen()
	
	
	/**
	 * captions()
	 *
	 * @return array $captions
	 **/

	function captions() {
		static $captions;
		if ( isset($captions) )
			return $captions;
		
		$captions = array_merge(parent::captions(), array(
			'manage_' . $this::types => __('Manage Products', 'sem-backend'),
			
			'published_' . $this::type => __('Product Activated.', 'sem-backend'),
			'untrashed_' . $this::type => __('Product Restored.', 'sem-backend'),
			'trashed_' . $this::type => __('Product Moved to Trash.', 'sem-backend'),
			'deleted_' . $this::type => __('Product Deleted Permanently.', 'sem-backend'),
			'bulk_published_' . $this::types => __('%d Products Activated.', 'sem-backend'),
			'bulk_untrashed_' . $this::types => __('%d Products Restored.', 'sem-backend'),
			'bulk_trashed_' . $this::types => __('%d Products Moved to Trash.', 'sem-backend'),
			'bulk_deleted_' . $this::types => __('%d Products Deleted Permanently', 'sem-backend'),
			
			'search_' . $this::types => __('Search Products', 'sem-backend'),
			
			'publish_' . $this::type . '_tip' => __('Activate &#8220;%1$s&#8221;', 'sem-backend'),
			'publish_' . $this::type . '_link' => __('Activate', 'sem-backend'),
			
			'active' => __('Active', 'sem-backend'),
			'inactive' => __('Inactive', 'sem-backend'),
			));
		
		return $captions;
	} # captions()
	
	
	/**
	 * columns()
	 *
	 * @return array $cols
	 **/

	function columns() {
		$cols = array();
		
		$cols['cb'] = '<input type="checkbox" />';
		$cols['name'] = __('Name', 'sem-backend');
		$cols['price'] = __('List Price', 'sem-backend');
		$cols['discount_price'] = __('Promo Price', 'sem-backend');
		$cols['comm'] = __('Affiliate Commission', 'sem-backend');
		$cols['status'] = __('Status', 'sem-backend');
		$cols['expires'] = __('Expires', 'sem-backend');
		$cols['orders'] = __('Orders', 'sem-backend');
		
		return $cols;
	} # columns()
	
	
	/**
	 * print_filters()
	 *
	 * @return object $this
	 **/

	function print_filters() {
		# nothing to do...
		return $this;
	} # print_filters()
	
	
	/**
	 * row_price()
	 *
	 * @return object $this
	 **/

	function row_price() {
		$row = $this->row();
		echo $this->format_price(
			$row->init_price(),
			$row->rec_price(),
			$row->rec_interval());
		return $this;
	} # row_price()
	
	
	/**
	 * row_discount_price()
	 *
	 * @return object $this
	 **/

	function row_discount_price() {
		$captions = $this->captions();
		$row = $this->row();
		echo $this->format_price(
			$row->init_price() - $row->coupon()->init_discount(),
			$row->rec_price() - $row->coupon()->rec_discount(),
			$row->rec_interval());
		if ( !$row->coupon()->is_active() ) {
			echo ' ('
				. '<span class="sm-state">'
				. $captions['inactive']
				. '</span>)';
		}
		return $this;
	} # row_discount_price()
	
	
	/**
	 * row_comm()
	 *
	 * @return object $this
	 **/

	function row_comm() {
		$row = $this->row();
		echo $this->format_price(
			$row->init_comm(),
			$row->rec_comm(),
			$row->rec_interval());
		return $this;
	} # row_comm()
	
	
	/**
	 * row_status()
	 *
	 * @return object $this
	 **/

	function row_status() {
		$row = $this->row();
		parent::row_status();
		
		if ( $row->is_scheduled() ) {
			echo '<br />' . "\n"
				. date_i18n(__('M j, Y', 'sem-backend'), strtotime($row->min_date() . ' GMT') + 3600 * get_option('gmt_offset'), true);
		}
		
		return $this;
	} # row_status()
	
	
	/**
	 * row_expires()
	 *
	 * @return object $this
	 **/

	function row_expires() {
		$row = $this->row();
		if ( !$row->is_active() && !$row->is_scheduled()
			|| !$row->max_date() && !self::is_quantity($row->max_orders()) ) {
			echo '&nbsp;';
			return $this;
		}
		
		if ( self::is_quantity($row->max_orders()) ) {
			echo sprintf(_n('%s Order', '%s Orders', 'sem-backend'), $row->max_orders(), number_format_i18n($row->max_orders()));
			if ( $row->max_date() )
				echo '<br />' . "\n";
		}
		
		if ( $row->max_date() ) {
			echo date_i18n(__('M j, Y', 'sem-backend'), @strtotime($row->max_date() . " GMT") + 3600 * get_option('gmt_offset'), true);
		}
		
		return $this;
	} # row_expires()
	
	
	/**
	 * row_orders()
	 *
	 * @return object $this
	 **/

	function row_orders() {
		$row = $this->row();
		$id = (int) $row->id();
		$orders = (int) wp_cache_get($this::type . '_orders_' . $id, 'counts');
		
		if ( !$orders ) {
			echo '&nbsp;';
			return $this;
		}
		
		$url = admin_url('admin.php?page=orders&product_id=' . $id);
		
		echo '<a href="' . esc_url($url) . '"'
			. ' title="' . esc_attr(__('View Orders', 'sem-backend')) . '"'
			. '>' . number_format_i18n($orders) . '</a>';
		
		return $this;
	} # row_orders()
} # manage_products
?>