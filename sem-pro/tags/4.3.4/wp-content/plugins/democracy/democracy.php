<?php
/*
Plugin Name: Democracy
Plugin URI: http://blog.jalenack.com/archives/democracy/
Description: Ajax polling plugin
Version: 1.12 ( fork)
Author: Andrew Sutherland
Author URI: http://blog.jalenack.com/
*/

if ( isset($_GET['jal_add_user_answer']) || isset($_GET['jal_no_js']) )
{
	function jal_redirect()
	{
		preg_match("/(.*)(\?|&)(jal_add_user_answer|jal_no_js)(.*)/i", $_SERVER['REQUEST_URI'], $match);

        header('HTTP/1.1 301 Moved Permanently');
        header('Status: 301 Moved Permanently');
		wp_redirect($match[1]);
		die;
	}
	add_action('init', 'jal_redirect');
}

// With hat tips from Denis de Bernhardy
// http://semiologic.com

// Released under the CC GPL 2.0:
// http://creativecommons.org/licenses/GPL/2.0/

// The current version of this plugin
$jal_dem_version = "1.2";

// When viewing results, order the answers by number of votes or by original order
// To order by votes, set this to TRUE
$jal_order_answers = FALSE;

// HTML to go around the poll's question
// Depending on your theme, you may want '<h2>';
$jal_before_question = '<strong id="poll-question">';

// Closing tags for the HTML around the question
$jal_after_question = '</strong>';

// Graph percentages as a percent of the total votes or of the winner
// If it is false, then the winning vote will be 100% of the graph, and the other answers will be a percent of that,
$jal_graph_from_total = TRUE;

// The length of time, in *seconds*, that cookies should remain. Default: 3 months
// Note that IP logging will still be in effect when you lower this number
// To take away IP stopping functionality, comment out the checkIP() calls at the bottom
$jal_cookietime = 60*60*24*30*3;
// Change it in the js file as well



/*/\\//\\//\\//\\//\\//\\//\\//

    No edits needed below here

//\\//\\//\\//\\//\\//\\//\\/*/

function jal_edit_poll () {
    global $wpdb, $table_prefix;

    // Security
    if ( !current_user_can('switch_themes')) { die('Nice try, you cheeky monkey!'); }

	check_admin_referer('democracy');

    $poll_id = (int) $_POST['poll_id'];

    // read which aids are in this poll
    $edits = explode(" ", $_POST['editable']);
	 $question = $wpdb->escape(stripslashes(wp_filter_post_kses($_POST['question'])));

    // Allow users to edit poll?
    $allowusers = (isset($_POST['allowNewAnswers'])) ? "1" : "0";

    // update the question
    $wpdb->query("UPDATE {$table_prefix}democracyQ SET question='{$question}', allowusers = '{$allowusers}' WHERE id = {$poll_id}");

	$answers = array_keys((array) $_POST['answer'] );

	foreach ( $edits as $aid )
	{
		$aid = (int) $aid;

		if ( !in_array($aid, $answers) )
		{
			$wpdb->query("DELETE FROM {$table_prefix}democracyA WHERE qid = {$poll_id} AND aid = {$aid}");
		}
	}

	foreach($_POST['answer'] as $aid => $answer) {
		$aid = (int) $aid;

		if (!empty($answer) && in_array($aid, $edits))
			$wpdb->query("UPDATE {$table_prefix}democracyA SET answers='".$wpdb->escape(stripslashes(wp_filter_post_kses($answer)))."' WHERE qid = {$poll_id} AND aid = {$aid}");

		if (!empty($answer) && !in_array($aid, $edits))
			$wpdb->query("INSERT INTO {$table_prefix}democracyA (qid, answers, votes) VALUES ({$poll_id}, '".$wpdb->escape(stripslashes(wp_filter_post_kses($answer)))."', 0)");

	}
}

function jal_activate_poll () {
    global $wpdb, $table_prefix;

    // Security
    if ( !current_user_can('switch_themes')) { die('Nice try, you cheeky monkey!'); }

	check_admin_referer('democracy');

    $id = (int) $_GET['poll_id'];

    // Deactivate the old active poll
    $wpdb->query("UPDATE {$table_prefix}democracyQ SET active='0' WHERE active = '1'");
    // Activate the new poll
    $wpdb->query("UPDATE {$table_prefix}democracyQ SET active='1' WHERE id = {$id}");
}


