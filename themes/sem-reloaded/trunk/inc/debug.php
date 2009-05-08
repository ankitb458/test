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
	$queries = get_num_queries();
	$milliseconds = timer_stop() * 1000;
	$out =  "$queries queries - {$milliseconds}ms";
	
	if ( function_exists('memory_get_usage') ) {
		$memory = number_format(memory_get_usage() / 1024, 0);
		$out .= " - {$memory}kB";
	}
	
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
	if ( $_POST )
		return;
	
	global $sem_stops;
	
	$stops = '';
	foreach ( $sem_stops as $where => $stop )
		$stops .= "$where: $stop\n";
	dump($stops);
	
	# only show queries to admin users
	if ( defined('SAVEQUERIES') && current_user_can('manage_options') ) {
		global $wpdb;
		foreach ( $wpdb->queries as $key => $data ) {
			$query = trim($data[0]);
			$query = preg_replace("/
				\s*
				(
					^INSERT |
					^UPDATE |
					^REPLACE |
					SELECT |
					(?:^DELETE\s+)?FROM |
					(?:(?:INNER|LEFT|RIGHT|CROSS|NATURAL)\s*)?JOIN |
					WHERE |
					AND |
					GROUP\s+BY |
					HAVING |
					ORDER\s+BY |
					LIMIT
				)
				/isx", "\n$1", $query) . "\n";
			$query = trim($query);
			
			$duration = number_format($data[1] * 1000, 3) . 'ms';
			
			if ( preg_match("/^SELECT/i", $query) ) {
				$explain = mysql_query("EXPLAIN $query", $wpdb->dbh);
				$explain = mysql_fetch_array($explain, MYSQL_ASSOC);
			} else {
				$explain = false;
			}
			
			$loc = trim($data[2]);
			$loc = preg_replace("/(require|include)(_once)?,\s*/ix", '', $loc);
			$loc = "\n" . preg_replace("/,\s*/", ",\n", $loc) . "\n";
			
			if ( $explain )
				dump($query, $duration, $explain, $loc);
			else
				dump($query, $duration, $loc);
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