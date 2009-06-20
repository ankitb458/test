<?php
#
# MC Active User Service
# ----------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class active_user
{
	protected static $user;
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;

		self::$user =& session::get('active_user');
	} # init()


	#
	# autologin()
	#

	public function autologin()
	{
		if ( ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) )
		{
			$is_post = true;
			$args =& $_POST;
		}
		else
		{
			$is_post = false;
			$args =& $_GET;
		}

		if ( isset($args['user_key']) )
		{
			if ( !self::login(
					new user("
						SELECT	*
						FROM	users
						WHERE	user_key = :user_key
						",
						array(
							'user_key' => $args['user_key']
							)
						)
					)
				)
			{
				$message = self::$captions->get('login_failed');
				new error($message);
			}

			unset($args['user_key']);
			unset($_REQUEST['user_key']);

			if ( !$is_post )
			{
				$url = request::self();
				$url = parse_url($url);
				args::parse($url['query']);
				unset($url['query']['user_key']);

				$url = url::glue($url);
				
				$url = preg_replace("|^.+?://[^/]+|", '', $url);
				
				$_SERVER['REQUEST_URI'] = $url;
				#debug::dump($_SERVER['REQUEST_URI']); die;
				#permalink::redirect($url);
			}
		}
	} # autologin()


	#
	# login()
	#

	public static function login($user)
	{
		if ( is_a($user, 'user') && isset($user->user_id) )
		{
			self::$user = $user;
			$_SESSION['user_id'] =  self::$user->user_id;

			new event('active_user');

			return true;
		}
		else
		{
			self::$user = null;
			$_SESSION['user_id'] =  null;

			new event('login_failed');

			return false;
		}
	} # login()


	#
	# logout()
	#

	public static function logout(&$args)
	{
		if ( request::is_post() )
		{
			self::$user = null;
			$_SESSION['user_id'] =  null;

			ob_clean();

			die;
		}
		else
		{
			if ( isset(self::$user) )
			{
				$message = self::$captions->get('goodbye', array('user_name' => self::$user->user_name));
				new notice($message);
			}

			self::$user = null;
			$_SESSION['user_id'] =  null;

			if ( !isset($args['redirect']) )
			{
				if ( isset($_SERVER['HTTP_REFERER']) )
				{
					$args['redirect'] = $_SERVER['HTTP_REFERER'];
				}
				else
				{
					$args['redirect'] = $args;
					unset($args['redirect']['cmd']);
				}
			}

			permalink::redirect($args['redirect']);
		}
	} # logout()


	#
	# is_guest()
	#

	public static function is_guest()
	{
		return !isset(self::$user);
	} # is_guest()


	#
	# is_admin()
	#

	public static function is_admin()
	{
		return isset(self::$user) && self::$user->is_admin;
	} # is_admin()


	#
	# can()
	#

	public static function can()
	{
		if ( !isset(self::$user) )
		{
			return false;
		}
		else
		{
			foreach ( func_get_args() as $cap )
			{
				if ( self::$user->can($cap) )
				{
					return true;
				}
			}

			return false;
		}
	} # can()


	#
	# can_access()
	#

	public static function can_access($node)
	{
		if ( !isset(self::$user) )
		{
			if ( is_numeric($node) )
			{
				return db::get_var(
					"SELECT user_can_access(:user_id, :node);",
					array(
						'user_id' => null,
						'node' => intval($node),
						)
					);
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( self::$user->is_admin )
			{
				return true;
			}
			elseif ( self::$user->can('manage_access') )
			{
				return true;
			}
			else
			{
				if ( is_numeric($node) )
				{
					return db::get_var(
						"SELECT user_can_access(:user_id, :node);",
						array(
							'user_id' => intval(self::$user->user_id),
							'node' => intval($node),
							)
						);
				}
				else
				{
					return db::get_var(
						"SELECT user_can_access_url(:user_id, :node);",
						array(
							'user_id' => intval(self::$user->user_id),
							'node' => $node,
							)
						);
				}
			}
		}
	} # can_access()


	#
	# refresh()
	#

	public static function refresh()
	{
		if ( isset(self::$user) )
		{
			self::$user = new user(self::$user->user_id);
		}
	} # refresh()


	#
	# get()
	#

	public static function get($var)
	{
		if ( $var == 'user_name')
		{
			return isset(self::$user) ? self::$user->{$var} : self::$captions->get('guest');
		}
		else
		{
			return isset(self::$user) ? self::$user->{$var} : null;
		}
	} # get()


	#
	# dump()
	#

	function dump()
	{
		debug::dump(self::$user);
	} # dump()
} # active_user

# start service
active_user::init();
?>