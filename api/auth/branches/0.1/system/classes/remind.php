<?php
#
# MC Remind Form
# --------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class remind extends request
{
	protected static $captions;


	#
	# init()
	#

	public static function init()
	{
		self::$captions =& new captions;

		event::attach('user_send_key', array(__CLASS__, 'send_key'));
	} # init()


	#
	# __construct()
	#

	public function __construct(&$args)
	{
		if ( !active_user::is_guest() )
		{
			permalink::redirect(array('cmd' => 'profile'));
		}

		$this->template = 'login';

		parent::__construct($args);
	} # __construct()


	#
	# map()
	#

	public function map()
	{
		$this->data =& new data;

		$field = $this->data->fields['user_email'] =& new textfield($this->data->user_email, '');
		$field->type = 'email';
		$field->required = true;
		$field->id = 'user_email';
		$field->name = 'user_email';
		$field->label = self::$captions->get('user_email');
		$field->bind($this->args['user_email']);
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
		echo self::$captions->get('remind');
	} # title()


	#
	# html()
	#

	public function html()
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
			. '</div>';


		new event(__CLASS__ . '_html', $this);


		echo '<div class="fieldset">'
			. '<div class="button">'
			. '<input type="submit"'
				. ' value="' . str::attr(self::$captions->get('send_details')) . '"'
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
						'cmd' => 'login',
						'redirect' => $this->redirect,
						)
					)
				)
			. '">'
			. self::$captions->get('login')
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
			$this->data = new user("
					SELECT	*
					FROM	users
					WHERE	user_email = :user_email
					",
					array(
						'user_email' => $this->data->user_email,
						)
					);

			if ( isset($this->data->user_id) )
			{
				$this->data->send_key();

				$message = self::$captions->get('details_sent');
				new notice($message);

				permalink::redirect(array('cmd' => 'login', 'redirect' => $this->redirect));
			}
			else
			{
				$message = self::$captions->get('user_not_found');
				new error($message);
			}
		}
	} # exec()


	#
	# send_key()
	#

	public static function send_key(&$user)
	{
		$email =& new email;
		$email->to = $user->user_email;

		$email->title = self::$captions->get(
			'send_user_key_title',
			array(
				'site_name' => options::get('site_name')
				)
			);

		$email->message = self::$captions->get(
			'send_user_key_message',
			array(
				'site_name' => options::get('site_name'),
				'site_url' => site_url . '/',
				'site_signature' => options::get('site_signature'),
				'user_name' => $user->user_name,
				'user_key' => $user->user_key,
				)
			);

		$email->send();
	} # send_key()
} # remind

remind::init();
?>