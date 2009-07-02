<?php
/*
Plugin Name: Trackback Validator
Plugin URI: http://trackback.cs.rice.edu/
Description: Eliminates spam trackbacks with surprising accuracy (see website for details).
Version: 0.7.1 (edited)
Author: Dan Sandler and Andres Thomas-Stivalet
Author URI: http://www.cs.rice.edu/~dsandler/
*/

function tb_add_option_page() {
  add_options_page('Trackback Validation', 'Trackback Validation', 'manage_options', __FILE__, 'tb_menu');
}

function tb_menu() {
  global $wpdb, $table_prefix;

  $tb_options=get_option("tb_options");
  if(!isset($tb_options['save_data'])) {
    $tb_options['save_data']=1;
    update_option("tb_options",$tb_options);
    tb_create_spam_table();
  }
  if (isset($_POST['info_update'])) {
    echo "<div class='updated'><p><strong>";
    $tb_options['auto_approve']=(isset($_POST['auto_approve']) ? 1 : 0);
    $tb_options['save_data']=(isset($_POST['save_data']) ? 1 : 0);
    if($tb_options['save_data']==1)
      tb_create_spam_table();
    update_option("tb_options",$tb_options);
    echo "Settings Updated.</strong></p></div>";
  }

  $count = 100;
?>
  <div class=wrap>
  <form method="post">
  <h2>Trackback Validator</h2>

	<fieldset name='set0'>
		<legend>Recent activity</legend>
		<div style="margin: 0.75em;">
       Most recent trackbacks:

	   <span style="margin-right: 2em;">
<?php include_once('trackback_graph.php');
      trackback_graph($count); ?>
	  </span>

	  <small>[<b>legend:</b>
	  &nbsp;
	  <span style="color:red"><sub>|</sub> spam</span>
	  &nbsp;
	  <span style="color:blue"><sup>|</sup> not spam</span>
	  ]</small>
	  </div>
	</fieldset>

  <fieldset name='set1'>
  <legend>
  Control Options
  </legend>
  <ul>
  <li>
  <input type="checkbox" name="auto_approve" value='1' <?php if($tb_options['auto_approve']) { echo "checked='checked'";  } ?>>
  Automatically approve trackback comments that have been validated.
  </li>
  <li>
  <input type="checkbox" name="save_data" value='1' <?php if($tb_options['save_data']) { echo "checked='checked'";  } ?>>
  Submit data to the <a href="http://seclab.cs.rice.edu/">Computer Security Lab</a> at <a href="http://www.rice.edu/">Rice University</a> for research.
  </li>
  </ul>
  </fieldset>
  <div class="submit">
  <input type="submit" name="info_update" value="Update options &raquo;"/>
  </div>
  </form>
  </div>
<?php
}

