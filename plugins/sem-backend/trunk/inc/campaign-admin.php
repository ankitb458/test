<?php
include_once s_path . '/inc/data-admin.php';


/**
 * edit_campaign()
 *
 * @param mixed $args
 * @return edit_campaign $edit_campaign
 **/

function edit_campaign($args = null) {
	return $args instanceof edit_campaign
		? $args
		: new edit_campaign($args);
} # edit_campaign()


/**
 * manage_campaigns()
 *
 * @param mixed $args
 * @return manage_campaigns $manage_campaigns
 **/

function manage_campaigns($args = null) {
	return $args instanceof manage_campaigns
		? $args
		: new manage_campaigns($args);
} # manage_campaigns()


/**
 * edit_campaign
 *
 * @package Semiologic Backend
 **/

class edit_campaign extends s_edit_base implements campaign_type, s_screen {
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
			'new_campaign' => __('New Campaign', 'sem-backend'),
			'edit_campaign' => __('Edit Campaign', 'sem-backend'),
			
			'campaign_updated' => sprintf(
				__('Campaign Saved. <a href="%1$s">View Campaigns</a>.', 'sem-backend'),
				esc_url(campaign_set(array('status' => $this->row()->status()))->admin_url())
				),
			
			'publish_campaign' => __('Launch', 'sem-backend'),
			
			'coupon_status' => __('Status:', 'sem-backend'),
			
			'publish_coupon_now' => __('Launch:', 'sem-backend'),
			'publish_coupon_on' => __('Launch on:', 'sem-backend'),
			'publish_coupon_on_past' => __('Launched on:', 'sem-backend'),
			
			'coupon_unit' => __('Coupon left', 'sem-backend'),
			'coupon_units' => __('Coupons left', 'sem-backend'),
			
			'ukey' => __('Tracking URL', 'sem-backend'),
			'ukey_tip' => sprintf(__('Any URL on this domain that is built such as the one above (i.e. the normal URL with an %1$s parameter) will track visitors referred by this campaign; %2$s also works, with the same result.', 'sem-backend'), '<code>aff=</code>', '<code>thank=</code>'),
			
			'coupon' => __('Coupon Code', 'sem-backend'),
			'coupon_tip' => __('The coupon code is automatically pre-filled for users who are referred through this campaign\'s tracking URL.', 'sem-backend'),
			
			'affiliate' => __('Affiliate', 'sem-backend'),
			'aff_tip' => __('Enter an affiliate\'s name or email above to change this campaign\'s owner.', 'sem-backend'),
			
			'product' => __('Product (for Promos, Coupons and Gift Certificates)', 'sem-backend'),
			'product_tip' => __('Coupons are a special kind of campaign, which allow you to retrocede part or all of your affiliate commission on a product. To create a coupon, enter a product\'s name or SKU above, and adjust its promo price.', 'sem-backend'),
			
			'discount' => __('Discount Information', 'sem-backend'),
			'discount_tip' => __('Please note that issuing coupons is subject to moderation.', 'sem-backend'),
			
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
		
		extract($row->get('status', 'product_id'));
		
