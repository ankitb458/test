<?php
/*
Plugin Name: WP-Autoblog
Plugin URI: http://www.elliottback.com/wp/
Description: Given RSS Feeds, automatically makes posts to your blog
Version: 1.2 (fork)
Author: Elliott Back
Author URI: http://www.elliottback.com/

NOTE:  Uses WP-Cron and Magpie RSS
*/

require_once(dirname(dirname(__FILE__)) . '/wp-cron/wp-cron.php');
require_once(dirname(__FILE__) . '/rss_fetch.inc');
define('MAGPIE_FETCH_TIME_OUT', 60);
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
define('MAGPIE_CACHE_DIR', ABSPATH . 'wp-content/cache/rss');
if ( !file_exists(MAGPIE_CACHE_DIR) )
{
	if ( !@mkdir(MAGPIE_CACHE_DIR, 0777) )
	{
		function wp_mapgie_err()
		{
			echo '<div class="error"><b>The WP Autoblog plugin requires that your wp-content/cache folder be writable by the server</b></div>';
		}

		add_action('admin_menu', 'wp_mapgie_err');
	}
}


/****
 * 1) INSTALLATION
 */

// installation
function wp_autoblog_install() {
	add_option('wp_autoblog_feeds', '');
	add_option('wp_autoblog_full', 0);
	add_option('wp_autoblog_attribution', 1);
	add_option('wp_autoblog_replace', '');
}

if (isset ($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('init', 'wp_autoblog_install');
}

/****
 * 2) DAILY USE
 */

add_action ('wp_cron_15', 'wp_autoblog');
#add_filter ('the_content', 'wp_autoblog_comment', 100);

function wp_autoblog_comment($text){
	return $text . '<!-- Created with WP-Autoblog (http://elliottback.com) -->';
}

function wp_autoblog_trim_excerpt($text) {
	$text = apply_filters('the_content', $text);
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text);
	$excerpt_length = 150;
	$words = explode(' ', $text, $excerpt_length + 1);

	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, '[...]');
		$text = implode(' ', $words);
	}

	return $text;
}

function lookup_cat($cat){
	global $wpdb;

	$cat = apply_filters('pre_category_nicename', trim($cat));
	$results = $wpdb->get_results("SELECT cat_ID, cat_name FROM $wpdb->categories WHERE cat_name = '$cat'");

	if(!$results || count($results) < 1){
		$wpdb->query("INSERT INTO $wpdb->categories SET cat_name='$cat', category_nicename='" . sanitize_title($cat) . "'");
		return $wpdb->insert_id;
	} else {
		return $results[0]->cat_ID;
	}
}

