<?php
#
# MC Debug Library
# ----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class debug
{
	#
	# dump()
	#

	public static function dump()
	{
		foreach ( (array) func_get_args() as $arg )
		{
			echo '<pre style="text-align: left !important; color: black !important; background-color: white !important; padding: 10px !important; margin: 10px !important; border: solid 1px silver !important;">';
			var_dump($arg);
			echo '</pre>';
		}
	} # dump()


	#
	# reformat()
	#

	public static function reformat($buffer = '')
	{
		$buffer = preg_replace_callback(
			"/
				<
				(\/)?
				([^>\s]+)
				(?:\s+[^>]*)?
				>
			/x",
			array('debug', 'reformat_callback'),
			$buffer
			);

		return $buffer;
	} # reformat()


	#
	# reformat_callback()
	#

	public static function reformat_callback($match)
	{
		switch ( $match[2] )
		{
		# line break after start and end tag
		case 'html':
		case 'head':
		case 'body':
		case 'link':
		case 'meta':
		case 'style':
		case 'script':
		case 'form':
		case 'div':
		case 'table':
		case 'tr':
		case 'ul':
		case 'ol':
		case 'br':
		case 'input':
		case 'button':
		case 'select':
		case '!--':
			return $match[0] . "\n";

		# line break after end tag
		case 'title':
		case 'h1':
		case 'h2':
		case 'h3':
		case 'p':
		case 'th':
		case 'td':
		case 'li':
		case 'pre':
		case 'label':
		case 'option':
			return $match[0] . ( $match[1] ? "\n" : '' );

		# no line break
		default:
			return $match[0];
		}
	} # reformat_callback()


	#
	# server()
	#

	public static function server($key = null)
	{
		return self::dump(isset($key) ? $_SERVER[$key] : $_SERVER);
	} # server()


	#
	# post()
	#

	public static function post($key = null)
	{
		return self::dump(isset($key) ? $_POST[$key] : $_POST);
	} # post()


	#
	# get()
	#

	public static function get($key = null)
	{
		return self::dump(isset($key) ? $_GET[$key] : $_GET);
	} # get()


	#
	# cookie()
	#

	public static function cookie($key = null)
	{
		return self::dump(isset($key) ? $_COOKIE[$key] : $_COOKIE);
	} # cookie()
} # debug
?>