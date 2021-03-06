<?php
/**
 * static_cache
 *
 * @package Semiologic Cache
 **/

foreach ( array(
	'sem_cache_debug',
	'static_cache',
	'memory_cache',
	) as $const ) {
	if ( !defined($const) )
		define($const, false);
}

if ( !defined('cache_timeout') )
	define('cache_timeout', 43200);

if ( static_cache && !class_exists('cache_fs') )
	include dirname(__FILE__) . '/cache-fs.php';

if ( memory_cache && !class_exists('object_cache') )
	include dirname(__FILE__) . '/object-cache.php';

class static_cache {
	private static $status_code = 200;
	private static $status_header;
	private static $static = static_cache;
	private static $memory = memory_cache;
	private static $nocache = false;
	private static $started = false;
	private static $host;
	
	
	/**
	 * disable()
	 *
	 * @param mixed $in
	 * @return mixed $in
	 **/

	static function disable($in = null) {
		self::$nocache = true;
		return $in;
	} # disable()
	
	
	/**
	 * status_header()
	 *
	 * @param string $status_header
	 * @param int $status_code
	 * @return string $status_header
	 **/

	static function status_header($status_header, $status_code) {
		self::$status_code = (int) $status_code;
		self::$status_header = $status_header;
		return $status_header;
	} # status_header()
	
	
	/**
	 * wp_redirect_status()
	 *
	 * @param int $status_code
	 * @return int $status_code
	 **/

	static function wp_redirect_status($status_code) {
		$text = get_status_header_desc($status_code);
		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
			$protocol = 'HTTP/1.0';
		$status_header = "$protocol $status_code $text";
		if ( function_exists('apply_filters') )
			$status_header = apply_filters('status_header', $status_header, $status_code, $text, $protocol);
		
		return $status_code;
	} # wp_redirect_status()
	
	
	/**
	 * send_headers()
	 *
	 * @param array $headers
	 * @return void
	 **/