		foreach ( array(
			'affiliate',
			'ukey',
			'product',
			'coupon',
			'discount',
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
			case 'affiliate':
				if ( !current_user_can('manage_campaigns') )
					continue 2;
			
			case 'ukey':
				if ( $row->is_product() )
					continue 2;
				break;
			
			case 'coupon':
				if ( $row->is_product() )
					continue 2;
				if ( !$product_id && !current_user_can('edit_coupons') )
					continue 2;
				$context = 'side';
				if ( !$product_id )
					$args['hidden'] = true;
				$args['group'] = 'coupon';
				break;
				
			case 'discount':
			case 'firesale':
				if ( !$product_id )
					$args['hidden'] = true;
			case 'product':
				if ( !$product_id && !current_user_can('edit_coupons') )
					continue 2;
				$args['readonly'] = !current_user_can('edit_coupons');
				$args['group'] = 'coupon';
				break;
			
			case 'memo':
				$priority = 20;
				break;
			}
			
			self::add_box(
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
				'group' => 'coupon',
				);
			$priority = 5;
			
			switch ( $meta_id ) {
			case 'status':
				$callback = 'status_meta';
				$values = array();
				$statuses = $row->statuses();
				if ( !$product_id && !current_user_can('edit_coupon', $row) ) {
					$values = array($status => $captions[$row->status()]);
				} else {
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
				}
				
				$args['readonly'] = ( count($values) <= 1 || !current_user_can('edit_coupon', $row) );
				$args['values'] = $values;
				break;
			
			case 'min_date':
				if ( !$product_id && !current_user_can('edit_coupon', $row) )
					continue 2;
				$callback = 'date_meta';
				$args['date_type'] = 'timestamp';
				$args['hidden'] = !$product_id;
				$args['readonly'] = !current_user_can('edit_coupon', $row);
				break;
				
			case 'max_date':
				if ( !$product_id && !current_user_can('edit_coupon', $row) )
					continue 2;
				$callback = 'date_meta';
				$args['date_type'] = 'expires';
				$args['hidden'] = !$product_id;
				$args['readonly'] = !current_user_can('edit_coupon', $row);
				break;
				
			case 'max_orders':
				if ( !$product_id && !current_user_can('edit_coupon', $row) )
					continue 2;
				$callback = 'availability_meta';
				$args['hidden'] = !$product_id;
				$args['readonly'] = !current_user_can('edit_coupon', $row);
				break;
			}
			
			self::add_meta(
				$meta_id,
				$callback,
				$args,
				$priority
				);
		}
		
		return $this;
	} # init()
	
	
	/**
	 * headers()
	 *
	 * @return object $this
	 **/

	function headers() {
		parent::headers();
		
		if ( $this->row()->is_product() )
			throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
		
		return $this;
	} # headers()
	
	
	/**
	 * affiliate_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function affiliate_box(array $args) {
		if ( !current_user_can('manage_campaigns') )
			return $this;
		
		$captions =& $this->captions();
		
		extract($this->get('aff_id'));
		
		if ( $aff_id ) {
			$user = new WP_User($aff_id);
			$aff_name = $user->display_name;
			$aff_name .= current_user_can('edit_users')
				? ( ' <' . $user->user_email .'>' )
				: ( ' <' . preg_replace('/@.*/', '@&#133;', $user->user_email) .'>' );
			$aff_name = esc_attr($aff_name);
		} else {
			$aff_name = '';
		}
		
		echo <<<EOS
<input type="hidden" id="user_id" name="aff_id" value="$aff_id" class="campaign_owner" />
<input type="hidden" id="hidden_user_id" value="$aff_id" />
<input type="hidden" id="hidden_suggest_user_id" value="$aff_name" />
<p><input type="text" id="suggest_user_id" value="$aff_name" autocomplete="off" size="48" class="sbsuggest suggest_user widefat suggest_campaign_owner" /></p>
<p>{$captions['aff_tip']}</p>

