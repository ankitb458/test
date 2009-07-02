<?php
/*
Plugin Name: Newsletter Manager
Plugin URI: http://www.semiologic.com/software/marketing/newsletter-manager/
Description: Lets you readily add a newsletter subscription form to your WordPress installation.
Author: Denis de Bernardy
Version: 3.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: newsletter_manager
*/

/*
Terms of use
------------
http://www.semiologic.com/legal/license/
**/


class sem_newsletter_manager
{
	#
	# init()
	#

	function init()
	{
		add_action('init', array('sem_newsletter_manager', 'subscribe_user'));
		add_action('the_content', array('sem_newsletter_manager', 'display_inline'));
		add_action('widgets_init', array('sem_newsletter_manager', 'widgetize'));
		add_action('_admin_menu', array('sem_newsletter_manager', 'admin'));
	} # init()


	#
	# get_options()
	#

	function get_options()
	{
		$options = get_option('sem_newsletter_manager_params');

		if ( @ $options['version'] < 3 )
		{
			sem_newsletter_manager::admin();
			$options = sem_newsletter_manager_admin::upgrade_options();
		}

		return $options;
	} # get_options()


	#
	# admin()
	#

	function admin()
	{
		require_once dirname(__FILE__) . '/sem-newsletter-manager-admin.php';
	} # admin()


	#
	# widgetize()
	#

	function widgetize()
	{
		register_sidebar_widget('Newsletter', array('sem_newsletter_manager', 'display_widget'));
		register_widget_control('Newsletter', array('sem_newsletter_manager_admin', 'widget_control'), 700, 430);
	} # widgetize()


	#
	# check_email()
	#

	function check_email($email)
	{
		return preg_match("/
			^
			[0-9a-zA-Z_.-]+
			@
			[0-9a-zA-Z_.-]+
			$
			/ix",
			$email
			);
	} # check_email()


	#
	# display_inline()
	#

