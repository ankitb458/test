<?php
/*
Plugin Name: Contact Form ][ (fork)
Plugin URI: http://chip.cuccio.us/projects/contact-form-II/
Description: Contact Form ][ is a drop-in form that allows site visitors to contact you. It can be implemented easily (via QuickTags) within any post or page.  This version is *specifically* for WordPress 2.0 only.  Original code derived from Ryan Duff's WP-ContactForm plugin.
Author: Chip Cuccio
Author URI: http://chip.cuccio.us
Version: 2.10 fork
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: contact_form
Update URI: http://www.semiologic.com/members/sem-pro/download/
*/

load_plugin_textdomain('wpcf'); // NLS

add_option('wpcf_require_phone', 0);
#var_dump(get_option('wpcf_require_phone'));
/* Declare strings that change depending on input. This also resets them so errors clear on resubmission. */
$wpcf_strings = array(
    'name' => '<input class="field" type="text" name="wpcf_your_name" id="wpcf_your_name" tabindex="1" size="30" maxlength="50" value="' . $_POST['wpcf_your_name'] . '" />',
    'email' => '<input class="field" type="text" name="wpcf_email" id="wpcf_email" tabindex="2" size="30" maxlength="50" value="' . $_POST['wpcf_email'] . '" />',
    'phone' => '<input class="field" type="text" name="wpcf_phone" id="wpcf_phone" tabindex="3" size="30" maxlength="50" value="' . $_POST['wpcf_phone'] . '" />',
    'tz' => '<input class="field" type="text" name="wpcf_tz" id="wpcf_tz" tabindex="4" size="30" maxlength="50" value="' . $_POST['wpcf_tz'] . '" />',
    'subject' => '<input class="field" type="text" name="wpcf_subject" id="wpcf_subject" tabindex="5" size="30" maxlength="50" value="' .$_POST['wpcf_subject'] . '" />',
    'msg' => '<textarea class="field" name="wpcf_msg" id="wpcf_msg" tabindex="6" cols="'.get_option('wpcf_textarea_cols').'" rows="'.get_option('wpcf_textarea_rows').'" >' . $_POST['wpcf_msg'] . '</textarea>',
    'carbon_copy' => '<input type="checkbox" name="carbon_copy" value="true" tabindex="7" />',
    'error' => '');

/*
This shows the quicktag on the write pages
Based off Buttonsnap Template
http://redalt.com/downloads
*/
if ( false ) :
#if(get_option('wpcf_show_quicktag') == true) {
    include(dirname(__FILE__) . '/buttonsnap.php');

    add_action('init', 'wpcf_button_init');
    add_action('marker_css', 'wpcf_marker_css');

    function wpcf_button_init() {
        $wpcf_button_url = buttonsnap_dirname(__FILE__) . '/wpcf_button.png';

        buttonsnap_textbutton($wpcf_button_url, 'Contact Form', '[CONTACT-FORM]');
        buttonsnap_register_marker('CONTACT-FORM', 'wpcf_marker');
    }

    function wpcf_marker_css() {
        $wpcf_marker_url = buttonsnap_dirname(__FILE__) . '/wpcf_marker.gif';
        echo "
            .wpcf_marker {
                    display: block;
                    height: 15px;
                    width: 155px
                    margin-top: 5px;
                    background-image: url({$wpcf_marker_url});
                    background-repeat: no-repeat;
                    background-position: center;
            }
        ";
    }
#}
endif;


function wpcf_is_malicious($input) {
	$is_malicious = false;
	#$bad_inputs = array("\r", "\n", "mime-version", "content-type", "cc:", "to:");
	$bad_inputs = array("<", ">", "&lt;", "&gt", "mime-version", "content-type", "cc:", "bcc:", "to:", "<a href", "</a>", "http://", "[/URL]", "[URL=");
	foreach($bad_inputs as $bad_input) {
		if(strpos(strtolower($input), strtolower($bad_input)) !== false) {
			$is_malicious = true; break;
		}
	}
	return $is_malicious;
}

