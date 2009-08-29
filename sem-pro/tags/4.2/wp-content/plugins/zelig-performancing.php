<?php
/*
Plugin Name: Zelig Performancing
Plugin URI: http://zeligplanet.altervista.org/2006/03/wordpress-plugin-zelig-performancing/
Description: Adds the required script (enhanced version!) for performancing.com stats service to your pages footer.
Version: 1.12 (fork)
Author: Corrado "Zelig"
Author URI: http://zeligplanet.altervista.org

--------------------------------------------------

INSTRUCTIONS

1. Get an account - for free - on performancing.com!
2. Copy this file into the plugins directory in your WordPress installation (wp-content/plugins).
3. (VERSION 1.1 ONLY) in case, change the value of $track_admin variable if you want to exclude the count of administrator’s visits.
4. Log in to WordPress administration. Go to the Plugins page and Activate this plugin.

Works with WordPress 1.5 and 2.0

--------------------------------------------------

Copyright (C) 2006 Corrado "Zelig" (email: zeligplanet AT altervista.org)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
The license is also available at http://www.gnu.org/copyleft/gpl.html
*/

function zelig_perfo()
{

// ---------------------------------------------------------------------
// Set as you prefer the value of $track_admin variable below:
// TRUE = counts administrator's visits too
// FALSE = ignores administrator's visits (the script is not added to WP pages at all)

$track_admin = true;

// DON'T EDIT BELOW THIS LINE! (if you don't know what you're doing...)
// ---------------------------------------------------------------------

global $userdata;
if ($userdata) {
if ($track_admin==true) {
?>
<script type="text/javascript">
<?php
echo "z_user_name=\"" . wp_filter_post_kses(strip_tags($userdata->display_name)) . "\";\n";
echo "z_user_email=\"" . wp_filter_post_kses(strip_tags($userdata->user_email)) . "\";\n";
if(is_home()) {
	echo "z_post_title=\"Homepage\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_category()) {
	echo "z_post_title=\"Archive: ".single_cat_title('',false)."\";\n";
	echo "z_post_category=\"".single_cat_title('',false)."\";\n";
} elseif(is_author()) {
	echo "z_post_title=\"Archive:".str_replace('  ',' ',wp_title('',false))."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_day()) {
	echo "z_post_title=\"Archive: ".the_date('F jS, Y', '', '', false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_month()) {
	echo "z_post_title=\"Archive: ".the_date('F, Y', '', '', false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_year()) {
	echo "z_post_title=\"Archive: ".the_date('Y', '', '', false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_page()) {
	echo "z_post_title=\"Page: ".the_title('','',false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_search()) {
	echo "z_post_title=\"Search result\";\n";
	echo "z_post_category=\"\";\n";
} else {
	$ctg=get_the_category();
	echo "z_post_title=\"".the_title('','',false)."\";\n";
	echo "z_post_category=\"".$ctg[0]->cat_name."\";\n";
}
?></script>
<script id="stats_script" type="text/javascript" src="http://metrics.performancing.com/wp.js"></script>
<?php
}

} else {
?>
<script type="text/javascript">
<?php
if(is_home()) {
	echo "z_post_title=\"Homepage\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_category()) {
	echo "z_post_title=\"Archive: ".single_cat_title('',false)."\";\n";
	echo "z_post_category=\"".single_cat_title('',false)."\";\n";
} elseif(is_author()) {
	echo "z_post_title=\"Archive:".str_replace('  ',' ',wp_title('',false))."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_day()) {
	echo "z_post_title=\"Archive: ".the_date('F jS, Y', '', '', false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_month()) {
	echo "z_post_title=\"Archive: ".the_date('F, Y', '', '', false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_year()) {
	echo "z_post_title=\"Archive: ".the_date('Y', '', '', false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_page()) {
	echo "z_post_title=\"Page: ".the_title('','',false)."\";\n";
	echo "z_post_category=\"\";\n";
} elseif(is_search()) {
	echo "z_post_title=\"Search result\";\n";
	echo "z_post_category=\"\";\n";
} else {
	$ctg=get_the_category();
	echo "z_post_title=\"".the_title('','',false)."\";\n";
	echo "z_post_category=\"".$ctg[0]->cat_name."\";\n";
}
?></script>
<script id="stats_script" type="text/javascript" src="http://metrics.performancing.com/wp.js"></script>
<?php
}
}

add_filter('wp_footer', 'zelig_perfo');
?>