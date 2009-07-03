<?php
/*
Plugin Name: Newsletter Manager
Plugin URI: http://www.semiologic.com/software/newsletter-manager/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/newsletter-manager/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Lets you manage a newsletter with WordPress. Requires ezmlm or any other solution with a similar subscription syntax. If your theme does not support widgets, add a call to the_subscribe_form() in your template.
Author: Denis de Bernardy
Version: 1.10
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------
http://www.semiologic.com/legal/license/
**/


if ( !defined('pattern_email') )
{
	define('pattern_email', # good enough for our purpose
		"/
			(
			^\s*
			(mailto:)?
			([a-zA-Z0-9_-]+\.)*
			[a-zA-Z0-9_-]+
			@
			[a-zA-Z0-9_-]+
			(\.[a-zA-Z0-9_-]+)+
			\s*$
			)
		/x"
		);
}

#
# the_subscribe_form()
#

function the_subscribe_form($args = null)
{
	$options = get_option('sem_newsletter_params');

	# drop call if no newsletter is defined

	if ( !$options
		|| !isset($options['email'])
		|| !preg_match(pattern_email, $options['email'])
		)
	{
		return;
	}

	# default args

	if ( !isset($args['before_widget']) )
	{
		$args['before_widget'] = '';
	}
	if ( !isset($args['after_widget']) )
	{
		$args['after_widget'] = '';
	}
	if ( !isset($args['before_title']) )
	{
		$args['before_title'] = '<h2>';
	}
	if ( !isset($args['after_title']) )
	{
		$args['after_title'] = '</h2>';
	}

	if ( !isset($options['title']) || ( $options['title'] == "" ) )
	{
		$options['title'] = __('Newsletter');
	}
	if ( !isset($options['teaser']) || ( $options['teaser'] == "" ) )
	{
		$options['teaser'] = __('<p>Sign up to receive an occasional newsletter.</p>');
	}
	if ( !isset($options['thanks']) || ( $options['thanks'] == "" ) )
	{
		$options['thanks'] = __('<p>Thank you for subscribing!</p>');
	}

	echo $args['before_widget'];
?>
<div id="subscribe_form">
<?php echo $args['before_title'] . $options['title'] . $args['after_title']; ?>
<?php
if ( !isset($GLOBALS['thanks4subscribing']) ) :
?>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
<input type="hidden" name="action" value="subscribe2newsletter" />
<p><input type="text"
	id="subscriber_email" name="subscriber_email"
	value="<?php echo __('Your email'); ?>"
	onfocus="if ( this.value == '<?php echo __('Your email'); ?>' ) this.value = '';"
	onblur="if ( this.value == '' ) this.value = '<?php echo __('Your email'); ?>';"
	/><input type="submit"
		value="<?php echo __('Sign up'); ?>"
		onclick="if ( !getElementById('subscriber_email').value.match(/\S+@\S+/) ) { getElementById('subscriber_email').focus(); return false; }"
		/></p>
<?php echo $options['teaser']; ?>
</form>
<?php
else :
	echo $options['thanks'];
endif;
?>
</div>
<?php
	echo $args['after_widget'];
} # end the_subscribe_form()


#
# subscribe2newsletter()
#

function subscribe2newsletter()
{
	if ( isset($_POST['action'])
		&& $_POST['action'] == 'subscribe2newsletter'
		)
	{
		$options = get_option('sem_newsletter_params');

		$pattern_email = "/^[0-9a-zA-Z_.-]+@[0-9a-zA-Z_.-]+$/";

		if ( !( isset($options['email'])
				&& preg_match($pattern_email, $options['email'])
				&& isset($_POST['subscriber_email'])
				&& preg_match($pattern_email, $_POST['subscriber_email'])
				)
			)
		{
			return;
		}

		$mail_to = str_replace('@', '-subscribe@', $options['email']);
		$mail_from = trim($_POST['subscriber_email']);

		$headers = "From: $mail_from\r\n"
			. "Reply-To: $mail_from\r\n"
			. "Return-Path: <$mail_from>"
			. "X-Sender: $mail_from\r\n"
			. "X-Mailer: PHP/" . phpversion();

		#var_dump($mail_to, $headers);

		mail(
			$mail_to,
			'subscribe',
			'subscribe',
			$headers,
			"-f $mail_from"
			);

		$GLOBALS['thanks4subscribing'] = true;
	}
} # end subscribe2newsletter()

add_action('init', 'subscribe2newsletter');


#
# update_newsletter_options()
#

function update_newsletter_options()
{
	$options = get_settings('sem_newsletter_params');

	$new_options = $options;

	$new_options['email'] = strip_tags(
			stripslashes(
					trim($_POST["sem_newsletter_email"])
					)
				);

	$new_options['title'] = strip_tags(
			stripslashes(
					$_POST["sem_newsletter_title"]
					)
				);

	$new_options['teaser'] = strip_tags(
					$_POST["sem_newsletter_teaser"]
				);

	$new_options['thanks'] = strip_tags(
					$_POST["sem_newsletter_thanks"]
				);

	if ( $options != $new_options )
	{
		$options = $new_options;

		update_option('sem_newsletter_params', $options);
	}
} # end update_newsletter_options()