function wp_autoblog(){
	global $wpdb;

	$feeds = preg_split("|[\r\n]+|i", get_option('wp_autoblog_feeds'), -1, PREG_SPLIT_NO_EMPTY);

	if(count($feeds) < 1)
		return;

	foreach($feeds as $feed){
		$rss = auto_blog_fetch_rss($feed);
		if (!$rss)
			continue;

		if(count($rss->items) < 1)
			continue;

		foreach($rss->items as $item){
			$post = array();

			// Content
			if($item['content']['encoded'] != "") $content = $item['content']['encoded'];
			else if($item['xhtml']['div'] != "") $content = $item['xhtml']['div'];
			else if($item['xhtml']['body'] != "") $content = $item['xhtml']['body'];
			else $content = $item['description'];

			$post['post_content'] = $content;

			$post['post_content'] = preg_replace("/<p>(<\/p>)?/i", "", $post['post_content']);

			#echo '<pre>';
			#var_dump(htmlspecialchars($post['post_content'], ENT_QUOTES));
			#echo '</pre>';

			if(get_option('wp_autoblog_full') == 0)
				$post['post_content'] = wp_autoblog_trim_excerpt($post['post_content']);
			else
			{
			}

			$replacements = preg_split("|[\r\n]+|i", get_option('wp_autoblog_replace'), -1, PREG_SPLIT_NO_EMPTY);
			foreach($replacements as $replacement){
				preg_match("|\((.*?),(.*?)\)|si", $replacement, $matches);
				$post['post_content'] = preg_replace('|' . $matches[1] . '|si', $matches[2], $post['post_content']);
			}

			// Title
			$post['post_title'] = strip_tags(trim($item['title']));

			// Categories
			$post['post_category'] = array();
			if(isset($item['category#'])){
				for ($i = 1; $i <= $item['category#']; $i++){
					$cat_idx = (($i > 1) ? "#{$i}" : "");
					$cat = $item["category{$cat_idx}"];
					$post['post_category'][] = $cat;
				}
			} else if($item['dc']['subject'] != ""){
				$post['post_category'][] = $item['dc']['subject'];
			}

			for($i = 0; $i < count($post['post_category']); $i++){
			 $post['post_category'][$i] = lookup_cat(strip_tags(trim($post['post_category'][$i])));
			}

			// Post date
			$date = "";
			if($item['published'] != "") $date = auto_blog_parse_w3cdtf($item['published']); // Atom 1.0
			else if($item['issued'] != "") $date = auto_blog_parse_w3cdtf($item['issued']);	// Atom 0.3
			else if($item['dcterms']['issued'] != "") $date = auto_blog_parse_w3cdtf($item['dcterms']['issued']);
			else if($item['dc']['date'] != "") $date = auto_blog_parse_w3cdtf($item['dc']['date']);
			else if($item['pubdate'] != "") $date = strtotime($item['pubdate']);	// RSS 2.0
			else $date = time();
			$post['post_date'] = date('Y-m-d H:i:s', $date);

			// Post status
			$post['post_status'] = 'publish';

			// Attribution
			if(get_option('wp_autoblog_attribution') == 1){
				if($item['author'] != "") $author = $item['author'];
				else if($item['dc']['creator'] != "") $author = $item['dc']['creator'];
				else if($item['author_name'] != "") $author = $item['author_name'];
				else if($item['source'] != "") $author = $item['source'];
				else if($item['dc']['contributor'] != "") $author = $item['dc']['contributor'];

				// if there is no author, try two things:
				if($author == "") $author = $rss->channel['title'];
				if($author == "") $author = 'Unknown';

				$author = strip_tags(trim($author));

				$post['post_content'] .= '<p>';
				if($author != ""){
					$post['post_content'] .= __('Source:') . ' <em><a href="' . htmlentities(strip_tags(trim($item['link']))) . '" title="' . htmlentities($post['title']) . '">' . htmlentities($author) . '</a></em>';
#					$post['post_content'] .= ' and <em>software</em> by <a href="http://elliottback.com">Elliott Back</a>';
				} else {
#					$post['post_content'] .= 'Created using <em>software</em> by <a href="http://elliottback.com">Elliott Back</a>';
				}
				$post['post_content'] .= '</p>';
			}

			// Escaping
			$post['post_content'] = $wpdb->escape($post['post_content']);
			$post['post_title'] = $wpdb->escape($post['post_title']);

			// Check existence
			$sql = "SELECT ID FROM $wpdb->posts WHERE post_title = '" . apply_filters('title_save_pre', $post['post_title']) . "'";
			if($wpdb->query($sql) === 0){
			#echo '<pre>';
			#var_dump($post['post_content']);
			#echo '</pre>';

			#echo '<pre>';
			#var_dump($post);
			#echo '</pre>';

				$pid = wp_insert_post($post);
				do_action('publish_post', $pid);
			}
		}
	}

	# update category cache

	auto_blog_update_category_cache();
}

/****
 * 3) ADMIN PANEL
 */

// Update admin options
function wp_autoblog_update() {
	if(isset($_POST['runnow']))
		wp_autoblog();

	if (isset ($_POST['update'])) {
		update_option('wp_autoblog_feeds', $_POST['feeds']);
		update_option('wp_autoblog_replace', $_POST['replace']);
		update_option('wp_autoblog_full', $_POST['full']);
		update_option('wp_autoblog_attribution', $_POST['attribution']);
	}
}

