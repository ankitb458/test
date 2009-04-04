<?php

/** Diable here because PHP4.3 does not make the global
 Serious bug?

$mutex_filename = 'wp_cache_mutex.lock';
$new_cache = false;
*/

function wp_cache_phase2() {
	global $cache_filename, $wp_cache_meta_object;
	
	if ( !function_exists('add_action') ) return;
	
	wp_cache_mutex_init();

	// Post ID is received
	add_action('publish_post', 'wp_cache_post_change', 0);
	add_action('edit_post', 'wp_cache_post_change', 0);
	add_action('delete_post', 'wp_cache_post_change', 0);
	add_action('publish_phone', 'wp_cache_post_change', 0);
	
	if ( !defined( 'WPLOCKDOWN' ) || !WPLOCKDOWN )
	{
		// Coment ID is received
		add_action('trackback_post', 'wp_cache_get_postid_from_comment', 0);
		add_action('pingback_post', 'wp_cache_get_postid_from_comment', 0);
		add_action('comment_post', 'wp_cache_get_postid_from_comment', 0);
		add_action('edit_comment', 'wp_cache_get_postid_from_comment', 0);
		add_action('wp_set_comment_status', 'wp_cache_get_postid_from_comment', 0);
		// No post_id is available
		add_action('delete_comment', 'wp_cache_no_postid', 0);
	}
	
	// Admin-triggered site changes
	add_action('switch_theme', 'wp_cache_no_postid', 0); 
	add_action('update_option_sidebars_widgets', 'wp_cache_no_postid', 0);
	add_action('update_option_active_plugins', 'wp_cache_no_postid', 0);
	add_action('update_option_sem5_options', 'wp_cache_no_postid', 0);
	add_action('update_option_sem6_options', 'wp_cache_no_postid', 0);
	add_action('update_option_sem_seo', 'wp_cache_no_postid', 0);
	add_action('update_option_script_manager', 'wp_cache_no_postid', 0);
	add_action('update_option_show_on_front', 'wp_cache_no_postid', 0);
	add_action('update_option_page_on_front', 'wp_cache_no_postid', 0);
	add_action('update_option_page_for_posts', 'wp_cache_no_postid', 0);
	add_action('generate_rewrite_rules', 'wp_cache_no_postid', 0);
	
	add_action('generate_rewrite_rules', 'wp_cache_toggle');
	
	add_action('update_option_plugin_wp-hashcash', 'wp_cache_no_postid', 0);
		
	if ( is_admin() ) return;

	if( $_SERVER["REQUEST_METHOD"] == 'POST') 
		return;
	$script = basename($_SERVER['PHP_SELF']);
	if ( !in_array($script,
			array('wp-atom.php', 'wp-comments-popup.php', 'wp-commentsrss2.php', 'wp-links-opml.php', 'wp-locations.php', 'wp-rdf.php', 'wp-rss.php', 'wp-rss2.php'))
		&& wp_cache_is_rejected($_SERVER["REQUEST_URI"]))
		return;
	if (wp_cache_user_agent_is_rejected()) return;
	$wp_cache_meta_object = new CacheMeta;
	ob_start('wp_cache_ob_callback'); 
	add_action('shutdown', 'wp_cache_shutdown_callback', 2);
}

function wp_cache_get_response_headers() {
	if(function_exists('apache_response_headers')) {
		flush();
		$headers = apache_response_headers();
	} else if(function_exists('headers_list')) {
		$headers = array();
		foreach(headers_list() as $hdr) {
			list($header_name, $header_value) = explode(': ', $hdr, 2);
			$headers[$header_name] = $header_value;
		}
	} else
		$headers = null;

	return $headers;
}

function wp_cache_is_rejected($uri) {
	global $cache_rejected_uri;

	if (strstr($uri, '/wp-admin/'))
		return true; // we don't allow caching of wp-admin for security reasons
	if (preg_match('/wp-.+\.php/', $uri))
		return true; // likely a WP file in the root folder
	foreach ($cache_rejected_uri as $expr) {
		$expr = str_replace('`', '\\`', $expr);
		if( preg_match( "`$expr`", $uri ) )
			return true;
	}
	return false;
}

