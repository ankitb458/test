<?php
/*
Plugin Name: Contact Form
Plugin URI: http://www.semiologic.com/software/contact-form/
Description: Contact form widgets for WordPress, with WP Hashcash and akismet integration to fight contact form spam. Use the Inline Widgets plugin to insert contact forms into your posts and pages.
Version: 2.0
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: contact-form
Domain Path: /lang
*/


/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('contact-form', false, dirname(plugin_basename(__FILE__)) . '/lang');


/**
 * contact_form
 *
 * @package Contact Form
 **/

class contact_form extends WP_Widget {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_contact_form') === false ) {
			foreach ( array(
				'contact_form_widgets' => 'upgrade',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('contact_form');
	} # widgets_init()
	
	
	/**
	 * contact_form()
	 *
	 * @return void
	 **/

	function contact_form() {
		$widget_ops = array(
			'classname' => 'contact_form',
			'description' => __('A contact form, with spam counter-measures through WP Hashcash and Akismet.', 'contact-form'),
			);
		$control_ops = array(
			'width' => 500,
			);
		
		$this->init();
		$this->WP_Widget('contact_form', __('Contact Form', 'contact-form'), $widget_ops, $control_ops);
	} # contact_form()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, contact_form::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. ( $email
					? ( $before_title . $email . $after_title )
					: ''
					)
				. $after_widget;
			return;
		}
		
		preg_match("/\d+$/", $widget_id, $number);
		$number = intval(end($number));
		
		if ( !is_email($email) ) {
			$form = '<div style="border: solid 1px red; background: #ffeeee; color: #cc0000; font-weight: bold; padding: 10px;">'
			. __('Please configure this contact form under Appearence / Widgets', 'contact-form')
			. '</div>' . "\n";
		} elseif ( intval($_POST['cf_number']) == $number
			&& $GLOBALS['cf_status'][intval($_POST['cf_number'])] == 'success'
			) {
			$form = '<div class="cf_success">'
				. wpautop($captions['success_message'])
				. '</div>' . "\n";
		} else {
			$form = '<form method="post" action="" class="form_event">' . "\n"
				. '<input type="hidden" class="event_label" value="' . esc_attr(sprintf(__('Contact: %s', 'contact-form'), sanitize_title(preg_replace("/@.+/", '', $email)))) . '" />' . "\n"
				. '<input type="hidden" name="cf_number" value="' . intval($number) . '">' . "\n";
			
			if ( intval($_POST['cf_number']) == $number ) {
				$errorCode = $GLOBALS['cf_status'][intval($_POST['cf_number'])];

				if ( $errorCode ) {
					$form .= '<div class="cf_error">'
						. $captions[$errorCode]
						. '</div>' . "\n";
				}
			}
			
			foreach ( array(
					'name',
					'email',
					'phone',
					'subject',
					'message',
					'cc',
					'send'
				) as $var ) {
				switch ( $var ) {
				case 'phone':
					if ( !$captions[$var] )
						break;
				case 'name':
				case 'email':
				case 'subject':
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
						. '<label>'
						. $captions[$var] . '<br />' . "\n"
						. '<input type="text" class="cf_field"'
							. ' name="cf_' . $var . '"'
							. ' value="' . esc_attr(stripslashes($_POST['cf_' . $var])) . '"'
							. ' />'
						. '</label>'
						. '</div>' . "\n";
					break;
				case 'message':
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
						. '<label>'
						. $captions[$var] . '<br />' . "\n"
						. '<textarea class="cf_field"'
							. ' name="cf_' . $var . '"'
							. ' >'
						. esc_attr(stripslashes($_POST['cf_' . $var]))
						. '</textarea>'
						. '</label>'
						. '</div>' . "\n";
					break;
				case 'cc':
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
						. '<label>'
						. '<input type="checkbox" class="cf_checkbox"'
							. ' name="cf_' . $var . '"'
							. ( isset($_POST['cf_' . $var])
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. '&nbsp;' . $captions[$var] . "\n"
						. '</label>'
						.'</div>' . "\n";
					break;
				case 'send':
					$form .= '<div class="cf_field cf_' . $var . '">' . "\n"
						. '<label>'
						. '<input type="submit" class="button submit"'
							. ' value="' . esc_attr($captions[$var]) . '"'
							. ' />'
						. '</label>'
						.'</div>' . "\n";
					break;
				}
			}
			
			if ( function_exists('wphc_option') )
				$form .= '<input type="hidden" name="wphc_value" value="" />' . "\n";

			$form .= '</form>' . "\n";
		}
		
		$title = apply_filters('widget_title', $title);
		
		echo $before_widget . "\n"
			. ( $title
				? ( $before_title . $title . $after_title )
				: ''
				)
			. '<div style="clear: both;"></div>' . "\n"
			. $form
			. $after_widget . "\n";
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$title = trim($new_instance['title']);
		$email = trim($new_instance['email']);
		
		if ( !is_email($email) )
			$email = get_option('admin_email');
		
		$captions = array();
		foreach ( array_keys(contact_form::captions()) as $var ) {
			if ( !current_user_can('unfiltered_html') )
				$captions[$var] = $old_instance['captions'][$var];
			else
				$captions[$var] = $new_instance['captions'][$var];
		}

		return compact('title', 'email', 'captions');
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = contact_form::defaults();
		$instance = wp_parse_args($instance, $defaults);
		$instance['captions'] = wp_parse_args($instance['captions'], $defaults['captions']);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Config', 'contact-form') . '</h3>' . "\n";
		
		echo '<table style="width: 460px;">' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row" style="width: 100px;">'
			. __('Title', 'contact-form')
			. '</th>' . "\n"
			. '<td>'
			.'<input type="text" size="20" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '<tr valign="top">' . "\n"
			. '<th scope="row" style="width: 100px;">'
			. __('Your Email', 'contact-form')
			. '</th>' . "\n"
			. '<td>'
			.'<input type="text" size="20" class="widefat"'
				. ' name="' . $this->get_field_name('email') . '"'
				. ' value="' . esc_attr($email) . '"'
				. ' />'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '<h3>' . __('Captions', 'contact-form') . '</h3>' . "\n";
		
		echo '<table style="width: 460px;">' . "\n";
		
		foreach ( contact_form::captions() as $var => $caption ) {
			switch ( $var ) {
			case 'success_message':
				echo '<tr valign="top">' . "\n"
					. '<th scope="row" style="width: 100px;">'
					. $caption
					. '</th>' . "\n"
					. '<td>'
					.'<textarea cols="20" rows="6" class="widefat"'
						. ' name="' . $this->get_field_name('captions') . '['. $var . ']"'
						. ' >'
					. esc_html($captions[$var])
					. '</textarea>'
					. '</td>' . "\n"
					. '</tr>' . "\n";
				break;
			default:
				echo '<tr valign="top">' . "\n"
					. '<th scope="row">'
					. $caption
					. '</th>' . "\n"
					. '<td>'
					.'<input type="text" size="20" class="widefat"'
						. ' name="' . $this->get_field_name('captions') . '['. $var . ']"'
						. ' value="' . esc_attr($captions[$var]) . '"'
						. ' />'
					. '</td>' . "\n"
					. '</tr>' . "\n";
				break;
			}
		}
		
		echo '</table>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance default options
	 **/

	function defaults() {
		return array(
			'title' => __('Contact Us', 'contact-form'),
			'email' => get_option('admin_email'),
			'captions' => array(
				'name' => __('Your Name', 'contact-form'),
				'email' => __('Your Email', 'contact-form'),
				'phone' => __('Your Phone Number (optional)', 'contact-form'),
				'subject' => __('Subject', 'contact-form'),
				'message' => __('Message', 'contact-form'),
				'cc' => __('Receive a carbon copy of this email', 'contact-form'),
				'send' => __('Send Email', 'contact-form'),
				'success_message' => __('Thank you for your email.', 'contact-form'),
				'invalid_email' => __('Please enter a valid email.', 'contact-form'),
				'required_field' => __('Please fill in all of the required fields.', 'contact-form'),
				'spam_caught' => __('Sorry... Your message has been caught as spam and was not sent.', 'contact-form'),
				)
			);
	} # defaults()
	
	
	/**
	 * captions()
	 *
	 * @return array $captions form captions
	 **/

	function captions() {
		return array(
			'name' => __('Name', 'contact-form'),
			'email' => __('Email', 'contact-form'),
			'phone' => __('Phone Number', 'contact-form'),
			'subject' => __('Subject', 'contact-form'),
			'message' => __('Message', 'contact-form'),
			'cc' => __('Receive a copy', 'contact-form'),
			'send' => __('Send Email', 'contact-form'),
			'success_message' => __('Thank you', 'contact-form'),
			'invalid_email' => __('Invalid Email', 'contact-form'),
			'required_field' => __('Missing Field', 'contact-form'),
			'spam_caught' => __('Spam Caught', 'contact-form'),
			);
	} # captions()
	
	
	/**
	 * add_css()
	 *
	 * @return void
	 **/
	
	function add_css() {
		$folder = plugin_dir_url(__FILE__);
		$css = $folder . 'css/contact-form.css';
		
		wp_enqueue_style('contact_form', $css, null, '20090903');
	} # add_css()
	
	
	/**
	 * send_message()
	 *
	 * @return void
	 **/

	function send_message() {
		if ( empty($_POST['cf_number']) ) {
			# toggle cf
			setcookie(
				'cf_' . COOKIEHASH,
				1,
				time() + 3600,
				COOKIEPATH,
				COOKIE_DOMAIN
				);

			return;
		}
		
		if ( contact_form::validate() ) {
			$options = get_option('widget_contact_form');
			
			$number = intval($_POST['cf_number']);
			
			$options = $options[$number];
			
			$to = $options['email'];
			
			if ( !is_email($to) )
				return;
			
			foreach ( array('name', 'email', 'phone', 'subject', 'message') as $var )
				$$var = strip_tags(stripslashes($_POST['cf_' . $var]));
			
			$headers = 'From: "' . $name . '" <' . $email . '>';
			
			$message = __('Site:', 'contact-form') . ' ' . get_option('blogname') . "\n"
				. __('From:', 'contact-form') . ' ' . $name . "\n"
				. __('Email:', 'contact-form') . ' ' . $email . "\n"
				. ( $phone
					? ( __('Phone:', 'contact-form') . ' '. $phone . "\n" )
					: ''
					)
				. "\n"
				. $message;
			
			wp_mail($to, $subject, $message, $headers);
			
			if ( $_POST['cf_cc'] )
				wp_mail($email, $subject, $message, $headers);
			
			$GLOBALS['cf_status'][$number] = 'success';
		}
	} # send_message()
	
	
	/**
	 * validate()
	 *
	 * @return void
	 **/

	function validate() {
		$status = 'spam_caught';
		
		$ok = strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) !== false
			&& $_COOKIE['cf_' . COOKIEHASH];
		
		# sanitize $_POST variables
		foreach ( array('name', 'email', 'phone', 'subject', 'message') as $var )
			$_POST['cf_' . $var] = trim(strip_tags($_POST['cf_' . $var]));

		if ( $ok ) {
			foreach ( array('name', 'email', 'subject', 'message') as $var ) {
				$$var = $_POST['cf_' . $var];

				switch ( $var ) {
				case 'email':
					if ( !is_email($$var) ) {
						$ok = false;
						$status = 'invalid_email';
					}
				case 'name':
					if ( urldecode($$var) != $$var )
						$ok = false;
					foreach ( array("\r", "\n", ":", "%") as $kvetch )
						if ( strpos($$var, $kvetch) !== false )
							$ok = false;
				default:
					if ( $$var === '' ) {
						$ok = false;
						$status = 'required_field';
					}
				}
				
				if ( !$ok )
					break;
			} # foreach
		}
		
		# filter through hashcash
		if ( $ok && function_exists('wphc_option') ) {
			$wphc_options = wphc_option();
			$ok = in_array($_POST["wphc_value"], $wphc_options['key']);
		}
		
		# filter through akismet
		if ( $ok ) {
			# create a fake comment
			$comment['comment_post_ID'] = intval($_POST['cf_number']);
			$comment['comment_author'] = stripslashes($_POST['cf_name']);
			$comment['comment_author_email'] = stripslashes($_POST['cf_email']);
			$comment['comment_author_url'] = '';
			$comment['comment_content'] = stripslashes($_POST['cf_message']);
			$comment['comment_type'] = '';
			$comment['user_ID'] = '';

			$args = array();
			$args['ok'] =& $ok;
			$args['comment'] =& $comment;
			
			# comment spam filters can now filter this the usual way with an appropriate method
			$args = apply_filters('contact_form_validate', $args);
		}
		
		if ( !$ok )
			$GLOBALS['cf_status'][intval($_POST['cf_number'])] = $status;
		
		return $ok;
	} # validate()
	
	
	/**
	 * hashcash()
	 *
	 * @return void
	 **/

	function hashcash() {
		if ( !function_exists('wphc_option') )
			return;
		
		if ( !is_singular() ) {
			$hc_loader = <<<EOS
function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}
EOS;
			$hc_js = wphc_getjs();
		} else {
			$hc_loader = '';
			$hc_js = '';
		}
		
		echo <<<EOS

