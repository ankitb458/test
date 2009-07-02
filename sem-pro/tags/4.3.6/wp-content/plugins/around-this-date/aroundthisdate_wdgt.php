<?php
/*
Plugin Name: Around this date in the past... - Widget Edition
Plugin URI: http://www.junyent.org/blog/2006/05/20/around-this-date-in-the-past-wordpress-widget/
Description: Widget that shows entries/posts around this date in the past (if they exist)
Version: 0.7.2 (fork)
Author: Joan Junyent Tarrida
Contributors: jjunyent, Mike Koepke, Cinefilo
Author URI: http://www.junyent.org/

[ Notas en castellano en la web ]  [  Notes en català a la web ]

== English notes ==

This plugin is based on:
	- "One year ago" plugin released by Borja Fernández.  [  http://www.lamateporunyogur.net/wp-plugins/one-year-ago/  ]  [  http://www.lamateporunyogur.net  ] 
	- "Wayback" plugin released by Chris Goringe.  [  http://code.goringe.net/WordPress/ ]  [  http://tis.goringe.net  ]  
	- "Wayback Widget" released by Sven Weidauer. [ http://dergraf.net/computer/wordpress/wayback-widget/  ] [ http://dergraf.net/  ]
	- My plugin Around this date in the past. [ http://www.junyent.org/blog/arxius/2006/05/20/around-this-date-in-the-past-wordpress-plugin/  ] 

Description

	It shows around this date entries/posts in the past (if they exist). By default it retreives a week around the current day X years ago.

Parameters
	$title = Title of the widget. By default "This week last year...: "
	$daysbefore = Days' posts that will show before one year ago. By default '3' (3 days before)
	$daysafter = Days' posts that will show after one year ago. By default '3' (3 days after)
	$mode = Select the mode that you want the widget to work. By default '1' (X years ago)
		Mode 1: get posts around this date from X years ago.
		Mode 2: get posts around this date for the last X years.
		Mode 3: get posts around this date since year X.
	$yearsago = It shows 'X' years ago posts. By default '1' (1 year). ONLY IF MODE 1 IS SELECTED.
	$lastxyears = It shows posts por the last "X" years. By default '1' (1 year). ONLY IF MODE 2 IS SELECTED.
	$sinceyear = It shows posts since the year "X". By default '2005' (since year 2005). ONLY IF MODE 3 IS SELECTED.
	$limit = Number of posts to retrieve. By default '4'.
	$none = Text shown when there are no posts. By default 'none'.
	$showdate = Show dates next to the links. By default unchecked.
	$dateformat = Format of the date displayed next to the links (if checked). See http://www.php.net/date 
	$showexcerpt = Show the excerpt next to the links. By default unchecked.		 
 

Use

	You can set the parameters from the widgets panel
		
Customize display

In adition to the options avaliable from the widget control panel you can highly customize the apearance of the output by using CSS. These are the classes avaliable:
	ul.atd-list {} // base list container.
	li.atd-year {} // Yearly headers.
		atd-yXXXX {} //yearly based class, eg. atd-y2006, atd-y2005 ...
	ul.atd-yearlylist {} // list container for each year around this date posts.
	li.atd-entry {} // list items containing each around this date posts.
		atd-yXXXX {} //yearly based class, eg. atd-y2006, atd-y2005 ...
		atd-mXX {} //montly based class, eg. atd-m01, atd-m02 ... atd-m12.
		atd-dXX {} //dayly based class, eg. adt-d01, atd-d02 ... atd-d31.
		atd-XXXXXXXX {} //date based class, eg. atd-20061205, atd-20050304 ...
	a.atd-entry-title {} // Link to the post.
	.atd-entry-date {} // span containing the date, if $showdate enabled.
	.atd-entry-excerpt {} // span containing the excerpt, if $showexcerpt enabled.

			
	
License

	This plugin is free software; you can redistribute it and/or modify (without commercial purposes) it under the terms of the Creative Commons License (don't remove credits to author, please)
	You can view the full text of the license here: http://creativecommons.org/licenses/by-nc/2.5/

	
Installing

	1. Once downloaded the file, change the file extension from .phps to .php
	2. Upload it through FTP to the server where your Wordpress blog is hosted.
	3. Copy it to the folder /wp-content/plugins/ (you can also copy it to /wp-content/plugins/widgets/ in order to have all the widegts together) .
	4. Activate it through the plugin management screen.
	5. Go to "Themes" > "Sidebar Widgets" and drag and drop the widget wherever you want to show it.

Changelog
	v.0.7 = All displayed in a single list with yearly sublists.
			NEW Feature: display date and/or excerpt.
			NEW Feature: added CSS markup to allow maximum flexibility on display.	
	v.0.6.3 = Fixed a bug when calling get_old_posts().
	v.0.6.2 = Fixed a bug when having  &amp; in post's title. Isolated repetitive code in its own function. Changes contributed by Mike Koepke. 
	v.0.6.1 = Fixed bug when having quotes in post's title. Thanks to Mike Koepke for bug repporting and helping with fixing.
	v0.6 = NEW Feature: added 2 new modes, last X years and since year X. 
		Corrected bug in $start_ago and $end_ago.
	v0.5.2 = XHTML valid. Luís Pérez (aka Cinéfilo)
	v0.5.1 = Now the $yearsago parameter works.
	v0.5.01 = Now the limit option works, for real ;)
	v0.5 = First release. I decided to start on version 0.5 because of preserving version relation with "One year ago" plugin.

Thanks

	Thanks to Borja Fernandez, Chris Goringe and Sven Weidauer for writing their plugins and to Luis P�rez and Mike Koepke for their contributions.
*/

