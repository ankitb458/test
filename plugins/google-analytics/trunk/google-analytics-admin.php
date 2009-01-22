<?php
class google_analytics_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('admin_menu', array('google_analytics_admin', 'add_option_page'));
	} # init()


	#
	# add_option_page()
	#

	function add_option_page()
	{
		add_options_page(
				__('Google&nbsp;Analytics'),
				__('Google&nbsp;Analytics'),
				'manage_options',
				__FILE__,
				array('google_analytics_admin', 'display_options')
				);
	} # add_option_page()


	#
	# update_options()
	#

	function update_options()
	{
		check_admin_referer('google_analytics');
		
		$o = array();

		$ga_script = stripslashes($_POST['ga_script']);
		
		if ( preg_match("/
				_gat\._getTracker\(\"(UA-\d+-\d+)\"\);
				/ix", $ga_script, $match)
			)
		{
			$o['uacct'] = $match[1];
		}
		else
		{
			$o['uacct'] = false;
		}

		update_option('google_analytics', $o);
	} # update_options()


	#
	# display_options()
	#

	function display_options()
	{
		# Process updates, if any

		if ( $_POST['update_google_analytics'] )
		{
			google_analytics_admin::update_options();

			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Settings saved.', 'google-analytics')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}

		$options = google_analytics::get_options();

		$ga_script = <<<EOF
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("{uacct}");
pageTracker._initData();
pageTracker._trackPageview();
} catch(err) {}</script>
EOF;

		if ( $options['uacct'] )
		{
			$ga_script = str_replace('{uacct}', $options['uacct'], $ga_script);
		}
		else
		{
			$ga_script = str_replace('{uacct}', __('Your Account ID'), $ga_script);
		}

		echo '<div class="wrap">' . "\n"
			. "<h2>" . __('Google Analytics Settings', 'google-analytics') . "</h2>\n"
			. '<form method="post" action="">' . "\n"
			. '<input type="hidden" name="update_google_analytics" value="1" />' . "\n";

		if ( function_exists('wp_nonce_field') ) wp_nonce_field('google_analytics');

		echo '<table class="form-table">' . "\n";
		
		echo '<tr valign="top">'
			. '<th scope="row">'
			. __('GA Script', 'google-analytics')
			. '</th>'
			. '<td>'
			. __('Paste the <b>ga.js</b> script from <a href="http://analytics.google.com">Google analytics</a> into the following textarea (<a href="http://www.google.com/support/googleanalytics/bin/answer.py?answer=55603">where do I find it?</a>):', 'google-analytics')
			. '<br />' ."\n"
			. '<textarea name="ga_script"'
					. ' class="code" cols="58" rows="12" style="width: 95%;"'
					. ' onfocus="var this_val=eval(this); this_val.select();"'
					. '>'
				. format_to_edit($ga_script)
			. '</textarea>'
			. '<p>'
			. __('<strong>Important</strong>: Use the new <strong>ga.js</strong> script, not the legacy urchin.js script.')
			. '</p>'
			. '</td>'
			. '</tr>';
		
		echo "</table>\n";
		
		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . attribute_escape(__('Save Changes')) . '"'
				. " />"
			. "</p>\n";

		echo "</form>\n";

		echo "<h3>" . __('Quick Reference', 'google-analytics') . "</h3>\n"
			. '<table class="form-table">';

		echo '<tr>'
			. '<th scope="row">'
			. '<p>' . 'Special Users' . '</p>'
			. '</th>'
			. '<td>';

		$str = <<<EOF
<p>Authors, editors and site admins are not tracked when logged in.</p>
EOF;

		echo __($str);
		
		echo '</td></tr>';

		echo '<tr>'
			. '<th scope="row">'
			. '<p>' . 'Special Urls' . '</p>'
			. '</th>'
			. '<td>';

		$str = <<<EOF
<p>The Google Analytics plugin tracks a few special use-cases differently, for quick reference:</p>
<ul>
<li><a href="http://www.semiologic.com/software/newsletter-manager/">Mailing list subscriptions</a> get tracked as /subscription/[url]</li>
<li>Search queries get tracked as /search/[keywords]</li>
<li>404 errors (page not found) get tracked as /404/[url]</li>
<li>File downloads get tracked as /file/[url]</li>
<li>Clicks on outbound links get tracked as /outbound/[url]</li>
</ul>
EOF;

		echo __($str);
		
		echo '</td></tr>';

		echo '<tr>'
			. '<th scope="row">'
			. '<p>' . 'Site Usage Patterns' . '</p>'
			. '</th>'
			. '<td>';

		$str = <<<EOF
<p>Make sure to spot Google Analytics's navigation summary and site overlay reports for individual urls. They're located to the right when you visit any url's statistics.</p>
<ul>
	<li>The first is a concise report. It lets you know, at a glance, where users came from upon entering the page, and where they left upon leaving it.</li>
	<li>The second is more graphical. It outputs your page with an overlay that shows where visitors are clicking.</li>
</ul>
EOF;

		echo __($str);
		
		echo '</td></tr>';

		echo '<tr>'
			. '<th scope="row">'
			. '<p>' . 'Advanced Tracking' . '</p>'
			. '</th>'
			. '<td>';

		$str = <<<EOF
<p>To insert extra site-wide tracking code, activate the script manager and, under Settings / Scripts, add the relevant lines that follow in the footer. Alternatively, edit a specific post or page and insert the lines in its footer scripts if this needs to be entry-specific.<p>

<p>Sample Extras:</p>
<ul>
	<li><a href="http://code.google.com/apis/analytics/docs/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._setSampleRate">Set the sampling rate</a> at 50% on a high traffic site (this speeds up GA reports quite a bit):<br />
	<textarea cols="58" rows="5" style="width: 95%;" class="code" onfocus="var this_val=eval(this); this_val.select();">
&lt;script type="text/javascript"&gt;
pageTracker._setSampleRate(50);
&lt;/script&gt;
</textarea>
	</li>
	<li><a href="http://code.google.com/apis/analytics/docs/gaTracking.html#Multiple">Share your stats</a> with your sales team's Google Analytics account:<br />
	<textarea cols="58" rows="5" style="width: 95%;" class="code" onfocus="var this_val=eval(this); this_val.select();">
&lt;script type="text/javascript"&gt;
var trackerSalesDept = _gat.getTracker("UA-12345-1");
trackerSalesDept._initData();
trackerSalesDept._trackPageview();
&lt;/script&gt;
</textarea>
	</li>
</ul>
<p>To insert extra page-specific tracking code, open "Footer" in the editor, and add the relevant lines. Examples:</p>
	<ul>
	<li><a href="http://code.google.com/apis/analytics/docs/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._setVar">Segment visitors</a> who visit your "Thank you for subscribing" page:<br />
	<textarea cols="58" rows="5" style="width: 95%;" class="code" onfocus="var this_val=eval(this); this_val.select();">
&lt;script type="text/javascript"&gt;
pageTracker._setVar("Prospect");
&lt;/script&gt;
</textarea>
	</li>
	<li><a href="http://code.google.com/apis/analytics/docs/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._setSessionTimeout">Increase the session duration</a> on a very long sales copy that visitors actually read:<br />
	<textarea cols="58" rows="5" style="width: 95%;" class="code" onfocus="var this_val=eval(this); this_val.select();">
&lt;script type="text/javascript"&gt;
pageTracker._setSessionTimeout(3600 * 2); // 2 hours
&lt;/script&gt;
</textarea>
	</li>
</ul>
EOF;

		echo __($str);
		
		echo '</td></tr>';

		echo '</table>';

		echo "</div>\n";
	} # display_options()
} # google_analytics_admin

google_analytics_admin::init();
?>