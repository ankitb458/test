<?php
#
# MC Login Form
# -------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class login extends request
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;
	} # init()


	public $template = 'login';


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		if ( !active_user::is_guest() )
		{
			if ( isset($args['redirect']) )
			{
				$args['redirect'] = parse_url($args['redirect']);
				$localhost = parse_url(mc_url, PHP_URL_HOST);

				if ( $args['redirect']['host'] != $localhost )
				{
					args::parse($args['redirect']['query']);

					$args['redirect']['query']['user_key'] = active_user::get('user_key');
				}

				$args['redirect'] = url::glue($args['redirect']);

				permalink::redirect($args['redirect']);
			}
			else
			{
				permalink::redirect(array('cmd' => 'profile'));
			}
		}

		if ( !isset($args['redirect']) )
		{
			if ( isset($_SERVER['HTTP_REFERER']) )
			{
				$args['redirect'] = $_SERVER['HTTP_REFERER'];
			}
			else
			{
				$args['redirect'] = permalink::get(array('cmd' => 'profile'));
			}
		}

		parent::__construct($args);
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		$this->data =& new data;


		$field = $this->data->fields['user_email'] =& new textfield($this->data->user_email, '');
		$field->required = true;
		$field->type = 'email';
		$field->id = 'user_email';
		$field->name = 'user_email';
		$field->label = self::$captions->get('user_email');
		$field->bind($this->args['user_email']);


		$field = $this->data->fields['user_pass'] =& new textfield($this->data->user_pass, '');
		$field->type = 'password';
		$field->required = true;
		$field->id = 'user_pass';
		$field->name = 'user_pass';
		$field->label = self::$captions->get('user_pass');
		$field->bind($this->args['user_pass']);
	} # map()


	#
	# wire()
	#

	public function wire()
	{
		css::load('/system/css/system.css');
		css::load('/system/css/login.css');
	} # wire()


	#
	# title()
	#

	public function title()
	{
		echo self::$captions->get('login');
	} # title()


	#
	# html()
	#

	public function html(&$args)
	{
		echo '<form method="post"'
				. ' action="' . ( iis ? ( mc_url . '/index.php' ) : '' ) . '"'
				. '>'
			. '<input type="hidden" name="cmd" value="' . str::attr($this->cmd) . '" />'
			. '<input type="hidden" name="redirect" value="' . str::attr($this->redirect) . '" />'
			;

		echo '<h2>';
		$this->title();
		echo '</h2>';


		echo '<div class="fieldset">'
			. '<div class="text">' . $this->data->fields['user_email'] . '</div>'
			. '<div class="text">' . $this->data->fields['user_pass'] . '</div>'
			. '</div>';


		new event(__CLASS__ . '_html', $this);


		echo '<div class="fieldset">'
			. '<div class="button">'
			. '<input type="submit"'
				. ' value="' . str::attr(self::$captions->get('login')) . '"'
				. ' />'
			. '</div>'
			. '</div>';


		echo '<div style="text-align: center;">';

		if ( options::get('allow_registrations') )
		{
			echo '<a href="'
					. str::attr(
						permalink::get(
							array(
								'cmd' => 'register',
								'redirect' => $this->redirect,
								)
							)
						)
					. '">'
				. self::$captions->get('register')
				. '</a>'
				. ' &bull; ';
		}

		echo '<a href="'
			. str::attr(
				permalink::get(
					array(
						'cmd' => 'remind',
						'redirect' => $this->redirect,
						)
					)
				)
			. '">'
			. self::$captions->get('lost_password')
			. '</a>';

		echo '</div>';

		echo '</form>';
	} # html()


	#
	# exec()
	#

	public function exec()
	{
		$this->data->sanitize();
		$this->data->validate();

		if ( !error::exists() )
		{
			if ( active_user::login(
					new user("
						SELECT	*
						FROM	users
						WHERE	user_email = :user_email
						AND		user_pass = md5(:user_pass)
						",
						array(
							'user_email' => $this->data->user_email,
							'user_pass' => $this->data->user_pass
							)
						)
					)
				)
			{
				$message = self::$captions->get('welcome', array('user_name' => active_user::get('user_name')));
				new notice($message);

				$args['redirect'] = parse_url($this->redirect);
				$localhost = parse_url(mc_url, PHP_URL_HOST);

				if ( $args['redirect']['host'] != $localhost )
				{
					args::parse($args['redirect']['query']);

					$args['redirect']['query']['user_key'] = active_user::get('user_key');
				}

				$args['redirect'] = url::glue($args['redirect']);

				debug::dump($args['redirect']);
				permalink::redirect($args['redirect']);
			}
			else
			{
				$message = self::$captions->get('login_failed');
				new error($message);
			}
		}
	} # exec()
} # login

login::init();
?>