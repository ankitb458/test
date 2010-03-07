<?php
/*
Plugin Name: Semiologic Backend
Plugin URI: http://www.semiologic.com/software/backend/
Description: A WordPress-powered backend.
Version: 0.1 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-backend
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/

global $wpdb;

if ( defined('SB_PREFIX') ) {
	$wpdb->s_prefix = SB_PREFIX;
} else {
	$wpdb->s_prefix = $wpdb->prefix;
}

$wpdb->products = $wpdb->s_prefix . 'products';
$wpdb->campaigns = $wpdb->s_prefix . 'campaigns';
$wpdb->orders = $wpdb->s_prefix . 'orders';
$wpdb->transactions = $wpdb->s_prefix . 'transactions';

$wpdb->memberships = $wpdb->s_prefix . 'memberships';
$wpdb->membership2product = $wpdb->s_prefix . 'membership2product'; # product allows membership

$wpdb->product2membership = $wpdb->s_prefix . 'product2membership'; # product grants membership
$wpdb->membership2post = $wpdb->prefix . 'membership2post'; # membership allows post, *per* site

$wpdb->user2membership = $wpdb->s_prefix . 'user2membership';


load_plugin_textdomain('sem-backend', false, dirname(plugin_basename(__FILE__)) . '/lang');

if ( !defined('s_rev') )
	define('s_rev', '20100206');
if ( !defineD('s_path') )
	define('s_path', dirname(__FILE__));
if ( !defined('s_url') )
	define('s_url', rtrim(plugin_dir_url(__FILE__), '/'));



/**
 * sb
 *
 * @package Semiologic Backend
 **/

class sb {
	/**
	 * activate()
	 *
	 * @return void
	 **/

