<?php
#
# MC Config Service
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class config
{
	# config file
	protected static $file;

	# config data
	protected static $data;
	protected static $start;


	#
	# init()
	#

	public static function init()
	{
		self::load();

		event::attach('exit', array(__CLASS__, 'on_exit'));
	} # init()


	#
	# load()
	#

	public static function load()
	{
		self::$file = mc_path . '/config/index.php';

		$data =& self::$data;

		include_once self::$file;

		self::$data = unserialize(self::$data);

		if ( self::$data !== false )
		{
			self::$start = self::$data;
		}
		else
		{
			self::$data = array();
			self::$start = null;
		}
	} # load()


	#
	# get()
	#

	public static function &get($key)
	{
		$value =& self::$data[$key];

		return $value;
	} # get()


	#
	# clean()
	#

	public static function clean()
	{
		foreach ( array_keys(self::$data) as $key )
		{
			if ( !isset(self::$data[$key]) )
			{
				unset(self::$data[$key]);
			}
		}
	} # clean()


	#
	# save()
	#

	public static function save()
	{
		@ file_put_contents(self::$file,
			'<?php' . "\n"
				. ' $data = \'' . str_replace("'", "\\'", serialize(self::$data)) . '\';' . "\n"
				. '?>'
			);
	} # save()


	#
	# on_exit()
	#

	public function on_exit()
	{
		self::clean();

		if ( self::$start !== self::$data )
		{
			self::save();
		}
	} # on_exit()


	#
	# dump()
	#

	public static function dump($key = null)
	{
		if ( isset($key) )
		{
			debug::dump(self::$data[$key]);
		}
		else
		{
			debug::dump(self::$data);
		}
	} # dump()
} # config

config::init();
?>