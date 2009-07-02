<?php
/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/


Hat tips
--------

	* David Young -- http://www.inspirationaljournal.com


IMPORTANT
---------

1. DO NOT USE THIS PLUGIN FOR COMMERCIAL PURPOSES or otherwise breach Yahoo!’s terms of use:
   http://developer.yahoo.net/terms/
2. Note that your server's IP is eligible to 5,000 calls per 24h
   http://developer.yahoo.net/documentation/rate.html
**/

#
# Config
#

if ( !defined('sem_cache_path') )
{
	define('sem_cache_path', ABSPATH . 'wp-content/cache/'); # same as wp-cache

	if ( !get_option('sem_cache_created') )
	{
		@mkdir(sem_cache_path, 0777);

		update_option('sem_cache_created', 1);
	}
}
if ( !defined('sem_cache_long_timeout') )
{
	define('sem_cache_long_timeout', 3600 * 24 * 7); # one week
}


class sem_extract_terms
{
	#
	# Variables
	#


	#
	# constructor
	#

	function sem_extract_terms()
	{
		add_action('shutdown', array(&$this, 'clean_cache'));
	} # end sem_extract_terms()


	#
	# clean_cache()
	#

	function clean_cache()
	{
		if ( ( get_option('sem_clean_yt_cache') + sem_cache_long_timeout ) < time() )
		{
			foreach ( glob(sem_cache_path . "yt-*") as $cache_file )
			{
				if ( ( filemtime($cache_file) + sem_cache_long_timeout ) < time() )
				{
					@unlink($cache_file);
				}
			}

			update_option('sem_clean_yt_cache', time());
		}
	} # end clean_cache()


	#
	# get_terms()
	#

	function get_terms($context = '', $query = '')
	{
		# catch no cache error

		if ( !is_writable(sem_cache_path) )
		{
			return array();
		}

		# clean up

		$context = str_replace("\r", "", $context);
		$context = trim(strip_tags($context));

		$query = str_replace("\r", "", $query);
		$query = trim(strip_tags($query));

		# query vars

		$vars = array(
			"appid" => "WordPress/Extract Terms Plugin (http://www.semiologic.com)",
			"context" => $context,
			"query" => $query
			);

		$cache_file = sem_cache_path . "yt-". md5($context.$query) . ".xml";

		clearstatcache(); // reset file cache status, in case of multiple calls

		if ( @file_exists($cache_file)
			&& ( ( @filemtime($cache_file) + sem_cache_long_timeout ) > time() )
			)
		{
			$xml = file_get_contents( $cache_file );
		}
		else
		{
			# Process content

			foreach ( $vars as $key => $value )
			{
				$content .= rawurlencode($key)
					. "=" . rawurlencode($value)
					. ( ( ++$i < sizeof($vars) )
						? "&"
						: ""
						);
			}

			# Build header

			$headers = "POST /ContentAnalysisService/V1/termExtraction HTTP/1.1
Accept: */*
Content-Type: application/x-www-form-urlencoded; charset=" . get_settings('blog_charset') . "
User-Agent: " . $vars['appid'] . "
Host: api.search.yahoo.com
Connection: Keep-Alive
Cache-Control: no-cache
Content-Length: " . strlen($content) . "

";

			# Open socket connection

			$fp = @fsockopen("api.search.yahoo.com", 80);

			# Discard the call if it times out

			if ( !$fp )
			{
				return false;
			}

			# Send headers and content

			fputs($fp, $headers);
			fputs($fp, $content);

			# Retrieve the result

			$xml = "";
			while ( !feof($fp) )
			{
				$xml .= fgets($fp, 1024);
			}
			fclose($fp);

			# Clean up

			$xml = preg_replace("/^[^<]*|[^>]*$/", "", $xml);

			# Cache

			$fp = @fopen($cache_file, "w+");
			@fwrite($fp, $xml);
			@fclose($fp);
		}

		preg_match_all("/<Result>([^<]+)<\/Result>/", $xml, $out);

		$terms = end($out);

		return $terms;
	} # end get_terms();


	#
	# get_post_terms()
	#

	function get_post_terms($post = null)
	{
		if ( !isset($post) )
		{
			$post =& $GLOBALS['post'];
		}

		return $this->get_terms($post->post_title . "\n\n" . $post->post_content);
	}
} # end sem_extract_terms

$sem_extract_terms =& new sem_extract_terms();


#
# Template tags
#

function get_the_post_terms($post = null)
{
	global $sem_extract_terms;

	return $sem_extract_terms->get_post_terms($post);
} # end get_the_post_terms()

function get_the_terms($context = null, $query = null)
{
	global $sem_extract_terms;

	return $sem_extract_terms->get_terms($context, $query);
} # end get_the_terms()


########################
#
# Backward compatibility
#

function sem_extract_terms($post = null)
{
	return get_the_post_terms($post);
} # end sem_extract_terms()

function sem_get_terms($context = null, $query = null)
{
	return get_the_terms($context, $query);
} # end sem_get_terms()
?>