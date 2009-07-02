<?php
/*
Plugin Name: Newsletter Manager
Plugin URI: http://www.semiologic.com/software/newsletter-manager/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/newsletter-manager/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Lets you readily add a newsletter subscription form to your WordPress installation. If your theme does not support widgets, add a call to the_subscribe_form() in your template.
Author: Denis de Bernardy
Version: 2.7
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------
http://www.semiologic.com/legal/license/
**/


#
# the_subscribe_form()
#

function the_subscribe_form($args = null)
{
	$options = get_option('sem_newsletter_params');

	# drop call if no newsletter is defined

	$pattern_email = "/^[0-9a-zA-Z_.-]+@[0-9a-zA-Z_.-]+$/";

	if ( !$options
		|| !isset($options['email'])
		|| !preg_match($pattern_email, $options['email'])
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

	if ( !isset($args['title']) )
	{
		$args['title'] = $options['title'];
	}

	echo $args['before_widget'];

	echo $args['before_title'] . $args['title'] . $args['after_title'];

	echo get_the_subscribe_form();

	echo $args['after_widget'];
} # end the_subscribe_form()


#
# get_the_subscribe_form()
#

function get_the_subscribe_form()
{
	$options = get_option('sem_newsletter_params');

	# drop call if no newsletter is defined

	$pattern_email = "/^[0-9a-zA-Z_.-]+@[0-9a-zA-Z_.-]+$/";

	if ( !$options
		|| !isset($options['email'])
		|| !preg_match($pattern_email, $options['email'])
		)
	{
		return;
	}

	if ( !isset($options['teaser']) || ( $options['teaser'] == "" ) )
	{
		$options['teaser'] = __('<p>Sign up to receive an occasional newsletter.</p>');
	}
	if ( !isset($options['thanks']) || ( $options['thanks'] == "" ) )
	{
		$options['thanks'] = __('<p>Thank you for subscribing!</p>');
	}

	if ( isset($GLOBALS['thanks4subscribing']) || isset($_GET['subscribed']) )
	{
		$o = $options['thanks'];
	}
	elseif ( isset($options['add_subscribe']) && $options['add_subscribe'] === 'aweber' )
	{
		$o = get_the_aweber_form();
	}
	else
	{
		$hash = md5(uniqid(rand()));

		$o = $options['teaser']
			. '<form method="post" action="'
			. $_SERVER['REQUEST_URI']
			. ( ( strpos($_SERVER['REQUEST_URI'], '?') !== false )
				? '&'
				: '?'
				) . 'subscribed'
				. '"'
				. '>'
			. '<input type="hidden" name="action" value="subscribe2newsletter" />'
			. '<div class="newsletter_fields">'
			. '<input type="text"'
				. ' id="subscriber_name_' . $hash . '" name="subscriber_name"'
				. ' value="' . __('Your Name') . '"'
				. ' onfocus="if ( this.value == \'' . __('Your Name') . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . __('Your Name') . '\';"'
				. '/><br />'
			. '<input type="text"'
				. ' id="subscriber_email_' . $hash . '" name="subscriber_email"'
				. ' value="' . __('Your Email') . '"'
				. ' onfocus="if ( this.value == \'' . __('Your Email') . '\' ) this.value = \'\';"'
				. ' onblur="if ( this.value == \'\' ) this.value = \'' . __('Your Email') . '\';"'
				. '/>'
			. '</div>'
			. '<div class="newsletter_submit">'
			. '<input type="submit"'
				. ' value="' . __('Sign Up') . '"'
				. ' onclick="if ( !getElementById(\'subscriber_email_' . $hash . '\').value.match(/\S+@\S+/) ) { getElementById(\'subscriber_email_' . $hash . '\').focus(); return false; }"'
				. '/></div>'
			. '</form>';
	}

	return $o;
} # end get_the_subscribe_form()


#
# get_the_aweber_form()
#

function get_the_aweber_form()
{
	$options = get_option('sem_newsletter_params');

	$hash = md5(uniqid(rand()));

	$unit = preg_replace("/@.+/", "", $options['email']);

	$o = $options['teaser']
		. '<form method="post" action="http://www.aweber.com/scripts/addlead.pl">'
		. '<input type="hidden" name="meta_split_id" value="">'
		. '<input type="hidden" name="unit" value="' . $unit . '">'
		. '<input type="hidden" name="redirect" value="'
			. 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://'
			. $_SERVER['HTTP_HOST']
			. $_SERVER['REQUEST_URI']
			. ( ( strpos($_SERVER['REQUEST_URI'], '?') !== false )
				? '&'
				: '?'
				) . 'subscribed'
			. '">'
		. '<input type="hidden" name="meta_message" value="1">'
		. '<input type="hidden" name="meta_required" value="name,from">'
		. '<input type="hidden" name="meta_forward_vars" value="0">'
		. '<div class="newsletter_fields">'
		. '<input type="text"'
			. ' id="subscriber_name_' . $hash . '" name="name"'
			. ' value="' . __('Your Name') . '"'
			. ' onfocus="if ( this.value == \'' . __('Your Name') . '\' ) this.value = \'\';"'
			. ' onblur="if ( this.value == \'\' ) this.value = \'' . __('Your Name') . '\';"'
			. '/><br />'
		. '<input type="text"'
			. ' id="subscriber_email_' . $hash . '" name="from"'
			. ' value="' . __('Your Email') . '"'
			. ' onfocus="if ( this.value == \'' . __('Your Email') . '\' ) this.value = \'\';"'
			. ' onblur="if ( this.value == \'\' ) this.value = \'' . __('Your Email') . '\';"'
			. '/>'
		. '</div>'
		. '<div class="newsletter_submit">'
		. '<input type="submit"'
			. ' value="' . __('Sign Up') . '"'
			. ' name="submit"'
			. ' onclick="if ( !getElementById(\'subscriber_email_' . $hash . '\').value.match(/\S+@\S+/) ) { getElementById(\'subscriber_email_' . $hash . '\').focus(); return false; }"'
			. '/></div>'
		. '</form>';

	return $o;
} # end get_the_aweber_form()


#
# the_newsletter_tag()
#

function the_newsletter_tag($content)
{
	$content = preg_replace(
		"/
			<p(?:\s+[^>]*)?>			\s*
			<!--\s*newsletter\s*-->
			\s*
			(?:<\/p>)?
		/ix", "<!--newsletter-->", $content);

	$content = preg_replace_callback(
		"/
			<!--\s*newsletter\s*-->
		/ix", 'get_the_subscribe_form', $content);

	return $content;
} # end the_newsletter_tag()

add_filter('the_content', 'the_newsletter_tag');


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

		$mail_to = $options['email'];

		if ( !isset($options['add_subscribe']) || $options['add_subscribe'] )
		{
			$mail_to = str_replace('@', '-subscribe@', $mail_to);
		}

		$subscriber_name = trim($_POST['subscriber_name']);
		$subscriber_email = trim($_POST['subscriber_email']);

		$subscriber_name = preg_replace("/[^\w ]+/", " ", $subscriber_name);
		if ( $subscriber_name == __('Your Name') )
		{
			$subscriber_name = '';
		}

		if ( $subscriber_name )
		{
			$mail_from = '"' . $subscriber_name . '" <' . $subscriber_email . ">";
		}
		else
		{
			$mail_from = $subscriber_email;
		}

		$headers = "From: $mail_from\r\n"
			. "Reply-To: <$subscriber_email>\r\n"
			. "Return-Path: <$subscriber_email>\r\n"
			. "X-Sender: $subscriber_email\r\n"
			. "X-Mailer: PHP/" . phpversion();

		#var_dump($mail_to, $headers);

		mail(
			$mail_to,
			'subscribe',
			'subscribe',
			$headers,
			"-f $subscriber_email"
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

	$new_options['email'] = stripslashes(strip_tags(
			wp_filter_post_kses(
					trim($_POST["sem_newsletter_email"])
					))
				);

	$new_options['title'] = stripslashes(strip_tags(
			wp_filter_post_kses(
					$_POST["sem_newsletter_title"]
					))
				);

	$new_options['teaser'] = stripslashes(
			wp_filter_post_kses(
					$_POST["sem_newsletter_teaser"]
					)
				);

	$new_options['thanks'] = stripslashes(
			wp_filter_post_kses(
					$_POST["sem_newsletter_thanks"]
					)
				);

	switch( $_POST['sem_newsletter_add_subscribe'] )
	{
	case 'aweber':
		$new_options['add_subscribe'] = strip_tags($_POST['sem_newsletter_add_subscribe']);
		break;
	default:
		$new_options['add_subscribe'] = (bool) $_POST['sem_newsletter_add_subscribe'];
		break;
	}


	//if ( $options != $new_options )
	//{
	//	$options = $new_options;

		update_option('sem_newsletter_params', array());
		update_option('sem_newsletter_params', $new_options);
	//}
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
?><form method="post" action="">
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
?><div class="wrap">
	<h2><?php echo __('Newsletter options'); ?></h2>
	<?php newsletter_widget_control(); ?>	<p class="submit">
	<input type="submit"
		value="<?php echo __('Update Options'); ?>"
		 />
	</p>
</div>
<div class="wrap">
	<h2>How to use this plugin</h2>
	<p>You will need a working version of mailing list manager that uses standard subscription commands (list-subscribe@domain.com or list@domain.com) to take advantage of this plugin.</p>

	<p>Such managers include <a href="http://www.ezmlm.org">ezmlm</a>, <a href="http://www.list.org">Mailman</a> and <a href="http://www.greatcircle.com/majordomo">Majordomo</a>. Your host typically installs one of these by default. It also includes popular commercial tools such as <a href="http://www.semiologic.com/go/aweber">aWeber</a>.</p>

	<h3>If you are using a non-commercial mailing list manager, be sure to configure your list:</h3>
	<ul>
		<li>Message moderation. (<strong>important</strong>: do not forget to add yourself to the moderators)</li>
		<li>Posts from addresses other than moderators are rejected.</li>
		<li>Respond to administrative requests. (<strong>important</strong>: the plugin won't work if you disable this)</li>
	</ul>

	<p>Please contact your host, or <a href="mailto:sales@semiologic.com">sales@semiologic.com</a>, if you need further assistance in configuring your list.</p>

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
		register_widget_control('Newsletter', 'newsletter_widget_control', 350, 400);
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
	if ( !isset($options['add_subscribe']) )
	{
		$options['add_subscribe'] = true;
	}

	$email = htmlspecialchars($options['email'], ENT_QUOTES);
	$title = htmlspecialchars($options['title'], ENT_QUOTES);

	$teaser = htmlspecialchars($options['teaser'], ENT_QUOTES);
	$thanks = htmlspecialchars($options['thanks'], ENT_QUOTES);
?><input type="hidden" name="update_newsletter_options" value="1" />
<div style="margin: 1em 0px;">
<label for="sem_newsletter_email"><?php echo __('Newsletter Email'); ?>:<br />
	<input type="text"
	id="sem_newsletter_email" name="sem_newsletter_email"
	value="<?php echo $email; ?>"
	style="width: 300px;"
	/></label>
</div>
<div style="margin: 1em 0px;">
<label for="sem_newsletter_title"><?php echo __('Widget Title'); ?>:<br />
	<input type="text"
	id="sem_newsletter_title" name="sem_newsletter_title"
	value="<?php echo $title; ?>"
	style="width: 300px;"
	/></label>
</div>
<div style="margin: 1em 0px;">
<label for="sem_newsletter_teaser"><?php echo __('Widget Teaser'); ?>:</label><br />
<textarea
	id="sem_newsletter_teaser" name="sem_newsletter_teaser"
	style="width: 300px; height: 60px;"
	><?php echo $teaser; ?></textarea>
</div>
<div style="margin: 1em 0px;">
<label for="sem_newsletter_thanks"><?php echo __('Acknowledgement'); ?>:</label><br />
<textarea
	id="sem_newsletter_thanks" name="sem_newsletter_thanks"
	style="width: 300px; height: 60px;"
	><?php echo $thanks; ?></textarea>
</div>
<div style="margin: 1em 0px;">
	<?php echo __('Subscription Syntax'); ?>:<br />
<label for="sem_newsletter_add_subscribe_yes">
	<input type="radio"
	id="sem_newsletter_add_subscribe_yes" name="sem_newsletter_add_subscribe"
	value="1"
	<?php echo ( $options['add_subscribe'] === true ) ? 'checked="checked"' : ''; ?>	/>&nbsp;<?php echo __('list-subscribe@domain.com (<i>e.g.</i> <a href="http://www.greatcircle.com/majordomo">Majordomo</a>)'); ?></label><br />
<label for="sem_newsletter_add_subscribe_no">
	<input type="radio"
	id="sem_newsletter_add_subscribe_no" name="sem_newsletter_add_subscribe"
	value="0"
	<?php echo ( $options['add_subscribe'] === false ) ? 'checked="checked"' : ''; ?>	/>&nbsp;<?php echo __('list@domain.com'); ?></label><br />
<label for="sem_newsletter_add_subscribe_aweber">
	<input type="radio"
	id="sem_newsletter_add_subscribe_aweber" name="sem_newsletter_add_subscribe"
	value="aweber"
	<?php echo ( $options['add_subscribe'] === 'aweber' ) ? 'checked="checked"' : ''; ?>	/>&nbsp;<?php echo __('<a href="http://www.semiologic.com/go/aweber">aWeber</a> subscription form'); ?></label>
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