function jal_deactivate_poll () {
    global $wpdb, $table_prefix;

    // Security
    if ( !current_user_can('switch_themes')) { die('Nice try, you cheeky monkey!'); }

	check_admin_referer('democracy');

    // Deactivate the old active poll
    $wpdb->query("UPDATE {$table_prefix}democracyQ SET active='0' WHERE active = '1'");
}


function jal_delete_poll () {
    global $wpdb, $table_prefix;

    // Security
    if ( !current_user_can('switch_themes')) { die('Nice try, you cheeky monkey!'); }

	check_admin_referer('democracy');

	$id = (int) $_GET['poll_id'];

    // Delete the poll question and its answers
    $wpdb->query("DELETE FROM {$table_prefix}democracyQ WHERE id = {$id}");
    $wpdb->query("DELETE FROM {$table_prefix}democracyA WHERE qid = {$id}");
}


function jal_add_question () {
    global $wpdb, $table_prefix;

    // Security
    if ( !current_user_can('switch_themes')) { die('Nice try, you cheeky monkey!'); }

	check_admin_referer('democracy');

    // deactive old poll
    $wpdb->query("UPDATE {$table_prefix}democracyQ SET active='0' WHERE active='1'");

    // Let users add their own answers?
    $allow_users_to_add = (isset($_POST['allowNewAnswers'])) ? "1" : "0";

    // Add a new question and activate it
    $wpdb->query("INSERT INTO {$table_prefix}democracyQ (question, timestamp, allowusers, active) VALUES ('".$wpdb->escape(stripslashes(wp_filter_post_kses($_POST['jal_dem_question'])))."', '".time()."', '".$allow_users_to_add."', '1')");

    // Get the id of that new question
    $new_q = $wpdb->get_var("SELECT id FROM {$table_prefix}democracyQ WHERE active = '1'");

    // Add the questions
    foreach($_POST['answer'] as $answer)
        if (!empty($answer))
        	$sql[] = "INSERT INTO {$table_prefix}democracyA (qid, answers, votes) VALUES ({$new_q}, '".$wpdb->escape(stripslashes(wp_filter_post_kses($answer)))."', 0);";

    foreach($sql as $query)
    	$wpdb->query($query);

}


function jal_dem_admin_head() { ?>    <script type="text/javascript" src="<?php echo get_bloginfo('wpurl') . "/wp-content/plugins/democracy/admin.js"; ?>"></script>
<?php }