function tb_create_spam_table() {
  global $wpdb, $table_prefix;

  $wpdb->query("DROP TABLE IF EXISTS `${table_prefix}tb_spam`");
  $wpdb->query("
CREATE TABLE IF NOT EXISTS `${table_prefix}tb_data` (
  `tb_ID` bigint(20) unsigned NOT NULL auto_increment,
  `tb_post_link` varchar(200) NOT NULL default '',
  `tb_author` tinytext NOT NULL,
  `tb_author_email` varchar(100) NOT NULL default '',
  `tb_author_url` varchar(200) NOT NULL default '',
  `tb_author_IP` varchar(100) NOT NULL default '',
  `tb_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `tb_date_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
  `tb_content` text NOT NULL,
  `tb_agent` varchar(255) NOT NULL default '',
  `tb_seen` enum('n','x','y') NOT NULL default 'n',
  `tb_comments_ID` bigint(20) unsigned NOT NULL default '0',
  `tb_type` enum('ham','spam') NOT NULL default 'ham',
  PRIMARY KEY  (`tb_ID`),
  KEY `tb_comments_ID` (`tb_comments_ID`)
) TYPE=MyISAM AUTO_INCREMENT=0;");
}

// tb_is_ham: trackback, permalink -> ham?
function tb_is_ham($tb_info, $permalink) {
  tb_load_snoopy();
  if (!class_exists('Snoopy')) { return true; /* nothing we can do here */ }
  $snoopy = new Snoopy;
  $snoopy->fetch($tb_info['comment_author_url']);
  $contents = $snoopy->results;
  $permalink_q=preg_quote($permalink,'/');
  $pattern="/<\s*a.*href\s*=[\"'\s]*".$permalink_q."[\"'\s]*.*>.*<\s*\/\s*a\s*>/i";

  return (preg_match($pattern,$contents));
}

// update wp_posts.comment_count (WP >=2.0 only)
function update_comment_count($comment_post_ID) {
	global $wpdb, $wp_version;

	if (preg_match("/^1\./", $wp_version)) { return; }

	$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = '$comment_post_ID' AND comment_approved = '1'");
	$wpdb->query( "UPDATE $wpdb->posts SET comment_count = $count WHERE ID = '$comment_post_ID'" );
}

//main function to validate trackback
function tb_check($comment_ID) {
  global $wpdb, $table_prefix;

  $tb_options=get_option('tb_options');
  $tb_info=$wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID = '$comment_ID'", ARRAY_A);

  $permalink=get_permalink($tb_info['comment_post_ID']);

  if(tb_is_ham($tb_info, $permalink)) {
    $tb_type="ham";
    if($tb_options['auto_approve'])
      $wpdb->query("UPDATE $wpdb->comments SET comment_approved = '1' WHERE comment_ID = '$comment_ID'");
  } else {
    $wpdb->query("UPDATE $wpdb->comments SET comment_approved = 'spam' WHERE comment_ID = '$comment_ID'");
    $tb_type="spam";
	update_comment_count($tb_info['comment_post_ID']);
  }

  if($tb_options['save_data']) {
    //set up data
    $permalink=$wpdb->escape($permalink);
    $author=$wpdb->escape($tb_info['comment_author']);
    $author_email=$wpdb->escape($tb_info['comment_author_email']);
    $author_url=$wpdb->escape($tb_info['comment_author_url']);
    $author_IP=$wpdb->escape($tb_info['comment_author_IP']);
    $date=$wpdb->escape($tb_info['comment_date']);
    $date_gmt=$wpdb->escape($tb_info['comment_date_gmt']);
    $content=$wpdb->escape($tb_info['comment_content']);
    $agent=$wpdb->escape($tb_info['comment_agent']);
    if($tb_type=="spam")
      $tb_seen='n';
    else
      $tb_seen='x';
    $wpdb->query("INSERT INTO ${table_prefix}tb_data (tb_post_link, tb_author, tb_author_email, tb_author_url, tb_author_IP, tb_date, tb_date_gmt, tb_content, tb_agent, tb_comments_ID, tb_type, tb_seen)
                                    VALUES ('$permalink','$author', '$author_email', '$author_url', '$author_IP', '$date', '$date_gmt', '$content', '$agent', '$comment_ID', '$tb_type', '$tb_seen')");

    if($tb_type=="spam") {
      if(tb_dump_trackback($permalink,$author,$author_email,$author_url,$author_IP,$date,$date_gmt,$content,$agent,$tb_type)) {
	$wpdb->query("UPDATE ${table_prefix}tb_data SET tb_seen = 'y' WHERE tb_comments_ID = '$comment_ID'");
	//try past failed trackback sends, if this one worked.
	//we really want your data =)
	$query = "SELECT * FROM ${table_prefix}tb_data WHERE tb_seen='n'";
	$result = $wpdb->get_results($query, ARRAY_A);
	for($i=0; $i<count($result); $i++) {
	  $row = $result[$i];
	  $tb_comment_ID=$wpdb->escape($row['tb_comments_ID']);
	  $permalink=$wpdb->escape($row['tb_post_link']);
	  $author=$wpdb->escape($row['tb_author']);
	  $author_email=$wpdb->escape($row['tb_author_email']);
	  $author_url=$wpdb->escape($row['tb_author_url']);
	  $author_IP=$wpdb->escape($row['tb_author_IP']);
	  $date=$wpdb->escape($row['tb_date']);
	  $date_gmt=$wpdb->escape($row['tb_date_gmt']);
	  $content=$wpdb->escape($row['tb_content']);
	  $agent=$wpdb->escape($row['tb_agent']);
	  $tb_type=$wpdb->escape($row['tb_type']);
	  if(tb_dump_trackback($permalink,$author,$author_email,$author_url,$author_IP,$date,$date_gmt,$content,$agent,$tb_type)) {
	    $wpdb->query("UPDATE ${table_prefix}tb_data SET tb_seen = 'y' WHERE tb_comments_ID = '$tb_comment_ID'");
	  } else {
	    break;
	  }
	}
      }
    }
  }
  return $comment_ID;
}

function tb_comment_deleted($comment_ID) {
  global $wpdb, $table_prefix;

  $tb_options=get_option('tb_options');
  if($tb_options['save_data']) {
    $row=$wpdb->get_row("SELECT * FROM ${table_prefix}tb_data WHERE tb_comments_ID = '$comment_ID' AND tb_seen = 'x'", ARRAY_A);
    if($row) {
      $tb_ID=$wpdb->escape($row['tb_ID']);
      $permalink=$wpdb->escape($row['tb_post_link']);
      $author=$wpdb->escape($row['tb_author']);
      $author_email=$wpdb->escape($row['tb_author_email']);
      $author_url=$wpdb->escape($row['tb_author_url']);
      $author_IP=$wpdb->escape($row['tb_author_IP']);
      $date=$wpdb->escape($row['tb_date']);
      $date_gmt=$wpdb->escape($row['tb_date_gmt']);
      $content=$wpdb->escape($row['tb_content']);
      $agent=$wpdb->escape($row['tb_agent']);
      $tb_type="ham"; //was ham now is spam
      if(tb_dump_trackback($permalink,$author,$author_email,$author_url,$author_IP,$date,$date_gmt,$content,$agent,$tb_type)) {
	$wpdb->query("UPDATE ${table_prefix}tb_data SET tb_seen = 'y', tb_type = 'spam' WHERE tb_ID = '$tb_ID'");
      } else {
	break;
      }
    }
  }
}

define(TRACKBACK_STATS_REPORT_URL, 'http://trackback-db.cs.rice.edu/report');

define(POST_SITE_URL,'site_url');
define(POST_UTC_OFFSET, 'site_timezone');
define(POST_SITE_NAME, 'site_name');
define(POST_SITE_EMAIL, 'site_email');

define(POST_TB_DATE, 'tb_date');
define(POST_TB_TYPE, 'tb_type');
define(POST_TB_POST_URL, 'tb_post_url');
define(POST_TB_AUTHOR, 'tb_author');
define(POST_TB_AUTHOR_EMAIL, 'tb_email');
define(POST_TB_AUTHOR_URL, 'tb_url');
define(POST_TB_AUTHOR_IP, 'tb_ip');
define(POST_TB_CONTENT, 'tb_content');

function tb_load_snoopy() {
	if (!class_exists('Snoopy')) {
		# attempt to load Snoopy
		if (@include_once('Snoopy.class.php')) {
			# ok, cool
		} elseif (@include_once(ABSPATH . WPINC . "/class-snoopy.php")) {
			# this is OK too
		} else {
			error_log(__FILE__ . ": error: can't load Snoopy class for reporting");
		}
	}
}

/*
function assoc_to_query($a) {
    $s='';
    foreach ($a as $k => $v){
        $s .= (empty($s)?'':'&') . urlencode($k) . '=' . urlencode($v);
    }
    return $s;
}
*/

function tb_dump_trackback($permalink,$author,$author_email,$author_url,$author_IP,$date,$date_gmt,$content,$agent,$tb_type) {

    tb_load_snoopy();
    if (!class_exists('Snoopy')) { return false; }

    $query_parts = array(
        POST_SITE_URL => get_option('siteurl'),
        POST_UTC_OFFSET => get_option('gmt_offset'),
        POST_SITE_NAME => get_option('blogname'),
        POST_SITE_EMAIL => get_option('admin_email'),

        POST_TB_DATE => $date_gmt,
        POST_TB_TYPE => $tb_type,
        POST_TB_POST_URL => $permalink,
        POST_TB_AUTHOR => $author,
        POST_TB_AUTHOR_EMAIL => $author_email,
        POST_TB_AUTHOR_URL => $author_url,
        POST_TB_AUTHOR_IP => $author_IP,
        POST_TB_CONTENT => $content,
    );

    $snoopy = new Snoopy;
	// We don't want Rice's (un)availability to mean trackbacks take
	// forever.
	$snoopy->read_timeout = 15; // seconds for read timeout
	$snoopy->_fp_timeout = 15; // seconds for socket timeout
    $snoopy->submit(TRACKBACK_STATS_REPORT_URL, $query_parts);

	error_log("TrackBack report result: " . $snoopy->contents);

    return true;
}

add_action('trackback_post', 'tb_check',1,1);
add_action('admin_menu', 'tb_add_option_page');
add_action('delete_comment', 'tb_comment_deleted',1,1);

?>