function wp_cache_user_agent_is_rejected() {
	if (!function_exists('apache_request_headers')) return false;
	$headers = apache_request_headers();
	if (!isset($headers["User-Agent"])) return false;
	foreach (array ( 0 => 'bot', 1 => 'ia_archive', 2 => 'slurp', 3 => 'crawl', 4 => 'spider') as $expr) {
		if (strlen($expr) > 0 && stristr($headers["User-Agent"], $expr))
			return true;
	}
	return false;
}


function wp_cache_mutex_init() {
	global $use_flock, $mutex, $cache_path, $mutex_filename, $sem_id;

	if(!is_bool($use_flock)) {
		if(function_exists('sem_get')) 
			$use_flock = false;
		else
			$use_flock = true;
	}

	$mutex = false;
	if ($use_flock) 
		$mutex = @fopen($cache_path . $mutex_filename, 'w');
	else
		$mutex = @sem_get($sem_id, 1, 0644 | IPC_CREAT, 1);
}

function wp_cache_writers_entry() {
	global $use_flock, $mutex, $cache_path, $mutex_filename;

	if( !$mutex )
		return false;

	if ($use_flock)
		flock($mutex,  LOCK_EX);
	else
		sem_acquire($mutex);

	return true;
}

function wp_cache_writers_exit() {
	global $use_flock, $mutex, $cache_path, $mutex_filename;

	if( !$mutex )
		return false;

	if ($use_flock)
		flock($mutex,  LOCK_UN);
	else
		sem_release($mutex);
}

function wp_cache_ob_callback($buffer) {
	global $cache_path, $cache_filename, $meta_file, $wp_start_time, $supercachedir;
	global $new_cache, $wp_cache_meta_object, $file_expired, $blog_id, $cache_compression;
	global $cache_enabled, $super_cache_enabled;
	
	/* Mode paranoic, check for closing tags 
	 * we avoid caching incomplete files */
	if (is_404() || !preg_match('/(<\/html>|<\/rss>|<\/feed>)/i',$buffer) || !$cache_enabled ) {
		$new_cache = false;
		return $buffer;
	}

	$duration = wp_cache_microtime_diff($wp_start_time, microtime());
	$duration = sprintf("%0.3f", $duration);

	if( !wp_cache_writers_entry() )
		return false;
	$mtime = @filemtime($cache_path . $cache_filename);
	/* Return if:
		the file didn't exist before but it does exist now (another connection created)
		OR
		the file was expired and its mtime is less than 5 seconds
	*/
	if( !((!$file_expired && $mtime) || ($mtime && $file_expired && (time() - $mtime) < 5)) ) {
		if ( $fr = @fopen($cache_path . $cache_filename, 'w') )
		{
			$buffer .= wp_cache_log("Dynamic Page Served (once) in $duration seconds");

			if (preg_match('/<!--mclude|<!--mfunc/', $buffer)) { //Dynamic content
				$store = preg_replace('|<!--mclude (.*?)-->(.*?)<!--/mclude-->|is', 
						"<!--mclude-->\n<?php include_once('" . ABSPATH . "$1'); ?>\n<!--/mclude-->", $buffer);
				$store = preg_replace('|<!--mfunc (.*?)-->(.*?)<!--/mfunc-->|is', 
						"<!--mfunc-->\n<?php $1 ;?>\n<!--/mfunc-->", $store);
				$wp_cache_meta_object->dynamic = true;
				/* Clean function calls in tag */
				$buffer = preg_replace('|<!--mclude (.*?)-->|is', '<!--mclude-->', $buffer);
				$buffer = preg_replace('|<!--mfunc (.*?)-->|is', '<!--mfunc-->', $buffer);
				fputs($fr, $store . wp_cache_log("Cached"));
			} else {
				fputs($fr, $buffer . wp_cache_log("Cached"));

				$uri = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', str_replace( '..', '', $_SERVER['REQUEST_URI']) );
				$dir = strtolower(preg_replace('/:.*$/', '',  $_SERVER["HTTP_HOST"])) . $uri; // To avoid XSS attacs
				$dir = trailingslashit( $cache_path . 'supercache/' . $dir );
				$supercachedir = $cache_path . 'supercache/' . preg_replace('/:.*$/', '',  $_SERVER["HTTP_HOST"]);
				
				if ( !empty( $_GET ) || !empty( $_COOKIE )
					|| is_feed()
					|| ( $super_cache_enabled == true && is_dir( substr( $supercachedir, 0, -1 ) . '.disabled' ) )
					)
				{
					$super_cache_enabled = false;
				}
				
				if ( $super_cache_enabled )
				{
					$dir = str_replace( '//', '/', $dir );
					if( @is_dir( $dir ) == false )
						@wp_mkdir_p( $dir );

					$user_info = wp_cache_get_cookies_values();
					
					$do_cache = apply_filters( 'do_createsupercache', $user_info );
					
					if ( $user_info === '' && $do_cache !== false || $do_cache === true )
					{
						if ( $fr2 = @fopen("{$dir}index.html", 'w') )
						{
							fputs($fr2, $buffer . wp_cache_log("Super Cached"));
							fclose($fr2);
						}
					}
				}
			}
			
			fclose($fr);
			$new_cache = true;
		}
	}
	wp_cache_writers_exit();
	return $buffer;
}