function jal_dem_admin_page() {
	global $wpdb, $table_prefix;
	$poll_id = (int) $_GET['poll_id'];

 if (isset($_GET['jal_dem_delete'])) { ?><div class="updated">
    <p>Poll #<?php echo $poll_id; ?> was deleted successfully</p>
</div>
<?php } ?>
<?php if (isset($_POST['jal_dem_edit'])) { ?><div class="updated">
    <p>Poll #<?php echo $_POST['poll_id']; ?> was edited successfully</p>
</div>
<?php } ?>
<?php if (!empty($_GET['edit'])) { ?>
<div class="wrap">

    <h2>Edit Poll #<?php echo $poll_id; ?></h2>

    <?php


    $latest = $wpdb->get_var("SELECT id FROM {$table_prefix}democracyQ ORDER BY id DESC LIMIT 1");
    $question = $wpdb->get_row("SELECT question, allowusers FROM {$table_prefix}democracyQ WHERE id = {$poll_id}", ARRAY_A);
    $results = $wpdb->get_results("SELECT * FROM {$table_prefix}democracyA WHERE qid = {$poll_id} ORDER BY aid");

    ?>    <p>To delete an answer, leave the input box blank. Moving an answer from one box to another will erase its votes.<br />
    </p>
        <form action="edit.php?page=democracy" method="post" onsubmit="return jal_validate();">
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('democracy'); ?>
    <strong>Question: <input id="question" type="text" name="question" value="<?php echo $question['question']; ?>" /></strong>

	<ol id="inputList"><?php

    $count = 1;
    $loop = "";
    foreach ($results as $r) {

        // Add to the list of answers in the hidden input element
        $loop = $loop ." ". $r->aid;

     ?><li><input type="text" value="<?php echo $r->answers; ?>" name="answer[<?php echo $r->aid; ?>]" /></li><?php $count++; }

		?></ol>
    <p>
    	<label for="allowNewAnswers"><input type="checkbox" <?php if ($question['allowusers'] == 1) echo 'checked="checked"'; ?> value="true" name="allowNewAnswers" id="allowNewAnswers" /> Allow users to add answers</label><br /><br />
        <a href="javascript: addQuestion();" id="adder">Add an Answer</a>&nbsp;&nbsp;
        <a href="javascript: eatQuestion();" id="subtractor">Subtract an Answer</a>&nbsp;

        <input type="hidden" id="qnum" name="qnum" value="<?php echo $count; ?>" />
        <input type="hidden" name="jal_dem_edit" value="true" />
        <input type="hidden" name="editable" value="<?php echo trim($loop); ?>" />
        <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>" />
        <input type="submit" value="Edit" />
    </p>
    </form>

   <?php } else { ?>
    <?php
    // get the current poll
    $current = $wpdb->get_var("SELECT id FROM {$table_prefix}democracyQ WHERE active = '1'");

    if (!$current) { ?>        <div class="updated">
            <p style="font-weight: bold">There are no active polls! The plugin will not show anything unless you Activate a poll.</p>
        </div>
    <?php } ?>
<div class="wrap">

    <h2>Manage Polls</h2>

    <table width="100%" border="0" cellspacing="3" cellpadding="3">
	    <tr>
         <th scope="col">ID</th>
	    	<th scope="col">Question</th>
	    	<th scope="col">Total Votes</th>
	    	<th scope="col">Winner</th>
	    	<th scope="col">Date Added</th>
	    	<th scope="col">Action</th>
	    </tr>
<?php
   $winners = array();
	$totalvotes = array();
	$x = (array) $wpdb->get_results("SELECT * from {$table_prefix}democracyQ ORDER BY id");

	$totalvotes = (array) $wpdb->get_results("
	 SELECT SUM(votes) as total_votes, qid
	 FROM {$table_prefix}democracyA
	 GROUP BY qid
	 ORDER BY qid"
	 , ARRAY_A);

	 $winner_answers = (array) $wpdb->get_results("
	  SELECT votes, answers, qid
	  FROM {$table_prefix}democracyA
	  GROUP BY qid, votes
	  ORDER BY qid"
	  , ARRAY_A);

	// index by qid
	foreach ($totalvotes as $winner)
		$total_vote[$winner['qid']] = $winner['total_votes'];

	// no need for the old query info
	unset ($y);

	// index by qid
	foreach ($winner_answers as $winning_answer)
		$winners[$winning_answer['qid']] = $winning_answer['answers'];

	// no need for the old query info
	unset ($y);

    $alt = true;
    if ($x) {
    foreach ($x as $r) {


	 $alt = ($alt) ? FALSE : TRUE;
?>	    <tr<?php if ($current == $r->id) { echo ' class="active"'; } if ($alt == true && $current !== $r->id) { echo ' class="alternate"';  } ?>>
	       <td style="text-align: center"><?php echo $r->id; ?></td>
	       <td style="text-align: center"><?php echo $r->question; ?></td>
	       <td style="text-align: center"><?php if ($current == $r->id) { echo "So far, "; } echo $total_vote[$r->id]; ?></td>
	       <td style="text-align: center"><?php echo $winners[$r->id]; ?>	       <td style="text-align: center"><?php echo date(get_settings('date_format'), $r->timestamp); ?></td>
	       <td style="text-align: center">
	           <form action="" method="get">
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('democracy'); ?>
	               <div>
	                   <input type="hidden" name="page" value="democracy" />
	                   <input type="hidden" name="poll_id" value="<?php echo $r->id; ?>" />
	                   <?php if ($current !== $r->id) :  ?>	                       <input type="submit" value="Activate" name="jal_dem_activate" />
	                   <?php else : ?>	                       <input type="submit" value="Deactivate" name="jal_dem_deactivate" />
	                   <?php endif; ?>	                   <input type="submit" value="Edit" name="edit">
	                   <input type="submit" value="Delete" onclick="return confirm('You are about to delete this poll.\n  \'Cancel\' to stop, \'OK\' to delete.');" name="jal_dem_delete" class="delete" />
	               </div>
	           </form>
	       </td>
	    </tr>
    <?php } } else { echo "<br />You have no polls in the database. Add a new one!<br /><br />"; } ?>
    </table>
    <h2>Add a New Poll</h2>
    <p>Some HTML is allowed in polls, and no character entities will be converted, so if you want to use &amp;, write <code>&amp;amp;</code>, etc.. If you have no idea know what the last two sentences meant, don't worry about 'em. Blank fields will be skipped.</p>
    <form action="edit.php?page=democracy" method="post" onsubmit="return jal_validate();">
		<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('democracy'); ?>
    <div id="form_questions">

    <a href="javascript: addQuestion();" id="adder">Add an Answer</a><br />
    <a href="javascript: eatQuestion();" id="subtractor">Subtract an Answer</a><br />
    <br />
    <label for="question">
        <strong>Question:</strong>
    </label>

        <input type="text" name="jal_dem_question" value="" id="question" />
        <ol id="inputList"><?php
 			for($i = 1; $i < 5; $i++) {
				echo '<li><input type="text" value="" name="answer[]" /></li>';
			} ?></ol>

    <label for="allowNewAnswers"><input type="checkbox" value="true" name="allowNewAnswers" id="allowNewAnswers" /> Allow users to add answers</label><br /><br />
	<input type="submit" value="Create New Poll" />
    </div>
    </form>
    <?php } // end of Edit conditional ?>
</div>

<?php }


