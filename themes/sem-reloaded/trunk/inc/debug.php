<?php
#
# Debug tools
#

if ( !defined('SAVEQUERIES') && isset($_GET['queries']) )
	define('SAVEQUERIES', true);


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
		$memory = number_format(memory_get_usage() / 1024, 0);
		$out .= " - {$memory}kB";
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
	if ( $_POST || !current_user_can('manage_options') )
		return $in;
	
	global $sem_stops;
	global $wp_object_cache;
	
	$stops = '';
	foreach ( $sem_stops as $where => $stop )
		$stops .= "$where: $stop\n";
	dump($stops);
	
	# only show queries to admin users
	if ( defined('SAVEQUERIES') && $_GET['debug'] == 'sql' ) {
		global $wpdb;
		foreach ( $wpdb->queries as $key => $data ) {
			$query = rtrim($data[0]);
			
			$duration = number_format($data[1] * 1000, 3) . 'ms';
			
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

add_action('wp_footer', 'dump_stops', 10000000);
add_action('admin_footer', 'dump_stops', 10000000);
?>