<?php
/**
 * s_edit_base
 *
 * @package Semiologic Backend
 **/

abstract class s_edit_base extends s_screen_base implements s_screen {
	const		icon = 'users';
	protected	static $screen;
	protected	$dataset,
				$boxes = array(),
				$metas = array();
	
	
	/**
	 * init_screen()
	 *
	 * @param string $class
	 * @return void
	 **/

	static function init_screen($class) {
		if ( $_POST ) {
			$args = stripslashes_deep($_POST);
			$class($args)->exec($args);
		}
		
		$args = stripslashes_deep($_GET);
		self::$screen = $class($args);
		
		if ( self::$screen->action() == 'edit' && !self::$screen->id() )
			self::$screen->dataset->import($args)->sanitize();
		
		self::$screen->headers();
		do_action($class . '_headers');
	} # init_screen()
	
	
	/**
	 * display_screen()
	 *
	 * @return void
	 **/

	static function display_screen() {
		self::$screen->display();
	} # display_screen()
	
	
	/**
	 * __construct()
	 *
	 * @param array $args
	 * @return void
	 **/
	
	function __construct($args = null) {
		$args = (array) $args;
		
		# force a singular dataset
		if ( !array_intersect_key($args, array_fill_keys(array('uuid', 'id', 'ukey', 'ids'), null)) )
			$args['id'] = 0;
		
		# force action
		$args['action'] = current_user_can('edit_' . $this::types)
			? 'edit'
			: 'view';
		
		parent::__construct($args);
		$this->dataset->cache()->cache_counts();
		
		# fix WP menu page
		$GLOBALS['plugin_page'] = $this->id()
			? $this::types
			: $this::type;
	} # __construct()
	
	
	/**
	 * captions()
	 *
	 * @return array $captions
	 **/

	function captions() {
		return array(
			'update' => __('Update', 'sem-backend'),
			'save' => __('Save', 'sem-backend'),
			'save_draft' => __('Save Draft', 'sem-backend'),
			'save_pending' => __('Save as Pending', 'sem-backend'),
			'delete' => __('Move to Trash', 'sem-backend'),
			
			'edit' => __('Edit', 'sem-backend'),
			'ok' => __('OK', 'sem-backend'),
			'cancel' => __('Cancel', 'sem-backend'),
			
			'immediately' => __('Immediately', 'sem-backend'),
			'publish_on_future' => __('Schedule for:', 'sem-backend'),
			
			'expires_never' => __('Expires:', 'sem-backend'),
			'never' => __('Never', 'sem-backend'),
			'expire_on' => __('Expire on:', 'sem-backend'),
			'expire_on_past' => __('Expired on:', 'sem-backend'),
			'expire_on_future' => __('Expires on:', 'sem-backend'),
			
			'availability' => __('Availability:', 'sem-backend'),
			'unlimited' => __('Unlimited', 'sem-backend'),
			
			'edit' => __('Edit', 'sem-backend'),
			'ok' => __('OK', 'sem-backend'),
			'cancel' => __('Cancel', 'sem-backend'),
			
			'memo' => __('Memo / Description', 'sem-backend'),
			
			'future' => __('Scheduled', 'sem-backend'),
			'pending' => __('Pending', 'sem-backend'),
			'draft' => __('Draft', 'sem-backend'),
			'inherit' => __('Inherit', 'sem-backend'),
			'trash' => __('Trash', 'sem-backend'),
			);
	} # captions()
	
	
	/**
	 * headers()
	 *
	 * @return object $this
	 **/

	function headers() {
		return $this;
	} # headers()
	
	
	/**
	 * display()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function display() {
		$captions =& $this->captions();
		$row = $this->dataset->current();
		
		extract($this->get('id', 'uuid', 'status'));
		
		echo <<<EOS
<div class="wrap">
<form method="post" action="">

EOS;
		
		wp_nonce_field('edit_' . $this::type . '_' .  $uuid);
		
		screen_icon($this::icon);
		
		echo '<h2>',
			( $id ? $captions['edit_' . $this::type] : $captions['new_' . $this::type] ),
			'</h2>', "\n";
		
		if ( !empty($_GET['updated']) ) {
			echo '<div class="updated">',
				wpautop($captions[$this::type . '_updated']),
				'</div>', "\n";
		}
		
		$uuid_attr = esc_attr($uuid);
		$status_attr = esc_attr($status);
		
		$cur_date = gmdate('m/d/Y H:i:s', gmdate('U') + 3600 * get_option('gmt_offset'));
		
		echo <<<EOS
<input type="hidden" id="action" name="action" value="edit" />
<input type="hidden" id="id" value="$id" />
<input type="hidden" id="uuid" name="uuid" value="$uuid_attr" />
<input type="hidden" id="cur_status" value="$status_attr" />
<input type="hidden" id="cur_date" value="$cur_date" />

<div id="poststuff" class="metabox-holder has-right-sidebar">

<div id="side-info-column" class="inner-sidebar">

EOS;
		
		$this->submit_box();
		$this->do_boxes('side');
		do_action($this->action() . '_' . $this::type . '_side', $row);
		
		echo <<<EOS
</div>

<div id="post-body">
<div id="post-body-content">

EOS;
		
		$this->title_box();
		$this->do_boxes('body');
		do_action($this->action() . '_' . $this::type . '_body', $row);
		
		echo <<<EOS
</div>
</div>

</div>

</form>
</div>

EOS;
		
		return $this;
	} # display()
	
	
	/**
	 * submit_box()
	 *
	 * @return object $this
	 **/