function wp_cache_phase2_clean_cache($file_prefix) {
	global $cache_path;

	if( !wp_cache_writers_entry() )
		return false;
	if ( ($handle = opendir( $cache_path )) ) { 
		while ( false !== ($file = readdir($handle))) {
			if ( preg_match("/^$file_prefix/", $file) ) {
				@unlink($cache_path . $file);
			}
		}
		closedir($handle);
	}
	wp_cache_writers_exit();
}

function prune_super_cache($directory, $force = false) {
	global $super_cache_max_time, $cache_path;

	if( !isset( $super_cache_max_time ) )
		$super_cache_max_time = 21600;

	$now = time();

	$protected_directories = array( $cache_path . '.htaccess', $cache_path . 'meta', $cache_path . 'supercache' );

	$oktodelete = false;
	if (is_dir($directory)) {
		$directory = trailingslashit( $directory );
		$entries = glob($directory. '*');
		if( is_array( $entries ) && !empty( $entries ) ) foreach ($entries as $entry) {
			if ($entry != '.' && $entry != '..') {
				prune_super_cache($entry, $force);
				if( is_dir( $entry ) && ( $force || @filemtime( $entry ) + $super_cache_max_time <= $now ) ) {
					$oktodelete = true;
					if( in_array( $entry, $protected_directories ) )
						$oktodelete = false;
					if( $oktodelete )
						@rmdir( addslashes( $entry ) );
				}
			}
		}
	} else {
		if( is_file($directory) && ($force || filemtime( $directory ) + $super_cache_max_time <= $now ) ) {
			$oktodelete = true;
			if( in_array( $directory, $protected_directories ) )
				$oktodelete = false;
			if( $oktodelete )
				@unlink( addslashes( $directory ) );
		}
	}
}

function wp_cache_phase2_clean_expired($file_prefix) {
	global $cache_path, $cache_max_time;

	clearstatcache();
	if( !wp_cache_writers_entry() )
		return false;
	$now = time();
	if ( ($handle = opendir( $cache_path )) ) { 
		while ( false !== ($file = readdir($handle))) {
			if ( preg_match("/^$file_prefix/", $file) && 
				(filemtime($cache_path . $file) + $cache_max_time) <= $now  ) {
				@unlink($cache_path . $file);
				@unlink($cache_path . 'meta/' . str_replace( '.html', '.meta', $file ) );
				continue;
			}
			if($file != '.' && $file != '..') {
				if( is_dir( $cache_path . $file ) == false && (filemtime($cache_path . $file) + $cache_max_time) <= $now  ) {
					if( substr( $file, -9 ) != '.htaccess' )
						@unlink($cache_path . $file);
				} elseif( is_dir( $cache_path . $file ) ) {
					prune_super_cache( $cache_path . $file );
				}
			}
		}
		closedir($handle);
	}

	wp_cache_writers_exit();
}