/* This function checks for errors on input and changes $wpcf_strings if there are any errors. Shortcircuits if there has not been a submission */
function wpcf_check_input()
{
	if(!(isset($_POST['wpcf_stage']))) {return false;} // Shortcircuit.

    $_POST['wpcf_your_name'] = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['wpcf_your_name']))));
    $_POST['wpcf_email'] = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['wpcf_email']))));
    $_POST['wpcf_phone'] = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['wpcf_phone']))));
    $_POST['wpcf_tz'] = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['wpcf_tz']))));
    $_POST['wpcf_carbon_copy'] = isset($_POST['wpcf_carbon_copy']);
    $_POST['wpcf_subject'] = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['wpcf_subject']))));
    $_POST['wpcf_msg'] = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['wpcf_msg']))));

	global $wpcf_strings;
	$ok = true;

	if(empty($_POST['wpcf_your_name']))
	{
		$ok = false; $reason = 'empty';
		$wpcf_strings['name'] = '<input class="field" type="text" name="wpcf_your_name" id="wpcf_your_name" size="30" maxlength="50" value="' . $_POST['wpcf_your_name'] . '" class="contacterror" />';
	}

    if(!is_email($_POST['wpcf_email']))
    {
	    $ok = false; $reason = 'empty';
	    $wpcf_strings['email'] = '<input class="field" type="text" name="wpcf_email" id="wpcf_email" size="30" maxlength="50" value="' . $_POST['wpcf_email'] . '" class="contacterror" />';
	}

	if ( get_option('wpcf_require_phone') && empty($_POST['wpcf_phone']) )
	{
	    $ok = false; $reason = 'empty';
	    $wpcf_strings['phone'] = '<input class="field" type="text" name="wpcf_phone" id="wpcf_phone" size="30" maxlength="50" value="' . $_POST['wpcf_phone'] . '" class="contacterror" />';
	}

    if(empty($_POST['wpcf_subject']))
    {
        $ok = false; $reason = 'empty';
        $wpcf_strings['subject'] = '<input class="field" type="text" name="wpcf_subject" id="wpcf_subject" size="30" maxlength="50" value="' .$_POST['wpcf_subject'] . '" class="contacterror" />';
    }

	if(wpcf_is_malicious($_POST['wpcf_your_name']) || wpcf_is_malicious($_POST['wpcf_email']|| wpcf_is_malicious($_POST['wpcf_subject'] ))) {
		$ok = false; $reason = 'malicious';
	}

	if(stristr($_POST['wpcf_your_name'], "\r")) {
        $ok = false; $reason = 'malicious';
    }
    if(stristr($_POST['wpcf_your_name'], "\n")) {
        $ok = false;
        $reason = 'malicious';
    }
    if(stristr($_POST['wpcf_email'], "\r")) {
        $ok = false;
        $reason = 'malicious';
    }
    if(stristr($_POST['wpcf_email'], "\n")) {
        $ok = false;
        $reason = 'malicious';
    }
    if(stristr($_POST['wpcf_subject'], "\r")) {
        $ok = false;
        $reason = 'malicious';
    }
    if(stristr($_POST['wpcf_subject'], "\n")) {
        $ok = false;
        $reason = 'malicious';
    }

	if($ok == true)
	{
		return true;
	}
	else {
		if($reason == 'malicious') {
			$wpcf_strings['error'] = "<div style='font-weight: bold;'>Your email was not sent due to suspicious activity. Please do not use html or enter urls into your email.</div>";
		} elseif($reason == 'empty') {
			$wpcf_strings['error'] = '<div style="font-weight: bold;">' . stripslashes(get_option('wpcf_error_msg')) . '</div>';
		}
		return false;
	}
}