// Adds the Democracy Poll Plugin tab to the admin navigation
function jal_add_page() {
    add_management_page('Polls', 'Polls', 'switch_themes', 'democracy', 'jal_dem_admin_page');
}


// Add the javascript to the head of the page
function jal_add_js () {
    global $jal_dem_version;

    //$jal_wp_url = (dirname($_SERVER['PHP_SELF']) == "/") ? "/" : dirname($_SERVER['PHP_SELF']) . "/";
    $jal_wp_url = get_bloginfo('wpurl') . "/";

    echo '
    <!-- Added By Democracy Plugin. Version '.$jal_dem_version.' -->
    <script type="text/javascript" src="'.$jal_wp_url.'wp-content/plugins/democracy/js.php"></script>
    <link rel="stylesheet" href="'.$jal_wp_url.'wp-content/plugins/democracy/democracy.css" type="text/css" />
	 ';
}

// Run a check of visitors IP to make sure they haven't voted already.
// This process is usually silent, cookies take care of the design
function jal_checkIP ($poll_id = 0) {
    global $wpdb, $table_prefix;

	$where = ($poll_id == 0) ? "active = '1'" : "id = '".intval($poll_id)."'";

    $all_ips = $wpdb->get_var("SELECT voters FROM {$table_prefix}democracyQ WHERE {$where}");

    $results = unserialize($all_ips);
    // make sure there have been votes
    if ($results)
        //check the ip address, and quit if there's a match
        if (in_array($_SERVER['REMOTE_ADDR'], $results))
            die("Go stuff the ballot box elsewhere!");

        // add the new IP address to the array
        $results[] = wp_filter_post_kses($_SERVER['REMOTE_ADDR']);
        $final = $wpdb->escape(serialize($results));

        $wpdb->query("UPDATE {$table_prefix}democracyQ SET voters = '{$final}' WHERE {$where}");
}


