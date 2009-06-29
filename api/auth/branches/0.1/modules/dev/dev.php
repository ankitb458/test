<?php
class dev
{
	#
	# start()
	#

	public static function start()
	{
		event::attach('rewrite', array('dev', 'rewrite'));

		foreach ( array(
			'debug' => true,
			'mc_url' => 'http://localhost/members/v.0.2',
			'site_url' => 'http://localhost/members/v.0.2',
			) as $key => $new_value )
		{
			$value =& config::get($key);
			$value = $new_value;
		}

		foreach ( array(
			'site_name' => 'oldbackend.semiologic.com',
			'site_email' => 'support@semiologic.com',
			'site_signature' =>
				'--  ' . "\n"
				. 'The semiologic.com team  ' . "\n"
				. 'support@semiologic.com',
			'allow_registrations' => 1,
			) as $key => $new_value )
		{
			$value =& options::get($key);
			$value = $new_value;
		}
	} # start()


	#
	# rewrite()
	#

	public static function rewrite($args)
	{
		if ( !isset($args['cmd']) )
		{
			$args['cmd'] = 'login';
		}
	} # rewrite()


	#
	# output()
	#

	public static function output(&$args)
	{
		#$user = new user(1);

		#echo $user->user_key;

		#session::dump('active_user');

		#session::login(new user(1));
		die;
	} # output()


	#
	# test()
	#

	public static function test(&$sender)
	{
		$sender->test = true;
	} # test()
} # dev





/*

#
# data
#

class data
{
} # data



#
# user
#

class user extends data
{
} # user



#
# user_addon
#

class user_addon
{
} # user_addon



#
# request class
#

class request
{
	private static $addons = array('user_editor' => array('user_editor_addon'));

	protected $procedure = array();


	# __construct()

	final function __construct(&$args)
	{
		$this->args =& $args;

		$class = get_class($this);
		$classes[] = $class;

		while ( $class = get_parent_class($class) )
		{
			$classes[] = $class;
		}

		foreach ( $this->procedure as $step )
		{
			event::reset(get_class($this) . '_' . $step);

			event::attach(get_class($this) . '_' . $step, array(&$this, $step));
		}

		foreach ( $classes as $class )
		{
			foreach ( @ (array) self::$addons[$class] as $addon )
			{
				$addon =& new $addon($this);

				foreach ( $this->procedure as $step )
				{
					event::attach(get_class($this) . '_' . $step, array(&$addon, $step));
				}
			}
		}

		foreach ( $this->procedure as $step )
		{
			new event(get_class($this) . '_' . $step, $this);
		}

		die;
	} # __construct()
} # request


#
# form
#

class form extends request
{
	protected $procedure = array('test1', 'test2');
} # form



#
# user_editor
#

class user_editor extends form
{
	# test1()

	public function test1()
	{
		debug::dump(__METHOD__);
	} # test1()

	# test2()

	public function test2()
	{
		debug::dump(__METHOD__);
	} # test2()
} # user_editor



#
# user_editor_ext
#

class user_editor_ext extends user_editor
{
	# test2()

	public function test2()
	{
		debug::dump(__METHOD__);
	} # test2()
} # user_editor_ext



#
# request_addon
#

class request_addon
{
	# __construct()

	function __construct(&$request)
	{
		$this->request =& $request;
	} # __construct()
} # request_addon



#
# form_addon
#

class form_addon extends request_addon
{
} # form_addon



#
# user_editor_addon
#

class user_editor_addon extends form_addon
{
	# test1()

	public function test1()
	{
		debug::dump(__METHOD__);
	} # test1()

	# test2()

	public function test2()
	{
		debug::dump(__METHOD__);
	} # test2()
} # user_editor_addon



# exec request
new user_editor_ext($args = array());


*/
?>