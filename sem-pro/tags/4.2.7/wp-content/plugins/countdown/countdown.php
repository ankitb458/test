<?php
/*
Plugin Name: Countdown
Plugin URI: http://redalt.com/wiki/Countdown
Description: Adds template tags to count down to a specified date.  <strong>Important:</strong> Edit your dates and settings on the Options | Countdown admin panel. The Plugin will not work as expected until you do.
Version: 2.0 (fork)
Author: Owen Winkler &amp; Denis de Bernardy
Author URI: http://www.asymptomatic.net
License: MIT License - http://www.opensource.org/licenses/mit-license.php
*/
/*
Countdown - Adds template tags to count down to a specified date

This code is licensed under the MIT License.
http://www.opensource.org/licenses/mit-license.php
Copyright (c) 2006 Owen Winkler

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the
Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to
do so, subject to the following conditions:

The above copyright notice and this permission notice shall
be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Fork since v.1.2, by Denis de Bernardy <http://www.semiologic.com>

- Widget support
- default options
- Mu compat
- Security fixes
- Revamp of admin interface
*/


function dtr_monthtonum($m)
{
	switch(strtolower(substr($m, 0, 3)))
	{
	case 'jan': return 1;
	case 'feb': return 2;
	case 'mar': return 3;
	case 'apr': return 4;
	case 'may': return 5;
	case 'jun': return 6;
	case 'jul': return 7;
	case 'aug': return 8;
	case 'sep': return 9;
	case 'oct': return 10;
	case 'nov': return 11;
	case 'dec': return 12;
	}
	return 0;
}

function dtr_weekdaytonum($m)
{
	switch(strtolower(substr($m, 0, 3)))
	{
	case 'mon': return 1;
	case 'tue': return 2;
	case 'wed': return 3;
	case 'thu': return 4;
	case 'fri': return 5;
	case 'sat': return 6;
	case 'sun': return 0;
	}
	return 0;
}

function dtr_xsttonum($x)
{
	switch(substr($x, 0, 1))
	{
	case '1': return 1;
	case '2': return 2;
	case '3': return 3;
	case '4': return 4;
	case '5': return 5;
	case 'l': return 6;
	}
}

function dtr_xst_weekday($index, $weekday, $month)
{
	$now = getdate();
	$year = $now['year'] + (($month<$now['mon'])? 1 : 0);

	$day = 1;
	$firstday = intval(date('w', mktime(0,0,0,$month, $day, $year)));

	$day += $weekday - $firstday;
	if($day <= 0) $day += 7;
	$index --;
	while($index > 0)
	{
		$day += 7;
		$index --;
		if(!checkdate($month, $day + 7, $year)) break;
	}
	return mktime(0, 0, 0, $month, $day, $year);
}

