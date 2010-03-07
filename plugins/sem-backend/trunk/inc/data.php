<?php
/**
 * s_data
 *
 * @package Semiologic Backend
 **/

interface s_data {
} # s_data


/**
 * s_dataset
 *
 * @package Semiologic Backend
 **/

interface s_dataset {
} # s_dataset


/**
 * s_screen
 *
 * @package Semiologic Backend
 **/

interface s_screen {
} # s_screen


/**
 * s_base
 *
 * @package Semiologic Backend
 **/

abstract class s_base {
	const	PERMISSION_DENIED = 1,
			INVALID_FIELD = 2,
			READONLY_FIELD = 4,
			INVALID_VALUE = 8,
			INVALID_ROW = 16;
	
	
	/**
	 * is_id()
	 *
	 * @param mixed $id
	 * @return bool $is_id
	 **/

	static function is_id($id) {
		return $id &&
			is_numeric($id) &&
			intval($id) == $id &&
			intval($id) > 0;
	} # is_id()
	
	
	/**
	 * set_id()
	 *
	 * @param id $value
	 * @param bool $null
	 * @return id $value
	 **/

	protected function set_id($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( !$value && $null )
			return null;
		if ( !self::is_id($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return (int) $value;
	} # set_id()
	
	
	/**
	 * is_uuid()
	 *
	 * @param mixed $uuid
	 * @return bool $is_uuid
	 **/

	static function is_uuid($uuid) {
		return $uuid &&
			is_string($uuid) &&
			preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/", $uuid);
	} # is_uuid()
	
	
	/**
	 * set_uuid()
	 *
	 * @param uuid $value
	 * @param bool $null
	 * @return uuid $value
	 **/

	protected function set_uuid($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !self::is_uuid($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return $value;
	} # set_uuid()
	
	
	/**
	 * set_status()
	 *
	 * @param interval $value
	 * @param bool $null
	 * @return interval $value
	 **/

	protected function set_status($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !in_array($value, $this->statuses()) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return $value;
	} # set_status()
	
	
	/**
	 * is_date()
	 *
	 * @param mixed $date
	 * @return bool $is_date
	 **/
	
	static function is_date($date) {
		return $date &&
			is_string($date) &&
			preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date);
	} # is_date()
	
	
	/**
	 * set_date()
	 *
	 * @param date $value
	 * @param bool $null
	 * @return date $value
	 **/

	protected function set_date($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !self::is_date($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return $value;
	} # set_date()
	
	
	/**
	 * is_price()
	 *
	 * @param mixed $price
	 * @return bool $is_price
	 **/

	static function is_price($price) {
		return is_numeric($price) &&
			floatval($price) >= 0;
	} # is_price()
	
	
	/**
	 * set_price()
	 *
	 * @param price $value
	 * @param bool $null
	 * @return price $value
	 **/

	protected function set_price($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( $value && !self::is_price($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return round(floatval($value), 2);
	} # set_price()
	
	
	/**
	 * is_interval()
	 *
	 * @param mixed $interval
	 * @return bool $is_interval
	 **/

	static function is_interval($interval) {
		return in_array($interval, array('', 'month', 'quarter', 'year'));
	} # is_interval()
	
	
	/**
	 * set_interval()
	 *
	 * @param interval $value
	 * @param bool $null
	 * @return interval $value
	 **/

	protected function set_interval($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !self::is_interval($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return $value;
	} # set_interval()
	
	
	/**
	 * is_amount()
	 *
	 * @param mixed $price
	 * @return bool $is_price
	 **/

	static function is_amount($price) {
		return is_numeric($price);
	} # is_amount()
	
	
	/**
	 * set_amount()
	 *
	 * @param amount $value
	 * @param bool $null
	 * @return amount $value
	 **/

	protected function set_amount($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !self::is_amount($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return round(floatval($value), 2);
	} # set_amount()
	
	
	/**
	 * is_quantity()
	 *
	 * @param mixed $quantity
	 * @return bool
	 **/

	static function is_quantity($quantity) {
		return is_numeric($quantity) &&
			intval($quantity) == $quantity &&
			$quantity >= 0;
	} # is_quantity()
	
	
	/**
	 * set_quantity()
	 *
	 * @param quantity $value
	 * @param bool $null
	 * @return quantity $value
	 **/

	protected function set_quantity($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !self::is_quantity($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return (int) $value;
	} # set_quantity()
	
	
	/**
	 * set_bool()
	 *
	 * @param bool $value
	 * @param bool $null
	 * @return bool $value
	 **/

	protected function set_bool($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		if ( !is_bool($value) && !is_numeric($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return (bool) $value;
	} # set_bool()
	
	
	/**
	 * set_string()
	 *
	 * @param string $value
	 * @param bool $null
	 * @return string $value
	 **/

	protected function set_string($value, $null = false) {
		if ( is_null($value) && $null )
			return null;
		if ( !is_scalar($value) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		if ( (string) $value === '' && $null )
			return null;
		return wp_kses_no_null(trim($value));
	} # set_string()
	
	
	/**
	 * format_amount()
	 *
	 * @param amount $init
	 * @param amount $rec
	 * @param string $interval
	 * @return void
	 **/

	function format_price($init, $rec, $interval) {
		$o = '';
		$currency = get_option('fancy_currency');
		$captions = array(
			'month' => __('Month', 'sem-backend'),
			'quarter' => __('Quarter', 'sem-backend'),
			'year' => __('Year', 'sem-backend'),
			);
		
		if ( $rec != $init ) {
			$o .= sprintf($currency, number_format_i18n($init, $init != intval($init) ? 2 : 0));
			if ( $rec )
				$o .= ' + ';
		}
		
		if ( $rec ) {
			$o .= sprintf($currency, number_format_i18n($rec, $rec != intval($rec) ? 2 : 0))
				. '&nbsp;/&nbsp;' . $captions[$interval];
		}
		
		return $o;
	} # format_price()
} # s_base


/**
 * s_data_base
 *
 * @package Semiologic Backend
 **/

abstract class s_data_base extends s_base {
	protected	$id,
				$uuid,
				$ukey,
				$status = 'draft',
				$name = '',
				$memo = '',
				$created_date,
				$modified_date;
	
	
	/**
	 * __construct()
	 *
	 * @param mixed $key
	 * @return void
	 **/

	function __construct($key = null) {
		$this->fetch($key);
		if ( self::is_id($key) && (int) $key !== (int) $this->id )
			throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
	} # __construct()
	
	
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
			'memo',
			'created_date',
			'modified_date',
			);
	} # fields()
	
	
	/**
	 * get()
	 *
	 * @param mixed $field
	 * @return array $values
	 **/

	function get($field = null) {
		if ( !isset($field) ) {
			$fields = $this->fields();
		} elseif ( is_array($field) ) {
			$fields = $field;
		} elseif ( is_string($field) ) {
			$fields = func_get_args();
		} else {
			throw new Exception('Invalid field.', self::INVALID_FIELD);
		}
		
		if ( $invalid = array_diff($fields, $this->fields()) )
			throw new Exception('Invalid fields: ' . implode(', ', $invalid), self::INVALID_FIELD);
		
		foreach ( $fields as $field )
			$$field = $this->$field();
		
		return compact($fields);
	} # get()
	
	
	/**
	 * set()
	 *
	 * @param mixed $field
	 * @param mixed $value
	 * @return object $this
	 **/

	function set($field = null, $value = null) {
		if ( is_string($field) )
			$fields = array($field => $value);
		elseif ( is_array($field) || is_object($field) )
			$fields = (array) $field;
		else
			throw new Exception('Invalid field.', self::INVALID_FIELD);
		
		if ( $invalid = array_diff(array_keys($fields), $this->fields()) )
			throw new Exception('Invalid fields: ' . implode(', ', $invalid), self::INVALID_FIELD);
		
		foreach ( $fields as $field => $value )
			$this->$field($value);
		
		return $this;
	} # set()
	
	
	/**
	 * id()
	 *
	 * @param id $id
	 * @return id $id
	 **/
	
	function id($id = null) {
		if ( !isset($id) )
			return (int) $this->id;
		
		$id = $this->set_id($id);
		
		if ( isset($this->id) && $id !== $this->id )
			throw new Exception('Read-only field.', self::READONLY_FIELD);
		
		return (int) $this->id = $id;
	} # id()
	
	
	/**
	 * uuid()
	 *
	 * @return uuid $uuid
	 * @return uuid $uuid
	 **/
	
	function uuid($uuid = null) {
		if ( !isset($uuid) ) {
			if ( isset($this->uuid) ) {
				return $this->uuid;
			} else {
				global $wpdb;
				return $this->uuid = $wpdb->get_var("SELECT UUID() as value;");
			}
		}
			
		$uuid = $this->set_uuid($uuid);
		
		if ( isset($this->uuid) && $uuid !== $this->uuid )
			throw new Exception('Read-only field.', self::READONLY_FIELD);
		
		return $this->uuid = $uuid;
	} # uuid()
	
	
	/**
	 * ukey()
	 *
	 * @param ukey $ukey
	 * @return ukey $ukey
	 **/
	
	function ukey($ukey = null) {
		return isset($ukey)
			? $this->ukey = $this->set_string($ukey, true)
			: $this->ukey;
	} # ukey()
	
	
	/**
	 * status()
	 *
	 * @param status $status
	 * @return status $status
	 **/
	
	function status($status = null) {
		return isset($status)
			? $this->status = $this->set_status($status)
			: $this->status;
	} # status()
	
	
	/**
	 * name()
	 *
	 * @param string $name
	 * @return string $name
	 **/
	
	function name($name = null) {
		return isset($name)
			? $this->name = $this->set_string($name)
			: $this->name;
	} # name()
	
	
	/**
	 * memo()
	 *
	 * @param string $memo
	 * @return string $memo
	 **/
	
	function memo($memo = null) {
		return isset($memo)
			? $this->memo = $this->set_string($memo)
			: $this->memo;
	} # memo()
	
	
	/**
	 * created_date()
	 *
	 * @param date $created_date
	 * @return date $created_date
	 **/

	function created_date($created_date = null) {
		return isset($created_date)
			? $this->created_date = $this->set_date($created_date)
			: $this->created_date;
	} # created_date()
	
	
	/**
	 * modified_date()
	 *
	 * @param date $modified_date
	 * @return date $modified_date
	 **/

	function modified_date($modified_date = null) {
		return isset($modified_date)
			? $this->modified_date = $this->set_date($modified_date)
			: $this->modified_date;
	} # modified_date()
	
	
	/**
	 * fetch()
	 *
	 * @param mixed $key
	 * @return object $this
	 **/

	function fetch($key = null) {
		if ( !isset($key) ) {
			# called like:
			# $row = new class;
			
			return $this;
		} elseif ( is_object($key) ) {
			# called like:
			# $row = $wpdb->get_results($sql);
			# foreach ( $rows as $row )
			#   $obj = new class($row);
			
			return $this->set($key);
		} elseif ( is_array($key) ) {
			# called like:
			# $args = stripslashes_deep($_GET);
			# $row = new class($args);
			
			extract(array_intersect_key($key, array_fill_keys(array('uuid', 'id', 'ukey'), null)));
			
			if ( isset($uuid) ) {
				if ( !self::is_uuid($uuid) )
					throw new Exception('Invalid ' . get_class($this) . '::$uuid', self::INVALID_VALUE);
				return $this->set('uuid', $uuid)->fetch($uuid);
			} elseif ( isset($id) ) {
				if ( is_scalar($id) && !$id )
					return $this;
				if ( !self::is_id($id) )
					throw new Exception('Invalid ' . get_class($this) . '::$id', self::INVALID_VALUE);
				return $this->fetch($id);
			} elseif ( isset($ukey) ) {
				if ( !is_scalar($ukey) )
					throw new Exception('Invalid ' . get_class($this) . '::$ukey', self::INVALID_VALUE);
				return $this->set('ukey', $ukey)->fetch($ukey);
			} else {
				return $this;
			}
		} elseif ( !is_scalar($key) ) {
			throw new Exception('Invalid ' . get_class($this) . ' key', self::INVALID_VALUE);
		}
		
		if ( !$key )
			return $this;
		
		$row = wp_cache_get($key, $this::types);
		if ( $row !== false ) {
			if ( !is_object($row) ) {
				$this->fetch($row);
				if ( self::is_uuid($key) )
					return $this->set('uuid', $key);
				elseif ( self::is_id($key) )
					return $this;
				else
					return $this->set('ukey', $key);
			}
				
			
			# cache() has already cleaned this up
			foreach ( $this->fields() as $field )
				$this->$field = $row->$field;
			
			return $this;
		}
		
		global $wpdb;
		$table = $this::types;
		
		if ( self::is_id($key) ) {
			# called like:
			# $row = new class($id);
			
			$id = (int) $key;
			$row = $wpdb->get_row("
				SELECT	*
				FROM	`{$wpdb->$table}`
				WHERE	id = $id
				");
		} elseif ( self::is_uuid($key) ) {
			# called like:
			# $row = new class($uuid);
			
			$this->set('uuid', $key);
			$uuid = $wpdb->_real_escape($key);
			$row = $wpdb->get_row("
				SELECT	*
				FROM	`{$wpdb->$table}`
				WHERE	uuid = '$uuid'
				");
		} else {
			# called like:
			# $row = new class($ukey);
			
			$this->set('ukey', $key);
			$ukey = $wpdb->_real_escape($key);
			$row = $wpdb->get_row("
				SELECT	*
				FROM	`{$wpdb->$table}`
				WHERE	ukey = '$ukey'
				");
		}
		
		return $row
			? $this->set($row)->cache()
			: $this;
	} # fetch()
	
	
	/**
	 * import()
	 *
	 * @param array|object $row
	 * @return object $this
	 **/

	function import($row = null) {
		$row = (array) $row;
		
		# catch insufficient permissions
		if ( !current_user_can('edit_' . $this::type, $this) )
			throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
		
		# catch publish UI
		if ( !empty($row['publish']) && !( $this->is_active() || $this->is_scheduled() ) ) {
			if ( current_user_can('publish_' . $this::type, $this) ) {
				$row['status'] = 'active';
			} else {
				if ( $this->is_draft() )
					$row['status'] = 'pending';
			}
		}
		
		# catch date/time UI
		foreach ( $this->dates() as $date => $time ) {
			if ( !isset($row[$time]) || empty($row[$date]) )
				continue;
			
			$row[$date] = $row[$date] . ' ' . ( $row[$time] ? $row[$time] : '00:00:00' ) . ' GMT';
			$row[$date] = @strtotime($row[$date]);
			$row[$date] = $row[$date]
				? gmdate('Y-m-d H:i:s', $row[$date] - 3600 * get_option('gmt_offset'))
				: '';
			
			unset($row[$time]);
		}
		
		# catch prices UI
		foreach ( array('price', 'comm', 'discount') as $var ) {
			$init = 'init_' . $var;
			$rec = 'rec_' . $var;
			if ( isset($row[$init]) && isset($row[$rec]) && !is_numeric($row[$init]) )
				$row[$init] = $row[$rec];
		}
		
		# strip non-fields
		$row = array_intersect_key($row, array_fill_keys($this->fields(), null));
		
		return $this->set($row);
	} # import()
	
	
	/**
	 * sanitize_ukey()
	 *
	 * @return object $this
	 **/

	function sanitize_ukey() {
		if ( !$this->ukey )
			return $this;
		
		global $wpdb;
		$table = $this::types;
		$ukey = $this->ukey;
		$suffix = 1;
		
		if ( $this->id )
			$exclude = "id <> " . (int) $this->id;
		else
			$exclude = "uuid <> '" . $wpdb->_real_escape($this->uuid()) . "'";
		
		# prevent ukeys from polluting the object cache
		if ( self::is_id($ukey) || self::is_uuid($ukey) )
			$ukey = "$ukey-$suffix";
		
		do {
			$conflict = $wpdb->get_var("
				SELECT EXISTS(
					SELECT	1
					FROM	`{$wpdb->$table}`
					WHERE	ukey = '" . $wpdb->_real_escape($ukey) . "'
					AND		$exclude
					) as conflict
				");
			
			if ( !$conflict )
				return $this->set('ukey', $ukey);
			
			$suffix++;
			$ukey = "$this->ukey-$suffix";
		} while ( $conflict );
	} # sanitize_ukey()
	
	
	/**
	 * cache()
	 *
	 * @return object $this
	 **/

	function cache() {
		if ( !$this->id )
			return $this;
		
		wp_cache_add($this->id, (object) $this->get(), $this::types);
		wp_cache_add($this->uuid, $this->id, $this::types);
		if ( $this->ukey && !self::is_id($this->ukey) && !self::is_uuid($this->ukey) )
			wp_cache_add($this->ukey, $this->id, $this::types);
		
		return $this;
	} # cache()
	
	
	/**
	 * clear_cache()
	 *
	 * @return object $this
	 **/

	function clear_cache() {
		if ( !$this->id )
			return $this;
		
		foreach ( array('id', 'uuid', 'ukey') as $key ) {
			if ( $this->$key )
				wp_cache_delete($key, $this::types);
		}
		
		return $this;
	} # clear_cache()
	
	
	/**
	 * clear_counts()
	 *
	 * @return object $this
	 **/

	function clear_counts() {
		if ( !$this->id )
			return $this;
		
		$type = $this::type;
		wp_cache_delete($type . '_orders_' . $this->id, 'counts');
		wp_cache_delete($type . '_can_delete_' . $this->id, 'counts');
		
		return $this;
	} # clear_counts()
	
	
	/**
	 * is_active()
	 *
	 * @return bool $is_active
	 **/

	function is_active() {
		return $this->id && ( $this->status == $this::active_status );
	} # is_active()
	
	
	/**
	 * is_scheduled()
	 *
	 * @return bool $is_trash
	 **/

	function is_scheduled() {
		return $this->id && ( $this->status == 'future' );
	} # is_scheduled()
	
	
	/**
	 * is_pending()
	 *
	 * @return bool $is_pending
	 **/

	function is_pending() {
		return $this->id && ( $this->status == 'pending' );
	} # is_pending()
	
	
	/**
	 * is_draft()
	 *
	 * @return bool $is_draft
	 **/

	function is_draft() {
		return !$this->id || ( $this->status == 'draft' );
	} # is_draft()
	
	
	/**
	 * is_trash()
	 *
	 * @return bool $is_trash
	 **/

	function is_trash() {
		return $this->id && ( $this->status == 'trash' );
	} # is_trash()
	
	
	/**
	 * admin_url()
	 *
	 * @param array $args
	 * @return string $url
	 **/

	function admin_url($args = null) {
		$url = admin_url('admin.php?page=' . $this::type);
		if ( $this->id )
			$url .= '&id=' . $this->id();
		if ( is_array($args) )
			$url .= '&' . sb::build_query($args);
		return $url;
	} # admin_url()
	
	
	/**
	 * publish()
	 *
	 * @return object $this
	 **/

	function publish() {
		return $this->id && !( $this->is_active() || $this->is_scheduled() )
			? $this->set(array('status' => $this::active_status))->save()
			: $this;
	} # publish()
	
	
	/**
	 * trash()
	 *
	 * @return object $this
	 **/

	function trash() {
		return $this->id && !$this->is_trash() && $this->can_delete()
			? $this->set('status', 'trash')->save()
			: $this;
	} # trash()
	
	
	/**
	 * untrash()
	 *
	 * @return object $this
	 **/

	function untrash() {
		return $this->id && $this->is_trash()
			? $this->set('status', 'draft')->save()
			: $this;
	} # untrash()
	
	
	/**
	 * delete()
	 *
	 * @return bool $success
	 **/

	function delete() {
		if ( !$this->id || !$this->is_trash() || !$this->can_delete() )
			return false;
		
		global $wpdb;
		$type = $this::type;
		$table = $this::types;
		
		# do stuff before
		do_action('delete_' . $type, $this);
		
		$wpdb->query("DELETE FROM {$wpdb->$table} WHERE id = " . $this->id());
		
		# clear cache and counts
		$this->clear_cache()->clear_counts();
		
		# do stuff after
		do_action('deleted_' . $type, $this);
		
		return true;
	} # delete()
	
	
	/**
	 * save()
	 *
	 * @return object $this
	 **/

	function save() {
		$this->sanitize();
		
		if ( $this->uuid ) {
			$class = $this::type;
			$old = new $class($this->uuid);
		} else {
			throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
		}
		
		$id = $old->id();
		
		do_action('pre_save_' . $this::type, $this, $old);
		
		global $wpdb;
		$table = $this::types;
		$sql = array();
		$now = gmdate('Y-m-d H:i:s'); # MySQL's NOW() isn't always GMT
		
		if ( !$id ) {
			foreach ( $this->fields() as $field ) {
				switch ( $field ) {
				case 'id':
					continue 2;
					
				case 'created_date':
				case 'modified_date':
					$sql["`$field`"] = "'$now'";
					continue 2;
					
				default:
					if ( is_null($this->$field) ) {
						$sql["`$field`"] = 'NULL';
					} elseif ( is_bool($this->$field) ) {
						$sql["`$field`"] = intval($this->$field);
					} elseif ( is_int($this->$field) || is_float($this->$field) ) {
						$sql["`$field`"] = $this->$field;
					} else {
						$sql["`$field`"] = "'" . $wpdb->_real_escape($this->$field) . "'";
					}
				}
			}
				
			$sql = "
				INSERT INTO `{$wpdb->$table}`
					( " . implode(', ', array_keys($sql)) . " )
				VALUES
					( " . implode(', ', $sql) . " )
				";
			
			$wpdb->query($sql);
			$this->id($wpdb->insert_id);
		} else {
			foreach ( $this->fields() as $field ) {
				switch ( $field ) {
				case 'id':
				case 'uuid':
				case 'created_date':
					continue 2;
					
				case 'modified_date':
					$sql[] = "`$field` = '$now'";
					continue 2;
				
				default:
					if ( is_null($this->$field) ) {
						$sql[] = "`$field` = NULL";
					} elseif ( is_bool($this->$field) ) {
						$sql[] = "`$field` = " . intval($this->$field);
					} elseif ( is_int($this->$field) || is_float($this->$field) ) {
						$sql[] = "`$field` = " . $this->$field;
					} else {
						$sql[] = "`$field` = '" . $wpdb->_real_escape($this->$field) . "'";
					}
				}
			}
			
			$sql = "
				UPDATE	`{$wpdb->$table}`
				SET		" . implode(', ', $sql) . "
				WHERE	id = $id
				";
			
			$wpdb->query($sql);
		}
		
		# clear cache
		$this->clear_cache()->clear_counts();
		$old->clear_cache();
		
		# broadcast the change
		do_action('save_' . $this::type, $this, $old);
		
		if ( $this->status != $old->status )
			do_action('transition_' . $old->status . '_' . $this->status . '_' . $this::type, $this, $old);
		
		if ( $this->is_trash() && !$old->is_trash() )
			do_action('trash_' . $this::type, $this, $old);
		elseif ( !$this->is_trash() && $old->is_trash() )
			do_action('untrash_' . $this::type, $this, $old);
		
		if ( $this->is_scheduled() && !$old->is_scheduled() )
			do_action('schedule_' . $this::type, $this, $old);
		elseif ( $this->is_active() && !$old->is_active() )
			do_action('publish_' . $this::type, $this, $old);
		elseif ( $this->is_pending() && !$old->is_pending() )
			do_action('pending_' . $this::type, $this, $old);
		elseif ( !$this->is_active() && $old->is_active() )
			do_action('unpublish_' . $this::type, $this, $old);
		elseif ( !$this->is_scheduled() && $old->is_scheduled() )
			do_action('unschedule_' . $this::type, $this, $old);
		
		return $this;
	} # save()
} # s_data_base


/**
 * s_dataset_base
 *
 * @package Semiologic Backend
 **/

abstract class s_dataset_base extends s_base implements Iterator {
	const		per_page = 20;
	protected	$action,
				$args,
				$rows,
				$is_singular = false;
	
	
	/**
	 * __construct()
	 *
	 * @param mixed $args
	 * @return void
	 **/

	function __construct($args = null) {
		$this->parse($args);
	} # __construct()
	
	
	/**
	 * set_ids()
	 *
	 * @param array $ids
	 * @return array $ids
	 **/

	function set_ids($ids) {
		if ( !is_array($ids) )
			throw new Exception('Invalid Value.', self::INVALID_VALUE);
		return array_map(array($this, 'set_id'), $ids);
	} # set_ids()
	
	
	/**
	 * set_paged()
	 *
	 * @return $paged
	 **/

	function set_paged($paged) {
		return $this->set_id($paged);
	} # set_paged()
	
	
	/**
	 * set_sort()
	 *
	 * @return $sort
	 **/

	function set_sort($sort) {
		$sorts = $this->sorts();
		if ( !in_array($sort, $sorts) &&
			!(substr($sort, 0, 1) == '_' && in_array(substr($sort, 1), $sorts)) )
			throw new Exception('Invalid value.', self::INVALID_VALUE);
		return $sort;
	} # set_sort()
	
	
	/**
	 * per_page()
	 *
	 * @return int $per_page
	 **/

	function per_page() {
		return $this::per_page;
	} # per_page()
	
	
	/**
	 * is_singular()
	 *
	 * @return bool $is_singular()
	 **/

	function is_singular() {
		return $this->is_singular;
	} # is_singular()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/

	function defaults() {
		return array(
				'status' => $this::active_status,
				's' => '',
				'paged' => 1,
				'sort' => 'name',
				);
	} # defaults()
	
	
	/**
	 * args()
	 *
	 * @param bool $strip
	 * @return array $args
	 **/

	function args($strip = false) {
		return $strip
			? array_diff_assoc($this->args, $this->defaults())
			: $this->args;
	} # args()
	
	
	/**
	 * arg()
	 *
	 * @param string $key
	 * @return mixed $value
	 **/

	function arg($key) {
		return isset($this->args[$key])
			? $this->args[$key]
			: null;
	} # arg()
	
	
	/**
	 * is_arg()
	 *
	 * @param string $ky
	 * @return bool $is_arg
	 **/

	function is_arg($key) {
		return isset($this->args[$key]);
	} # is_arg()
	
	
	/**
	 * has_arg()
	 *
	 * @return void
	 **/

	function has_arg($key) {
		$args = $this->args(true);
		return isset($args[$key]);
	} # has_arg()
	
	
	/**
	 * parse()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	protected function parse($args = null) {
		$type = $this::type;
		$this->args = array();
		$this->is_singular = false;
		unset($this->rows);
		
		$args = (array) $args;
		
		extract(array_intersect_key($args, array_fill_keys(array('uuid', 'id', 'ukey', 'ids'), null)));
		
		if ( isset($uuid) ) {
			$this->is_singular = true;
			$this->args['uuid'] = $this->set_uuid($uuid);
		} elseif ( isset($id) ) {
			$this->is_singular = true;
			$this->args['id'] = $this->set_id($id, true);
		} elseif ( isset($ukey) ) {
			$this->is_singular = true;
			$this->args['ukey'] = $this->set_ukey($ukey, true);
		} elseif ( isset($ids) ) {
			$this->args['ids'] = $this->set_ids($args['ids']);
		}
		
		foreach ( $this->defaults() as $k => $v ) {
			switch ( $k ) {
			case 'status':
				$this->args[$k] = isset($args[$k])
					? $this->set_status($args[$k])
					: $v;
				break;
		
			case 's':
				$this->args[$k] = isset($args[$k])
					? $this->set_string($args[$k])
					: $v;
				break;
		
			case 'sort':
				$this->args[$k] = isset($args[$k])
					? $this->set_sort($args[$k])
					: $v;
				break;
		
			case 'paged':
				$this->args[$k] = isset($args[$k])
					? $this->set_paged($args[$k])
					: $v;
				break;
			}
		}
		
		return $this;
	} # parse()
	
	
	/**
	 * actions()
	 *
	 * @return array $actions
	 **/

	function actions() {
		static $actions;
		if ( isset($actions) )
			return $actions;
		
		$actions = array();
		
		if ( current_user_can('view_' . $this::types) ) {
			$actions['view'] = 'view';
		}
		
		if ( !is_admin() )
			return $actions;
		
		if ( current_user_can('publish_' . $this::types) ) {
			if ( !in_array($this->args['status'], array($this::active_status, 'trash')) )
				$actions['publish'] = 'publish';
		}
		
		if ( current_user_can('edit_' . $this::types) ) {
			if ( $this->args['status'] != 'trash' )
				$actions['edit'] = 'edit';
			else
				$actions['untrash'] = 'edit';
		}
		
		if ( current_user_can('delete_' . $this::types) ) {
			if ( $this->args['status'] != 'trash' )
				$actions['trash'] = 'delete';
			else
				$actions['delete'] = 'delete';
		}
		
		return $actions;
	} # actions()
	
	
	/**
	 * set_action()
	 *
	 * @param string $action
	 * @return string $action
	 **/

	function set_action($action) {
		if ( !is_string($action) || !in_array($action, array_keys($this->actions())) )
			throw new Exception('Invalid Value.', self::INVALID_VALUE);
		
		$actions = $this->actions();
		$perm = $actions[$action];
		
		if ( !current_user_can($perm . '_' . $this::types) )
			throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
		
		return $action;
	} # set_action()
	
	
	/**
	 * action()
	 *
	 * @param string $action
	 * @return string $action
	 **/

	function action($action = null) {
		return isset($action)
			? $this->action = $this->set_action($action)
			: $this->action;
	} # action()
	
	
	/**
	 * parse_action()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function parse_action($args = null) {
		$args = (array) $args;
		
		if ( !isset($args['action']) ) {
			$actions = array_keys($this->actions());
			foreach ( $actions as $action ) {
				switch ( $action ) {
				case 'view':
				case 'edit':
					continue 2;
				
				default:
					if ( isset($args[$action . '_all']) ) {
						$args['action'] = $action;
						$this->args['paged'] = false;
						$this->args['sort'] = false;
					} elseif ( !empty($this->args['ids']) ) {
						foreach ( array(
							'bulk_manage' => 'bulk_action',
							'bulk_manage2' => 'bulk_action2',
							) as $k => $v ) {
							if ( !empty($args[$k]) && !empty($args[$v]) &&
								$args[$v] == 'bulk_' . $action ) {
								$args['action'] = $action;
								break 3;
							}
						}
					}
				}
			}
		}
		
		if ( !isset($args['action']) )
			$args['action'] = 'view';
		
		$this->action($args['action']);
		
		return $this;
	} # parse_action()
	
	
	/**
	 * fetch()
	 *
	 * @param array $sql
	 * @param array $args
	 * @return object $this
	 **/

	function fetch($sql = null, $args = null) {
		$this->rows = array();
		$class = $this::type;
		
		if ( $this->is_singular ) {
			$this->push($class($this->args)->cache());
		} else {
			global $wpdb;
			$rows = $wpdb->get_results($this->sql($sql, $args));
			foreach ( $rows as $row )
				$this->push($class($row)->cache());
		}
		
		return $this;
	} # fetch()
	
	
	/**
	 * push()
	 *
	 * @param product $row
	 * @return object $this
	 **/

	protected function push(s_data $row) {
		if ( !isset($this->rows) )
			$this->rows = array();
		$this->rows[(int) $row->id()] = $row;
		return $this;
	} # push()
	
	
	/**
	 * rows()
	 *
	 * @return array $rows
	 **/

	function rows() {
		if ( !isset($this->rows) )
			$this->fetch();
		return $this->rows;
	} # rows()
	
	
	/**
	 * current()
	 *
	 * @return object $row
	 **/

	function current() {
		if ( !isset($this->rows) )
			$this->fetch();
		return current($this->rows);
	} # current()
	
	
	/**
	 * key()
	 *
	 * @return int $key
	 **/

	function key() {
		if ( !isset($this->rows) )
			$this->fetch();
		return key($this->rows);
	} # key()
	
	
	/**
	 * next()
	 *
	 * @return object $row
	 **/

	function next() {
		if ( !isset($this->rows) )
			$this->fetch();
		return next($this->rows);
	} # next()
	
	
	/**
	 * prev()
	 *
	 * @return object $row
	 **/

	function prev() {
		if ( !isset($this->rows) )
			$this->fetch();
		return prev($this->rows);
	} # prev()
	
	
	/**
	 * rewind()
	 *
	 * @return object $this
	 **/

	function rewind() {
		if ( !isset($this->rows) )
			$this->fetch();
		reset($this->rows);
		return $this;
	} # rewind()
	
	
	/**
	 * valid()
	 *
	 * @return bool $valid
	 **/

	function valid() {
		if ( !isset($this->rows) )
			$this->fetch();
		$valid = (bool) current($this->rows);
		if ( !$valid )
			$this->rewind();
		return $valid;
	} # valid()
	
	
	/**
	 * count()
	 *
	 * @return int $count
	 **/

	function count() {
		if ( !isset($this->rows) )
			$this->fetch();
		return count($this->rows);
	} # count()
	
	
	/**
	 * each()
	 *
	 * @param $callback
	 * @return object $this
	 **/

	function each($callback) {
		if ( !isset($this->rows) )
			$this->fetch();
		
		$args = array_slice(func_args(), 1);
		
		foreach ( $this as $row ) {
			$result = call_user_func_array($callback, array_merge(array($row), $args));
			if ( $result === false )
				break;
		}
		
		return $this;
	} # each()
	
	
	/**
	 * filters(
	 *
	 * @param array $args
	 * @return array $args
	 **/

	function filters($args = null, $toggle = false) {
		$args = (array) $args;
		
		if ( $toggle )
			$toggle = key($args);
		
		$args = array_merge($this->args, $args);
		$defaults = $this->defaults();
		
		# toggle arg
		if ( $toggle ) {
			# always dump paged when changing filters
			$args['paged'] = $defaults['paged'];
			
			# unset search too if it matches an id or uuid
			if ( self::is_id($args['s']) || self::is_uuid($args['s']) )
				$args['s'] = $defaults['s'];
			
			if ( $args[$toggle] == $this->args[$toggle] ) {
				if ( $toggle == 'status' ) {
					if ( $args[$toggle] == 'all' )
						$args[$toggle] = $defaults[$toggle];
					else
						$args[$toggle] = 'all';
				} else {
					$args[$toggle] = $defaults[$toggle];
				}
			}
		}
		
		if ( !empty($args['ids']) )
			$args['paged'] = false;
		
		return $args;
	} # filters()
	
	
	/**
	 * admin_url()
	 *
	 * @param mixed $args
	 * @param string $toggle
	 * @return string $url
	 **/

	function admin_url($args = null, $toggle = false) {
		$args = is_string($args)
			? array('action' => $args)
			: (array) $args;
		
		$args = $this->filters($args, $toggle);
			
		$args = array_diff_assoc($args, $this->defaults());
		
		$url = admin_url('admin.php?page=' . $this::types);
		
		$nonce = isset($args['action']);
		if ( $nonce ) {
			$nonce = isset($args['id']) && isset($this->rows[$args['id']])
				? $args['action'] . '_' . $this::type . '_' . $this->rows[$args['id']]->uuid()
				: $args['action'] . '_' . $this::types;
		}
		
		# always reset paged when changing filters
		$keys = array_keys(array_diff_assoc($args, $this->args));
		if ( $nonce )
			$keys = array_diff($keys, array('action', 'id'));
		if ( $keys != array('paged') )
			unset($args['paged']);
		
		$args = sb::build_query($args);
		
		$url = $url . ( $args ? '&' . $args : '' );
		
		if ( $nonce )
			$url = wp_nonce_url($url, $nonce);
		
		return $url;
	} # admin_url()
	
	
	/**
	 * sql()
	 *
	 * @param array $sql
	 * @param array $args
	 * @return string $sql
	 **/

	function sql($sql = null, $args = null) {
		global $wpdb;
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		$filters = $this->sql_filters($args);
		
		extract((array) $sql, EXTR_SKIP);
		
		if ( !isset($select) )
			$select = "$t.*";
		if ( !isset($from) )
			$from = "`{$wpdb->$table}` as $t";
		if ( !isset($order_by) )
			$order_by = isset($filters['sort']['order_by'])
				? $filters['sort']['order_by']
				 : '';
		if ( !isset($limit) )
			$limit = isset($filters['paged']['limit'])
				? $filters['paged']['limit']
				: '';
		
		foreach ( array(
			'join',
			'where',
			'group_by',
			'having',
			) as $var ) {
			if ( !isset($$var) )
				$$var = array();
		}
		
		foreach ( $filters as $key => $filter ) {
			foreach ( array(
				'join',
				'where',
				'group_by',
				'having',
				) as $var ) {
				if ( empty($filter[$var]) )
					continue;
				switch ( $var ) {
				case 'join':
					if ( is_array($filter[$var]) )
						${$var} += array_map('trim', $filter[$var]);
					else 
						${$var}[] = trim($filter[$var]);
					break;
				case 'where':
				case 'having':
					${$var}[] = is_array($filter[$var])
						? ( "( " . implode(' OR ', $filter[$var]) . " )" )
						: $filter[$var];
					break;
				case 'group_by':
					${$var}[] = is_array($filter[$var])
						? implode(", ", $filter[$var])
						: $filter[$var];
					break;
				}
			}
		}
		
		$sql = "
			SELECT	$select
			FROM	$from";
		if ( $join )
			$sql .= "
			" . implode("
			", (array) $join);
		if ( $where )
			$sql .= "
			WHERE	" . implode(" AND ", (array) $where);
		if ( $group_by )
			$sql .= "
			GROUP BY " . implode(", ", (array) $group_by);
		if ( $having )
			$sql .= "
			HAVING	" . implode(" AND ", (array) $having);
		if ( $order_by )
			$sql .= "
			ORDER BY $order_by";
		if ( $limit )
			$sql .= "
			LIMIT $limit";
		
		return $sql;
	} # sql()
	
	
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
		$sql = array();
		
		$sql['status'] = $this->status_sql($args);
		$sql['s'] = $this->search_sql($args);
		$sql['sort'] = $this->sort_sql($args);
		$sql['paged'] = $this->paged_sql($args);
		$sql['ids'] = $this->ids_sql($args);
		
		return $sql;
	} # sql_filters()
	
	
	/**
	 * ids_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function ids_sql($args) {
		global $wpdb;
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		return !empty($args['ids'])
			? array('where' => "$t.id IN ( " . implode(', ', array_map('intval', $args['ids'])) . ")")
			: array();
	} # ids_sql()
	
	
	/**
	 * status_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function status_sql($args) {
		$status = $args['status'];
		$filter = array();
		
		if ( !$status  )
			return $filter;
		
		global $wpdb;
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		if ( $status != 'all' ) {
			$status = $wpdb->_real_escape($status);
			$filter['where'] = "$t.status = '$status'";
		} else {
			$filter['where'] = "$t.status <> 'trash'";
		}
		
		return $filter;
	} # status_sql()
	
	
	/**
	 * search_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function search_sql($args) {
		$s = $args['s'];
		$filter = array();
		
		if ( !$s )
			return $filter;
		
		global $wpdb;
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		if ( is_numeric($s) ) {
			$s_sql = intval($s);
			$filter['where'] = "$t.id = $s_sql";
		} elseif ( self::is_uuid($s) ) {
			$s_sql = $wpdb->_real_escape($s);
			$filter['where'] = "$t.uuid = '$s_sql'";
		} else {
			$s_sql = $wpdb->_real_escape(addcslashes($s, '_%\\'));
			if ( strlen($s) < 5 )
				$match = "LIKE '$s_sql%'";
			else
				$match = "LIKE '%$s_sql%'";
			
			$filter['where'] = array("$t.name $match", "$t.ukey $match");
		}
		
		return $filter;
	} # search_sql()
	
	
	/**
	 * sort_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function sort_sql($args) {
		$sort = $args['sort'];
		$filter = array();
		
		if ( !$sort )
			return $filter;
		
		$values = $this->sorts();
		
		if ( in_array($sort, $values) ) {
			$order = 'ASC';
		} elseif ( in_array('_' . $sort, $values) ) {
			$order = 'DESC';
		} else {
			$sort = $this->defaults['sort'];
			$order = 'ASC';
		}
		
		global $wpdb;
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		$filter['order_by'] = $order
			? "$t.$sort $order"
			: "$t.$sort";
		
		return $filter;
	} # sort_sql()
	
	
	/**
	 * paged_sql()
	 *
	 * @param array $args
	 * @return array $filter
	 **/

	function paged_sql($args) {
		$paged = $args['paged'];
		$filter = array();
		
		if ( !$paged )
			return $filter;
		
		$per_page = (int) $this->per_page();
		$start = ( (int) $paged - 1 ) * $per_page;
		$filter['limit'] = "$start, $per_page";
		
		return $filter;
	} # paged_sql()
	
	
	/**
	 * num_rows()
	 *
	 * @return int $num_rows
	 **/

	function num_rows() {
		$cache_id = md5('paginate_' . $this::types . '_' . serialize($this->args));
		
		$num_rows = wp_cache_get($cache_id, 'counts');
		if ( $num_rows !== false )
			return $num_rows;
		
		global $wpdb;
		$sql = $this->sql(array(
			'select' => 'COUNT(*)',
			'order_by' => false,
			'limit' => false,
			));
		$num_rows = (int) $wpdb->get_var($sql);
		wp_cache_add($cache_id, $num_rows, 'counts');
		
		return $num_rows;
	} # num_rows()
	
	
	/**
	 * status_counts()
	 *
	 * @return array $status_counts
	 **/

	function status_counts() {
		$cache_id = md5($this::type . '_status_counts_' . serialize($this->args));
		
		$values = wp_cache_get($cache_id, 'counts');
		if ( $values !== false )
			return $values;

		global $wpdb;
		$table = $this::types;
		$t = substr($table, 0, 1);
		
		$values = array_fill_keys($this->statuses(), 0);
		
		$strip = array_merge(array_keys($this->defaults()), array('uuid', 'id', 'ukey', 'ids'));
		$strip = array_fill_keys($strip, false);
		
		$sql = $this->sql(array(
			'select' => "$t.status as status, COUNT(*) as count",
			'group_by' => "$t.status",
			'order_by' => false,
			'limit' => false,
			), $strip);
		
		$res = $wpdb->get_results($sql);
		
		foreach ( $res as $status_count ) {
			if ( !isset($values[$status_count->status]) )
				continue;
			$values[$status_count->status] = (int) $status_count->count;
			if ( $status_count->status != 'trash' )
				$values['all'] += $status_count->count;
		}
		
		$values = array_diff($values, array(0));
		wp_cache_add($cache_id, $values, 'counts');
		
		$cache_id = md5('paginate_' . $this::types . '_' . serialize($this->args));
		$num_rows = wp_cache_get($cache_id, 'counts');
		if ( $num_rows === false ) {
			$num_rows = isset($values[$this->args['status']])
				? $values[$this->args['status']]
				: 0;
			wp_cache_add($cache_id, $num_rows, 'counts');
		}
		
		return $values;
	} # status_counts()
	
	
	/**
	 * id()
	 *
	 * @return int $id
	 **/

	function id() {
		return (int) $this->current()->id();
	} # id()
	
	
	/**
	 * uuid()
	 *
	 * @return uuid $uuid
	 **/

	function uuid() {
		return $this->current()->uuid();
	} # uuid()
	
	
	/**
	 * get()
	 *
	 * @param mixed $field
	 * @return array $values
	 **/

	function get($field = null) {
		if ( !isset($field) ) {
			$fields = null;
		} elseif ( is_string($field) ) {
			$fields = func_get_args();
		} elseif ( is_array($field) ) {
			$fields = $field;
		} else {
			throw new Exception('Invalid field.', self::INVALID_FIELD);
		}
		return $this->current()->get($fields);
	} # get()
	
	
	/**
	 * set()
	 *
	 * @param array $rows
	 * @return object $this
	 **/

	function set($rows = null) {
		if ( $this->is_singular )
			$rows = array($this->id() => $rows);
		foreach ( $rows as $id => $row ) {
			if ( !isset($this->rows[$id]) )
				throw new Exception('Invalid row', self::INVALID_ROW);
			$this->rows[$id]->set($row);
		}
		return $this;
	} # set()
	
	
	/**
	 * import()
	 *
	 * @param array $rows
	 * @return object $this
	 **/

	function import($rows = null) {
		if ( $this->is_singular )
			$rows = array($this->id() => (array) $rows);
		foreach ( $rows as $id => $row ) {
			if ( !isset($this->rows[$id]) )
				throw new Exception('Invalid row', self::INVALID_ROW);
			$this->rows[$id]->import($row);
		}
		return $this;
	} # import()
	
	
	/**
	 * sanitize()
	 *
	 * @return object $this
	 **/

	function sanitize() {
		foreach ( array_keys($this->rows) as $id )
			$this->rows[$id]->sanitize();
		return $this;
	} # sanitize()
	
	
	/**
	 * edit()
	 *
	 * @return array $feedback
	 **/

	function edit() {
		$feedback = 0;
		
		foreach ( $this as $row ) {
			if ( current_user_can('edit_' . $row::type, $row) )
				$feedback += intval(!is_wp_error($row->save()));
		}
		
		return $this->is_singular()
			? $this->current()->admin_url(array('updated' => $feedback))
			: array('updated' => $feedback);
	} # edit()
	
	
	/**
	 * publish()
	 *
	 * @return array $feedback
	 **/

	function publish() {
		$feedback = 0;
		
		foreach ( $this as $row ) {
			if ( current_user_can('publish_' . $row::type, $row) )
				$feedback += intval(!is_wp_error($row->publish()));
		}
		
		return array('published' => $feedback);
	} # publish()
	
	
	/**
	 * trash()
	 *
	 * @return array $feedback
	 **/

	function trash() {
		$feedback = 0;
		
		foreach ( $this as $row ) {
			if ( current_user_can('delete_' . $row::type, $row) )
				$feedback += intval(!is_wp_error($row->trash()));
		}
		
		return array('trashed' => $feedback);
	} # trash()
	
	
	/**
	 * untrash()
	 *
	 * @return array $feedback
	 **/

	function untrash() {
		$feedback = 0;
		
		foreach ( $this as $row ) {
			if ( current_user_can('edit_' . $row::type, $row) )
				$feedback += intval(!is_wp_error($row->untrash()));
		}
		
		return array('untrashed' => $feedback, 'status' => 'trash');
	} # untrash()
	
	
	/**
	 * delete()
	 *
	 * @return array $feedback
	 **/

	function delete() {
		$feedback = 0;
		
		foreach ( $this as $row ) {
			if ( current_user_can('delete_' . $row::type, $row) )
				$feedback += intval(!is_wp_error($row->delete()));
		}
		
		return array('deleted' => $feedback, 'status' => 'trash');
	} # delete()
} # s_dataset_base


/**
 * s_screen_base
 *
 * @package Semiologic Backend
 **/

abstract class s_screen_base extends s_base {
	protected	$action,
				$dataset;
	
	
	/**
	 * __construct()
	 *
	 * @param array $args
	 * @return void
	 **/
	
	function __construct($args = null) {
		$class = $this::type . '_set';
		$this->dataset = $class($args)->parse_action($args);
	} # __construct()
	
	
	/**
	 * exec()
	 *
	 * @return object $this;
	 **/

	function exec($args = null) {
		$action = $this->action();
		
		if ( $action == 'view' )
			return $this;
		
		$actions = $this->dataset->actions();
		$perm = $actions[$action];
		
		if ( $this->dataset->is_singular() ) {
			if ( !current_user_can($perm . '_' . $this::type, $this->row()) )
				throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
			check_admin_referer($action . '_' . $this::type . '_' . $this->uuid());
		} else {
			if ( !current_user_can($perm . '_' . $this::types) )
				throw new Exception('Permission Denied.', self::PERMISSION_DENIED);
			check_admin_referer('bulk_manage_' . $this::types);
		}
		
		switch ( $action ) {
		case 'edit':
			$feedback = $this->dataset->import($args)->sanitize()->$action();
			break;
		
		default:
			$feedback = $this->dataset->$action();
			break;
		}
		
		if ( !is_wp_error($feedback) ) {
			if ( is_string($feedback) ) {
				wp_redirect($feedback);
			} else {
				$feedback = array_merge($feedback, array_fill_keys(array('uuid', 'id', 'ukey', 'ids'), false));
				wp_redirect($this->dataset->admin_url($feedback));
			}
			die;
		} else {
			wp_die($feedback);
		}
		
		return $this;
	} # exec()
	
	
	/**
	 * id()
	 *
	 * @return int $id
	 **/

	function id() {
		return $this->dataset->id();
	} # id()
	
	
	/**
	 * uuid()
	 *
	 * @return uuid $uuid
	 **/

	function uuid() {
		return $this->dataset->uuid();
	} # uuid()
	
	
	/**
	 * action()
	 *
	 * @return string $action
	 **/

	function action() {
		return $this->dataset->action();
	} # action()
	
	
	/**
	 * get()
	 *
	 * @param mixed $field
	 * @return array $values
	 **/

	function get($field = null) {
		if ( !isset($field) ) {
			$fields = null;
		} elseif ( is_string($field) ) {
			$fields = func_get_args();
		} elseif ( is_array($field) ) {
			$fields = $field;
		} else {
			throw new Exception('Invalid field.', self::INVALID_FIELD);
		}
		return $this->dataset->get($fields);
	} # get()
	
	
	/**
	 * set()
	 *
	 * @param array $rows
	 * @return object $this
	 **/

	function set($rows = null) {
		$this->dataset->set($rows);
		return $this;
	} # set()
	
	
	/**
	 * row()
	 *
	 * @return object $row
	 **/

	function row() {
		return $this->dataset->current();
	} # row()
	
	
	/**
	 * args()
	 *
	 * @param bool $strip
	 * @return array $args
	 **/

	function args($strip = false) {
		return $this->dataset->args($strip);
	} # args()
} # s_screen_base
?>