function wp_cache_shutdown_callback() {
	global $cache_path, $cache_max_time, $file_expired, $file_prefix, $meta_file, $new_cache;
	global $wp_cache_meta_object, $known_headers, $blog_id;

	$wp_cache_meta_object->uri = $_SERVER["SERVER_NAME"].preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', $_SERVER['REQUEST_URI']); // To avoid XSS attacs
	$wp_cache_meta_object->blog_id=$blog_id;
	$wp_cache_meta_object->post = wp_cache_post_id();

	$response = wp_cache_get_response_headers();
	foreach ($known_headers as $key) {
		if(isset($response{$key})) {
			array_push($wp_cache_meta_object->headers, "$key: " . $response{$key});
		}
	}
	/* Not used because it gives problems with some
	 * PHP installations
	if (!$response{'Content-Length'}) {
	// WP does not set content size
		$content_size = ob_get_length();
		@header("Content-Length: $content_size");
		array_push($wp_cache_meta_object->headers, "Content-Length: $content_size");
	}
	*/
	if (!$response{'Last-Modified'}) {
		$value = gmdate('D, d M Y H:i:s') . ' GMT';
		/* Dont send this the first time */
		/* @header('Last-Modified: ' . $value); */
		array_push($wp_cache_meta_object->headers, "Last-Modified: $value");
	}
	if (!$response{'Content-Type'} && !$response{'Content-type'}) {
		// On some systems, headers set by PHP can't be fetched from
		// the output buffer. This is a last ditch effort to set the
		// correct Content-Type header for feeds, if we didn't see
		// it in the response headers already. -- dougal
		if (is_feed()) {
			$type = get_query_var('feed');
			$type = str_replace('/','',$type);
			switch ($type) {
				case 'atom':
					$value = "application/atom+xml";
					break;
				case 'rdf':
					$value = "application/rdf+xml";
					break;
				case 'rss':
				case 'rss2':
				default:
					$value = "application/rss+xml";
			}
		} else { // not a feed
			$value = 'text/html';
		}
		$value .=  "; charset=\"" . get_option('blog_charset')  . "\"";

		@header("Content-Type: $value");
		array_push($wp_cache_meta_object->headers, "Content-Type: $value");
	}

	ob_end_flush();
	if ($new_cache) {
		$serial = serialize($wp_cache_meta_object);
		if( !wp_cache_writers_entry() )
			return false;
		$fr = @fopen($cache_path . 'meta/' . $meta_file, 'w');
		if( !$fr )
		{
			@mkdir( $cache_path . 'meta' );
			$fr = fopen($cache_path . 'meta/' . $meta_file, 'w');
		}
		fputs($fr, $serial);
		fclose($fr);
		wp_cache_writers_exit();
	}

	if ($file_expired == false) {
		return;
	}

	// we delete expired files
	flush(); //Ensure we send data to the client
	wp_cache_phase2_clean_expired($file_prefix);
}

function wp_cache_no_postid($id) {
	wp_cache_post_change(wp_cache_post_id());
	return $id;
}

function wp_cache_get_postid_from_comment($comment_id) {
	global $super_cache_enabled;
	$comment = get_commentdata($comment_id, 1, true);
	$postid = $comment['comment_post_ID'];
	// Do nothing if comment is not moderated
	// http://ocaoimh.ie/2006/12/05/caching-wordpress-with-wp-cache-in-a-spam-filled-world
	if( !preg_match('/wp-admin\//', $_SERVER['REQUEST_URI']) ) 
		if( $comment['comment_approved'] == 'spam' ) { // changed from 1 to "spam"
			return $post_id;
		} elseif( $comment['comment_approved'] == '0' ) {
			$super_cache_enabled = 0; // don't remove the super cache static file until comment is approved
		}
	// We must check it up again due to WP bugs calling two different actions
	// for delete, for example both wp_set_comment_status and delete_comment 
	// are called when deleting a comment
	if ($postid > 0) 
		return wp_cache_post_change($postid);
	else 
		return wp_cache_post_change(wp_cache_post_id());
}