function dates_to_remember($showonly = -1, $timefrom = null, $startswith = '<li>', $endswith = '</li>', $paststartswith = '<li class="pastevent">', $pastendswith = '</li>')
{
	$options = get_settings('dtr_options');
	if(!is_array($options)) {
		$options['listformat'] = '<b>%date%</b> (%until%)<br />%event%';
		$options['dateformat'] = 'M j';
		$options['timeoffset'] = 0;
		update_option('dtr_options', $options);
	}

	$datefile = get_settings('countdown_datefile');

	if ( !$datefile )
	{
		$datefile = implode('', file(dirname(__FILE__) . '/default-dates.txt'));

		update_option('countdown_datefile', $datefile);
	}

	#echo '<pre>';
	#var_dump($datefile, get_settings('countdown_datefile'));
	#echo '</pre>';

	$dates = explode("\n", $datefile);
	$dtr = array();
	$dtrflags = array();

	if($timefrom == null) $timefrom = strtotime(date('Y-m-d', time() + ($options['timeoffset'] * 3600)));

	foreach($dates as $entry)
	{
		$entry = $entry;

		if(trim($entry) == '') continue;
		$flags = array();
		if(preg_match('/every ?(2nd|other|3rd|4th)? week (starting|from) ([0-9]{4}-[0-9]{2}-[0-9]{2})( until ([0-9]{4}-[0-9]{2}-[0-9]{2}))?[\\s]+(.*)/i', $entry, $matches))
		{
			switch($matches[1])
			{
			case '2nd':
			case 'other': $inc = 14; break;
			case '3rd': $inc = 21; break;
			case '4th': $inc = 28; break;
			default: $inc = 7;
			}
			$date_info = getdate(strtotime($matches[3]));
			$absday = ceil($date_info[0] / 86400);
			$today_info = getdate(time() + ($options['timeoffset'] * 3600));
			$todayday = ceil($today_info[0] / 86400);
			if($absday == $todayday)
			{
				$eventtime = $absday * 86400;
			}
			else
			{
				$chunk = ceil(($todayday - $absday) / $inc);
				$absday = $absday + ($chunk * $inc);
				$eventtime = $absday * 86400;
			}
			if($matches[5] != '')
			{
				$limit = strtotime($matches[5]);
				if($timefrom - 86400 > $limit) $eventtime = $limit;
			}
			$eventname = $matches[6];
		}
		else if(preg_match('/easter[\\s]+(.*)/i', $entry, $matches) && function_exists('easter_date')) {
			$eventtime = easter_date(intval(date('Y')));
			if($eventtime < time()) $eventtime = easter_date(intval(date('Y')) + 1);
			$eventname = $matches[1];
		}
		else if(preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})[\\s]+(?:through|thru)[\\s+]([0-9]{4}-[0-9]{2}-[0-9]{2})[\\s]+(.*)/i', $entry, $matches))
		{
			$eventtime = strtotime($matches[1]);
			$eventname = $matches[3];
		}
		else if(preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})[\\s]+(.*)/i', $entry, $matches))
		{
			$eventtime = strtotime($matches[1]);
			$eventname = $matches[2];
		}
		else if(preg_match('/([0-9]{2}-[0-9]{2})[\\s]+(.*)/i', $entry, $matches))
		{
			$eventtime = strtotime(date('Y', time() + ($options['timeoffset'] * 3600)).'-'.$matches[1]);
			if($timefrom > $eventtime) $eventtime = strtotime(date('Y', time() + 31536000).'-'.$matches[1]);
			$eventname = $matches[2];
		}
		else if(preg_match('/(1st|2nd|3rd|4th|5th|last)[\\s]+(mon(?:day)?|tue(?:sday)?|wed(?:nesday)?|thu(?:rsday)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)[\\s]+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?|all)(.*)/i', $entry, $matches))
		{
			$eventname = $matches[4];
			$xst = dtr_xsttonum($matches[1]);
			$day = dtr_weekdaytonum($matches[2]);
			if($matches[3] == 'all')
			{
				$month = dtr_monthtonum(date('M', $timefrom));
				$eventtime = dtr_xst_weekday($xst, $day, $month);
				if($eventtime < $timefrom)
				{
					$zero_hour = getdate($timefrom);
					$month = dtr_monthtonum(date('M', mktime(0,0,0, ($zero_hour['mon'] % 12) + 1, 1, $_zero_hour['year'])));
					$eventtime = dtr_xst_weekday($xst, $day, $month);
				}
			}
			else
			{
				$month = dtr_monthtonum($matches[3]);
				$eventtime = dtr_xst_weekday($xst, $day, $month);
			}
		}
		else
		{
			continue;
		}

		if(preg_match('/^the[\\s]+(mon(?:day)?|tue(?:sday)?|wed(?:nesday)?|thu(?:rsday)?|fri(?:day)?|sat(?:urday)?|sun(?:day)?)[\\s]+(before|after)/i', $entry, $matches)) {
			switch($matches[2]) {
				case 'before': $direction = 'last'; break;
				case 'after': $direction = 'next'; break;
			}
			$eventtime = strtotime("{$direction} {$matches[1]}", $eventtime);
		}
		if(preg_match('/^([0-9]+)[\\s]+(days?|weeks?|months?)[\\s]+(before|after)/i', $entry, $matches)) {
			switch($matches[3]) {
				case 'before': $direction = '-'; break;
				case 'after': $direction = '+'; break;
			}
			$amount = intval($matches[1]);
			switch($matches[2]) {
			case 'week':
			case 'weeks':
				$unit = "weeks";
				break;
			case 'day':
			case 'days':
				$unit = "days";
				break;
			case 'month':
			case 'months':
				$unit = "months";
				break;
			}
			$eventtime = strtotime("{$direction}{$amount} {$unit}", $eventtime);
		}

		$flags = array();
		$eventname = preg_replace('/%(.*?)%/e', '($flags[]="\\1")?"":""', $eventname);

		if($timefrom <= $eventtime)
		{
			while(isset($dtr[$eventtime]) && $dtr[$eventtime] != $eventname) $eventtime ++;
			$dtr[$eventtime] = $eventname;
			$dtrflags[$eventtime] = $flags;
		}
	}
	ksort($dtr);

	foreach($dtr as $eventtime => $event)
	{
		$do_daystil = !in_array('nocountdown', $dtrflags[$eventtime]);
		countdown_days($event, date('Y-m-d', $eventtime), $startswith, $endswith, $paststartswith, $pastendswith, $do_daystil);
		$showonly --;
		if($showonly == 0) break;
	}
}