	function submit_box() {
		$captions =& $this->captions();
		$row = $this->row();
		
		$title = $row->is_draft() || $row->is_pending()
			? $captions['publish_' . $this::type]
			: $captions['update'];
		
		$save_attr = esc_attr($captions['save']);
		
		echo <<<EOS
<div id="submitdiv" class="postbox submitdiv">
<h3>$title</h3>
<div class="inside">
<div class="submitbox" id="submitpost">
<div id="minor-publishing">

<div style="display:none;"><input type="submit" id="hidden-save" name="save" value="$save_attr" /></div>

<div id="minor-publishing-actions">
<div id="save-action">

EOS;
		
		if ( $row->is_draft() || $row->is_pending() ) {
			$save_draft_attr = esc_attr($captions['save_' . $row->status()]);
			echo <<<EOS
<input type="submit" id="save-post" name="save" value="$save_draft_attr" tabindex="4" class="button button-highlighted" />

EOS;
		}
		
		echo <<<EOS
</div><!--#save-action-->
<div class="clear"></div>
</div><!--#minor-publishing-actions-->

<div id="misc-publishing-actions">

EOS;
		
		$this->do_meta();
		do_action($this->action() . '_' . $this::type . '_meta', $row);
		
		echo <<<EOS

</div><!--#misc-publishing-actions-->

<div class="clear"></div>
</div><!--#minor-publishing-->

<div id="major-publishing-actions">

EOS;
		
		if ( $row->id() && current_user_can('delete_' . $this::type, $row) ) {
			$delete_url = esc_url($this->dataset->admin_url(array('action' => 'trash', 'id' => $row->id())));
			echo <<<EOS
<div id="delete-action">
<a class="submitdelete deletion" href="$delete_url">{$captions['delete']}</a>
</div><!--#delete-action-->

EOS;
		}
		
		if ( !current_user_can('publish_' . $this::type, $row) )
			$publish_attr = esc_attr($captions['save_pending']);
		elseif ( !$row->is_draft() && !$row->is_pending() )
			$publish_attr = esc_attr($captions['update']);
		elseif ( $row->is_scheduled() )
			$publish_attr = esc_attr($captions['schedule']);
		else
			$publish_attr = esc_attr($captions['publish_' . $this::type]);
		
		$admin_url = untrailingslashit(admin_url());
		echo <<<EOS
<div id="publishing-action">
<img src="$admin_url/images/wpspin_light.gif" id="ajax-loading" style="visibility:hidden;" alt="" />
<input name="publish" id="publish" type="submit" class="button-primary" tabindex="5" accesskey="p" value="$publish_attr" />
</div><!--#publishing-action-->

<div class="clear"></div>
</div><!--#major-publishing-actions-->

</div><!--#submitpost-->
</div>
</div>

EOS;
		
		return $this;
	} # submit_box()
	
	
	/**
	 * status_meta()
	 *
	 * @param array $args
	 * @return object $this
	 **/
	