EOS;
		
		return $this;
	} # affiliate_box()
	
	
	/**
	 * ukey_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function ukey_box(array $args) {
		$captions =& $this->captions();
		
		extract($this->get('id', 'ukey'));
		
		$home_url = esc_attr(trailingslashit(get_option('home')));
		$ukey = esc_attr($ukey);
		
		if ( !$id ) {
			echo <<<EOS
<p><code>$home_url<b>?aff=</b></code><input type="text" id="ukey" name="ukey" value="$ukey" size="30" autocomplete="off" class="code coupon_code" /></p>
<p>{$captions['ukey_tip']}</p>

EOS;
		} else {
			echo <<<EOS
<p><input type="text" id="ukey" value="$home_url?aff=$ukey" autocomplete="off" class="widefat code" readonly="readonly" /></p>
<p>{$captions['ukey_tip']}</p>

EOS;
		}
		
		return $this;
	} # ukey_box()
	
	
	/**
	 * coupon_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function coupon_box(array $args) {
		$captions =& $this->captions();
		
		extract($this->get('id', 'ukey'));
		
		$ukey = esc_attr($ukey);
		
		$readonly = $id
			? 'readonly="readonly"'
			: '';
		
		echo <<<EOS
<p><input type="text" size="30" value="$ukey" autocomplete="off" id="coupon_code" class="widefat code coupon_code" $readonly tabindex="-1" /></p>

<p>{$captions['coupon_tip']}</p>

EOS;
		
		return $this;
	} # coupon_box()
	
	
	/**
	 * product_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function product_box(array $args) {
		$captions =& $this->captions();
		$row = $this->row();
		
		extract($row->product()->get('id', 'name'), EXTR_PREFIX_ALL, 'product');
		
		$product_name = esc_attr($product_name);
		
		if ( !current_user_can('edit_coupon', $row) ) {
			if ( !$product_id )
				return $this;
			$disabled = 'disabled="disabled"';
		} else{
			$disabled = '';
		}
		
		echo <<<EOS
<input type="hidden" id="product_id" name="product_id" value="$product_id" />
<input type="hidden" id="hidden_product_id" value="$product_id" />
<input type="hidden" id="hidden_suggest_product_id" value="$product_name" />
<p><input type="text" id="suggest_product_id" value="$product_name" autocomplete="off" size="48" class="sbsuggest suggest_product campaign_product widefat" $disabled /></p>

<p>{$captions['product_tip']}</p>

EOS;
		
		return $this;
	} # product_box()
	
	
	/**
	 * discount_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function discount_box(array $args) {
		$captions =& $this->captions();
		$row = $this->row();
		
		extract($this->get('id', 'aff_id', 'init_discount', 'rec_discount'));
		extract($row->product()->get('init_price', 'init_comm', 'rec_price', 'rec_comm', 'rec_interval'));
		
		$init_discount_price = $init_price - $init_discount;
		$rec_discount_price = $rec_price - $rec_discount;
		
		if ( $aff_id ) {
			$init_comm = $init_comm - $init_discount;
			$rec_comm = $rec_comm - $rec_discount;
		}
		
		foreach ( array('init', 'rec') as $type ) {
			foreach ( array('price', 'comm', 'discount', 'discount_price') as $var ) {
				$price = $type . '_price';
				$var = $type . '_' . $var;
				if ( !$$price && !$$var )
					$$var = '';
			}
		}
		
		$hidden = ( !$id || !$product_id ) ? 'style="display: none;"' : '';
		$disabled = !$aff_id ? 'disabled="disabled"' : '';
		
		if ( !current_user_can('edit_coupon', $row) ) {
			if ( !$product_id )
				return $this;
			$all_disabled = $disabled = 'disabled="disabled"';
		} else {
			$all_disabled = '';
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
<tr>
<td class="price">
<input type="hidden" id="hidden_init_price" value="$init_price" />
<input type="hidden" id="hidden_rec_price" value="$rec_price" />
<input type="text" size="5" id="init_price" value="$init_price" disabled="disabled" class="number $group" autocomplete="off" />
+&nbsp;<input type="text" size="5" id="rec_price" value="$rec_price" disabled="disabled" class="number $group" autocomplete="off" />
/&nbsp;<span class="rec_interval">{$captions[$rec_interval]}</span>
</td>
<td class="price">
<input type="hidden" id="init_discount" name="init_discount" value="$init_discount" />
<input type="hidden" id="rec_discount" name="rec_discount" value="$rec_discount" />
<input type="text" size="5" id="init_discount_price" value="$init_discount_price" class="number $group" autocomplete="off"$all_disabled />
+&nbsp;<input type="text" size="5" id="rec_discount_price" value="$rec_discount_price" class="number $group" autocomplete="off"$all_disabled />
/&nbsp;<span class="rec_interval">{$captions[$rec_interval]}</span>
</td>
<td class="price">
<input type="hidden" id="hidden_init_comm" value="$init_comm" />
<input type="hidden" id="hidden_rec_comm" value="$rec_comm" />
<input type="text" size="5" id="init_comm" value="$init_comm" $disabled class="number $group" autocomplete="off" />
+&nbsp;<input type="text" size="5" id="rec_comm" value="$rec_comm" $disabled class="number $group" autocomplete="off" />
/&nbsp;<span class="rec_interval">{$captions[$rec_interval]}</span>
</td>
</tr>
</table>

<p>{$captions['discount_tip']}</p>

EOS;
		
		return $this;
	} # discount_box()
	
	
	/**
	 * firesale_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function firesale_box(array $args) {
		$captions =& $this->captions();
		$checked = checked($this->row()->firesale(), true, false);
		
		echo <<<EOS
<p><label><input type="checkbox" id="firesale" name="firesale" $checked />&nbsp;{$captions['firesale_tip']}</label></p>

EOS;
		
		return $this;
	} # firesale_box()
} # edit_campaign


/**
 * manage_campaigns
 *
 * @package Semiologic Backend
 **/