function countdown_days($event, $date, $startswith = '', $endswith = '', $paststartswith = '', $pastendswith = '', $do_daystil = true) {
	$options = get_settings('dtr_options');

	$until = intval((strtotime($date) - strtotime(date('Y-m-d', time() + ($options['timeoffset'] * 3600)))) / 86400);
	$remaining = '';
	if($until >= 0) {
 		echo $startswith;
		$date_format = $options['dateformat'];
		$date_str = date($date_format, strtotime($date));
		if($do_daystil)
		{
			switch($until)
			{
			case 0: $remaining = 'Today'; break;
			case 1: $remaining = '1 day'; break;
			default: $remaining = "{$until} days"; break;
			}
		}
		echo str_replace(array('%date%', '%event%', '%until%', '%untilnum%'), array($date_str, $event, $remaining, $until), $options['listformat']);
		echo $endswith;
	}
	else
	{
 		echo $paststartswith;
		$date_format = $options['dateformat'];
		$date_str = date($date_format, strtotime($date));
		if($do_daystil)
		{
			switch(abs($until))
			{
			case 1: $remaining = '1 day ago'; break;
			default: $remaining = "{$until} days ago"; break;
			}
		}
		echo str_replace(array('%date%', '%event%', '%until%', '%untilnum%'), array($date_str, $event, $remaining, $until), $options['listformat']);
		echo $pastendswith;
	}
}

function dtr_admin_menu()
{
	add_management_page('Events', 'Events', 'edit_pages', basename(__FILE__), 'dtr_management_page');
	add_options_page('Countdown', 'Countdown', 'manage_options', basename(__FILE__), 'dtr_options_page');
}

function dtr_management_page()
{
	$datefile = get_settings('countdown_datefile');

	if ( !$datefile )
	{
		$datefile = implode('', file(dirname(__FILE__) . '/default-dates.txt'));

		update_option('countdown_datefile', $datefile);
	}

	#echo '<pre>';
	#var_dump(get_settings('countdown_datefile'), $_POST['dates']);
	#echo '</pre>';

	if (isset($_POST['action']) && $_POST['action'] == 'update_countdown') {
		if (get_magic_quotes_gpc()) {
			$_POST = array_map('stripslashes', $_POST);
		}

		$datefile = stripslashes(wp_filter_post_kses($_POST['dates']));

		update_option('countdown_datefile', $datefile);

		echo '<div id="message" class="updated fade"><p><strong>Options Updated.</strong></p></div>';
	}
	?>	<div class="wrap">
	<h2>Countdown Events</h2>
	<p>Countdown shows the next few events that are scheduled in your dates list, and provides very flexible recurring date settings.</p>
	<form action="" method="post" id="countdown_events">
		<input type="hidden" name="action" value="update_countdown" />
		<h3>Dates</h3>
		<p>This is a list of events that will be used for output. You can add new events manually, or use the form
		underneath this field to add events.</p>
		<textarea style="width:100%;height:200px;" name="dates" id="dates"><?php echo htmlspecialchars($datefile); ?></textarea>
		<p class="submit"><input type="submit" name="Submit" value="Submit" /></p>
	</form>
	</div>
	<div class="wrap">
		<h2>Add an Event</h2>
		<p>Use this form to add an event. Choose the type of event, fill out the attributes, and click "Create".</p>
		<p>Don't forget to submit the changes to your options (including the new events you've added) after you've created a new event with this form.</p>

