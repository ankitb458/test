<?php
#
# MC String Manipulation Library
# ------------------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class str
{
	#
	# markdown()
	#

	public static function markdown($str = '')
	{
		if ( !function_exists('markdown') )
		{
			require_once mc_path . '/system/inc/markdown.php';
		}

		return trim(markdown($str));
	} # markdown()


	#
	# attr()
	# ------
	# Format a string as attribute output
	#

	public static function attr($str = '')
	{
		$str = htmlspecialchars($str);

		return $str;
	} # attr()


	#
	# encode()
	# --------
	# Format a string for use as a url parameter
	#

	public static function encode($str = '')
	{
		$str = urlencode($str);

		return $str;
	} # encode()


	#
	# xml()
	# -----
	# Format a string for xml output
	#

	public static function xml($str = '')
	{
		$str = htmlentities($str);

		return $str;
	} # xml()


	#
	# cdata()
	# -------
	# Format a string for xml cdata output
	#

	public static function cdata($str = '')
	{
		$str = '<![CDATA[' . str_replace(']]>', ']]&gt;', $str) . ']]>';

		return $str;
	} # cdata()
} # str
?>