class manage_campaigns extends s_manage_base implements campaign_type, s_screen {
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
			'manage_' . $this::types => __('Manage Campaigns', 'sem-backend'),
			
			'published_' . $this::type => __('Campaign Activated.', 'sem-backend'),
			'untrashed_' . $this::type => __('Campaign Restored.', 'sem-backend'),
			'trashed_' . $this::type => __('Campaign Moved to Trash.', 'sem-backend'),
			'deleted_' . $this::type => __('Campaign Deleted Permanently.', 'sem-backend'),
			'bulk_published_' . $this::types => __('%d Campaigns Activated.', 'sem-backend'),
			'bulk_untrashed_' . $this::types => __('%d Campaigns Restored.', 'sem-backend'),
			'bulk_trashed_' . $this::types => __('%d Campaigns Moved to Trash.', 'sem-backend'),
			'bulk_deleted_' . $this::types => __('%d Campaigns Deleted Permanently', 'sem-backend'),
			
			'search_' . $this::types => __('Search Campaigns', 'sem-backend'),
			
			'publish_' . $this::type . '_tip' => __('Activate &#8220;%1$s&#8221;', 'sem-backend'),
			'publish_' . $this::type . '_link' => __('Activate', 'sem-backend'),
			
			'affiliate' => __('Affiliate', 'sem-backend'),
			'product' => __('Product', 'sem-backend'),
			
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
		$cols['name'] = __('Campaign', 'sem-backend');
		if ( current_user_can('manage_campaigns') )
			$cols['affiliate'] = __('Affiliate', 'sem-backend');
		$cols['product'] = __('Product', 'sem-backend');
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
		$captions =& $this->captions();
		
		$args = $this->dataset->args();
		$defaults = $this->dataset->defaults();
		$done = false;
		
		if ( current_user_can('manage_campaigns') ) {
			$suggest_aff = esc_attr($captions['affiliate']);
			
			if ( $args['aff_id'] !== $defaults['aff_id'] ) {
				$user = new WP_User($args['aff_id']);
				$aff_id = (int) $user->id;
				$aff_name = $user->display_name;
				$aff_name .= current_user_can('edit_users')
					? ( ' <' . $user->user_email .'>' )
					: ( ' <' . preg_replace('/@.*/', '@&#133;', $user->user_email) .'>' );
				$aff_name = esc_attr($aff_name);
			}
			
			if ( empty($aff_id) ) {
				$aff_id = '';
				$aff_name = $suggest_aff;
			}
			
			echo <<<EOS
<input type="hidden" id="hidden_aff_id" value="$aff_id" />
<input type="hidden" id="aff_id" name="aff_id" value="$aff_id" />
<input type="hidden" id="hidden_suggest_aff_id" value="$aff_name" />
<input type="text" size="20" id="suggest_aff_id" class="sbsuggest suggest_user" value="$aff_name" title="$suggest_aff" />

EOS;
			$done = true;
		}
		
		if ( current_user_can('edit_coupons') ) {
			$suggest_product = esc_attr($captions['product']);
			
			if ( $args['product_id'] !== $defaults['product_id'] ) {
				$product = product($args['product_id']);
				$product_id = (int) $product->id();
				$product_name = esc_attr($product->name());
			}
			
			if ( empty($product_id) ) {
				$product_id = '';
				$product_name = $suggest_product;
			}
			
			echo <<<EOS
<input type="hidden" id="hidden_product_id" value="$product_id" />
<input type="hidden" id="product_id" name="product_id" value="$product_id" />
<input type="hidden" id="hidden_suggest_product_id" value="$product_name" />
<input type="text" size="20" id="suggest_product_id" class="sbsuggest suggest_product" value="$product_name" title="$suggest_product" />

EOS;
			$done = true;
		}
		