<script type="text/javascript">
function $d(e)
{
	return document.getElementById(e);
}
function newevent()
{
	var event = '';
	if($d('net1').checked) {
		event = 'every ' + $d('t1freq').value + ' week from ' + $d('t1yearstart').value + '-' + $d('t1monthstart').value + '-' + $d('t1daystart').value;
		if($d('t1end').checked) event = event + ' until ' + $d('t1yearend').value + '-' + $d('t1monthend').value + '-' + $d('t1dayend').value;
	}
	if($d('net2').checked) {
		event = $d('t2year').value + '-' + $d('t2month').value + '-' + $d('t2day').value;
	}
	if($d('net3').checked) {
		event = $d('t3month').value + '-' + $d('t3day').value;
	}
	if($d('net4').checked) {
		event = $d('t4freq').value + ' ' + $d('t4day').value + ' ' + $d('t4month').value;
	}
	if(event == '') {
		alert('You need to select one of the options to specify the type of event to create.');
		return;
	}
	if($d('neweventname').value == '') {
		alert('You did not set an event name.  Set the event name at the top of the form.');
		return;
	}
	event += ' ' + $d('neweventname').value;
	//if(confirm('Add this event:\n' + event)) {
		$d('dates').value = $d('dates').value + '\n' + event;
		// alert('You must submit the options form to save this event.');
		$d('countdown_events').submit();
	//}
}
</script>
		<div style="border:1px solid #999999;padding:5px;">
			<p>Event Name: <input type="text" id="neweventname" />

			<div style="border:1px solid #cccccc;margin-top:5px;">
				<h4><label><input id="net2" type="radio" value="t2" name="eventtype" /> On a specific date</label></h4>
				<p>Date:
				  Year:<input type="text" id="t2year" value="<?php echo date('Y'); ?>" size="4"/>
				  Month:<input type="text" id="t2month" value="<?php echo date('m'); ?>" size="4"/>
				  Day:<input type="text" id="t2day" value="<?php echo date('d'); ?>" size="4"/></p>
			</div>
			<div style="border:1px solid #cccccc;margin-top:5px;">
				<h4><label><input id="net3" type="radio" value="t3" name="eventtype" /> Same day every year</label></h4>
				<p>Date:
				  Month:<input type="text" id="t3month" value="<?php echo date('m'); ?>" size="4"/>
				  Day:<input type="text" id="t3day" value="<?php echo date('d'); ?>" size="4"/></p>
			</div>
			<div style="border:1px solid #cccccc;margin-top:5px;">
				<h4><label><input id="net4" type="radio" value="t4" name="eventtype" /> <i>X</i>th weekday of a specific month</label></h4>
				<p>Which incident: <select id="t4freq"><option value="1st">1st</option><option value="2nd">2nd</option><option value="3rd">3rd</option><option value="4th">4th</option><option value="5th">5th</option><option value="last">last</option></select></p>
				<p>Which weekday: <select id="t4day"><option value="mon">Monday</option><option value="tue">Tuesday</option><option value="wed">Wednesday</option><option value="thu">Thursday</option><option value="fri">Friday</option><option value="sat">Saturday</option><option value="sun">Sunday</option></select></p>
				<p>Which month: <select id="t4month"><option value="jan">January</option><option value="feb">February</option><option value="mar">March</option><option value="apr">April</option><option value="may">May</option><option value="jun">June</option><option value="jul">July</option><option value="aug">August</option><option value="sep">September</option><option value="oct">October</option><option value="nov">November</option><option value="dec">December</option><option value="any">Any</option></select></p>
			</div>
			<div style="border:1px solid #cccccc;margin-top:5px;">
				<h4><label><input id="net1" type="radio" value="t1" name="eventtype" /> Repeating every <i>X</i> weeks</label></h4>
				<p>Repeating: <select id="t1freq"><option value="2nd">Every 2nd week</option><option value="3rd">Every 3rd week</option><option value="4th">Every 4th week</option></select></p>
				<p>Starting:
				  Year:<input type="text" id="t1yearstart" value="<?php echo date('Y'); ?>" size="4"/>
				  Month:<input type="text" id="t1monthstart" value="<?php echo date('m'); ?>" size="4"/>
				  Day:<input type="text" id="t1daystart" value="<?php echo date('d'); ?>" size="4"/></p>
				<p>Ending (<label><input type="checkbox" id="t1end" />Use End Date?</label>):
				  Year:<input type="text" id="t1yearend" value="<?php echo date('Y'); ?>" size="4"/>
				  Month:<input type="text" id="t1monthend" value="<?php echo date('m'); ?>" size="4"/>
				  Day:<input type="text" id="t1dayend" value="<?php echo date('d'); ?>" size="4"/></p>
			</div>

			<p class="submit"><input type="submit" value="Create" onclick="newevent();return false;" /></p>
		</div>
	</div>
	<div class="wrap">
	<h2>Sample</h2>
	<p>Here is a sample output of Countdown, called as <code>&lt;?php dates_to_remember(10); ?&gt;</code>:</p>
	<ul>
	<?php dates_to_remember(10); ?>	</ul>
	</div>
	<?php
}