	function status_meta(array $args) {
		$captions =& $this->captions();
		$row = $this->row();
		
		extract(wp_parse_args($args, array(
			'values' => array(),
			'readonly' => false,
			'id' => false,
			'name' => false,
			'field' => false,
			'main' => true,
			'group' => $this::type,
			)), EXTR_SKIP);
		
		if ( !isset($value) && $field )
			$value = current($row->get($field));
		if ( $id === false )
			$id = $field;
		if ( $name === false )
			$name = $field;
		
		$id = esc_attr($id);
		$name = esc_attr($name);
		$main = $main ? 'main-group' : '';
		$type_status = $captions[$group . '_status'];
		$group = $group ? esc_attr("$group-group") : '';
		$value = esc_attr($value);
		$status_attr = esc_attr($value);
		
		echo <<<EOS
<label for="$id">$type_status</label>
<span id="$id-display">{$values[$value]}</span>

EOS;
		
		if ( $readonly )
			return;
		
		echo <<<EOS
<a href="#$id" class="edit-status hide-if-no-js" tabindex='4' $readonly>{$captions['edit']}</a>

<div id="$id-select" class="status-select hide-if-js">
<input type="hidden" id="hidden_$id" value="$status_attr" />
<select id='$id' name='$name' class="status $main $group" tabindex='4'>

EOS;
		
		foreach ( $values as $status => $caption ) {
			echo '<option value="' . esc_attr($status) . '"'
				. selected($status, $value, false)
				. '>'
				. $caption
				. '</option>' . "\n";
		}
		
		echo <<<EOS
</select>
<a href="#$id" class="save-status hide-if-no-js button $js-captions">{$captions['ok']}</a>
<a href="#$id" class="cancel-status hide-if-no-js $js-captions">{$captions['cancel']}</a>
</div>

EOS;

		return $this;
	} # status_meta()
	
	
	/**
	 * date_meta()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function date_meta(array $args) {
		$captions =& $this->captions();
		
		extract(wp_parse_args($args, array(
			'date_type' => 'timestamp',
			'readonly' => false,
			'id' => false,
			'name' => false,
			'field' => false,
			'main' => true,
			'group' => $this::type,
			'row' => $this->row(),
			)), EXTR_SKIP);
		
		if ( !isset($value) && $field )
			$value = current($row->get($field));
		if ( $id === false )
			$id = $field;
		if ( $name === false )
			$name = $field;
		
		$date = $value ? @strtotime($value . ' GMT') : false;
		$gmt_offset = 3600 * get_option('gmt_offset');
		$now = gmdate('U');
		
		switch ( $date_type ) {
		case 'max_date':
		case 'expires':
			$date_type = 'expires';
			if ( $date ) {
				$expired = $date < $now;
				if ( $row->is_active() && !$expired )
					$timestamp = $captions['expire_on_future'];
				elseif ( $expired )
					$timestamp = $captions['expire_on_past'];
				else
					$timestamp = $captions['expire_on'];
				$timestamp .= ' <b>' . date_i18n(__('M j, Y @ h:i a', 'sem-backend'), $date + $gmt_offset, true) . '</b>';
			} else {
				$timestamp = $captions['expires_never']
					. ' <b>' . $captions['never'] . '</b>';
			}
			break;
		
		case 'min_date':
		case 'timestamp':
		default:
			$date_type = 'timestamp';
			if ( $date ) {
				if ( $date > $now )
					$timestamp = $captions['publish_on_future'];
				elseif ( $row->is_active() )
					$timestamp = $captions['publish_' . $group . '_on_past'];
				else
					$timestamp = $captions['publish_' . $group . '_on'];
				$timestamp .= ' <b>' . date_i18n(__('M j, Y @ h:i a', 'sem-backend'), $date + $gmt_offset, true) . '</b>';
			} else {
				$timestamp = $captions['publish_' . $group . '_now']
					. ' <b>' . $captions['immediately'] . '</b>';
			}
			break;
		}
		
		if ( $date ) {
			$time = gmdate('h:i a', $date + $gmt_offset);
			$date = gmdate('m/d/Y', $date + $gmt_offset);
		} else {
			$time = '';
			$date = '';
		}
		
		$date_id = esc_attr($id);
		$time_id = esc_attr(str_replace('date', 'time', $id));
		$date_name = esc_attr($name);
		$time_name = esc_attr(str_replace('date', 'time', $name));
		$main = $main ? 'main-group' : '';
		$group = $group ? esc_attr("$group-group") : '';
		$date = esc_attr($date);
		
		echo <<<EOS
<span id="$date_id-display" class="$date_type $date_type-display">$timestamp</span>

EOS;
		
		if ( $readonly )
			return;
		
		echo <<<EOS
<a href="#$date_id" class="edit-$date_type hide-if-no-js" tabindex='4' $readonly>{$captions['edit']}</a>

<div id="{$date_id}div" class="{$date_type}div hide-if-js">
<input type="hidden" id="hidden_{$date_id}" value="$date" />
<input type="hidden" id="hidden_{$time_id}" value="$time" />
<input type="text" size="10" id="$date_id" name="$date_name" value="$date" class="date {$date_type}_date $main $group" autocomplete="off" tabindex="4" /> @ <input type="text" size="8" id="$time_id" name="$time_name" value="$time" class="time {$date_type}_time $main $group" autocomplete="off" tabindex="4" />
<a href="#$date_id" class="save-$date_type hide-if-no-js button">{$captions['ok']}</a>
<a href="#$date_id" class="cancel-$date_type hide-if-no-js">{$captions['cancel']}</a>
</div>

EOS;
		
		return $this;
	} # date_meta()
	
	
	/**
	 * availability_meta()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function availability_meta(array $args) {
		$captions =& $this->captions();
		$row = $this->row();
		
		extract(wp_parse_args($args, array(
			'readonly' => false,
			'id' => false,
			'name' => false,
			'field' => false,
			'main' => true,
			'group' => $this::type,
			)), EXTR_SKIP);
		
		if ( !isset($value) && $field )
			$value = current($row->get($field));
		if ( $id === false )
			$id = $field;
		if ( $name === false )
			$name = $field;
		
		$value = self::is_quantity($value) ? (int) $value : '';
		
		switch ( (string) $value ) {
		case '':
			$units = $group . '_units';
			$hide_unlimited = '';
			$hide_select = ' style="display: none;"';
			break;
		
		case '1':
			$units = $group . '_unit';
			$hide_unlimited = ' style="display: none;"';
			$hide_select = '';
			break;
		
		default:
			$units = $group . '_units';
			$hide_unlimited = ' style="display: none;"';
			$hide_select = '';
			break;
		}
		
		$id = esc_attr($id);
		$name = esc_attr($name);
		$main = $main ? 'main-group' : '';
		$group = $group ? esc_attr("$group-group") : '';
		$value = esc_attr($value);
		$hide_link = $readonly ? ' style="display: none;"' : '';
		$disabled = $readonly ? ' disabled="disabled"' : '';
		
		echo <<<EOS
<label for="$id">{$captions['availability']}</label>
<span id="$id-unlimited" class="availability-unlimited" $hide_unlimited>
<b>{$captions['unlimited']}</b>
<a href="#$id" class="edit-availability hide-if-no-js" tabindex='4' $hide_link>{$captions['edit']}</a>
</span>

<span id="$id-select" $hide_select>
<input type="text" id="$id" name="$name" value="$value" size="5" class="availability number $main $group" autocomplete="off" tabindex="4" $disabled />
<span id="$id-units">$captions[$units]</span>
</span>

EOS;
		
		return $this;
	} # availability_meta()
	
	
	/**
	 * title_box()
	 *
	 * @return object $this
	 **/

