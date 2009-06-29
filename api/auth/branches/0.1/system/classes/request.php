<?php
#
# MC Request Service
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All right reserved
#

class request
{
	#
	# Request Service
	#

	protected static $self;
	protected static $is_post;
	protected static $addons = array();


	#
	# attach()
	#

	public static function attach($handler, $addon)
	{
		if ( @ !in_array($addon, (array) self::$addons[$handler]) )
		{
			self::$addons[$handler][] = $addon;
		}
	} # attach()


	#
	# detach()
	#

	public static function detach($handler, $addon)
	{
		$key = @ array_search($addon, (array) self::$addons[$handler]);

		if ( $key !== false )
		{
			unset(self::$addons[$handler][$key]);
		}
	} # detach()


	#
	# is_post()
	#

	public static function is_post()
	{
		if ( !isset(self::$is_post) )
		{
			self::$is_post = ( $_SERVER['REQUEST_METHOD'] !== 'GET' );
		}

		return self::$is_post;
	} # is_post()


	#
	# self()
	#

	public function self()
	{
		if ( !isset(self::$self) )
		{
			if ( !iis )
			{
				self::$self = ( ( @ $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http' ) . '://'
					. $_SERVER['HTTP_HOST']
					. $_SERVER['REQUEST_URI'];
			}
			else
			{
				self::$self = array();
				self::$self['sheme'] = ( $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http';
				self::$self['host'] = $_SERVER['HTTP_HOST'];
				self::$self['path'] = str_replace('/index.php', '/', $_SERVER['PHP_SELF']);
				self::$self['query'] = @ $_SERVER['QUERY_STRING'];
				self::$self = url::glue(self::$self);
			}
		}

		return self::$self;
	} # self()


	#
	# dump()
	#

	public static function dump($handler = null)
	{
		if ( isset($handler) )
		{
			debug::dump(self::$addons[$handler]);
		}
		else
		{
			debug::dump(self::$addons);
		}
	} # dump()




	#
	# Request Object
	#

	public $args;
	public $data;
	public $template;
	public $output;


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		$this->args =& $args;

		$defaults = array(
			'output' => 'html',
			);
		args::merge($this->args, $defaults);

		$this->cmd =& $this->args['cmd'];
		$this->redirect =& $this->args['redirect'];
		$this->output =& $this->args['output'];

		foreach ( @ (array) self::$addons[get_class($this)] as $addon )
		{
			foreach ( array('map', 'html') as $step )
			{
				event::attach(get_class($this) . '_' . $step, array($addon, $step));
			}
		}

		$this->map();

		new event(get_class($this) . '_map', $this);

		#debug::dump($this);
		#die;

		if ( self::is_post() )
		{
			$this->exec();
		}

		$template =& new template($this);
		$template->output($this->args);
	} # __construct()


	#
	# html_headers()
	#

	function html_headers()
	{
		$protocol = $_SERVER['SERVER_PROTOCOL'];

		if ( ( $protocol != 'HTTP/1.1' ) && ( $protocol != 'HTTP/1.0' ) )
		{
			$protocol = 'HTTP/1.0';
		}

		$status_header = "$protocol 200 OK";

		header($status_header);

		header('Content-Type: text/html; Charset: ' . config::get('charset'));

		new event(get_class($this) . '_html_headers', $this);
	} # html_headers()


	#
	# xml_headers()
	#

	function xml_headers()
	{
		$protocol = $_SERVER['SERVER_PROTOCOL'];

		if ( ( $protocol != 'HTTP/1.1' ) && ( $protocol != 'HTTP/1.0' ) )
		{
			$protocol = 'HTTP/1.0';
		}

		$status_header = "$protocol 200 OK";

		header($status_header);

		header('Content-Type: text/xml; Charset: ' . config::get('charset'));

		new event(get_class($this) . '_xml_headers', $this);
	} # xml_headers()
} # request
?>