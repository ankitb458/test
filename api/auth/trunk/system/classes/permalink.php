<?php
#
# MC Permalink Service
# --------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class permalink
{
	public static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
	} # init()


	#
	# get()
	# -----
	# Build a site url from an array
	#

	public static function get($query = null)
	{
		$url = site_url . '/'
			. ( ( isset($query) && !empty($query) )
				? ( '?' . ( is_array($query) ? http_build_query($query) : $query ) )
				: ''
				);
		
		return $url;
	} # get()


	#
	# redirect()
	#

	public static function redirect($location = null)
	{
		ob::clean();

		if ( isset($location) )
		{
			if ( is_array($location) )
			{
				$location = permalink::get($location);
			}

			$protocol = $_SERVER['SERVER_PROTOCOL'];

			if ( ( $protocol != 'HTTP/1.1' ) && ( $protocol != 'HTTP/1.0' ) )
			{
				$protocol = 'HTTP/1.0';
			}

			$status_header = "$protocol 301 Permanent Redirect";

			if ( iis )
			{
				header("Refresh: 0;url=$location");
			}
			else
			{
				header($status_header);
				header("Location: $location");
			}
		}

		# new notice($location);

		die;
	} # redirect()
} # permalink

permalink::init();
?>