	function title_box() {
		$captions =& $this->captions();
		
		$title = __('Name', 'sem-backend');
		$value = esc_attr($this->row()->name());
		
		echo <<<EOS
<div id="titlediv">
<div id="titlewrap">
<label class="screen-reader-text" for="title">{$title}</label>
<input type="text" id="title" name="name" size="30" tabindex="1" autocomplete="off" value="$value" />
</div>
</div>

EOS;
		
		return $this;
	} # title_box()
	
	
	/**
	 * memo_box()
	 *
	 * @param array $args
	 * @return object $this
	 **/

	function memo_box(array $args) {
		$memo = esc_html($this->row()->memo());
		
		echo <<<EOS
<p><textarea id="memo" name="memo" cols="30" rows="4" class="widefat">$memo</textarea></p>

EOS;
		
		return $this;
	} # memo_box()
	
	
	/**
	 * add_box()
	 *
	 * @param string $context
	 * @param string $box_id
	 * @param string $title
	 * @param callback $callback
	 * @param array $args
	 * @param int $priority
	 * @return void
	 **/

	function add_box($context, $box_id, $title, $callback, $args = array(), $priority = 10) {
		$this->boxes[$context][$priority][$box_id] = array(
			'title' => $title,
			'callback' => $callback,
			'args' => $args,
			);
	} # add_box()
	
	
	/**
	 * do_boxes()
	 *
	 * @param string $context
	 * @return object $this
	 **/

	function do_boxes($context) {
		if ( empty($this->boxes[$context]) )
			return $this;
		
		foreach ( $this->boxes[$context] as $priority => $boxes ) {
			foreach ( $boxes as $box_id => $box ) {
				$box_id = esc_attr($box_id);
				$hidden = !empty($box['args']['hidden']) ? ' style="display: none;"' : '';
				$label = esc_attr(!empty($box['args']['label']) ? $box['args']['label'] : $box_id);
				$group = !empty($box['args']['group']) ? esc_attr("{$box['args']['group']}-group") : '';
				
				echo <<<EOS
<div id="{$box_id}div" $hidden class="{$box_id}div $group">
<h3><label for="$label">{$box['title']}</label></h3>
<div class="inside">

EOS;
				
				$callback = $box['callback'];
				
				$this->$callback($box['args']);
				
				echo <<<EOS
</div>
</div>

EOS;
			}
		}
		
		return $this;
	} # do_boxes()
	
	
	/**
	 * add_meta()
	 *
	 * @param string $context
	 * @param string $box_id
	 * @param callback $callback
	 * @param array $args
	 * @param int $priority
	 * @return void
	 **/

	function add_meta($box_id, $callback, $args = array(), $priority = 10) {
		$this->metas[$priority][$box_id] = array(
			'callback' => $callback,
			'args' => $args,
			);
	} # add_meta()
	
	
	/**
	 * do_meta()
	 *
	 * @return object $this
	 **/
	
	function do_meta() {
		if ( empty($this->metas) )
			return $this;
		
		$last = array();
		foreach ( $this->metas as $priority => $metas ) {
			foreach ( $metas as $meta_id => $meta ) {
				$cur_group = !empty($meta['args']['group']) ? $meta['args']['group'] : 'main';
				if ( empty($meta['args']['hidden']) )
					$last[$cur_group] = $meta_id;
			}
		}
		$last_cur_group = false;
		foreach ( $this->metas as $priority => $metas ) {
			foreach ( $metas as $meta_id => $meta ) {
				$meta_id = esc_attr($meta_id);
				$hidden = !empty($meta['args']['hidden']) ? ' style="display: none;"' : '';
				$group = !empty($meta['args']['group']) ? esc_attr("{$meta['args']['group']}-group") : '';
				
				$cur_group = !empty($meta['args']['group']) ? $meta['args']['group'] : 'main';
				$last_section = ( $last[$cur_group] == $meta_id ) ? 'misc-pub-section-last' : '';
				
				if ( $last_cur_group && $cur_group != $last_cur_group ) {
					echo <<<EOS
<div class="misc-pub-section misc-pub-section-last $group"></div>

EOS;
				}
				
				$last_cur_group = $cur_group;
				
				echo <<<EOS
<div id="$meta_id-picker" class="misc-pub-section $last_section $group" $hidden>

EOS;
				
				$callback = $meta['callback'];
				$this->$callback($meta['args']);
				
				echo <<<EOS
</div>

EOS;
			}
		}
		
		return $this;
	} # do_meta()
} # s_edit_base


/**
 * s_manage_base
 *
 * @package Semiologic Backend
 **/

abstract class s_manage_base extends s_screen_base {
	const		icon = 'users',
				per_page = 20;
	protected	static $screen;
	
	
	/**
	 * init_screen()
	 *
	 * @return void
	 **/

	static function init_screen($class) {
		$args = stripslashes_deep($_GET);
		self::$screen = new $class($args);
		
		self::$screen->exec($args);
		
		self::$screen->headers();
		do_action($class . '_headers');
	} # init_screen()
	
	
	/**
	 * display_screen()
	 *
	 * @return void
	 **/