function dtr_options_page()
{
	if (isset($_POST['Submit'])) {
		if (get_magic_quotes_gpc()) {
			$_POST = array_map('stripslashes', $_POST);
		}

		$listformat = trim(stripslashes(wp_filter_post_kses($_POST['listformat'])));
		$dateformat = trim(stripslashes(wp_filter_post_kses(strip_tags($_POST['dateformat']))));
		$timeoffset = intval($_POST['timeoffset']);

		$options = array(
			'listformat' => $listformat,
			'dateformat' => $dateformat,
			'timeoffset' => $timeoffset
			);
		update_option('dtr_options', $options);
		echo '<div id="message" class="updated fade"><p><strong>Options Updated.</strong></p></div>';
	}
	$options = get_settings('dtr_options');
	if(!is_array($options)) {
		$options['listformat'] = '<b>%date%</b> (%until%)<br />%event%';
		$options['dateformat'] = 'M j';
		$options['timeoffset'] = 0;
	}
	?>	<div class="wrap">
	<h2>Countdown Options</h2>
	<p>Countdown shows the next few events that are scheduled in your dates list, and provides very flexible recurring date settings.</p>
	<form method="post">
		<h3>Event List</h3>
		<p>Each entry in the output will appear inside a &lt;li&gt;&lt;/li&gt; in the format specified here.</p>
		<p>List format: <input type="text" name="listformat" value="<?php echo htmlspecialchars($options['listformat'], ENT_QUOTES); ?>" /></p>
		<p>Use these tags to output special values:</p>
		<ul>
			<li>%date% - Outputs the event date in the format specified below.</li>
			<li>%event% - Outputs the event name.</li>
			<li>%until% - Outputs the days remaining until that event.</li>
		</ul>

		<h3>Date Format</h3>
		<p>When outputting the date, you may want to output the month name or just a number.
		Use this setting to select the style you prefer.  Use the <a href="http://php.net/date">PHP date format strings</a> to
		set this.</p>
		<p>Date format: <input type="text" name="dateformat" value="<?php echo htmlspecialchars($options['dateformat'], ENT_QUOTES); ?>" /></p>

		<h3>The Time Is</h3>
		<p>Countdown thinks that the current date/time is <?php echo date('Y-m-d h:i:s a', time() + ($options['timeoffset'] * 3600)); ?>.</p>
		<input type="hidden" name="timeoffset" value="0" />

		<p>If that seems awfully incorrect, you might check out <a href="<?php echo trailingslashit(get_settings('siteurl')); ?>wp-admin/options-general.php">your timezone settings</a> in WordPress, or the system time on your server.</p>

		<p class="submit"><input type="submit" name="Submit" value="Submit" /></p>
	</form>
	</div>
	<?php
}

add_action('admin_menu', 'dtr_admin_menu');



function countdown_widget_init()
{
	if ( !function_exists('register_sidebar_widget') ) return;

	function countdown_widget($args)
	{
		extract($args);
		$options = get_settings('countdown_widget');


		$options['number'] = $options['number'] ? $options['number'] : 5;

		echo $before_widget
			. $before_title
			. ( ( isset($options['title']) && $options['title'] )
				? $options['title']
				: __('Upcoming Events')
				)
			. $after_title
			. '<ul>';

			dates_to_remember($options['number']);
		echo '</ul>'
			. $after_widget;
	}

	function countdown_widget_control()
	{
		$options = get_settings('countdown_widget');

		if ( $_POST["countdown_widget_update"] )
		{
			$new_options = $options;

			$new_options['title'] = strip_tags(stripslashes($_POST["countdown_widget_title"]));
			$new_options['number'] = intval($_POST["countdown_widget_number"]);

			if ( $options != $new_options )
			{
				$options = $new_options;

				update_option('countdown_widget', $options);
			}
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$number = $options['number'] ? $options['number'] : '';

		?>			<ul>
				<li><label for="countdown_widget_title">Title: <input type="text" id="countdown_widget_title" name="countdown_widget_title" value="<?php echo $title ?>" /></label></li>
				<li><label for="countdown_widget_number">Number: <input type="text" id="countdown_widget_number" name="countdown_widget_number" value="<?php echo $number ?>" /></label></li>
			</ul>
			<input type="hidden" id="countdown_widget_update" name="countdown_widget_update" value="1" />
		<?php
	}

	register_sidebar_widget('Countdown', 'countdown_widget' );
	register_widget_control('Countdown', 'countdown_widget_control');
}

add_action('plugins_loaded', 'countdown_widget_init');
?>