// Admin options
function wp_autoblog_editor() {
	wp_autoblog_update();

	echo '<style type="text/css">';
	echo '#autoblogleft { float: left; width:40%; padding-right: 5%;}';
	echo '#autoblogright { float: left; width:40%; }';
	echo '#autoblogcenter { text-align:center; background: #FF3333; }';
	echo '</style>';

	echo '<div class="wrap">';
	echo '<h2>WP Autoblog Settings</h2>';

	echo '<form action="options-general.php?page=' . $_GET['page'] . '" method="post">';
	echo '<div id="autoblogleft">';
	echo '<h3>The Basic Settings</h3>';
	echo '<p>';
	echo 'A list of RSS or ATOM feeds, one per line, to use as sources:';
	echo '</p>';
	echo '<textarea name="feeds" rows="10" style="width:100%">' . get_option('wp_autoblog_feeds') . '</textarea><br/>';
	echo '<input type="hidden" name="update" value="yes" />';

	echo '<h3>Intellectual Property Settings</h3>';
	echo '<ul>';

	echo '<li>';
	echo 'Choose full or partial feeds to repost: ';
	echo '<select name="full">';
	echo '<option value="0"' . ((get_option('wp_autoblog_full') == 0)?' selected':'') .   ' />Excerpts';
	echo '<option value="1"' . ((get_option('wp_autoblog_full') == 1)?' selected':'') .   ' />Full feeds';
	echo '</select>';
	echo '</li>';

	echo '<li>';
	echo 'Give attribution to the original author? ';
	echo '<select name="attribution">';
	echo '<option value="1"' . ((get_option('wp_autoblog_attribution') == 1)?' selected':'') .   ' />Yes';
	echo '<option value="0"' . ((get_option('wp_autoblog_attribution') == 0)?' selected':'') .   ' />No';
	echo '</select>';
	echo '</li>';

	echo '</ul>';
	echo '</div>';

	echo '<div id="autoblogright">';
	echo '<h3>Advanced Settings</h3>';
	echo '<p>';
	echo 'WP Autoblog can automatically replace content in the syndicated posts (affiliate IDs, etc).  '
		.'Just enter pairs of values (old,new) on seperate lines.  For example, to rewrite Google Adsense '
		.'affiliate IDs, you would add a line, "(google_ad_client = "pub-xxx",google_ad_client = "pub-yyy")". '
		.'Note that replacements are case insensitive!';
	echo '</p>';
	echo '<textarea name="replace" rows="10" style="width:100%;">' . get_option('wp_autoblog_replace') . '</textarea>';
	echo '</div>';

	echo '<div style="clear:both;"></div>';

	echo '<div id="autoblogcenter">';
	echo '<input type="submit" value="Save Settings" />';
	echo '</form>';
	echo '<form action="options-general.php?page=' . $_GET['page'] . '" method="post">';
	echo '<input type="hidden" name="runnow" value="yes" />';
	echo '<input type="submit" value="Run script now" />';
	echo '</form>';
	echo '</div>';

	echo '<p>';
	echo '<strong>';
	echo 'WP Autoblog';
	echo '</strong>';
	echo ' by <a href="http://elliottback.com/wp/">Elliott Back</a>';
	echo '</p>';

	echo '</div>';
}

function wp_autoblog_add_options_to_admin() {
	add_options_page('WP Autoblog', 'WP Autoblog', 8, __FILE__, 'wp_autoblog_editor');
}

if (function_exists('add_action')) {
	add_action('admin_menu', 'wp_autoblog_add_options_to_admin');
}



function auto_blog_update_category_cache()
{
	global $wpdb;

	$cats = $wpdb->get_results("
		SELECT post2cat.category_id as category_id,
			COUNT(posts.ID) as post_count,
			cats.category_count as category_count
		FROM $wpdb->post2cat as post2cat
		INNER JOIN $wpdb->posts as posts
			ON posts.ID = post2cat.post_id
		INNER JOIN $wpdb->categories as cats
			ON cats.cat_ID = post2cat.category_id
		GROUP BY post2cat.category_id
		HAVING post_count <> category_count
		");

	foreach ( (array) $cats as $cat )
	{
		$cat_id = intval($cat->category_id);
		$count = intval($cat->post_count);
		$wpdb->query("UPDATE $wpdb->categories SET category_count = '$count' WHERE cat_ID = '$cat_id'");
		wp_cache_delete($cat_id, 'category');
	}

#	echo '<pre>';
#	var_dump($cats);
#	echo '</pre>';
}

#auto_blog_update_category_cache();
?>