function wp_cache_post_change($post_id) {
	global $file_prefix, $cache_path, $blog_id, $blogcacheid, $super_cache_enabled;
	static $last_processed = -1;

	// Avoid cleaning twice the same pages
	if ($post_id == $last_processed) return $post_id;
	$last_processed = $post_id;
	$permalink = trailingslashit( str_replace( get_option( 'siteurl' ), '', post_permalink( $post_id ) ) );
	if( $super_cache_enabled ) {
		$permalink = trailingslashit( str_replace( get_option( 'siteurl' ), '', post_permalink( $post_id ) ) );
		$siteurl = str_replace( 'http://', '', get_option( 'siteurl' ) );
		$dir = $cache_path . 'supercache/' . strtolower(preg_replace('/:.*$/', '', $siteurl ) );
		$files_to_delete = array( $dir . '/index.html', $dir . '/feed/index.html', $dir . $permalink . 'index.html', $dir . $permalink . 'feed/index.html' );
		foreach( $files_to_delete as $cache_file ) {
			@unlink( $cache_file );
			@unlink( $cache_file . '.gz' );
		}
	}

	$meta = new CacheMeta;
	$matches = array();
	if( !wp_cache_writers_entry() )
		return $post_id;
	if ( ($handle = opendir( $cache_path . 'meta/' )) ) { 
		while ( false !== ($file = readdir($handle))) {
			if ( preg_match("/^({$file_prefix}{$blogcacheid}.*)\.meta/", $file, $matches) ) {
				$meta_pathname = $cache_path . 'meta/' . $file;
				$content_pathname = $cache_path . $matches[1] . ".html";
				$meta = unserialize(@file_get_contents($meta_pathname));
				if ($post_id > 0 && $meta) {
					if ($meta->blog_id == $blog_id  && (!$meta->post || $meta->post == $post_id) ) {
						@unlink($meta_pathname);
						@unlink($content_pathname);
					}
				} elseif ($meta->blog_id == $blog_id) {
					@unlink($meta_pathname);
					@unlink($content_pathname);
				}

			}
		}
		closedir($handle);
	}
	wp_cache_writers_exit();
	return $post_id;
}

function wp_cache_microtime_diff($a, $b) {
	list($a_dec, $a_sec) = explode(' ', $a);
	list($b_dec, $b_sec) = explode(' ', $b);
	return $b_sec - $a_sec + $b_dec - $a_dec;
}

function wp_cache_post_id() {
	global $posts, $comment_post_ID, $post_ID, $wp_query;
	// We try hard all options. More frequent first.
	if ($post_ID > 0 ) return $post_ID;
	if ($comment_post_ID > 0 )  return $comment_post_ID;
	if (is_singular()) return $wp_query->get_queried_object_id();
	if ($_GET['p'] > 0) return $_GET['p'];
	if ($_POST['p'] > 0) return $_POST['p'];
	return 0;
}

function wp_cache_log($data)
{
	global $cache_debug;
	
	if ( $cache_debug && !is_feed() )
	{
		return "\n<code>$data...</code>\n";
	}
	else
	{
		return "\n<!-- $data -->\n";
	}
}

function wp_cache_toggle()
{
	if ( function_exists('wp_cache_can_super_cache') && defined('WP_CACHE') )
	{
		global $super_cache_enabled;
		
		if ( wp_cache_can_super_cache() != $super_cache_enabled )
		{
			wp_cache_disable();
			wp_cache_enable(defined('WPLOCKDOWN'));
		}
	}
}
?>