	static function display_screen() {
		self::$screen->display();
	} # display_screen()
	
	
	/**
	 * __construct()
	 *
	 * @param array $args
	 * @return void
	 **/

	function __construct($args) {
		$args = (array) $args;
		
		parent::__construct($args);
		$this->dataset->cache()->cache_counts();
	} # __construct()
	
	
	/**
	 * captions()
	 *
	 * @return void
	 **/

	function captions() {
		return array(
			'add_new' => __('Add New', 'sem-backend'),
			
			'displaying' => __('Displaying %1$s-%2$s of %3$s', 'sem-backend'),
			'no_items' => __('No items found', 'sem-backend'),
			
			'bulk_actions' => __('Bulk Actions', 'sem-backend'),
			'apply' => __('Apply', 'sem-backend'),

			'empty_trash' => __('Empty Trash', 'sem-backend'),
			
			'filter' => __('Filter', 'sem-backend'),
			
			'edit_tip' => __('Edit &#8220;%s&#8221;', 'sem-backend'),
			'edit_link' => __('Edit', 'sem-backend'),
			'view_tip' => __('View &#8220;%s&#8221;', 'sem-backend'),
			'view_link' => __('View', 'sem-backend'),
			'trash_tip' => __('Move &#8220;%s&#8221; to Trash', 'sem-backend'),
			'trash_link' => __('Move to Trash', 'sem-backend'),
			'untrash_tip' => __('Restore &#8220;%s&#8221;', 'sem-backend'),
			'untrash_link' => __('Restore', 'sem-backend'),
			'delete_tip' => __('Delete &#8220;%s&#8221; Permanently', 'sem-backend'),
			'delete_link' => __('Delete Permanently', 'sem-backend'),
			
			'all' => __('All', 'sem-backend'),
			'future' => __('Scheduled', 'sem-backend'),
			'pending' => __('Pending', 'sem-backend'),
			'draft' => __('Draft', 'sem-backend'),
			'trash' => __('Trash', 'sem-backend'),
			);
	} # captions()
	
	
	/**
	 * headers()
	 *
	 * @return object $this
	 **/

	function headers() {
		$args = $this->dataset->args();
		
		# catch searches
		if ( self::is_id($args['s']) || self::is_uuid($args['s']) ) {
			$class = $this::type;
			$row = new $class($args['s']);
			if ( $row->id() ) {
				wp_redirect($row->admin_url());
				die;
			}
		}
		
		if ( !$this->dataset->count() && $args['paged'] != 1 ) {
			$defaults = $this->dataset->defaults();
			$per_page = $this->dataset->per_page();
			$num_rows = $this->dataset->num_rows();
			
			$first = ( $args['paged'] - 1 ) * $per_page + 1;
			
			if ( $num_rows && $first <= $num_rows )
				return $this;
			
			# redirect to the last valid page when relevant
			$max = ceil($num_rows / $per_page);
			if ( $max ) {
				wp_redirect($this->dataset->admin_url(array('paged' => $max)));
				die;
			}
		}
		
		return $this;
	} # headers()
	
	
	/**
	 * display()
	 *
	 * @return object $this
	 **/

	function display() {
		$captions =& $this->captions();
		$type = $this::type;
		$types = $this::types;
		
		register_column_headers($this::type, $this->columns());
		
		echo '<div class="wrap">' . "\n";
		
		screen_icon($this::icon);
		
		$manage = $captions['manage_' . $this::types];
		$add_new = $captions['add_new'];
		
		echo <<<EOS
<h2>$manage <a href="admin.php?page=$type" class="button add-new-h2">$add_new</a></h2>

EOS;
		
		$this->print_feedback();
		
		echo <<<EOS
<form id="$types-filters" action="admin.php" method="get">
<input type="hidden" name="page" value="$types" />

EOS;
		
		$this->print_status_filter()->print_search_filter();
		
		echo <<<EOS
<div class="tablenav">
<div class="alignleft actions">

EOS;
		
		$this->print_bulk_actions()->print_filters();
		
		echo <<<EOS
</div>
<div class="tablenav-pages">

EOS;
		
		$this->print_paginate_filter();
		
		echo <<<EOS
</div>
<div class="clear"></div>
</div>

<table class="widefat post fixed" cellspacing="0">
	<thead>
	<tr>

EOS;
		
		print_column_headers($this::type);
		
		echo <<<EOS
	</tr>
	</thead>

	<tfoot>
	<tr>

EOS;

		print_column_headers($this::type, false);

echo <<<EOS
	</tr>
	</tfoot>

	<tbody>

EOS;
		
		$this->print_rows();

echo <<<EOS
	</tbody>
</table>

<div class="tablenav">
<div class="alignleft actions">

EOS;
		
		$this->print_bulk_actions();
		
		echo <<<EOS
</div>
<div class="tablenav-pages">

EOS;

		$this->print_paginate_filter();
		
		echo <<<EOS
</div>
<div class="clear"></div>
</div>

</form>
</div>

EOS;
		
		return $this;
	} # display()
	
	
	/**
	 * feedback()
	 *
	 * @return array $single => $bulk
	 **/

	function feedback() {
		return array(
			'published' => 'bulk_published',
			'untrashed' => 'bulk_untrashed',
			'trashed' => 'bulk_trashed',
			'deleted'=> 'bulk_deleted',
			);
	} # feedback()
	
	
	/**
	 * print_feedback()
	 *
	 * @return object $this
	 **/