	function display_inline($content)
	{
		$content = preg_replace(
			"/
				<p(?:\s+[^>]*)?>
				\s*
				<!--\s*newsletter\s*-->
				\s*
				(?:<\/p>)?
			/isx", "<!--newsletter-->", $content);

		$content = preg_replace_callback(
			"/
				<!--\s*newsletter\s*-->
			/ix", array('sem_newsletter_manager', 'get_form'), $content);

		return $content;
	} # display_inline()


	#
	# display_widget()
	#

	function display_widget($args = null)
	{
		$options = sem_newsletter_manager::get_options();

		# default args

		$defaults = array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>',
			'title' => $options['captions']['widget_title'],
			'teaser' => $options['captions']['widget_teaser'],
			'thank_you' => $options['captions']['thank_you'],
			);

		$args = array_merge($defaults, (array) $args);

		$args['before_widget'] = str_replace('class="', 'class="the_subscribe_form ', $args['before_widget']);

		echo $args['before_widget'];

		echo $args['title'] ? ( $args['before_title'] . $args['title'] . $args['after_title'] ) : '';

		if ( !isset($_GET['subscribed']) )
		{
			echo $args['teaser'] ? ( '<div class="teaser">' . $args['teaser'] . '</div>' ) : '';
		}

		echo sem_newsletter_manager::get_form();

		echo $args['after_widget'];
	} # display_widget()


	#
	# get_form()
	#

	function get_form($input = null)
	{
		$options = sem_newsletter_manager::get_options();

		if ( isset($_GET['subscribed']) )
		{
			return $options['captions']['thank_you'];
		}
		elseif ( !sem_newsletter_manager::check_email($options['email']) )
		{
			return '<div style="border: solid 2px firebrick; padding: 5px; background-color: AntiqueWhite; color: firebrick; font-weight: bold;">'
				. __('Your mailing list is not configured.')
				. '</div>';
		}

		switch ( $options['syntax'] )
		{
		case 'aweber':
			return sem_newsletter_manager::aweber_form();
			break;

		default:
			return sem_newsletter_manager::default_form();
			break;
		}
	} # get_form()


	#
	# default_form()
	#

	function default_form()
	{
		$id = ++$GLOBALS['newsletter_forms'];

		$options = sem_newsletter_manager::get_options();
		$captions =& $options['captions'];

		$o = '<form method="post" action="'
					. $_SERVER['REQUEST_URI']
				. '"'
				. '>'
			. '<input type="hidden" name="method" value="subscribe2newsletter" />'
			. '<div class="newsletter_fields">'
			. '<input type="text"'
				. ' id="name_nm' . $id . '" name="name"'
				. ' value="' . htmlspecialchars($captions['your_name']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\';"'
				. ' /><br />'
			. '<input type="text"'
				. ' id="email_nm' . $id . '" name="email"'
				. ' value="' . htmlspecialchars($captions['your_email']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\';"'
				. ' />'
			. '</div>'
			. '<div class="newsletter_submit">'
			. '<input type="submit"'
				. ' value="' . htmlspecialchars($captions['sign_up']) . '"'
				. ' onclick="if ( !getElementById(\'email_nm' . $id . '\').value.match(/\S+@\S+/) ) { getElementById(\'email_nm' . $id . '\').focus(); return false; }"'
				. ' /></div>'
			. '</form>';

		return $o;
	} # default_form()



	#
	# aweber_form()
	#

	function aweber_form()
	{
		$id = ++$GLOBALS['newsletter_forms'];

		$options = sem_newsletter_manager::get_options();
		$captions =& $options['captions'];

		$unit = $options['email'];

		if ( strpos($unit, '@aweber.com') !== false )
		{
			$unit = preg_replace("/@.+/", "", $unit);
		}

		$o = $options['teaser']
			. '<form method="post" action="http://www.aweber.com/scripts/addlead.pl">'
			. '<input type="hidden" name="unit" value="' . $unit . '" />'
			. '<input type="hidden" name="meta_message" value="1" />'
			. '<input type="hidden" name="meta_required" value="from" />'
			. '<input type="hidden" name="redirect" value="'
				. ( $options['redirect']
					? htmlspecialchars($options['redirect'])
					: ( 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://'
						. $_SERVER['HTTP_HOST']
						. $_SERVER['REQUEST_URI']
						)
					)
				 . '" />'
			. '<div class="newsletter_fields">'
			. '<input type="text"'
				. ' id="name_nm' . $id . '" name="name"'
				. ' value="' . htmlspecialchars($captions['your_name']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_name'])) . '\';"'
				. ' /><br />'
			. '<input type="text"'
				. ' id="email_nm' . $id . '" name="from"'
				. ' value="' . htmlspecialchars($captions['your_email']) . '"'
				. ' onfocus="if ( this.value == \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . addslashes(htmlspecialchars($captions['your_email'])) . '\';"'
				. ' />'
			. '</div>'
			. '<div class="newsletter_submit">'
			. '<input type="submit"'
				. ' value="' . __('Sign Up') . '"'
				. ' name="submit"'
				. ' onclick="if ( !getElementById(\'email_nm' . $id . '\').value.match(/\S+@\S+/) ) { getElementById(\'email_nm' . $id . '\').focus(); return false; }"'
				. ' /></div>'
			. '</form>';

		return $o;
	} # aweber_form()


	#
	# subscribe_user()
	#

	function subscribe_user()
	{
		$options = sem_newsletter_manager::get_options();

		if ( @ $_POST['method'] == 'subscribe2newsletter' )
		{
			if ( sem_newsletter_manager::check_email($options['email'])
				&& sem_newsletter_manager::check_email($_POST['email'])
				)
			{
				$to = $options['email'];

				if ( $options['syntax'] == 'list-subscribe' )
				{
					$to = str_replace('@', '-subscribe@', $to);
				}

				$name = trim($_POST['name']);
				$email = $_POST['email'];

				$name = preg_replace("/[^\w ]+/", " ", $name);

				if ( !$name
					|| $name == $options['captions']['your_name']
					)
				{
					$name = $email;
				}

				$from = $name ? ( '"' . $name . '" <' . $email . ">" ) : $email;

				$headers = "From: $from";

				$title = 'subscribe';
				$message = 'subscribe';

				wp_mail(
					$to,
					$title,
					$message,
					$headers
					);

				if ( $options['redirect'] != '' )
				{
					wp_redirect($options['redirect']);
				}
				else
				{
					wp_redirect(
						$_SERVER['REQUEST_URI']
						. ( ( strpos($_SERVER['REQUEST_URI'], '?') !== false )
							? '&'
							: '?'
							) . 'subscribed'
						);
				}
				die;
			}
		}
		elseif ( $redirect = $options['redirect'] )
		{
			$req_uri = $_SERVER['REQUEST_URI'];

			foreach ( array('req_uri', 'redirect') as $var )
			{
				$$var = str_replace('/index.php', '/', $$var);
				$$var = rtrim($$var, '/');

				if ( !strpos($$var, '://') )
				{
					$$var = 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://'
						. $_SERVER['HTTP_HOST']
						. $$var;
				}
			}

			if ( $req_uri == $redirect )
			{
				# toggle $_GET['subscribed']
				$_GET['subscribed'] = true;
			}
		}
	} # subscribe_user()
} # sem_newsletter_manager

sem_newsletter_manager::init();



########################
#
# backward compatibility
#

function the_subscribe_form($args = null)
{
	echo newsletter_manager::get_form();
} # end the_subscribe_form()

function sem_newsletter_form()
{
	the_subscribe_form();
} // end sem_newsletter_form()
?>