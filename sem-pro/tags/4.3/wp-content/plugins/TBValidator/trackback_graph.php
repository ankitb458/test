<?php

function trackback_graph($count) {
	global $wpdb, $table_prefix;
#echo "<span style=\"border: 1px solid #eee; \">";
	echo "<span>";
	$query = "SELECT * FROM $wpdb->comments WHERE comment_type='trackback' ORDER BY comment_date DESC LIMIT $count";
	$result = $wpdb->get_results($query, ARRAY_A);
	for($i=count($result)-1; $i>=0; $i--) {
		$row = $result[$i];
		if($row['comment_approved']=='spam') {
			$color='red';
			$direction="-1";
		} else {
			$color='blue';
			$direction='1';
		}

		$parent = basename(dirname(__FILE__));
		$mypath = get_option('siteurl') . '/wp-content/plugins/';

		if ($parent != 'plugins')
			$mypath .= $parent . '/';

		$spam = ($direction>0) ? "notspam" : "spam";
		echo "<img title=\"$row[comment_date]: $spam\"
			src=\"$mypath/tb_bar_$spam.png\" width=2
			style='height: 1em;' valign=baseline />";
	}
	echo "</span>";
}
?>
