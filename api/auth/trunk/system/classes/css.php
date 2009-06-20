<?php
#
# MC CSS Service
# --------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class css
{
	static protected $files = array();


	#
	# init()
	#

	public static function init()
	{
		event::attach('html_css', array('css', 'html'));
	} # init()


	#
	# load()
	#

	public static function load($file)
	{
		$file = str_replace(mc_path, mc_url, $file);

		if ( strpos($file, '://') === false )
		{
			$file = mc_url . '/' . ltrim($file, '/');
		}

		if ( !in_array($file, self::$files) )
		{
			self::$files[] = $file;
		}
	} # load()


	#
	# output()
	#

	public static function output(&$args)
	{
		$this->{$args['output']}($args);
	} # output()


	#
	# html()
	#

	public static function html(&$args)
	{
		foreach ( self::$files as $file )
		{
			echo '<link rel="stylesheet" type="text/css" href="' . $file . '" />';
		}
	} # html()


	#
	# xml()
	#

	public static function xml()
	{
	} # xml()
} # css

css::init();
?>