function widget_atd_init()
{
	if (!function_exists('register_sidebar_widget') || !function_exists('register_widget_control')) 
		return;
		
	function around_this_date( $daysbefore, $daysafter, $mode, $yearsago, $lastxyears, $sinceyear, $limit, $none, $showyear, $showdate, 
		$dateformat, $showexcerpt) 
	{
		$outputlist = ''; // empty the 'outputlist' string
		$outputlist .= '<ul class="atd-list">';
			
		switch ($mode) 
		{
		case 1: // "classic" mode
			$start_ago = (365*$yearsago)+$daysbefore;
			$end_ago = (365*$yearsago)-$daysafter;
			$year = date("Y")-$yearsago;
			
			$liststart = '';
			$listend = '';
			
			if ($showyear)
			{
				$liststart = '<li class="atd-year">'.$year;
				$listend = '</li>';
			}

			$outputlist .= $liststart;
			$outputlist .= get_old_posts( $start_ago, $end_ago, $limit, $none, $showyear, $showdate, $dateformat, $showexcerpt);
			$outputlist .= $listend;
			
			break;
			
		case 2:  // last x years mode
			for($year = 1; $year <= $lastxyears; $year++) 
			{
				$start_ago = (365*$year)+$daysbefore;
				$end_ago = (365*$year)-$daysafter;
				
				$liststart = '';
				$listend = '';
			
				if ($showyear)
				{
					$liststart = '<li class="atd-year atd-'.(date("Y")-$year).'">'.(date("Y")-$year);
					$listend = '</li>';
				}

				$outputlist .= $liststart;
				$outputlist .= get_old_posts( $start_ago, $end_ago, $limit, $none, $showyear, $showdate, $dateformat, $showexcerpt);
				$outputlist .= $listend;				
			}
			break;
			
		case 3: // since year x mode
			for($year = 1; $year <= (date("Y")-$sinceyear); $year++) 
			{
				$start_ago = (365*$year)+$daysbefore;
				$end_ago = (365*$year)-$daysafter;
				
				$liststart = '';
				$listend = '';
				
				if ($showyear)
				{
					$liststart = '<li class="atd-year atd-'.(date("Y")-$year).'">'.(date("Y")-$year);
					$listend = '</li>';
				}
				
				$outputlist .= $liststart;
				$outputlist .= get_old_posts( $start_ago, $end_ago, $limit, $none, $showyear, $showdate, $dateformat, $showexcerpt);
				$outputlist .= $listend;				
			}
			break;
		}
		
		$outputlist .= '</ul>';
		echo "$outputlist";
	}

	function get_old_posts( $start_ago, $end_ago, $limit, $none, $showyear, $showdate, $dateformat, $showexcerpt )
	{
		global $wpdb;
		global $wp_version;		

		$q = "SELECT ID, post_title, post_date, post_excerpt, post_content FROM $wpdb->posts ";
		$q .= " WHERE post_status = 'publish'";
		if (version_compare($wp_version, '2.1', '>=')) 
			$q .= " AND post_type = 'post'";		
		$q .= " AND (( TO_DAYS( NOW() ) - TO_DAYS( post_date ) ) BETWEEN " . $end_ago . " AND " . $start_ago . ")";
		$q .= " ORDER BY post_date ASC";
		$q .= " LIMIT $limit";
		
		$entries = $wpdb->get_results($q);
		
		$output = ''; // empty the 'output' string
								
		if ($showyear)
			$output .= '<ul class="atd-yearlylist">';
			
		if (!empty($entries)) 
		{
			foreach ($entries as $entry) 
			{
				$title = str_replace('"', '',$entry->post_title);
				$title = htmlspecialchars($title);
				
				if($showdate) 
					$postdate = '<span class="atd-entry-date"> - ' .mysql2date($dateformat,$entry->post_date). '</span>'; 
				else 
					$postdate = ''; 	
				
				if($showexcerpt) 
				{ 
					if (empty($entry->post_excerpt)) 
					{
							$entry->post_excerpt = explode(" ",strrev(substr(strip_tags($entry->post_content), 0, 100)),2);
							$entry->post_excerpt = strrev($entry->post_excerpt[1]);
							$entry->post_excerpt.= " [...]";
					}
					$postexcerpt = htmlspecialchars($entry->post_excerpt);
					$postexcerpt = '<br /><span class="atd-entry-excerpt">' .$postexcerpt. '</span>'; 
				} 
				else 
					$postexcerpt = ''; 	
				
				$classes = ' atd-y'. mysql2date('Y',$entry->post_date). ' atd-m'. mysql2date('m',$entry->post_date). ' atd-d' .
					mysql2date('d',$entry->post_date) . ' atd-' . mysql2date('Ymd',$entry->post_date) . '';	
				$output .= '<li class="atd-entry'. $classes. '"><a class="atd-entry-title" href="' . get_permalink($entry->ID) . 
					'" rel="bookmark" title="Permanent link to ' . $title . '">' . htmlspecialchars($entry->post_title) . '</a> ' . 
					$postdate . ' ' . $postexcerpt . '</li>';
			}
		}
		else 
		{
			$output .= '<li>' . $none. '</li>';	
		}
		
		if ($showyear)		
			$output .=  '</ul>';
		return $output;
	}

	function widget_atd_options() 
	{
		$defaults = array( 
			'title' => 'This week last year...',
			'daysbefore' => 3, 
			'daysafter' => 3, 
			'mode' => 1, 
			'yearsago' => 1, 
			'lastxyears' => 1, 
			'sinceyear' => 2006, 
			'limit' => 4, 
			'none' => 'none', 
			'showyear' => true,
			'showdate' => false, 
			'dateformat' => 'F j', 
			'showexcerpt' => false 
		);

		$options = (array) get_option('widget_atd');
	
		foreach ( $defaults as $key => $value )
			if ( !isset($options[$key]) )
				$options[$key] = $defaults[$key];

	    return $options;
	}
	
	function widget_atd_display( $args )
	{
		extract( $args );
		$options = widget_atd_options();
				
		echo $before_widget . $before_title . $options['title'] . $after_title;
		around_this_date( $options['daysbefore'], $options['daysafter'], $options['mode'], $options['yearsago'], $options['lastxyears'], 
			$options['sinceyear'], $options['limit'], $options['none'], $options['showyear'], $options['showdate'], $options['dateformat'], 
			$options['showexcerpt'] );
		echo $after_widget;
	}
	
	function widget_atd_control()
	{
		$options = $newoptions = widget_atd_options();
			
		if ($_POST['atd-submit']) 
		{
			$newoptions['title'] = strip_tags(stripslashes($_POST['atd-title']));
			$newoptions['daysbefore'] = strip_tags(stripslashes($_POST['atd-daysbefore']));
			$newoptions['daysafter'] = strip_tags(stripslashes($_POST['atd-daysafter']));
			$newoptions['mode'] = strip_tags(stripslashes($_POST['atd-mode']));
			$newoptions['yearsago'] = strip_tags(stripslashes($_POST['atd-yearsago']));
			$newoptions['sinceyear'] = strip_tags(stripslashes($_POST['atd-sinceyear']));
			$newoptions['lastxyears'] = strip_tags(stripslashes($_POST['atd-lastxyears']));
			$newoptions['limit'] = strip_tags(stripslashes($_POST['atd-limit']));
			$newoptions['none'] = strip_tags(stripslashes($_POST['atd-none']));
			$newoptions['showyear'] = isset($_POST['atd-showyear']);
			$newoptions['showdate'] = isset($_POST['atd-showdate']);
			$newoptions['dateformat'] = strip_tags(stripslashes($_POST['atd-dateformat']));
			$newoptions['showexcerpt'] = isset($_POST['atd-showexcerpt']);
		}

		if ( $options != $newoptions ) 
		{
			$options = $newoptions;
			update_option('widget_atd', $options);
		}
		
		$title = attribute_escape($options['title']);
		$daysbefore = (int) $options['daysbefore'];
		$daysafter = (int) $options['daysafter'];
		$mode = (int) $options['mode'];
		$yearsago = (int) $options['yearsago'];
		$sinceyear = (int) $options['sinceyear'];
		$lastxyears = (int) $options['lastxyears'];
		$limit = (int) $options['limit'];
		$none = attribute_escape($options['none']);
		$showyear = ($options['showyear'] ? 'checked="checked"' : '');
		$showdate = ($options['showdate'] ? 'checked="checked"' : '');
		$dateformat = attribute_escape($options['dateformat']);
		$showexcerpt = ($options['showexcerpt'] ? 'checked="checked"' : '');

	?>
		<fieldset>
				<legend>Basic options:</legend>
				<p style="text-align:left;margin-left:30px;">				
				<label style="line-height:25px;">Title: <input type="text" id="atd-title" name="atd-title" size="45" value="<?php echo $title ?>" /></label><br />
				<label style="line-height:25px;">Days before: <input type="text" id="atd-daysbefore" name="atd-daysbefore" size="2" value="<?php echo $daysbefore ?>" /></label><br />
				<label style="line-height:25px;">Days after: <input type="text" id="atd-daysafter" name="atd-daysafter" size="2" value="<?php echo $daysafter ?>" /></label><br />
				<label style="line-height:25px;">Limit of entries: <input type="text" id="atd-limit" name="atd-limit" size="2" value="<?php echo $limit ?>" /></label><br />
				<label style="line-height:25px;">Text when no entries: <input type="text" id="atd-none" name="atd-none" size="35" value="<?php echo $none ?>" /></label><br />
				</p>
				<label>Select Mode: <select id="atd-mode" name="atd-mode"><?php for ( $i = 1; $i <= 3; ++$i ) echo "<option value='$i' ".($mode==$i ? "selected='selected'" : '').">$i</option>"; ?></select><label>
					<ul>
						<li>mode 1: get posts around this date from X years ago.</li>
						<li>mode 2: get posts around this date from the last X years.</li>
						<li>mode 3: get posts around this date since year X.</li>
					</ul>
		</fieldset>	
		<fieldset>
			<legend>Mode Options:</legend>
			<ul>
				<li><label>X Years ago &nbsp;&nbsp;(only if mode 1 is selected) : <input type="text" id="atd-yearsago" name="atd-yearsago" size="1" value="<?php echo $yearsago ?>" /></label></li>
				<li><label>Last X years &nbsp;(only if mode 2 is selected) : <input type="text" id="atd-lastxyears" name="atd-lastxyears" size="1" value="<?php echo $lastxyears ?>" /></label></li>
				<li><label>Since year X &nbsp;(only if mode 3 is selected) : <input type="text" id="atd-sinceyear" name="atd-sinceyear" size="4" value="<?php echo $sinceyear ?>" /></label></li>
			</ul>
		</fieldset>		
		<fieldset>
			<legend>Advanced options:</legend>
			<p style="text-align:left;margin-left:30px;">				
			<label style="line-height:25px;">Show excerpt: <input type="checkbox" id="atd-showexcerpt" name="atd-showexcerpt" value="<?php echo $showexcerpt ?>" /></label><br />
			<label style="line-height:25px;">Show year subtitle: <input type="checkbox" id="atd-showyear" name="atd-showyear" value="<?php echo $showyear ?>" /></label><br />
			<label style="line-height:25px;">Show post date: <input type="checkbox" id="atd-showdate" name="atd-showdate" value="<?php echo $showdate ?>" /></label><br />
			<label style="line-height:25px;">Date format: <input type="text" id="atd-dateformat" name="atd-dateformat" size="25" value="<?php echo $dateformat ?>" /></label> See <a href="http://www.php.net/date" title="PHP Manual: Date">PHP Date manual</a><br />
			</p>
		</fieldset>							
		<input type="hidden" id="atd-submit" name="atd-submit" value="1" />
	<?php
	}
	
	register_sidebar_widget( 'Around this date', 'widget_atd_display' );
	register_widget_control( 'Around this date', 'widget_atd_control', 475, 570 );
}

add_action( 'plugins_loaded', 'widget_atd_init' );

?>