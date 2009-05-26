<?php
/**
 * google_analytics_admin
 *
 * @package Google Analytics
 **/

add_action('settings_page_google-analytics', array('google_analytics_admin', 'save_options'), 0);

class google_analytics_admin {
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST )
			return;
		
		check_admin_referer('google_analytics');
		
		$ga_script = stripslashes($_POST['ga_script']);
		
		if ( preg_match("/_gat\._getTracker\(\"(UA-\d+-\d+)\"\);/ix", $ga_script, $match)
			) {
			$uacct = $match[1];
		} else {
			$uacct = false;
		}

		update_option('google_analytics', compact('uacct'));
		
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
		$options = google_analytics::get_options();
		extract($options, EXTR_SKIP);
		
		if ( !$uacct )
			$uacct = addslashes(esc_html(__('Your Account ID', 'sem-google-analytics')));
		
		$ga_script = <<<EOF
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("$uacct");
pageTracker._initData();
pageTracker._trackPageview();
} catch(err) {}
</script>
EOF;
		
		echo '<div class="wrap">' . "\n"
			. '<form method="post" action="">' . "\n";
		
		wp_nonce_field('google_analytics');
		
		screen_icon();
		
		echo "<h2>" . __('Google Analytics Settings', 'google-analytics') . "</h2>\n";
		
		echo '<table class="form-table">' . "\n";
		
		echo '<tr valign="top">'
			. '<th scope="row">'
			. __('GA Script', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('Paste the <b>ga.js</b> script from <a href="http://www.google.com/analytics/">Google analytics</a> into the following textarea (<a href="http://www.google.com/support/googleanalytics/bin/answer.py?answer=55603">where do I find it?</a>):', 'google-analytics')
			. '</p>' ."\n"
			. '<textarea name="ga_script"'
					. ' class="code widefat" cols="58" rows="12"'
					. ' onfocus="var this_val=eval(this); this_val.select();"'
					. '>'
				. format_to_edit($ga_script)
			. '</textarea>'
			. '<p>'
			. __('<strong>Important</strong>: Use the new <strong>ga.js</strong> script, not the legacy urchin.js script.', 'google-analytics')
			. '</p>'
			. '</td>'
			. '</tr>';
		
		echo "</table>\n";
		
		echo '<div class="submit">'
			. '<input type="submit"'
				. ' value="' . esc_attr(__('Save Changes')) . '"'
				. " />"
			. "</div>\n";
		
		echo '</form>' . "\n";
		
		
		echo "<h3>" . __('Quick Reference', 'google-analytics') . "</h3>\n";
		
		echo '<table class="form-table">';

		echo '<tr>'
			. '<th scope="row">'
			. __('Special Users', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('Authors, editors and site admins are not tracked when logged in.', 'google-analytics')
			. '</p>' . "\n"
			. '</td>'
			. '</tr>' . "\n";
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Special Urls', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('The Google Analytics plugin tracks a few special use-cases differently, for quick reference:', 'google-analytics')
			. '</p>' . "\n"
			. '<ul class="ul-square">' . "\n"
			. '<li>'
			. __('File downloads get tracked as /file/[url]', 'google-analytics')
			. '</li>' . "\n"
			. '<li>'
			. __('Clicks on outbound links get tracked as /outbound/[url]', 'google-analytics')
			. '</li>' . "\n"
			. '</ul>' . "\n"
			. '</td>'
			. '</tr>' . "\n";
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Site Usage Patterns', 'google-analytics')
			. '</th>'
			. '<td>'
			. '<p>'
			. __('Make sure to spot Google Analytics\'s navigation summary and site overlay reports for individual urls. They\'re located to the right when you visit any url\'s statistics.', 'google-analytics')
			. '</p>' . "\n"
			. '<ul class="ul-square">' . "\n"
			. '<li>'
			. __('The first is a concise report. It lets you know, at a glance, where users came from upon entering the page, and where they left upon leaving it.', 'google-analytics')
			. '</li>' . "\n"
			. '<li>'
			. __('The second is more graphical. It outputs your page with an overlay that shows where visitors are clicking.', 'google-analytics')
			. '</li>' . "\n"
			. '</ul>' . "\n"
			. '</td>'
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		
		echo "<h3>" . __('Advanced Tracking With The Script Manager Plugin', 'google-analytics') . "</h3>\n";
		
		echo '<p>'
			. __('To insert extra site-wide tracking code, activate the script manager plugin and, under <a href="?page=script-manager">Settings / Scripts</a>, add the relevant lines that follow in the footer scripts.')
			. '</p>' . "\n";
		
		echo '<p>'
			. __('To insert post- or page-specific tracking, edit that post or page and insert the lines in its footer scripts.', 'google-analytics')
			. '</p>' . "\n";
		
		echo '<table class="form-table">';
		
		
		$adv_js = <<<EOS
<script type="text/javascript">
pageTracker._setSampleRate(50);
</script>
EOS;
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Sampling Rate', 'google-analytics')
			. '</th>' . "\n"
			. '<td>'
			. '<p>'
			. __('To <a href="http://code.google.com/apis/analytics/docs/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._setSampleRate">set the sampling rate</a> at 50% on a high traffic site (this can speed up GA reports quite a bit):', 'google-analytics')
			. '</p>' . "\n"
			. '<textarea cols="58" rows="5" class="code widefat" onfocus="var this_val=eval(this); this_val.select();">'
			. format_to_edit($adv_js)
			. '</textarea>' . "\n"
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		
		$adv_js = <<<EOS
<script type="text/javascript">
var trackerSalesDept = _gat.getTracker("UA-12345-1");
trackerSalesDept._initData();
trackerSalesDept._trackPageview();
</script>
EOS;
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Shared Stats', 'google-analytics')
			. '</th>' . "\n"
			. '<td>'
			. '<p>'
			. __('To <a href="http://code.google.com/apis/analytics/docs/gaTracking.html#Multiple">share your stats</a> with your sales team\'s Google Analytics account:', 'google-analytics')
			. '</p>' . "\n"
			. '<textarea cols="58" rows="5" class="code widefat" onfocus="var this_val=eval(this); this_val.select();">'
			. format_to_edit($adv_js)
			. '</textarea>' . "\n"
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		
		$adv_js = <<<EOS
<script type="text/javascript">
pageTracker._setVar("Prospect");
</script>
EOS;
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Visitor Segmentation', 'google-analytics')
			. '</th>' . "\n"
			. '<td>'
			. '<p>'
			. __('To <a href="http://code.google.com/apis/analytics/docs/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._setVar">segment visitors</a> who visit your &quot;Thank you for subscribing&quot; page:', 'google-analytics')
			. '</p>' . "\n"
			. '<textarea cols="58" rows="5" class="code widefat" onfocus="var this_val=eval(this); this_val.select();">'
			. format_to_edit($adv_js)
			. '</textarea>' . "\n"
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		
		$adv_js = <<<EOS
<script type="text/javascript">
pageTracker._setSessionTimeout(3600 * 2); // 2 hours
</script>
EOS;
		
		echo '<tr>'
			. '<th scope="row">'
			. __('Session Timeout', 'google-analytics')
			. '</th>' . "\n"
			. '<td>'
			. '<p>'
			. __('To <a href="http://code.google.com/apis/analytics/docs/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._setSessionTimeout">increase the session duration</a> on a very long sales copy that visitors actually read:', 'google-analytics')
			. '</p>' . "\n"
			. '<textarea cols="58" rows="5" class="code widefat" onfocus="var this_val=eval(this); this_val.select();">'
			. format_to_edit($adv_js)
			. '</textarea>' . "\n"
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '</div>' . "\n";
	} # edit_options()
} # google_analytics_admin
?>