	static function activate() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		include s_path . '/inc/schema.php';
	} # activate()
	
	
	/**
	 * deactivate()
	 *
	 * @return void
	 **/

	static function deactivate() {
	} # deactivate()
	
	
	/**
	 * build_query()
	 *
	 * @param $args
	 * @return string $query_string
	 **/

	static function build_query($args) {
		$query_string = array();
		
		foreach ( $args as $key => $arg ) {
			if ( empty($arg) ) {
				continue;
			} elseif ( !is_array($arg) ) {
				$query_string[] = urlencode($key) . '=' . urlencode($arg);
			} else {
				foreach ( $arg as $k => $v )
					$query_string[] = urlencode($key) . '[' . intval($k) . ']=' . urlencode($v);
			}
		}
		
		$query_string = implode('&', $query_string);
		
		return $query_string;
	} # build_query()
	
	
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/

	static function admin_menu() {
		add_utility_page(
			__('Manage Products', 'sem-backend'),
			__('Products', 'sem-backend'),
			'edit_products',
			'products',
			array('manage_products', 'display_screen')
			);
		
		$hook = add_submenu_page(
			'products',
			__('Manage Products', 'sem-backend'),
			__('Products', 'sem-backend'),
			'edit_products',
			'products',
			array('manage_products', 'display_screen')
			);
		add_action('load-' . $hook, array('sb', 'products'), 1);
		add_action('load-' . $hook, array('sb', 'assets'), 1);
		add_action('load-' . $hook, array('manage_products', 'init_screen'), 5);
		
		$hook = add_submenu_page(
			'products',
			__('Add New Product', 'sem-backend'),
			__('Add New', 'sem-backend'),
			'edit_products',
			'product',
			array('edit_product', 'display_screen')
			);
		add_action('load-' . $hook, array('sb', 'products'), 1);
		add_action('load-' . $hook, array('sb', 'assets'), 1);
		add_action('load-' . $hook, array('edit_product', 'init_screen'), 5);
		
		add_utility_page(
			__('Manage Campaigns', 'sem-backend'),
			!current_user_can('manage_campaigns')
				? __('Affiliates', 'sem-backend')
				: __('Campaigns', 'sem-backend'),
			'edit_campaigns',
			'campaigns',
			array('manage_campaigns', 'display_screen')
			);
		
		$hook = add_submenu_page(
			'campaigns',
			__('Manage Campaigns', 'sem-backend'),
			__('Campaigns', 'sem-backend'),
			'edit_campaigns',
			'campaigns',
			array('manage_campaigns', 'display_screen')
			);
		add_action('load-' . $hook, array('sb', 'campaigns'), 1);
		add_action('load-' . $hook, array('sb', 'assets'), 1);
		add_action('load-' . $hook, array('manage_campaigns', 'init_screen'), 5);
		
		$hook = add_submenu_page(
			'campaigns',
			__('Add New Campaign', 'sem-backend'),
			__('Add New', 'sem-backend'),
			'edit_campaigns',
			'campaign',
			array('edit_campaign', 'display_screen')
			);
		add_action('load-' . $hook, array('sb', 'campaigns'), 1);
		add_action('load-' . $hook, array('sb', 'assets'), 1);
		add_action('load-' . $hook, array('edit_campaign', 'init_screen'), 5);
	} # admin_menu()
	
	
	/**
	 * products()
	 *
	 * @return void
	 **/

	static function products() {
		include s_path . '/inc/product-admin.php';
	} # products()
	
	
	/**
	 * campaigns()
	 *
	 * @return void
	 **/

	static function campaigns() {
		include s_path . '/inc/campaign-admin.php';
	} # campaigns()
	
	
	/**
	 * assets()
	 *
	 * @return void
	 **/

	function assets() {
		wp_register_script('jquery-ui-datepicker', s_url . '/js/jquery-ui-datepicker.js', array('jquery-ui-core'), '1.7.2', true);
		wp_register_script('jquery-sbsuggest', s_url . '/js/jquery-sbsuggest.js', array('jquery'), '1.0', true);
		wp_register_script('sb-data-admin', s_url . '/js/data-admin.js', array('jquery-ui-datepicker', 'jquery-sbsuggest'), s_rev, true);
		
		wp_register_style('jquery-ui-datepicker', s_url . '/css/smoothness/jquery-ui.css', null, '1.7.2');
		wp_register_style('sb-data-admin', s_url . '/css/data-admin.css', array('jquery-ui-datepicker'), s_rev);
		
		wp_enqueue_script('sb-data-admin');
		wp_enqueue_style('sb-data-admin');
		
		$captions = array(
			'save_draft' => __('Save Draft', 'sem-backend'),
			'save_pending' => __('Save as Pending', 'sem-backend'),
			'update' => __('Update', 'sem-backend'),
			'schedule' => __('Schedule', 'sem-backend'),
			'publishOnFuture' => __('Schedule for:', 'sem-backend'),
			'immediately' => __('Immediately', 'sem-backend'),
			'expiresNever' => __('Expires:', 'sem-backend'),
			'never' => __('Never', 'sem-backend'),
			'expireOn' => __('Expire on:', 'sem-backend'),
			'expireOnPast' => __('Expired on:', 'sem-backend'),
			'expireOnFuture' => __('Expires on:', 'sem-backend'),
			'unlimited' => __('Unlimited', 'sem-backend'),
			'month' => __('Month', 'sem-backend'),
			'quarter' => __('Quarter', 'sem-backend'),
			'year' => __('Year', 'sem-backend'),
			'l10n_print_after' => 'try{convertEntities(sem_backendL10n);}catch(e){};',
		);
		
		$defaults = array(
			'publish' => __('Publish', 'sem-backend'),
			'publishNow' => __('Publish:', 'sem-backend'),
			'publishOn' => __('Publish on:', 'sem-backend'),
			'publishOnPast' => __('Published on:', 'sem-backend'),
			'unit' => __('Unit left', 'sem-backend'),
			'units' => __('Units left', 'sem-backend'),
			);
		
		$product = array(
			'publish' => __('Release', 'sem-backend'),
			'publishNow' => __('Release:', 'sem-backend'),
			'publishOn' => __('Release on:', 'sem-backend'),
			'publishOnPast' => __('Released on:', 'sem-backend'),
			'unit' => __('Product left', 'sem-backend'),
			'units' => __('Products left', 'sem-backend'),
			);
		
		$campaign = array(
			'publish' => __('Launch', 'sem-backend'),
			'publishNow' => __('Launch:', 'sem-backend'),
			'publishOn' => __('Launch on:', 'sem-backend'),
			'publishOnPast' => __('Launched on:', 'sem-backend'),
			'unit' => __('Coupon left', 'sem-backend'),
			'units' => __('Coupons left', 'sem-backend'),
			);
		
		$extra_captions = compact('defaults', 'product', 'campaign');
		$extra_captions = apply_filters('sem_backend_localize_js', $extra_captions);
		
		foreach ( $extra_captions as $key => $extra ) {
			$data = "\n\nsem_backendL10n['$key'] = {\n";
			$eol = '';
			foreach ( $extra as $var => $val ) {
				$data .= "$eol\t$var: \"" . esc_js($val) . '"';
				$eol = ",\n";
			}
			$data .= "\n};\n";
			$data .= "try{convertEntities(sem_backendL10n.$key);}catch(e){};\n";
			$captions['l10n_print_after'] .= $data;
		}
		
		wp_localize_script('sb-data-admin', 'sem_backendL10n', $captions);
		
		add_action('admin_head', array('sb', 'fix_admin_css'), 20);
	} # assets()
	
	
	/**
	 * fix_admin_css()
	 *
	 * @return void
	 **/

	function fix_admin_css() {
		$admin_url = untrailingslashit(admin_url());
		echo <<<EOS
<style type="text/css">
.timestamp-display,
.expires-display {
	background-image: url($admin_url/images/date-button.gif);
	background-repeat: no-repeat;
	background-position: left top;
	padding-left: 18px;
}
</style>

EOS;
	} # fix_admin_css()
	
	
	/**
	 * exception_handler()
	 *
	 * @param Exception $e
	 * @return void
	 **/

	function exception_handler(Exception $e) {
		echo '<pre style="margin-left: 0px; margin-right: 0px; padding: 10px; border: solid 1px black; background-color: ghostwhite; color: black; text-align: left;">';
		
		echo 'Uncaught ', get_class($e), ' thrown in ',
			str_replace(
				array(ABSPATH, WP_PLUGIN_DIR, TEMPLATEPATH, STYLESHEETPATH),
				array('', 'plugins', 'themes', 'themes'),
				$e->getFile()),
			'(',
			$e->getLine(),
			'): ', "\n\n",
			$e->getMessage();
		
		if ( function_exists('wp_get_current_user') && current_user_can('manage_options') ) {
			echo "\n\n",
				str_replace(
					array(ABSPATH, WP_PLUGIN_DIR, TEMPLATEPATH, STYLESHEETPATH),
					array('', 'plugins', 'themes', 'themes'),
					$e->getTraceAsString());
			}
		
		echo '</pre>';
	} # exception_handler()
} # sb

set_exception_handler(array('sb', 'exception_handler'));

register_activation_hook(__FILE__, array('sb', 'activate'));
register_deactivation_hook(__FILE__, array('sb', 'deactivate'));

foreach ( array(
	'product',
	'campaign',
	) as $s_type ) {
	include s_path . '/inc/' . $s_type . '.php';
	add_filter('map_meta_cap', array($s_type, 'map_meta_cap'), 0, 4);
}

if ( is_admin() ) {
	add_action('admin_menu', array('sb', 'admin_menu'), 5);
	if ( defined('DOING_AJAX') )
		include dirname(__FILE__) . '/inc/ajax-admin.php';
} else {
	#add_action('sem_admin_menu_user', array('sb', 'front_menu'), 20);
}

#sb::activate();
?>