		return $done
			? parent::print_filters()
			: $this;
	} # print_filters()
	
	
	/**
	 * row_name()
	 *
	 * @return object $this
	 **/

	function row_name() {
		$row = $this->row();
		
		echo '<strong>';
		$this->print_row_name();
		$this->print_row_state();
		echo '</strong>';
		
		if ( $row->ukey() ) {
			echo '<br />' . "\n"
				. trailingslashit(get_option('home')) . '?aff=<strong>' . $row->ukey() . '</strong>';
		} else {
			if ( $row->is_product() ) {
				echo '<br />' . "\n"
					. sprintf(__('Applies to all orders of %s', 'sem-backend'), "<strong>" . $row->product()->name() . "</strong>");
			}
		}
		
		$this->print_row_actions();
		
		return $this;
	} # row_name()
	
	
	/**
	 * row_affiliate()
	 *
	 * @return object $this
	 **/

	function row_affiliate() {
		$row = $this->row();
		$aff_id = $row->aff_id();
		
		if ( !$aff_id ) {
			echo '&nbsp;';
			return $this;
		}
		
		$url = $this->dataset->admin_url(array('aff_id' => $aff_id), true);
		$user = new WP_User($aff_id);
		
		echo '<a href="' . esc_url($url) . '">'
			. $user->display_name
			. '</a>';
		
		if ( is_email($user->user_email) ) {
			echo '<br />'
				. '<a href="' . esc_url('mailto:' . $user->user_email) . '"'
				. ' title="' . esc_attr(sprintf(__('Email &#8220;%s&#8221;', 'sem-backend'), $user->display_name)) . '"'
				. '>'
				. $user->user_email
				. '</a>';
		}
		
		return $this;
	} # row_affiliate()
	
	
	/**
	 * row_product()
	 *
	 * @return object $this
	 **/

	function row_product() {
		$row = $this->row();
		$product = $row->product();
		$product_id = (int) $product->id();
		
		if ( !$product_id ) {
			echo '&nbsp;';
			return $this;
		}
		
		
		$url = $this->dataset->admin_url(array('product_id' => $product_id), true);
		
		echo '<a href="' . esc_url($url) . '">'
			. $product->name()
			. '</a>';
		
		if ( !$product->is_active() && !$product->is_scheduled() ) {
			$captions = $this->captions();
			echo ' - '
				. '<span class="sm-state">'
				. $captions[$product->status()]
				. '</span>';
		}
		
		$discount = $this->format_price(
			$row->init_discount(),
			$row->rec_discount(),
			$product->rec_interval());
		
		if ( !$discount )
			return $this;
		
		echo '<br />' . "\n";
		
		if ( $row->firesale() ) {
			echo sprintf(__('%s Off (Firesale)', 'sem-backend'), $discount);
		} else {
			echo sprintf(__('%s Off', 'sem-backend'), $discount);
		}
		
		return $this;
	} # row_product()
	
	
	/**
	 * row_status()
	 *
	 * @return object $this
	 **/

	function row_status() {
		$row = $this->row();
		parent::row_status();
		
		extract($row->get('min_date', 'max_date', 'firesale'));
		
		switch ( $row->is_product() ) {
		case true:
			if ( !$row->is_coupon() )
				return $this;
			if ( $row->is_scheduled() ||
				$min_date && $max_date ||
				$min_date && $firesale )
				break;
			
		default:
			return $this;
		}
		
		echo '<br />' . "\n"
			. date_i18n(__('M j, Y', 'sem-backend'), strtotime($min_date . ' GMT') + 3600 * get_option('gmt_offset'), true);
		
		return $this;
	} # row_status()
	
	
	/**
	 * row_expires()
	 *
	 * @return object $this
	 **/

	function row_expires() {
		$row = $this->row();
		extract($row->get('max_date', 'max_orders'));
		
		if ( !$row->is_coupon()
			|| !$row->is_active() && !$row->is_scheduled()
			|| !$max_date && !self::is_quantity($max_orders) ) {
			echo '&nbsp;';
			return $this;
		}
		
		if ( self::is_quantity($max_orders) ) {
			echo sprintf(_n('%s Order', '%s Orders', 'sem-backend'), $max_orders, number_format_i18n($max_orders));
			if ( $max_date )
				echo '<br />' . "\n";
		}
		
		if ( $max_date ) {
			echo date_i18n(__('M j, Y', 'sem-backend'), @strtotime("$max_date GMT") + 3600 * get_option('gmt_offset'), true);
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
		
		if ( $row->is_product() )
			$url = admin_url('admin.php?page=orders&coupon_id=' . $id);
		else
			$url = admin_url('admin.php?page=orders&campaign_id=' . $id);
		
		echo '<a href="' . esc_url($url) . '"'
			. ' title="' . esc_attr(__('View Orders', 'sem-backend')) . '"'
			. '>' . number_format_i18n($orders) . '</a>';
		
		return $this;
	} # row_orders()
} # manage_campaigns
?>