	static function send_headers($headers) {
		$bail = false;
		
		$server_etag = false;
		$client_etag = !empty($_SERVER['HTTP_IF_NONE_MATCH'])
			? trim(strip_tags(stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])))
			: $req_etag = false;
		
		$server_modified = false;
		$client_modified = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			? trim($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			: '';
		
		if ( $client_etag || $client_modified ) {
			foreach ( $headers as $header ) {
				list($k, $v) = preg_split("/:\s*/", $header);
				switch ( strtolower($k) ) {
				case 'etag':
					$server_etag = trim(strip_tags($v));
					break;
				case 'last-modified':
					$server_modified = trim($v);
				}
			}
		}
		
		if ( $client_etag && $server_etag || $client_modified && $server_modified ) {
			if ( $client_modified && $server_modified ) {
				$client_modified = strtotime($client_modified);
				$server_modified = strtotime($server_modified);
			}
			
			if ( $client_etag && $server_etag && $client_modified && $server_modified ) {
				$bail = ( $client_etag == $server_etag ) && ( $client_modified >= $server_modified );
			} elseif ( $client_etag && $server_etag ) {
				$bail = ( $client_etag == $server_etag );
			} elseif ( $client_modified && $server_modified ) {
				$bail = ( $client_modified >= $server_modified );
			}
		}
		
		foreach ( $headers as $header )
			header($header, true);
		
		if ( $bail ) {
			header("$protocol 304 Not Modified", true, 304);
			die;
		}
	} # send_headers()
	
	
	/**
	 * start()
	 *
	 * @return void
	 **/

	static function start() {
		# some things can be taken care of at all times
		switch ( basename($_SERVER['REQUEST_URI']) ) {
		case 'favicon.ico':
			# bypass WP entirely on favicon.ico
			$protocol = $_SERVER["SERVER_PROTOCOL"];
			if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
				$protocol = 'HTTP/1.0';
			$headers = array(
				"$protocol 404 Not Found",
				'Expires: Wed, 11 Jan 1984 05:00:00 GMT',
				'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT',
				'Cache-Control: no-cache, must-revalidate, max-age=0',
				'Pragma: no-cache',
				'Content-Type: image/x-icon',
				);
			foreach ( $headers as $header )
				header($header, true);
			die;
		
		case 'sitemap.xml':
		case 'sitemap.xml.gz':
			# let xml sitemap plugins cache themselves
			return;
		}
		
		global $sem_cache_cookies;
		if ( ( !defined('WP_CACHE') || !WP_CACHE )
			|| isset($_GET['action']) || isset($_GET['doing_wp_cron'])
			|| isset($_GET['debug']) || isset($_GET['preview'])
			|| ( defined('WP_INSTALLING') && WP_INSTALLING )
			|| ( defined('WP_ADMIN') && WP_ADMIN )
			|| ( defined('DOING_CRON') && DOING_CRON )
			|| ( defined('DOING_AJAX') && DOING_AJAX )
			|| $_POST || !$sem_cache_cookies
			|| array_intersect(array_keys($_COOKIE), (array) $sem_cache_cookies)
			|| self::$nocache
			)
			return;
		
		global $sem_mobile_agents;
		$mobile_agents = $sem_mobile_agents;
		$mobile_agents = array_map('preg_quote', (array) $mobile_agents);
		$mobile_agents = implode("|", $mobile_agents);
		if ( preg_match("{($mobile_agents)}", $_SERVER['HTTP_USER_AGENT']) )
			return;
		
		#header("Content-Type: text/plain");
		#var_dump($_SERVER);
		#die;
		
		# kill static cache on multisite installs
		self::$static &= !( function_exists('is_multisite') && is_multisite() );
		
		if ( self::$started || headers_sent() || !self::$static && !self::$memory )
			return;
		
		if ( isset($_SERVER['HTTPS']) && ( 'on' == strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS'] ) || isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			self::$host = 'https://' . $_SERVER['HTTP_HOST'];
		} else {
			self::$host = 'http://' . $_SERVER['HTTP_HOST'];
		}
		
		$cache_id = self::$host . preg_replace("/#.*/", '', $_SERVER['REQUEST_URI']);
		$cache_id = md5($cache_id);
		
		if ( self::$memory ) {
			$headers = wp_cache_get($cache_id, 'cached_headers');
			
			if ( $headers !== false ) {
				self::send_headers($headers);
				
				$buffer = wp_cache_get($cache_id, 'cached_buffers');
				if ( $buffer )
					echo $buffer;
				die;
			}
		} elseif ( !( function_exists('is_multisite') && is_multisite() ) ) {
			# poor man's memcached
			$headers = '/semi-static/' . $cache_id . '.meta';
			
			if ( cache_fs::exists($headers, cache_timeout) ) {
				$headers = unserialize(cache_fs::get_contents($headers));
				self::send_headers($headers);
				
				$buffer = $path . $cache_id . '.html';
				if ( cache_fs::exists($buffer) )
					cache_fs::readfile($buffer);
				die;
			}
		}
		
		self::$started = true;
		ob_start(array('static_cache', 'ob_callback'));
	} # start()
	
	
	/**
	 * ob_callback()
	 *
	 * @param string $buffer
	 * @return string $buffer
	 **/

	static function ob_callback($buffer) {
		if ( !class_exists('sem_cache') )
			return $buffer;
		
		# some things just shouldn't be cached
		if ( self::$nocache && !in_array(self::$status_code, array(301, 302, 404))
			|| !in_array(self::$status_code, array(200, 301, 302, 404)) ) {
			return $buffer;
		}
		
		# sanity check on cookies
		global $sem_cache_cookies;
		if ( !$sem_cache_cookies || $sem_cache_cookies != sem_cache::get_cookies() )
			return $buffer;
		
		# only cache visitor requests
		if ( array_intersect(array_keys($_COOKIE), $sem_cache_cookies) )
			return $buffer;
		
		# bail on valid but rarely served pages
		if ( self::$status_code == 200
			&& !(
				# post or page
				!is_feed() && is_singular()
				# first page of blog or category
				|| !is_feed() && ( is_home() || is_category() ) && !is_paged()
				# blog and category feed
				|| is_feed() && ( !is_archive() && !is_singular() /* home feed */ || is_category() )
			) ) {
			return $buffer;
		}
		
		$permalink_structure = get_option('permalink_structure');
		
		# statically cache only when relevant
		self::$static &= ( strpos($_SERVER['REQUEST_URI'], './') === false )
			&& ( self::$status_code == 200 )
			&& !is_feed()
			&& empty($_GET)
			&& $permalink_structure
			&& ( strpos($permalink_structure, "|/index\.php/|") === false );
		
		# sanity check on the base folder
		$host = get_option('home');
		if ( preg_match("|^([^/]+://[^/]+)/|", $host, $_host) )
			$host = end($_host);
		if ( $host != self::$host )
			return $buffer;
		
		# sanity check on incomplete files
		if ( !in_array(self::$status_code, array(301, 302)) && !preg_match("/(?:<\/html>|<\/rss>|<\/feed>)/i",$buffer) )
			return $buffer;
		
		# sanity check on mobile users
		global $sem_mobile_agents;
		if ( $sem_mobile_agents != sem_cache::get_mobile_agents() )
			return $buffer;
		$mobile_agents = $sem_mobile_agents;
		$mobile_agents = array_map('preg_quote', (array) $mobile_agents);
		$mobile_agents = implode("|", $mobile_agents);
		if ( preg_match("{($mobile_agents)}", $_SERVER['HTTP_USER_AGENT']) )
			return $buffer;
		
		if ( self::$static ) {
			$file = preg_replace("/#.*/", '', $_SERVER['REQUEST_URI']);
			$file = '/static/' . trim($file, '/');
			if ( !preg_match("/\.html$/", $file) ) {
				global $wp_rewrite;
				if ( $wp_rewrite->use_trailing_slashes )
					$file = $file . '/index.html';
				else
					$file = $file . '.html';
			}
			cache_fs::put_contents($file, $buffer);
		} elseif ( self::$memory ) {
			$cache_id = $host . preg_replace("/#.*/", '', $_SERVER['REQUEST_URI']);
			$cache_id = md5($cache_id);
			
			$headers = headers_list();
			if ( self::$status_header )
				array_unshift($headers, self::$status_header);
			
			wp_cache_add($cache_id, $headers, 'cached_headers', cache_timeout);
			if ( $buffer && !in_array(self::$status_code, array(301, 302)) )
				wp_cache_add($cache_id, $buffer, 'cached_buffers', cache_timeout);
		} elseif ( !( function_exists('is_multisite') && is_multisite() ) ) {
			 # poor man's memcached
			$cache_id = $host . preg_replace("/#.*/", '', $_SERVER['REQUEST_URI']);
			$cache_id = md5($cache_id);
			$file = '/semi-static/' . $cache_id;
			
			$headers = headers_list();
			if ( self::$status_header )
				array_unshift($headers, self::$status_header);
			
			cache_fs::put_contents($file . '.meta', serialize($headers));
			if ( $buffer && !in_array(self::$status_code, array(301, 302)) )
				cache_fs::put_contents($file . '.html', $buffer);
		}
		
		return $buffer;
	} # ob_callback()
} # static_cache

static_cache::start();
?>