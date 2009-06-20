<?php
#
# MC Options Service
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class options
{
	# option data
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
		$dbs = db::query("
			SELECT	*
			FROM	options
			");

		$dbs->bind('option_key', $key);
		$dbs->bind('option_value', $value);

		while ( $dbs->get_row() )
		{
			@ $unserialized = unserialize($value);

			if ( $unserialized !== false )
			{
				$value = $unserialized;
			}

			self::$data[$key] = $value;
			self::$start[$key] = $value;

			$key = null;
			$value = null;
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
			elseif ( is_bool(self::$data[$key]) )
			{
				self::$data[$key] = (int) self::$data[$key];
				self::$data[$key] = (string) self::$data[$key];
			}
			elseif ( is_numeric(self::$data[$key]) )
			{
				self::$data[$key] = (string) self::$data[$key];
			}
		}
	} # clean()


	#
	# save()
	#

	public static function save()
	{
		db::start();


		$dbs = db::prepare("SELECT save_option(:option_key, :option_value);");

		foreach ( self::$data as $key => $value )
		{
			if ( @ self::$start[$key] !== self::$data[$key] )
			{
				if ( !( is_numeric($value) || is_string($value) ) )
				{
					$value = serialize($value);
				}

				#debug::dump('saving...', $key);

				$dbs->bind_var('option_key', $key);
				$dbs->bind_var('option_value', $value);

				$dbs->exec();
			}

			unset(self::$start[$key]);
		}


		$dbs = db::prepare("SELECT delete_option(:option_key);");

		foreach ( array_keys((array) self::$start) as $key )
		{
			#debug::dump('deleting...', $key);

			$dbs->bind_var('option_key', $key);

			$dbs->exec();
		}


		db::commit();
	} # save()


	#
	# on_exit()
	#

	public function on_exit()
	{
		if ( active_user::is_admin() )
		{
			self::clean();

			if ( self::$start !== self::$data )
			{
				self::save();
			}
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
} # options

options::init();
?>