	function print_feedback() {
		$captions =& $this->captions();
		
		$caption = false;
		foreach ( $this->feedback() as $var => $bulk ) {
			if ( isset($_GET[$var]) ) {
				$caption = $captions[$var . '_' . $this::type];
			} elseif ( isset($_GET[$bulk]) ) {
				$caption = $_GET[$bulk] == 1
					? $captions[$var]
					: sprintf($captions[$bulk . '_' . $this::types], (int) $_GET[$bulk]);
			}
			
			if ( !$caption )
				continue;
			
			echo '<div class="updated">'
				. wpautop($caption)
				. '</div>' . "\n";
			break;
		}
		
		return $this;
	} # print_feedback()
	
	
	/**
	 * print_status_filter()
	 *
	 * @return object $this
	 **/

	function print_status_filter() {
		$captions =& $this->captions();
		
		$args = $this->dataset->args();
		$defaults = $this->dataset->defaults();
		$strip = array_fill_keys(array_keys($this->dataset->defaults()), false);
		
		$values = $this->dataset->status_counts();
		
		$i = 0;
		$last = count($values);
		$current = $args['status'];
		$default = $defaults['status'];
		
		if ( $current != $default ) {
			$current_attr = esc_attr($current);
			echo <<<EOS
<input type="hidden" name="status" value="$current_attr" />

EOS;
		}
		
		echo '<ul class="subsubsub">' . "\n";
		foreach ( $values as $status => $count ) {
			$i++;
			$filter = $strip;
			$filter['status'] = $status;
			$filter_url = $this->dataset->admin_url($filter);
			echo '<li>'
				. '<a href="' . esc_url($filter_url) . '"'
					. ( $status == $current ? ' class="current"' : '' )
					. '>'
				. $captions[$status]
				. ' '
				. '<span class="count">(' . number_format_i18n((int) $count) . ')<span>'
				. '</a>'
				. ( $i != $last ? ' |' : '' )
				. '</li>' . "\n";
		}
		echo '</ul>' . "\n";
		
		return $this;
	} # print_status_filter()
	
	
	/**
	 * print_search_filter()
	 *
	 * @return object $this
	 **/

	function print_search_filter() {
		$captions =& $this->captions();
		
		$args = $this->dataset->args();
		$s_attr = !empty($args['s'])
			? esc_attr($args['s'])
			: '';
		$search = $captions['search_' . $this::types];
		$search_attr = esc_attr($search);
		
		echo <<<EOS
<p class="search-box">
	<label class="screen-reader-text" for="search-input">$search:</label>
	<input type="text" id="search-input" name="s" value="$s_attr" />
	<input type="submit" id="search-button" value="$search_attr" class="button" />
</p>

EOS;
		
		return $this;
	} # print_search_filter()
	
	
	/**
	 * bulk_actions()
	 *
	 * @return array $actions
	 **/

	function bulk_actions() {
		$captions =& $this->captions();
		$args = $this->dataset->args();
		
		$actions = array();
		if ( !empty($args['status']) && $args['status'] == 'trash' ) {
			$actions['bulk_untrash'] = $captions['untrash_link'];
			$actions['bulk_delete'] = $captions['delete_link'];
		} else {
			if ( !empty($args['status']) && $args['status'] != $this::active_status )
				$actions['bulk_publish'] = $captions['publish_' . $this::type . '_link'];
			$actions['bulk_trash'] = $captions['trash_link'];
		}
		return $actions;
	} # bulk_actions()
	
	
	/**
	 * print_bulk_actions()
	 *
	 * @return object $this
	 **/

	function print_bulk_actions() {
		$bulk_actions = $this->bulk_actions();
		
		static $i = 0;
		if ( $bulk_actions ) {
			$captions =& $this->captions();
			$args = $this->dataset->args();
			
			if ( !$i )
				wp_nonce_field('bulk_manage_' . $this::types);
			
			$extra = $i++ ? $i : '';
			
			echo <<<EOS
<select id="bulk_action$extra" name="bulk_action$extra">
<option value="" selected="selected">{$captions['bulk_actions']}</option>

EOS;
			
			foreach ( $bulk_actions as $k => $v ) {
				$k = esc_attr($k);
				echo <<<EOS
<option value="$k">$v</option>
EOS;
			}
			
			$apply = esc_attr($captions['apply']);
			echo <<<EOS
</select>
<input type="submit" id="bulk_manage$extra" name="bulk_manage$extra" value="$apply" class="button-secondary" />

EOS;
		}
		
		if ( !empty($args['status']) && $args['status'] == 'trash' && current_user_can('delete_' . $this::types) ) {
			$empty_trash_attr = $captions['empty_trash'];
			echo <<<EOS
<input type="submit" id="delete_all" name="delete_all" value="$empty_trash_attr" class="button-secondary apply" />

EOS;
		}
		
		return $this;
	} # print_bulk_actions()
	
	
	/**
	 * print_filters()
	 *
	 * @return object $this
	 **/
	
	function print_filters() {
		$captions =& $this->captions();
		$filter = esc_attr($captions['filter']);
		
		echo <<<EOS
<input type="submit" id="filters-submit" value="$filter" class="button-secondary" />

EOS;
		
		return $this;
	} # print_filters()
	
	
	/**
	 * print_paginate_filter()
	 *
	 * @return object $this
	 **/

