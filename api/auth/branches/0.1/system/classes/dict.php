<?php
#
# MC Dictionary Service
# ---------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class dict
{
	# Active context
	protected static $context;

	# Loaded captions
	protected static $captions;


	#
	# load()
	#

	function load($context)
	{
		if ( !isset(self::$captions[$context]) )
		{
			self::$context = $context;

			self::$captions[self::$context] = array();

			foreach ( (array) glob(mc_path . '/{system,modules/' . self::$context . '}/lang/' . config::get('locale') . '.php', GLOB_BRACE) as $file )
			{
				include $file;
			}
		}
	} # load()


	#
	# set()
	#

	public static function set($caption, $value)
	{
		self::$captions[self::$context][$caption] = $value;
	} # set()


	#
	# get()
	#

	public static function &get($context)
	{
		$captions =& self::$captions[$context];

		return $captions;
	} # get()


	#
	# dump()
	#

	public static function dump($context = null)
	{
		if ( isset($context) )
		{
			debug::dump(self::$captions[$context]);
		}
		else
		{
			debug::dump(self::$captions);
		}
	} # dump()
} # dict
?>