// Print the poll html
function jal_democracy($poll_id = 0) {
 global $wpdb, $table_prefix, $jal_before_question, $jal_after_question;

	$where = ($poll_id == 0) ? "active = '1'" : "id = '".intval($poll_id)."'";
 	$poll_question = $wpdb->get_row("SELECT id, question, voters, allowusers FROM {$table_prefix}democracyQ WHERE {$where}");

// Check if they've voted
 if ($_GET['jal_no_js'] || isset($_COOKIE['demVoted_'.$GLOBALS['table_prefix'].$poll_question->id])
 	|| isset($_COOKIE['demVoted_'.$poll_question->id]))
    jal_SeeResults($poll_question->id);
 else {
        $wpdb->hide_errors();
		  $poll_answers = $wpdb->get_results("SELECT aid, answers, added_by FROM {$table_prefix}democracyA WHERE qid = {$poll_question->id} ORDER BY aid");
        $wpdb->show_errors();

        if (empty($poll_question) || empty($poll_answers))
				echo "<!-- There are no active polls in the database, or there was an error in finding one -->";
		  else {

        // Check if there are already arguments in the URL
        $x = (strstr($_SERVER['REQUEST_URI'], '?')) ? "&amp;" : "?";

 		$latestaid = $wpdb->get_var("SELECT aid FROM {$table_prefix}democracyA ORDER BY aid DESC LIMIT 1");

 		$total_votes = $wpdb->get_var("SELECT SUM(votes) FROM {$table_prefix}democracyA WHERE qid = ".$poll_question->id);
    ?>     <form action="<?php echo get_bloginfo('wpurl') . '/wp-content/plugins/democracy/democracy.php?jal_nojs=true'; ?>" method="post" id="democracyForm" onsubmit="return ReadVote();">
        <div id="democracy">
        <?php echo $jal_before_question . $poll_question->question . $jal_after_question; ?>
        <ul>
    	  <?php foreach ( $poll_answers as $r) { ?>            <li>
            	<label for="choice_<?php echo $r->aid; ?>">
            		<input type="radio" id="choice_<?php echo $r->aid; ?>" value="<?php echo $r->aid; ?>" name="poll_aid" />
            		<?php echo $r->answers; ?><?php if ($r->added_by == "1") { echo '<sup title="Added by users">1</sup>'; $user_added = TRUE; }?>            	</label>
            </li>
    	  <?php }
    	  if ( $poll_question->allowusers == 1) {
    	   ?>

    	    <?php /* No-JS users */ if (false && isset($_GET['jal_add_user_answer'])) { ?>
    	    <li>
    	    <input type="radio" name="poll_aid" id="jalAddAnswerRadio" value="newAnswer" checked="checked" />
    	    <input type="text" size="15" id="jalAddAnswerInput" name="poll_vote" value="" />

    	    </li>

			<?php } else { ?>    	    <li>
    	    	<script type="text/javascript">document.write('<a href="#" id="jalAddAnswer">Add an Answer</a>');</script>
    	    	<input type="radio" name="poll_aid" id="jalAddAnswerRadio" value="<?php echo $latestaid + 1; ?>" style="display: none" /> <input type="text" size="15" style="display: none" id="jalAddAnswerInput" />
    	    </li>

        	<?php } } ?>        </ul>
        <p><input type="hidden" id="poll_id" name="poll_id" value="<?php echo $poll_question->id; ?>" /><input type="submit" name="vote" value="Vote" /></p>
        <p><?php if ($total_votes > 0) {
        	// For non-js users...JS users get this link changed onload
        ?>  <script type="text/javascript">
        	document.write('<a id="view-results" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES).$x; ?>jal_no_js=true&amp;poll_id=<?php echo $poll_question->id; ?>">View Results</a>');
        	</script>
        	<?php if ($user_added) echo "<br /><small><sup>1</sup> = Added by a guest</small>"; ?>        <?php } else { echo "No votes yet"; } ?></p>
       </div>
    </form>

<?php } }
}

// Installs the tables needed for polls
// Check the codex article for more info
// http://codex.wordpress.org/creating_tables_with_plugins
function jal_dem_install () {
   global $table_prefix, $wpdb;

   $table_name = $table_prefix . "democracyQ";

   $result = mysql_list_tables(DB_NAME);
   $tables = array();

   while ($row = mysql_fetch_row($result)) { $tables[] = $row[0]; }

	$first_install = (in_array($table_name, $tables) || in_array(strtolower($table_name), $tables)) ? FALSE : TRUE;

   // PRIMARY KEY has 2 spaces on purpose ... some weird dbDelta thing...

   $qry = "CREATE TABLE {$table_prefix}democracyA (
            aid int(10) unsigned NOT NULL auto_increment,
            qid int(10) NOT NULL default '0',
            answers varchar(200) NOT NULL default '',
            votes int(10) NOT NULL default '0',
            added_by enum('1','0') NOT NULL default '0',
            PRIMARY KEY  (aid)
           );

           CREATE TABLE {$table_prefix}democracyQ (
            id int(10) unsigned NOT NULL auto_increment,
            question varchar(200) NOT NULL default '',
            timestamp int(10) NOT NULL,
            voters text NULL default '',
            allowusers enum('0','1') NOT NULL default '0',
            active enum('0','1') NOT NULL default '0',
            PRIMARY KEY  (id)
           ); ";

    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    dbDelta($qry);

    if ($first_install == true) {

        // Add In Poll Question/Answers
        $sql[] = "INSERT INTO {$table_prefix}democracyQ VALUES (1, 'Rate my site', '".time()."', '', '0', '0');";
        $sql[] = "INSERT INTO {$table_prefix}democracyA VALUES (1, 1, 'Best. Blog. Ever.', 0, 0);";
        $sql[] = "INSERT INTO {$table_prefix}democracyA VALUES (2, 1, 'Could be better...', 0, 0);";
        $sql[] = "INSERT INTO {$table_prefix}democracyA VALUES (3, 1, 'My grandma could make a better website!', 0, 0);";
        $sql[] = "INSERT INTO {$table_prefix}democracyA VALUES (4, 1, 'Ooo look, a butterfly!', 0, 0);";
        $sql[] = "INSERT INTO {$table_prefix}democracyA VALUES (5, 1, 'No Comment', 0, 0);";

        foreach($sql as $query) {
        	$wpdb->query($query);
        }

    }
}