	function print_paginate_filter() {
		$captions =& $this->captions();
		
		$num_items = $this->dataset->num_rows();
		
		$current = $this->dataset->args();
		$current = $current['paged'];
		$default = $this->dataset->defaults();
		$default = $default['paged'];
		
		$per_page = $this->dataset->per_page();
		$first = ( $current - 1 ) * $per_page + 1;
		$last = $first + $per_page - 1;
		
		if ( $first <= $num_items && $last > $num_items )
			$last = $num_items;
		
		if ( $num_items ) {
			echo '<span class="displaying-num">'
				. sprintf(
					$captions['displaying'],
					number_format_i18n($first),
					number_format_i18n($last),
					number_format_i18n($num_items)
					)
				. '</span>' . "\n";
		} else {
			echo '<span class="displaying-num">'
				. $captions['no_items']
				. '</span>' . "\n";
		}
		
		if ( $num_items <= $per_page )
			return $this;
		
		$min_page = 1;
		$max_page = ceil($num_items / $per_page);
		$range = array();
		for ( $i = -2; $i <= 2; $i++ ) {
			$j = $current + $i;
			if ( $j >= $min_page && $j <= $max_page )
				$range[] = $j;
		}
		
		if ( current($range) > $min_page + 1 ) {
			array_unshift($range, '...');
			array_unshift($range, $min_page);
			array_unshift($range, '&laquo;');
		} elseif ( current($range) > $min_page ) {
			array_unshift($range, $min_page);
			array_unshift($range, '&laquo;');
		}
		
		if ( end($range) < $max_page - 1 ) {
			$range[] = '...';
			$range[] = $max_page;
			$range[] = '&raquo;';
		} elseif ( end($range) < $max_page ) {
			$range[] = $max_page;
			$range[] = '&raquo;';
		}
		
		reset($range);
		
		$base_url = $this->dataset->admin_url(array('paged' => false));
		
		foreach ( $range as $i ) {
			$classes = array('page_numbers');
			$page_url = $base_url;
			$j = $i;
			switch ( $i ) {
			case '&laquo;':
				$classes[] = 'prev';
				$j = $current - 1;
				break;
			case '&raquo;':
				$classes[] = 'next';
				$j = $current + 1;
				break;
			case '...':
				$classes[] = 'dots';
				$page_url = false;
				break;
			default:
				if ( $i == $current ) {
					$classes[] = 'current';
					$page_url = false;
				}
			}
			if ( $page_url ) {
				if ( $j != $default )
					$page_url .= '&paged=' . $j;
				echo '<a class="' . implode(' ', $classes). '" href="' . esc_url($page_url) . '">'
					. $i
					. '</a>' . "\n";
			} else {
				echo '<span class="' . implode(' ', $classes). '">'
					. $i
					. '</span>' . "\n";
			}
		}
		
		return $this;
	} # print_paginate_filter()
	
	
	/**
	 * print_rows()
	 *
	 * @return object $this
	 **/

	function print_rows() {
		$cols = get_column_headers($this::type);
		$hidden = get_hidden_columns($this::type);
		
		static $row_class = '';
		$row_class = 'alternate' == $row_class ? '' : 'alternate';
		
		foreach ( $this->dataset as $row ) {
			$classes = array($row_class, 'status-' . $row->status());
			$classes = esc_attr(implode(' ', $classes));
			echo '<tr id="' . esc_attr($this::type . '-' . $row->id()) . '" valign="top" class="' . $classes . '">' . "\n";
			
			foreach ( array_keys($cols) as $col ) {
				$cell_class = "class=\"$col column-$col\"";
				$cell_style = '';
				if ( in_array($col, $hidden) )
					$cell_style = 'style="display:none;"';
				$attributes = "$cell_class $cell_style";
				
				if ( $col != 'cb' )
					echo '<td ' . $attributes . '>';
				else
					echo '<th scope="row" class="check-column">';
				
				if ( has_action($this::type . '_row_' . $col) ) {
					do_action($this::type . '_row_' . $col, $row);
				} elseif ( method_exists($this, 'row_' . $col) ) {
					$callback = 'row_' . $col;
					$this->$callback();
				} elseif ( !in_array($col, $row->fields()) ) {
					echo '&nbsp;';
				} else {
					$value = $row->$col();
					echo ( is_numeric($value)
							? number_format_i18n($value)
							: $value
							);
				}
				
				if ( $col != 'cb' )
					echo '</td>' . "\n";
				else
					echo '</th>' . "\n";
			}
			
			echo '</tr>' . "\n";
		}
	} # print_rows()
	
	
	/**
	 * row_cb()
	 *
	 * @return object $this
	 **/

	function row_cb() {
		echo '<input type="checkbox" name="ids[]" value="' . intval($this->row()->id()) . '"/>';
		return $this;
	} # row_cb()
	
	
	/**
	 * row_name()
	 *
	 * @return object $this
	 **/

	function row_name() {
		echo '<strong>';
		$this->print_row_name();
		$this->print_row_state();
		echo '</strong>';
		
		$this->print_row_actions();
		
		return $this;
	} # row_name()
	
	
	/**
	 * print_row_name()
	 *
	 * @return object $this
	 **/

