<?php
#
# MC Template Object
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class template
{
	# template data
	protected $template;

	protected static $panels = array(
			'before_wrapper',
			'after_wrapper',
			'header',
			'footer',
			'before_content',
			'after_content',
			'top_sidebar',
			'left_sidebar',
			'right_sidebar',
			'ext_sidebar',
			);

	protected static $wrappers = array(
			'main',
			'content',
			'wrapper',
			'top_wrapper',
			'main_wrapper',
			'ext_wrapper',
			'body',
			);

	protected static $captions;


	#
	# init()
	#

	public function init()
	{
		self::$captions =& new captions;
	} # init


	#
	# __construct()
	#

	public function __construct(&$request)
	{
		$this->request =& $request;
		$this->args =& $request->args;

		if ( isset($this->request->template) )
		{
			$this->template = $this->request->template;
		}
		else
		{
			$this->template = 'default';
		}

		$templates = array(
			'default' => array(
				'name' => self::$captions->get('system_template'),
				'layout' => 'mr',
				'before_content' => array(
					array('messages'),
					),
				'right_sidebar' => array(
					array('active_user_widget'),
					array('users_widget'),
					array('profiles_widget'),
					array('domains_widget'),
					array('docs_widget'),
					),
				),
			'login' => array(
				'name' => self::$captions->get('login_form'),
				'layout' => 'm',
				'before_content' => array(
					array('messages')
					),
				),
			);

		if ( !isset($templates[$this->template]) )
		{
			$this->template = 'default';
		}

		foreach ( array_keys($templates[$this->template]) as $var )
		{
			$this->{$var} =& $templates[$this->template][$var];
		}
	} # __construct()


	#
	# output()
	#

	public function output()
	{
		$this->{$this->args['output']}();
	} # output()


	#
	# wire()
	#

	public function wire()
	{
		# load layout css
		css::load('/system/css/layout.css');

		# create panels
		foreach ( array_merge(self::$panels, self::$wrappers) as $panel )
		{
			$widgets = isset($this->{$panel}) ? $this->{$panel} : array();

			$this->{$panel} =& new panel($panel);

			foreach ( $widgets as $widget )
			{
				$this->{$panel}->load($widget);
			}
		}

		# setup layout

		$this->body->id = 'body';

		$this->body->load($this->before_wrapper);
		$this->body->load($this->ext_wrapper);
		$this->body->load($this->after_wrapper);

		$this->ext_wrapper->load($this->wrapper);

		$this->wrapper->id = 'wrapper';
		$this->header->id = 'header';
		$this->main->id = 'main';
		$this->footer->id = 'footer';

		$this->wrapper->load($this->header);
		$this->wrapper->load($this->main);
		$this->wrapper->load($this->footer);

		$this->content->load($this->before_content);
		$this->content->load($this->request);
		$this->content->load($this->after_content);

		$this->ext_sidebar->class = 'sidebar';
		$this->top_sidebar->class = 'sidebar';
		$this->left_sidebar->class = 'sidebar';
		$this->right_sidebar->class = 'sidebar';
		$this->content->class = 'content';

		$this->content->pad = true;
		$this->ext_sidebar->pad = true;
		$this->top_sidebar->pad = true;
		$this->left_sidebar->pad = true;
		$this->right_sidebar->pad = true;

		switch ( $this->layout )
		{
		case 'm':
			$this->content->id = 'content';

			$this->main->load($this->content);
			break;

		case 'mr':

			$this->content->id = 'content';
			$this->right_sidebar->id = 'right_sidebar';

			$this->main->load($this->content);
			$this->main->load($this->right_sidebar);
			$this->main->spacer = true;
			break;

		case 'lm':
			$this->content->id = 'content';
			$this->left_sidebar->id = 'left_sidebar';

			$this->main->load($this->content);
			$this->main->load($this->left_sidebar);
			$this->main->spacer = true;
			break;

		case 'me':
		case 'em':
			$this->content->id = 'content';
			$this->ext_sidebar->id = 'ext_sidebar';
			$this->ext_wrapper->id = 'ext_wrapper';

			$this->main->load($this->content);

			$this->ext_wrapper->load($this->ext_sidebar);
			$this->ext_wrapper->spacer = true;
			break;

		case 'lmr':
			$this->content->id = 'content';
			$this->left_sidebar->id = 'left_sidebar';
			$this->right_sidebar->id = 'right_sidebar';
			$this->main_wrapper->id = 'main_wrapper';

			$this->main_wrapper->load($this->content);
			$this->main_wrapper->load($this->right_sidebar);

			$this->main->load($this->main_wrapper);
			$this->main->load($this->left_sidebar);
			$this->main->spacer = true;
			break;

		case 'mtlr':
		case 'tlrm':
			$this->top_sidebar->id = 'top_sidebar';

			$this->top_wrapper->load($this->top_sidebar);

		case 'mlr':
		case 'lrm':
			$this->content->id = 'content';
			$this->left_sidebar->id = 'left_sidebar';
			$this->right_sidebar->id = 'right_sidebar';
			$this->top_wrapper->id = 'top_wrapper';

			$this->top_wrapper->load($this->left_sidebar);
			$this->top_wrapper->load($this->right_sidebar);
			$this->top_wrapper->spacer = true;

			$this->main->load($this->content);
			$this->main->load($this->top_wrapper);
			$this->main->spacer = true;
			break;

		case 'lme':
		case 'elm':
			$this->content->id = 'content';
			$this->left_sidebar->id = 'left_sidebar';
			$this->ext_sidebar->id = 'ext_sidebar';
			$this->ext_wrapper->id = 'ext_wrapper';

			$this->main->load($this->content);
			$this->main->load($this->left_sidebar);
			$this->main->spacer = true;

			$this->ext_wrapper->load($this->ext_sidebar);
			$this->ext_wrapper->spacer = true;
			break;

		case 'mre':
		case 'emr':
			$this->content->id = 'content';
			$this->right_sidebar->id = 'right_sidebar';
			$this->ext_sidebar->id = 'ext_sidebar';
			$this->ext_wrapper->id = 'ext_wrapper';

			$this->main->load($this->content);
			$this->main->load($this->right_sidebar);
			$this->main->spacer = true;

			$this->ext_wrapper->load($this->ext_sidebar);
			$this->ext_wrapper->spacer = true;
			break;
		}

		# wire body
		$this->body->wire($this->args);
	} # wire()


	#
	# html()
	#

	public function html()
	{
		$this->wire();

		# send headers
		$this->request->html_headers();

		# send document
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
			. '<html>'
			. '<head>';

		echo '<title>';
		$this->request->title($this->args);
		echo '</title>';

		css::html($this->args);

		echo '</head>'
			. '<body class="'
				. $this->template
				. ' '
				. $this->layout
				. ' '
				. 'skin'
				. '">';

		#admin_menu::html($this->args);

		$this->body->html($this->args);

		echo '</body>'
			. '</html>';

		die;
	} # html()


	#
	# xml()
	#

	public function xml()
	{
		$this->request->xml_headers();

		echo '<?xml version="1.0" encoding="' . config::get('charset') . '" ?>';

		if ( error::exists() )
		{
			messages::xml();
		}
		else
		{
			$this->request->xml();
		}
		die;
	} # xml()
} # template

template::init();
?>