<?php
#
# MC Database Service
# -------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class db
{
	# db handler
	protected static $dbh;


	#
	# init()
	#

	public static function init()
	{
		self::connect();
	} # init()


	#
	# connect()
	#

	public static function connect()
	{
		$db_host = config::get('db_host');
		$db_name = config::get('db_name');
		$db_user = config::get('db_user');
		$db_pass = config::get('db_pass');

		try
		{
			self::$dbh =& new PDO(
				'pgsql:host=' . $db_host . ';dbname=' . $db_name,
				$db_user,
				$db_pass
				);

			self::$dbh->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('dbs'));
		}
		catch ( PDOException $e )
		{
			$captions =& new captions;

			$message = $captions->get(
					'err_db_connect'
					);
			throw new exception($message);
		}
	} # connect()


	#
	# escape()
	#

	public static function escape($var)
	{
		return self::$dbh->quote($var);
	} # escape()


	#
	# start()
	#

	public static function start()
	{
		return self::$dbh->beginTransaction();
	} # start()


	#
	# commit()
	#

	public static function commit()
	{
		return self::$dbh->commit();
	} # commit()


	#
	# rollback()
	#

	public static function rollback()
	{
		return self::$dbh->rollBack();
	} # rollback()


	#
	# prepare()
	#

	public static function prepare($sql)
	{
		return self::$dbh->prepare($sql);
	} # prepare()


	#
	# query()
	#

	public static function query($sql, $args = null)
	{
		$dbs = self::prepare($sql);

		$dbs->execute($args);

		return $dbs;
	} # query()


	#
	# get_results()
	#

	public static function get_results($sql, $args = null)
	{
		$dbs = self::query($sql, $args);

		return $dbs->get_results();
	} # get_results()


	#
	# get_row()
	#

	public static function get_row($sql, $args = null)
	{
		$dbs = self::query($sql, $args);

		return $dbs->get_row();
	} # get_row()


	#
	# get_col()
	#

	public static function get_col($sql, $args = null)
	{
		$dbs = self::query($sql, $args);

		return $dbs->get_col();
	} # get_col()


	#
	# get_var()
	#

	public static function get_var($sql, $args = null)
	{
		$dbs = self::query($sql, $args);

		return $dbs->get_var();
	} # get_var()


	#
	# dump()
	#

	public static function dump($sql = null, $args = null)
	{
		if ( isset($sql) )
		{
			debug::dump(self::get_results($sql, $args));
		}
		else
		{
			debug::dump(self::$dbh);
		}
	} # dump()
} # db

db::init();
?>