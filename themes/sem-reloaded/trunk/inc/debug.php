<?php
#
# Debug tools
#

if ( !defined('SAVEQUERIES') && isset($_GET['debug']) && $_GET['debug'] == 'sql' )
	define('SAVEQUERIES', true);

if ( !defined('sem_sql_debug') )
	define('sem_sql_debug', false);


/**
 * add_stop()
 *
 * @param mixed $in
 * @param string $where
 * @return mixed $in
 **/

function add_stop($in = null, $where = null) {
	global $sem_stops;
	global $wp_object_cache;
	
	$queries = get_num_queries();
	$milliseconds = timer_stop() * 1000;
	$out =  "$queries queries - {$milliseconds}ms";
	
	if ( function_exists('memory_get_usage') ) {
		$memory = number_format(memory_get_usage() / ( 1024 * 1024 ), 1);
		$out .= " - {$memory}MB";
	}
	
	$out .= " - $wp_object_cache->cache_hits cache hits / " . ( $wp_object_cache->cache_hits + $wp_object_cache->cache_misses );
	
	if ( $where ) {
		$sem_stops[$where] = $out;
	} else {
		dump($out);
	}

	return $in;
} # add_stop()


/**
 * dump_stops()
 *
 * @param mixed $in
 * @return mixed $in
 **/

function dump_stops($in = null) {
	if ( ( $_POST || !current_user_can('manage_options') ) && !sem_sql_debug )
		return $in;
	
	global $sem_stops;
	global $wp_object_cache;
	
	$stops = '';
	foreach ( $sem_stops as $where => $stop )
		$stops .= "$where: $stop\n";
	dump("\n" . trim($stops) . "\n");
	
	# only show queries to admin users
	if ( defined('SAVEQUERIES') && $_GET['debug'] == 'sql' ) {
		global $wpdb;
		foreach ( $wpdb->queries as $key => $data ) {
			$query = rtrim($data[0]);
			
			$duration = number_format($data[1] * 1000, 1) . 'ms';
			
			$loc = trim($data[2]);
			$loc = preg_replace("/(require|include)(_once)?,\s*/ix", '', $loc);
			$loc = "\n" . preg_replace("/,\s*/", ",\n", $loc) . "\n";
			
			dump($query, $duration, $loc);
		}
	}
	
	if ( $_GET['debug'] == 'cache' )
		dump($wp_object_cache->cache);

	if ( $_GET['debug'] == 'cron' ) {
		$crons = get_option('cron');
		
		foreach ( $crons as $time => $_crons ) {
			if ( !is_array($_crons) )
				continue;
			foreach ( $_crons as $event => $_cron ) {
				foreach ( $_cron as $details ) {
					$date = date('Y-m-d H:m:i', $time);
					$schedule = isset($details['schedule']) ? "({$details['schedule']})" : '';
					if ( $details['args'] )
						dump("$date: $event $schedule", $details['args']);
					else
						dump("$date: $event $schedule");
				}
			}
		}
	}
	
	if ( $_GET['debug'] == 'js' ) {
		$js = <<<EOS
<div id='jsdump'></div>
<script type="text/javascript">
jQuery.log('rendering - done');
jQuery(document).ready(function() {
	jQuery.log('scripts - done');
	jQuery.dumpLogs();
})
jQuery.initLogs();
jQuery.log('scripts - start');
</script>
EOS;

		dump($js);
	}
	
	return $in;
} # dump_stops()


add_action('init', create_function('$in', '
	return add_stop($in, "Load");
	'), 10000000);

add_action('template_redirect', create_function('$in', '
	return add_stop($in, "Query");
	'), -10000000);

add_action('wp_footer', create_function('$in', '
	return add_stop($in, "Display");
	'), 10000000);

add_action('admin_footer', create_function('$in', '
	return add_stop($in, "Display");
	'), 10000000);

/**
 * init_dump()
 *
 * @return void
 **/

function init_dump() {
	global $hook_suffix;
	if ( !is_admin() || empty($hook_suffix) ) {
		add_action('wp_footer', 'dump_stops', 10000000);
		add_action('admin_footer', 'dump_stops', 10000000);
	} else {
		add_action('wp_footer', 'dump_stops', 10000000);
		add_action("admin_footer-$hook_suffix", 'dump_stops', 10000000);
	}
} # init_dump()

add_action('wp_print_scripts', 'init_dump');


/**
 * dump_phpinfo()
 *
 * @return void
 **/

function dump_phpinfo() {
	if ( isset($_GET['debug']) && $_GET['debug'] == 'phpinfo' && current_user_can('manage_options')) {
		phpinfo();
		die;
	}
} # dump_phpinfo()

add_action('init', 'dump_phpinfo');


/**
 * dump_js()
 *
 * @return void
 **/

function dump_js() {
	$folder = sem_url . '/js';
	wp_enqueue_script('jquery-logger', $folder . '/jquery.logger.js', array('jquery'),  '20090903');
} # dump_js()

if ( $_GET['debug'] == 'js' )
	add_action('wp_print_scripts', 'dump_js');


/**
 * dump_http()
 *
 * @param array $args
 * @param string $url
 * @return array $args
 **/

function dump_http($args, $url) {
	dump(preg_replace("|/[0-9a-f]{32}/?$|", '', $url));
	return $args;
} # dump_http()

if ( $_GET['debug'] == 'http' )
	add_filter('http_request_args', 'dump_http', 0, 2);
?>