// Prints the standings of a poll
function jal_SeeResults($poll_id = 0, $javascript = FALSE) {
    global $wpdb, $table_prefix, $jal_order_answers, $jal_before_question, $jal_after_question, $jal_graph_from_total;

   $order_by = ($jal_order_answers) ? " ORDER BY votes DESC" : " ORDER by aid ASC";

   $where = ($poll_id == 0) ? "active = '1'" : "id = '".intval($poll_id)."'";
   $poll_question = $wpdb->get_row("SELECT id, question, voters FROM {$table_prefix}democracyQ WHERE ".$where);
   $poll_answers = $wpdb->get_results("SELECT aid, answers, votes, added_by FROM {$table_prefix}democracyA WHERE qid = ".$poll_question->id . $order_by);

    if (!$javascript)
        echo "<div id='democracy'>\n\n\t";
    else
        header('Content-Type: text/html; charset='.get_option('blog_charset'));
?>
                                <?php echo $jal_before_question . $poll_question->question . $jal_after_question; ?>
                                <ul>
    <?php

    $output = "";
    $cookie1 = $_COOKIE['demVoted_'.$GLOBALS['table_prefix'].$poll_question->id];
    $cookie2 = $_COOKIE['demVoted_'.$poll_question->id];
    $values = array();

     // Search for the winner of the poll
    foreach ($poll_answers as $row)
        $values[] = $row->votes;

    $winner      = max($values);
    $total_votes = array_sum($values);

    // Loop for the number of answers
    foreach ($poll_answers as $r) {

        // Percent of total votes
        $percent = round($r->votes / ($total_votes + 0.0001) * 100);

        // Percent to display in the graph, as set at the top of the file
        $graph_percent = ($jal_graph_from_total) ? $percent : round($r->votes / ($winner + 0.0001) * 100);

        // See which choice they voted for
        $voted_for_this = ($cookie1 == $r->aid || $cookie2 == $r->aid) ? TRUE : FALSE;
        $user_added = ($r->added_by == "1") ? '<sup title="Added by users">1</sup>' : '';
        if ($r->added_by == "1") {
                $user_added = '<sup title="Added by a guest">1</sup>';
                $add_sup    = TRUE;
        } else $user_added = "";


        // In the graphs, define which class/id to use.
                $graph_hooks = ($voted_for_this) ? 'id="voted-for-this" class="dem-choice-border"' : 'class="dem-choice-border"';

                $output .="\n\t\t\t\t\t";

                $output .= "<li>";

                $output .= $r->answers . $user_added . ": ";
                $output .= "<strong>".$percent."%</strong>";
                $output .= " (".$r->votes.")";

                // Graph it
                $output .= '<span '.$graph_hooks.'><span class="democracy-choice" style="width: '.$graph_percent.'%"></span></span>';

                $output .= "</li>";

                echo $output;

                // reset $output for the next loop
                $output = "";

        } ?>
                                </ul>
                                <p>
                                        <em id="dem-total-votes">Total Votes : <?php echo $total_votes; ?></em>
         <?php /* if they are just looking at the results and haven't voted */
          if (!$_COOKIE['demVoted_'.$GLOBALS['table_prefix'].$poll_question->id]
          && !$_COOKIE['demVoted_'.$poll_question->id]) { ?>                                        <br />
                                        <a href="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES); ?>">Vote</a>
     <?php } ?>     <?php if ($add_sup) echo "<br /><small><sup>1</sup> = Added by a guest</small>"; ?>
                        </p>

                        <?php if (!$javascript) echo "</div>\n\n";
 }

/*
===========================
Archiving Functions
===========================
*/