<script type="text/javascript">
<!--
$hc_loader

$hc_js

addLoadEvent(function() {
	var value = wphc();
	for ( var i = 0; i < document.getElementsByName('wphc_value').length; i++ ) {
		document.getElementsByName('wphc_value')[i].value=value;
	}
});
//-->
</script>

EOS;
	} # hashcash()
	
	
	/**
	 * akismet()
	 *
	 * @param array $args Status and fake WP comment
	 * @return void
	 **/

	function akismet($args) {
		if ( !$args['ok'])
			return $args;
		else
			$comment =& $args['comment'];
		
		# pass posted message through akismet
		if ( function_exists('akismet_auto_check_comment') && get_option('wordpress_api_key') ) {
			global $akismet_api_host, $akismet_api_port;

			$comment['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$comment['referrer'] = $_SERVER['HTTP_REFERER'];
			$comment['blog'] = user_trailingslashit(get_option('home'));

			$ignore = array( 'HTTP_COOKIE' );

			foreach ( $_SERVER as $key => $value )
				if ( !in_array( $key, $ignore ) )
					$comment["$key"] = $value;

			$query_string = '';
			foreach ( $comment as $key => $data )
				$query_string .= $key . '=' . urlencode( stripslashes($data) ) . '&';

			$response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);

			if ( 'true' == $response[1] )
				$args['ok'] = false;
		}
		
		return $args;
	} # akismet()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		foreach ( $ops as $k => $o ) {
			if ( isset($widget_contexts['contact_form-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['contact_form-' . $k];
			}
		}
		
		return $ops;
	} # upgrade()
	
	
	/**
	 * fix_hashcash()
	 *
	 * @return void
	 **/
	
	function fix_hashcash() {
		# hashcash
		if ( function_exists('wphc_add_commentform') && !class_exists('sem_fixes') ) {
			add_filter('option_plugin_wp-hashcash', array('contact_form', 'hc_options'));
			remove_action('admin_menu', 'wphc_add_options_to_admin');
			remove_action('widgets_init', 'wphc_widget_init');
			remove_action('comment_form', 'wphc_add_commentform');
			remove_action('wp_head', 'wphc_posthead');
			add_action('comment_form', array('contact_form', 'hc_add_message'));
			add_action('wp_head', array('contact_form', 'hc_addhead'));
			
			if ( is_admin() )
				remove_filter('preprocess_comment', 'wphc_check_hidden_tag');
		}
	} # fix_hashcash()
	
	
	/**
	 * hc_options()
	 *
	 * @param array $o
	 * @return array $o
	 **/
	
	function hc_options($o) {
		if ( function_exists('akismet_init') && get_option('wordpress_api_key') ) {
			$o['moderation'] = 'akismet';
		} else {
			$o['moderation'] = 'delete';
		}
		
		$o['validate-ip'] = 'on';
		$o['validate-url'] = 'on';
		$o['logging'] = '';
		
		return $o;
	} # hc_options()
	
	
	/**
	 * hc_add_message()
	 *
	 * @return void
	 **/

	function hc_add_message() {
		$options = wphc_option();

		switch( $options['moderation'] ) {
		case 'delete':
			$warning = __('Wordpress Hashcash needs javascript to work, but your browser has javascript disabled. Your comment will be deleted!', 'contact-form');
			break;
		case 'akismet':
			$warning = __('Wordpress Hashcash needs javascript to work, but your browser has javascript disabled. Your comment will be queued in Akismet!', 'contact-form');
			break;
		case 'moderate':
		default:
			$warning = __('Wordpress Hashcash needs javascript to work, but your browser has javascript disabled. Your comment will be placed in moderation!', 'contact-form');
			break;
		}
		
		echo '<input type="hidden" id="wphc_value" name="wphc_value" value="" />' . "\n";
		echo '<noscript><p><strong>' . $warning . '</stron></p></noscript>' . "\n";
	} # hc_add_message()
	
	
	/**
	 * hc_addhead()
	 *
	 * @return void
	 **/
	
	function hc_addhead() {
		if ( !is_singular() )
			return;
		
		$hc_js = wphc_getjs();

		echo <<<EOS

<script type="text/javascript">
<!--
function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}

$hc_js

addLoadEvent(function(){
	if ( document.getElementById('wphc_value') )
		document.getElementById('wphc_value').value=wphc();
});
//-->
</script>

EOS;
	} # hc_addhead()
} # contact_form

if ( !is_admin() ) {
	add_action('init', array('contact_form', 'send_message'));
	
	add_action('wp_print_styles', array('contact_form', 'add_css'));
	add_action('wp_head', array('contact_form', 'hashcash'), 20);
	
	add_filter('contact_form_validate', array('contact_form', 'akismet'));
}

add_action('widgets_init', array('contact_form', 'widgets_init'));
add_action('plugins_loaded', array('contact_form', 'fix_hashcash'));
?>