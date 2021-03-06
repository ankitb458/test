<?php
/**
 * google_analytics_admin
 *
 * @package Google Analytics
 **/

class google_analytics_admin {
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST || !current_user_can('manage_options') )
			return;
		
		check_admin_referer('google_analytics');
		
		$ga_script = stripslashes($_POST['ga_script']);
		
		if ( preg_match("/^UA-[a-z0-9-]+$/ix", $ga_script, $match) ) {
			$uacct = $match[0];
		} else {
			$uacct = '';
		}
		
		update_option('google_analytics', $uacct);
		
		echo '<div class="updated fade">' . "\n"
			. '<p>'
				. '<strong>'
				. __('Settings saved.', 'google-analytics')
				. '</strong>'
			. '</p>' . "\n"
			. '</div>' . "\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		$uacct = google_analytics::get_options();
		
		if ( !$uacct )
			$uacct = __('Your Account ID', 'google-analytics');
		
		echo '<div class="wrap">' . "\n"
			. '<form method="post" action="">' . "\n";
		
		wp_nonce_field('google_analytics');
		
		screen_icon();
		
		echo '<h2>' . __('Google Analytics Settings', 'google-analytics') . '</h2>' . "\n";
		
		echo '<h3>'
			. __('Google Analytics Id', 'google-analytics')
			. '</h3>' . "\n";
		
		echo '<table class="form-table">' . "\n";
		
		echo '<tr valign="top">'
			. '<th scope="row">'
			. __('GA Script', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('Paste the user ID from the <b>ga.js</b> script from <a href="http://analytics.google.com">Google analytics</a> into the following textarea (<a href="http://www.google.com/support/googleanalytics/bin/answer.py?answer=55603">where do I find it?</a>):', 'google-analytics')
			. '</p>' ."\n"
			. '<input type="text" name="ga_script"'
					. ' class="widefat code"'
					. ' value="' . esc_attr($uacct) . '"'
					. ' />'
			. '<p>'
			. __('Tip: in <code>var pageTracker = _gat._getTracker("UA-123456-1");</code>, your ID is <code>UA-123456-1</code>.', 'google-analytics')
			. '</p>'
			. '</td>'
			. '</tr>';
		
		echo "</table>\n";
		
		echo '<div class="submit">'
			. '<input type="submit"'
				. ' value="' . esc_attr(__('Save Changes', 'google-analytics')) . '"'
				. " />"
			. "</div>\n";
		
		echo '</form>' . "\n";
		
		google_analytics_admin::crash_course();
		
		echo '</div>' . "\n";
	} # edit_options()
	
	
	/**
	 * crash_course()
	 *
	 * @return void
	 **/

	function crash_course() {
		echo '<h3>' . __('Quick Reference', 'google-analytics') . '</h3>' . "\n";
		
		echo '<table class="form-table">' . "\n";
		
		echo '<tr>'
			. '<th scope="row">'
			. __('AdSense Revenue Tracking', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('To enable AdSense revenue tracking using Google Analytics, simply <a href="http://www.google.com/support/googleanalytics/bin/answer.py?hl=en&answer=55507">declare this site for AdSense tracking</a> in <a href="http://analytics.google.com">Google Analytics\'s administration panels</a>. (The needed code is inserted automatically by the plugin.)', 'google-analytics')
			. '</p>' . "\n"
			. '</td>'
			. '</tr>' . "\n";
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Event Tracking', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('The Google Analytics plugin allows you to track special events. Support for the following is built-in:', 'google-analytics')
			. '</p>' . "\n"
			. '<ul class="ul-square">' . "\n"
			. '<li>'
			. sprintf(__('Newsletter sign-ups when using the <a href="%s">Newsletter Manager</a> plugin. Visitors are additionally segmented automatically on a successful signup if they\'re redirected on a page on this site.', 'google-analytics'), 'http://www.semiologic.com/software/newsletter-manager/')
			. '</li>' . "\n"
			. '<li>'
			. sprintf(__('Clicks on ad units that were inserted using the <a href="%s">Ad Manager</a> plugin. It will additionally tell you the <strong>cumulative</strong> number of times each ad unit was clicked by that visitor. Note: This only works with ads that do not rely on iFrames.', 'google-analytics'), 'http://www.semiologic.com/software/ad-manager/')
			. '</li>' . "\n"
			. '<li>'
			. sprintf(__('Media player usage and file downloads that were inserted using the <a href="%s">Mediacaster</a> plugin.', 'google-analytics'), 'http://www.semiologic.com/software/mediacaster/')
			. '</li>' . "\n"
			. '<li>'
			. sprintf(__('Contact Form submissions when using the <a href="%s">Contact Form</a> plugin.', 'google-analytics'), 'http://www.semiologic.com/software/contact-form/')
			. '</li>' . "\n"
			. '<li>'
			. sprintf(__('Poll usage when using the Poll Widget plugin that comes with <a href="%s">Semiologic Pro</a>.', 'google-analytics'), 'http://www.getsemiologic.com')
			. '</li>' . "\n"
			. '</ul>' . "\n"
			. '</td>'
			. '</tr>' . "\n";
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Special Users', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p><strong>'
			. __('Authors, editors and site admins are not tracked when logged in.', 'google-analytics')
			. '</strong></p>' . "\n"
			. '</td>'
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
	} # crash_course()
} # google_analytics_admin

add_action('settings_page_google-analytics', array('google_analytics_admin', 'save_options'), 0);
?>