function jal_democracy_archives ($show_active = FALSE, $before_title = '<h3>',$after_title = '</h3>') {
        global $wpdb, $table_prefix, $jal_graph_from_total;

        $where = ($show_active) ? "" : "WHERE active = '0'";

        $poll_questions = $wpdb->get_results("SELECT * FROM {$table_prefix}democracyQ {$where} ORDER BY id DESC", ARRAY_A);

        $poll_answers   = $wpdb->get_results("SELECT * FROM {$table_prefix}democracyA ORDER BY votes DESC", ARRAY_A);

        $poll_q = array();
        $poll_votes = array();

        // index by poll question id. Much faster than querying for each question
        foreach  ($poll_answers as $answer) {
             // index answer arrays
             $poll_q[$answer['qid']][] = $answer;
             // index total votes
             $poll_votes[$answer['qid']][] = $answer['votes'];
        }

        // loop for all the poll questions
        foreach ($poll_questions as $question) {

                $total_votes = array_sum($poll_votes[$question['id']]);
                $winner = max($poll_votes[$question['id']]);

                echo $before_title.$question['question'].$after_title;
                echo "<br /><strong>Started:</strong> ".date(get_settings('date_format'), $question['timestamp']);
                echo "<br /><strong>Total Votes:</strong> {$total_votes}";

                echo "<ul>";

                foreach  ($poll_q[$question['id']] as $answer) {
                        // Percent of total votes
                        $percent = round($answer['votes'] / ($total_votes + 0.0001) * 100);
                        // Percent to display in the graph, as set at the top of the file
                        $graph_percent = ($jal_graph_from_total) ? $percent : round($answer['votes'] / ($winner + 0.0001) * 100);

                        // See which choice they voted for
                        $voted_for_this = ($cookie == $answer['aid']) ? TRUE : FALSE;

                        if ($answer['added_by'] == "1") {
                                $user_added = '<sup title="Added by a guest">1</sup>';
                                $add_sup    = TRUE;
                        } else $user_added = "";

                        $output = "<li>";

                        $output .= $answer['answers']. $user_added . ": ";
                        $output .= "<strong>".$percent."%</strong>";
                        $output .= " ({$answer['votes']})";

                        // Graph it
                        $output .= '<span class="dem-choice-border"><span class="democracy-choice" style="width: '.$graph_percent.'%"></span></span>';

                        $output .= "</li>";
                        echo $output;
                }
                echo "</ul>";
        }

        if ($add_sup) echo "<br /><small><sup>1</sup> = Added by a guest</small>";
}



/*
===========================
Now, the functions are done
Let's run them....
===========================
*/


// for users with js turned off
if (isset($_GET['jal_nojs'])) {
    include ("../../../wp-config.php");

    $jal_cookie_path = dirname(dirname(dirname(dirname($_SERVER['PHP_SELF']))));

    // Set the cookies, it doesn't matter that they might already have voted.
    if($_POST['poll_aid'] == "newAnswer") {
       $aid = $wpdb->get_var("SELECT aid FROM {$table_prefix}democracyA ORDER BY aid DESC LIMIT 1");
       // Give the user a cookie
       setcookie("demVoted_".$GLOBALS['table_prefix'].$_POST['poll_id'],$aid + 1,time()+$jal_cookietime, $jal_cookie_path);
	} else
	    setcookie("demVoted_".$GLOBALS['table_prefix'].$_POST['poll_id'],$_POST['poll_aid'],time()+$jal_cookietime, $jal_cookie_path);

    // Check their IP against past votes
	jal_checkIP($wpdb->escape($_POST['poll_id']));

    // Add a new answer to the choice list
    if ($_POST['poll_aid'] == "newAnswer") {
    	if ($wpdb->get_var("SELECT allowusers FROM {$table_prefix}democracyQ WHERE id = '".intval($_POST['poll_id'])."'") == 1) {
    		// Add the new choice
    		$wpdb->query("INSERT INTO {$table_prefix}democracyA (qid, answers, votes, added_by) VALUES (".$wpdb->escape($_POST['poll_id']).", '".$wpdb->escape(stripslashes(wp_filter_post_kses($_POST['poll_vote'])))."', 1, 1)");
    	}

    } else
    	$wpdb->query("UPDATE {$table_prefix}democracyA SET votes = (votes+1) WHERE qid = ".$wpdb->escape($_POST['poll_id'])." AND aid = ".$wpdb->escape($_POST['poll_aid']));

    $x = (strstr($_SERVER['HTTP_REFERER'], '?')) ? "&" : "?";

    header("location: ".$_SERVER['HTTP_REFERER'].$x."jal_no_js=true");
}