	function print_row_name() {
		$row = $this->row();
		$name = $row->name();
		if ( $row->is_trash() ) {
			echo $name;
		} elseif ( current_user_can('edit_' . $this::type, $row) ) {
			echo '<a href="' . esc_url($row->admin_url()) . '"'
				. ' class="row-title"'
				. ' title="' . esc_attr(sprintf(__('Edit &#8220;%s&#8221;', 'sem-backend'), $name)) . '"'
				. '>'
				. $name
				. '</a>';
		} elseif ( current_user_can('view_' . $this::type, $row)) {
			echo '<a href="' . esc_url($row->admin_url()) . '"'
				. ' class="row-title"'
				. ' title="' . esc_attr(sprintf(__('View &#8220;%s&#8221;', 'sem-backend'), $name)) . '"'
				. '>'
				. $name
				. '</a>';
		} else {
			echo $name;
		}
		
		return $this;
	} # print_row_name()
	
	
	/**
	 * print_row_state()
	 *
	 * @return object $this
	 **/

	function print_row_state() {
		$row = $this->row();
		
		if ( $row->is_active() )
			return $this;
		
		$args = $this->dataset->args();
		if ( !empty($args['status']) && $row->status() == $args['status'] )
			return $this;
		
		$captions =& $this->captions();
		
		echo ' - '
			. '<span class="sm-state">'
			. $captions[$row->status()]
			. '</span>';
		
		return $this;
	} # print_row_state()
	
	
	/**
	 * row_actions()
	 *
	 * @return array $actions
	 **/

	function row_actions() {
		$captions =& $this->captions();
		
		$actions = array();
		$row = $this->row();
		
		extract($row->get('id', 'name'));
		
		if ( !$row->is_trash() ) {
			if ( !$row->is_active() && current_user_can('publish_' . $this::type, $row) ) {
				$url = $this->dataset->admin_url(array('action' => 'publish', 'id' => $id));
				if ( $url ) {
					$tip = sprintf($captions['publish_' . $this::type . '_tip'], $name);
					$link = $captions['publish_' . $this::type . '_link'];
					$actions['publish'] = '<a href="' . esc_url($url) . '"'
						. ' title="' . esc_attr($tip) . '"'
						. '>' . $link . '</a>';
				}
			}
			if ( current_user_can('edit_' . $this::type, $row) ) {
				$url = $row->admin_url();
				$tip = sprintf($captions['edit_tip'], $name);
				$link = $captions['edit_link'];
				$actions['edit'] = '<a href="' . esc_url($url) . '"'
					. ' title="' . esc_attr(sprintf($tip, $name)) . '"'
					. '>' . $link . '</a>';
			} elseif ( current_user_can('view_' . $this::type, $row) ) {
				$url = $row->admin_url();
				$tip = sprintf($captions['view_tip'], $name);
				$link = $captions['view_link'];
				$actions['view'] = '<a href="' . esc_url($url) . '"'
					. ' title="' . esc_attr(sprintf($tip, $name)) . '"'
					. '>' . $link . '</a>';
			}
			if ( current_user_can('delete_' . $this::type, $row) ) {
				$url = $this->dataset->admin_url(array('action' => 'trash', 'id' => $id));
				if ( $url ) {
					$tip = sprintf($captions['trash_tip'], $name);
					$link = $captions['trash_link'];
					$actions['trash'] = '<a href="' . esc_url($url) . '"'
						. ' title="' . esc_attr(sprintf($tip, $name)) . '"'
						. '>' . $link . '</a>';
				}
			}
		} else {
			if ( current_user_can('edit_' . $this::type, $row) ) {
				$url = $this->dataset->admin_url(array('action' => 'untrash', 'id' => $id));
				if ( $url ) {
					$tip = sprintf($captions['untrash_tip'], $name);
					$link = $captions['untrash_link'];
					$actions['untrash'] = '<a href="' . esc_url($url) . '"'
						. ' title="' . esc_attr(sprintf($tip, $name)) . '"'
						. '>' . $link . '</a>';
				}
			}
			if ( current_user_can('delete_' . $this::type, $row) ) {
				$url = $this->dataset->admin_url(array('action' => 'delete', 'id' => $id));
				if ( $url ) {
					$tip = sprintf($captions['delete_tip'], $name);
					$link = $captions['delete_link'];
					$actions['delete'] = '<a href="' . esc_url($url) . '"'
						. ' title="' . esc_attr(sprintf($tip, $name)) . '"'
						. '>' . $link . '</a>';
				}
			}
		}
		
		return $actions;
	} # row_actions()
	
	
	/**
	 * print_row_actions()
	 *
	 * @return void
	 **/

	function print_row_actions() {
		$row = $this->row();
		$actions = $this->row_actions();
		$actions = apply_filters($this::type . '_row_actions', $actions, $row);
		
		$action_count = count($actions);
		if ( !$action_count )
			return;
		$i = 0;
		echo '<div class="row-actions">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			echo "<span class='$action'>$link$sep</span>";
		}
		echo '</div>';
		
		return $this;
	} # print_row_actions()
	
	
	/**
	 * row_status()
	 *
	 * @return object $this
	 **/

	function row_status() {
		$captions =& $this->captions();
		$args = $this->dataset->args();
		$row = $this->row();
		
		$status = $row->status();
		
		$url = $this->dataset->admin_url(array('status' => $status), true);
		
		echo '<a href="' . esc_url($url) .'">'
			. $captions[$status]
			. '</a>';
				
		return $this;
	} # row_status()
} # s_manage_base
?>