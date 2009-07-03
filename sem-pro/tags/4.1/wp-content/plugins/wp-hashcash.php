<?php
/*
Plugin Name: WordPress Hashcash
Plugin URI: http://elliottback.com/wp/archives/2005/10/23/wordpress-hashcash-30-beta/
Description: Client-side javascript blocks all spam bots.  XHTML 1.1 compliant.
Author: Elliott Back
Author URI: http://elliottback.com
Version: 3.2 (edited)
*/

require_once(realpath(dirname(__FILE__) . '/') . '/wp-hashcash.lib');

// CREATE HASHCASH BADLIST TABLE
if(function_exists('get_option')){
	global $wpdb, $table_prefix;

	if(!get_option('wp_hashcash_db')){
		$sql = "CREATE TABLE $table_prefix" . WP_HASHCASH . " (hash VARCHAR(32) NOT NULL, day DATETIME NOT NULL, INDEX(hash, day))";

		if($wpdb->query($sql) === false)
			$wpdb->print_error();

		update_option('wp_hashcash_db', true);
	}
}

// UPDATE RANDOM SECRET
$curr = @file_get_contents(HASHCASH_SECRET_FILE);
if(empty($curr) || (time() - @filemtime(HASHCASH_SECRET_FILE)) > HASHCASH_REFRESH){
	global $table_prefix, $wpdb;

	// update our secret
	$fp = @fopen(HASHCASH_SECRET_FILE, 'w');

	if ( $fp )
	{
		if(@flock($fp, LOCK_EX)){
			fwrite($fp, rand(21474836, 2126008810));
			@flock($fp, LOCK_UN);
		}

		fclose($fp);
	}

	// remove old entries from DB
	$res = $wpdb->query("DELETE FROM $table_prefix" . WP_HASHCASH . " WHERE (unix_timestamp(NOW()) - unix_timestamp(day)) > " . HASHCASH_IP_EXPIRE);
	if(FALSE === $res)
		$wpdb->print_error();

	// clean wp-cache
	if(function_exists('wp_cache_clean_expired')){
		wp_cache_clean_expired('wp-cache-');
	}
}

function hashcash_add_hidden_tag() {
	global $post;

	if ((is_single() || is_page()) && $post->comment_status == 'open'){
		echo '<link rel="powered" title="Elliott Back\'s Antispam" href="http://elliottback.com" />';
		echo '<script type="text/javascript" src="' . get_bloginfo('url') . '/wp-content/plugins/wp-hashcash-js.php"></script>';
	}
}

function hashcash_check_hidden_tag($comment_id) {
	global $wpdb, $table_prefix;

	// Ignore trackbacks
	$type = $wpdb->get_var("SELECT comment_type FROM $wpdb->comments WHERE comment_ID = '$comment_id'");
	if($type === "trackback" || $type === "pingback"){
		return $comment_id;
	}

	// Check the banlist
	$ban_count = $wpdb->get_var("SELECT count(*) FROM $table_prefix" . WP_HASHCASH . " WHERE hash = '" . md5($_SERVER['REMOTE_ADDR']) . "'");
	if($ban_count < 4){
		if($_POST["hashcash_value"] == hashcash_field_value())
			return $comment_id;

		$res = $wpdb->query("INSERT INTO $table_prefix" . WP_HASHCASH . " (hash, day) VALUES ('" . md5($_SERVER['REMOTE_ADDR']) . "', '" . date("y-m-d H:i:s") . "')");
		if($res === false)
			$wpdb->print_error();
	}

	// If here, the comment has failed the check: delete
	if ($wpdb->query("DELETE FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1") === false)
		$wpdb->print_error();

	// Be more user friendly if we detect spam, and it sends a referer
	if(strlen(trim($_SERVER['HTTP_REFERER'])) > 0 && preg_match('|' . get_bloginfo('url') . '|i', $_SERVER['HTTP_REFERER']))
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head profile="http://gmpg.org/xfn/11">
		<title>WP-Hashcash Check Failed</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style type="text/css">
			body {
				font-family: Arial, Verdana, Helvetica;
				color: #3F3F3F;
			}

			h1 {
				margin: 0px;
				color: #6A8E1C;
				font-size: 1.8em;
			}

			a:link {
				color: #78A515;
				font-weight: bold;
				text-decoration: none;
			}

			a:visited { color: #999; }

			a:hover, a:active {
				background: #78A515;
				color: #fff;
				text-decoration: none;
			}
		</style>
	</head>

	<body>
		<div style="margin: 0 auto; margin-top:50px; padding: 20px; text-align: left; width: 400px; border: 1px solid #78A515;">
			<h1>WP-Hashcash Check Failed</h1>

			<p>Your client has failed to compute the special javascript code required to comment on this blog.
			If you believe this to be in error, please contact the blog administrator, and check for javascript,
			validation, or php errors.  It is also possible that you are trying to spam this blog.</p>

			<p>If you are using Google Web Accelerator, a proxy, or some other caching system, WP-Hashcash may not let you comment.
			There are known issues with caching that are fundamentally insoluble, because the page being written to you must be generated freshly.
			Turn off your caching software and reload the page. If you are using a proxy, commenting should work, but it is untested.</p>

			<?php if($ban_count >= 4)
				echo '<p style="border: 2px solid red; color:red; padding:4px;">You have previously failed the check ' . $ban_count . ' times.</p>';
			?>

			<?php
				echo '<p style="border: 2px solid red; color:red; padding:4px;">Your POST variables are: ';
				print_r($_POST);
				echo '</p>';
			?>

			<p>This comment has been logged, and will not be displayed on the blog.</p>
		</div>
	</body>
</html>
<?php	die();
}

add_filter('comment_post', 'hashcash_check_hidden_tag');
add_action('wp_head', 'hashcash_add_hidden_tag');

?>