// When the poll sends the vote
if (isset($_GET['demSend'])) {
	include ("../../../wp-config.php");
	jal_checkIP($wpdb->escape($_POST['poll_id']));

	$poll_id = (int) $_POST['poll_id'];

	if (isset($_POST['new_vote'])) {
		// Make sure users are allowed to add comments
		if ($wpdb->get_var("SELECT allowusers FROM {$table_prefix}democracyQ WHERE id = '{$poll_id}'") == 1) {
    		// Add the new question and give it one vote
    		$wpdb->query("INSERT INTO {$table_prefix}democracyA (qid, answers, votes, added_by) VALUES ($poll_id, '".$wpdb->escape(stripslashes(wp_filter_post_kses($_POST['vote'])))."', 1, 1)");
    	    // Get the new id of the new answer
    	}
	} else
		$wpdb->query("UPDATE {$table_prefix}democracyA SET votes = (votes+1) WHERE qid = {$poll_id} AND aid = ".$wpdb->escape(stripslashes(wp_filter_post_kses($_POST['vote']))));
}

// When the poll wants results
if (isset($_GET['demGet'])) {
	include ("../../../wp-config.php");
   jal_SeeResults($_GET['poll_id'], TRUE);
}

// Make sure WP is running
if (function_exists('add_action')) {

    // javascript for main blog
	 add_action('wp_head', 'jal_add_js');
	 // add the management page to the admin nav bar
    add_action('admin_menu', 'jal_add_page');
    // add javascript to admin area, only on democracy admin page
    if ($_REQUEST['page'] == "democracy")
		add_action('admin_head', 'jal_dem_admin_head');

/* These actions are run through 'init' for security */

    // Run the install script if a plugin is activated
    if (isset($_GET['activate']) && $_GET['activate'] == 'true')
       add_action('init', 'jal_dem_install');

    // Add a new question and its answers via admin panel
    if (isset($_POST['jal_dem_question']))
        add_action('init', 'jal_add_question');

    // When user deletes a poll
    if (isset($_GET['jal_dem_delete']))
        add_action('init', 'jal_delete_poll');

    // When user activates a poll
    if (isset($_GET['jal_dem_activate']))
        add_action('init', 'jal_activate_poll');

    // When user deactivates a poll
    if (isset($_GET['jal_dem_deactivate']))
        add_action('init', 'jal_deactivate_poll');

    // When a user edits a poll
    if (isset($_POST['jal_dem_edit']))
        add_action('init', 'jal_edit_poll');

	add_action( 'plugins_loaded', 'widget_democracy_init' );
}



/*  Copyright 2006  Alkar  (email : alkarin@gmail.com)

		  This code is based on Wayback_Widget, by Sven Weidauer (web:dergraf.net email:dergraf@dergraf.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

function widget_democracy_init()
{
	if (!function_exists('register_sidebar_widget')) return;
	if (!function_exists('jal_democracy')) return;

	function widget_democracy( $args )
	{
		extract( $args );
		$options = get_option( 'widget_democracy' );

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		if ( !$title )
		{
			$title = __('Democracy');
		}

		$before_widget = str_replace('id="democracy"', 'id="widget_democracy"', $before_widget);

		echo $before_widget . $before_title . $title . $after_title;
		jal_democracy();
		echo $after_widget;
	}

	function widget_democracy_control()
	{
		$options = get_option( 'widget_democracy' );
		if (!is_array( $options )) {
			$options = array( 'title' => 'Democracy' );
		}

		if ($_POST['democracy-submit']) {
			$options['title'] = stripslashes(wp_filter_post_kses(strip_tags($_POST['democracy-title'])));

			update_option( 'widget_democracy', $options );
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		?>			<ul>
				<li>Title: <input type="text" id="democracy-title" name="democracy-title" value="<?php echo $title ?>" /></li>
			</ul>
			<input type="hidden" id="democracy-submit" name="democracy-submit" value="1" />
		<?php
	}

	register_sidebar_widget( 'Democracy', 'widget_democracy' );
	register_widget_control( 'Democracy', 'widget_democracy_control', 300, 300 );
}

?>