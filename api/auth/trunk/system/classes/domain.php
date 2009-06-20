<?php
#
# MC Domain Object
# -----------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class domain extends data
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
	# __construct()
	#

	public function __construct($sql = null, $args = null)
	{
		if ( is_numeric($sql) )
		{
			$row = db::get_row("
				SELECT	*
				FROM	domains
				WHERE	domain_id = :domain_id
				",
				array(
					'domain_id' => intval($sql)
					)
				);
		}
		elseif ( is_string($sql) )
		{
			$row = db::get_row($sql, $args);
		}
		else
		{
			$row = $sql;
		}

		parent::__construct($row);
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		$field = $this->fields['domain_id'] =& new field($this->domain_id);
		$field->type = 'id';

		$field = $this->fields['domain_name'] =& new textfield($this->domain_name, '');
		$field->required = true;

		$field = $this->fields['domain_desc'] =& new textarea($this->domain_desc);

		$field = $this->fields['domain_urls'] =& new textarea($this->domain_urls);


		new event(__CLASS__ . '_map', $this);
	} # map()


	#
	# cache_restricted_urls()
	#

	public static function cache_restricted_urls()
	{
		$restricted_urls =& config::get('restricted_urls');

		$restricted_urls = array();

		$mc_url = self::get_url_handle(mc_url . '/');

		$dbs = db::query("
			SELECT	DISTINCT url_handle
			FROM	domain2url
			");

		$dbs->bind('url_handle', $url_handle);

		while ( $dbs->get_row() )
		{
			if ( strpos($mc_url, $url_handle) !== 0 )
			{
				$restricted_urls[] = $url_handle;
			}
			else
			{
				$message = self::$captions->get('invalid_url_handle', array('url_handle' => $url_handle));
				new notice($message);
			}
		}
	} # cache_restricted_urls()


	#
	# get_url_handle()
	#

	public static function get_url_handle($url)
	{
		$url = trim($url);
		$url = strtolower($url);
		$url = preg_replace("/^(.+:\/\/)?(.*@)?(www\.)?/i", "", $url);
		$url = preg_replace("/^([^\/]+):[^\/]*/", "$1", $url);
		$url = preg_replace("/(\?.*)?(#.*)?$/", "", $url);
		$url = preg_replace("/\/(index|home)\.[a-z]{2,4}$/", "/", $url);
		$url = rtrim($url, '/');

		return $url;
	} # get_url_handle()


	#
	# register_caps()
	#

	public static function register_caps(&$caps)
	{
		args::merge(
			$caps,
			array(
				'manage_access' => self::$captions->get('manage_access'),
				)
			);
	} # register_caps()


	#
	# restrict()
	#

	public static function restrict()
	{
		$restricted_urls =& config::get('restricted_urls');

		$restricted_urls = (array) $restricted_urls;

		$url = request::self();

		$url = self::get_url_handle($url);

		$restricted = false;

		foreach ( $restricted_urls as $restricted_url )
		{
			if ( strpos($url, $restricted_url) === 0 )
			{
				if ( !active_user::can_access($restricted_url) )
				{
					$restricted[] = $restricted_url;
				}
			}
		}
#debug::dump($restricted);
		if ( $restricted )
		{
			$url_handle = '';

			foreach ( $restricted as $restricted_url )
			{
				if ( strlen($restricted_url) > strlen($url_handle) )
				{
					$url_handle = $restricted_url;
				}
			}

			$args = array();

			$args['cmd'] = 'restrict';
			$args['url'] = $url_handle;

			if ( ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) && isset($_POST['output']) )
			{
				$args['output'] = $_POST['output'];
			}
			elseif ( isset($_GET['output']) )
			{
				$args['output'] = $_GET['output'];
			}

			new status_403($args);
		}
	} # restrict()
} # domain

domain::init();
?>