#
# add_newsletter_admin()
#

function add_newsletter_admin()
{
	add_options_page(
			__('Newsletter'),
			__('Newsletter'),
			7,
			str_replace("\\", "/", __FILE__),
			'display_newsletter_admin'
			);
} # end add_newsletter_admin()

add_action('admin_menu', 'add_newsletter_admin');


#
# display_newsletter_admin()
#

function display_newsletter_admin()
{
?>
<form method="post" action="">
<?php
	if ( $_POST['update_newsletter_options'] )
	{
		echo "<div class=\"updated\">\n"
			. "<p>"
				. "<strong>"
				. __('Options saved.')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	}
?>
<div class="wrap">
	<h2><?php echo __('Newsletter options'); ?></h2>
	<?php newsletter_widget_control(); ?>
	<p class="submit">
	<input type="submit"
		value="<?php echo __('Update Options'); ?>"
		 />
	</p>
</div>
<div class="wrap">
	<h2>How to use this plugin</h2>
	<p>You will need a working version of mailing list manager that uses standard subscription commands (yourlist-subscribe@yourdomain.com) to take advantage of this plugin.</p>

	<p>Such managers include <a href="http://www.ezmlm.org">ezmlm</a>, <a href="http://www.list.org">Mailman</a>. Your host typically installs one of these by default.</p>

	<h3>Typical broadcast list configuration:</h3>
	<ul>
		<li>Message moderation. (<strong>important</strong>: do not forget to add yourself to the moderators)</li>
		<li>Posts from addresses other than moderators are rejected.</li>
		<li>Respond to administrative requests. (<strong>important</strong>: the plugin won't work if you disable this)</li>
	</ul>

	<p>Please contact your host, or <a href="mailto:sales@semiologic.com">sales@semiologic.com</a>, if you need further assistance.</p>

</div>
</form>
<?php
} # end display_newsletter_admin()


#
# widgetize_newsletter()
#

function widgetize_newsletter()
{
	if ( function_exists('register_sidebar_widget') )
	{
		register_sidebar_widget('Newsletter', 'the_subscribe_form');
		register_widget_control('Newsletter', 'newsletter_widget_control', null, 300);
	}
} # end widgetize_newsletter()

add_action('plugins_loaded', 'widgetize_newsletter');


#
# newsletter_widget_control()
#

function newsletter_widget_control()
{
	if ( $_POST['update_newsletter_options'] )
	{
		update_newsletter_options();
	}

	$options = get_settings('sem_newsletter_params');

	if ( !isset($options['title']) || ( $options['title'] == "" ) )
	{
		$options['title'] = __('Newsletter');
	}
	if ( !isset($options['teaser']) || ( $options['teaser'] == "" ) )
	{
		$options['teaser'] = __('<p>Sign up to receive an occasional newsletter.</p>');
	}
	if ( !isset($options['thanks']) || ( $options['thanks'] == "" ) )
	{
		$options['thanks'] = __('<p>Thank you for subscribing!</p>');
	}

	$email = htmlspecialchars($options['email'], ENT_QUOTES);
	$title = htmlspecialchars($options['title'], ENT_QUOTES);

	$teaser = htmlspecialchars($options['teaser'], ENT_QUOTES);
	$thanks = htmlspecialchars($options['thanks'], ENT_QUOTES);
?>
<input type="hidden" name="update_newsletter_options" value="1" />
<div style="margin: 1em 0px;">
<label for="sem_newsletter_email"><?php echo __('Newsletter email:'); ?><br />
	<input type="text"
	id="sem_newsletter_email" name="sem_newsletter_email"
	value="<?php echo $email; ?>"
	/></label>
</div>
<div style="margin: 1em 0px;">
<label for="sem_newsletter_title"><?php echo __('Widget title:'); ?><br />
	<input type="text"
	id="sem_newsletter_title" name="sem_newsletter_title"
	value="<?php echo $title; ?>"
	/></label>
</div>
<div style="margin: 1em 0px;">
<label for="sem_newsletter_teaser"><?php echo __('Widget teaser:'); ?></label><br />
<textarea
	id="sem_newsletter_teaser" name="sem_newsletter_teaser"
	style="width: 240px; height: 60px;"
	><?php echo $teaser; ?></textarea>
</div>
<div style="margin: 1em 0px;">
<label for="sem_newsletter_thanks"><?php echo __('Acknowledgement:'); ?></label><br />
<textarea
	id="sem_newsletter_thanks" name="sem_newsletter_thanks"
	style="width: 240px; height: 60px;"
	><?php echo $thanks; ?></textarea>
</div>
<?php
} # end newsletter_widget_control()


#
# backward compatibility
#

function sem_newsletter_form()
{
	the_subscribe_form();
} // end sem_newsletter_form()
?>