/*Wrapper function which calls the form.*/
function wpcf_callback( $content )
{
	global $wpcf_strings;

	/* Run the input check. */

		if(! preg_match('|\[CONTACT-FORM\]|', $content)) {
		return $content;
		}

    if(wpcf_check_input()) // If the input check returns true (ie. there has been a submission & input is ok)
    {
            $recipient   = get_option('wpcf_email');
            $subj_suffix = stripslashes(get_option('wpcf_subject_suffix'));
            $subject     = stripslashes('wpcf_subject');
            $success_msg = get_option('wpcf_success_msg');
            $success_msg = stripslashes($success_msg);

            $name        = $_POST['wpcf_your_name'];
            $email       = $_POST['wpcf_email'];
            $phone       = $_POST['wpcf_phone'];
            $tz  	     = $_POST['wpcf_tz'];
            $carbon_copy = $_POST['wpcf_carbon_copy'];
            $subject     = $_POST['wpcf_subject'];
            $msg         = $_POST['wpcf_msg'];
            $browser     = $_SERVER['HTTP_USER_AGENT'];

#            echo '<pre>';
#            var_dump($_POST);
#            echo '</pre>';

			if ( $_POST['wpcf_stage'] != 'sent' )
			{
	            if ($_POST['carbon_copy'] ) {
	                $headers     = "MIME-Version: 1.0\n";
	                $headers    .= "From: $name <$email>\n";
	                $headers    .= "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	                $headers    .= "Cc: $email\n";
	                $fullmsg    .= wordwrap($msg, 76, "\n") . "\n\n";
	                $fullmsg    .= "Phone: $phone\n\n";
	                $fullmsg    .= "Time Zone: $tz\n\n";
	                $fullmsg    .= "\n----------------------------------------------------------------------------\n";
	                $fullmsg    .= "Sender info:\n\n";
	                $fullmsg    .= "IP: " . getip(). " <http://ws.arin.net/whois/?queryinput=".getip().">\n";
	                $fullmsg    .= "Browser/OS: " . wordwrap($browser, 76, "\n\t    ") . "\n";
	                $fullmsg    .= "----------------------------------------------------------------------------\n";
	                wp_mail($recipient, $subject ." ". $subj_suffix, $fullmsg, $headers);
	            } else {
	                $headers     = "MIME-Version: 1.0\n";
	                $headers    .= "From: $name <$email>\n";
	                $headers    .= "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	                $fullmsg    .= wordwrap($msg, 76, "\n") . "\n\n";
	                $fullmsg    .= "Phone: $phone\n\n";
	                $fullmsg    .= "Time Zone: $tz\n\n";
	                $fullmsg    .= "\n----------------------------------------------------------------------------\n";
	                $fullmsg    .= "Sender info:\n\n";
	                $fullmsg    .= "IP: " . getip(). " <http://ws.arin.net/whois/?queryinput=".getip().">\n";
	                $fullmsg    .= "Browser/OS: " . wordwrap($browser, 76, "\n\t    ") . "\n";
	                $fullmsg    .= "----------------------------------------------------------------------------\n";
	                wp_mail($recipient, $subject ." ". $subj_suffix, $fullmsg, $headers);
	            }

	            $_POST['wpcf_stage'] = 'sent';
        	}
            $results = '<div style="font-weight: bold;">' . $success_msg . '</div>';

            return $results;
    }
    else // Else show the form. If there are errors the strings will have updated during running the inputcheck.
    {
        $form = '<div class="contactform">
            <form action="' . get_permalink() . '" method="post">
                <p><label for="wpcf_your_name">' . __('Your Name: ', 'wpcf') . ' ' . __('(required)') . '</label><br />' . $wpcf_strings['name']  . '</p>
                <p><label for="wpcf_email">' . __('Your Email:', 'wpcf') . ' ' . __('(required)') . '</label><br />' . $wpcf_strings['email'] . '</p>';
        if ( get_option('wpcf_request_phone') )
        {
	        $form .= '
                <p><label for="wpcf_phone">' . __('Your Phone:', 'wpcf') . ( get_option('wpcf_require_phone') ? ( ' ' . __('(required)') ) : '' ) . '</label><br />' . $wpcf_strings['phone'] . '</p>
                <p><label for="wpcf_tz">' . __('Your Time Zone:', 'wpcf') . '</label><br />' . $wpcf_strings['tz'] . '</p>';
        }
        $form .= '
                <p><label for="wpcf_subject">' . __('Subject:', 'wpcf') . ' ' . __('(required)') . '</label><br />' . $wpcf_strings['subject'] . '</p>
                <div><label for="wpcf_msg">' . __('Your Message: ', 'wpcf') . '</label><br />' . $wpcf_strings['msg'] . '</div>
                <p><label for="carbon_copy">'. $wpcf_strings['carbon_copy'] . '&nbsp;' .__('Send a copy to yourself?', 'wpcf') . '</label></p>
                <p style="text-align: right"><input type="submit" name="Submit" value="Send Message" tabindex="8" /><input type="hidden" name="wpcf_stage" value="process" /></p>
            </form>
        </div>
        <div style="clear:both; height:1px;">&nbsp;</div>'
        . $wpcf_strings['error'];

        return str_replace('[CONTACT-FORM]', $form, $content);
    }
}


/*Can't use WP's function here, so lets use our own*/
function getip()
{
	if (isset($_SERVER))
	{
 	    $ip_addr = $_SERVER["REMOTE_ADDR"];
	}
	else
	{
  	    $ip_addr = getenv('REMOTE_ADDR');
	}
return $ip_addr;
}


/*CSS Styling*/
function wpcf_css()
	{
		$css_url = trailingslashit(get_option('siteurl'))
				. 'wp-content/'
				. 'plugins/'
				. 'wp-contact-form/'
				. 'wp-contactform.css';

		echo '<link'
			. ' rel="stylesheet" type="text/css"'
			. ' href="' . $css_url . '"'
			. ' />';

	}

function wpcf_add_options_page()
	{
		add_options_page('Contact&nbsp;Form', 'Contact&nbsp;Form', 'manage_options', 'wp-contact-form/options-contactform.php');
	}


function wpcf_addjs()
{
?>
	<script type="text/javascript">
		function focusit() {
			document.getElementById('wpcf_your_name').focus();
		}
		window.onload = focusit;
	</script>
<?php
}

/* Action calls for all functions */

//if(get_option('wpcf_show_quicktag') == true) {add_action('admin_footer', 'wpcf_add_quicktag');}

add_action('admin_head', 'wpcf_add_options_page');
add_filter('wp_head', 'wpcf_css');
#add_filter('wp_head', 'wpcf_addjs');
add_filter('the_content', 'wpcf_callback', 7);
add_filter('widget_text', 'wpcf_callback', 7);

?>