<?php
/*
Plugin Name: Paged Comment Editing 
Plugin URI: http://www.coldforged.org/paged-comment-editing-plugin/
Description: Replaces the stock Comments Management page with a more flexible page that allows paging through comments as well as optionally showing _all_ comments, including spam comments. This way it is possible to correct false-positives and monitor the activity of the built-in spam countermeasures. Simply activate the plugin and visit the Manage -> Comments page.
Version: 0.4
Author: Brian "ColdForged" Dupuis
Author URI: http://www.coldforged.org
*/

/*
Paged Comment Editing plugin for WordPress
Copyright (C) 2005  Brian "ColdForged" Dupuis

Parts of this code adapted from WordPress 1.5 Strayhorn.

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
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

if( !function_exists('PagedCommentEditing_add_submenu') ) {
	function PagedCommentEditing_add_submenu() {
		// This would be the normal way of adding a submenu. But why have two
		// different methods of viewing and editing comments? Let's get a 
		// little kinky and hijack the main comments editing page. Yeah,
		// that sounds a _whole_ lot more fun.
		/*add_management_page('Edit Full Comments','Full Comments', 1, __FILE__, 'PagedCommentEditing_view_comments');*/
		global $submenu;

		// Dig through all of the submenus of the edit.php parent page.
		foreach($submenu['edit.php'] as $key => $value) {
			// Is it the one we desire to usurp?
				if( 'edit-comments.php' == $value[2] ) {
			   // Hell yes, let's get in there and boot that other thing
			   // out the way. Yo.
				$submenu['edit.php'][$key][2] = basename(__FILE__);
/*				$hookname = get_plugin_page_hookname(plugin_basename(__FILE__), plugin_basename('edit.php'));
				add_action($hookname, 'PagedCommentEditing_view_comments');*/
			}
		}
	}
}

if( !function_exists( 'PagedCommentEditing_construct_url' ) ) {

	function PagedCommentEditing_construct_url( $s, $mode, $spam, $offset, $perpage ) {
		return "?page=".plugin_basename(__FILE__).( '' != $s ? "&s=$s":'').( '' != $mode ? "&mode=$mode":'').( '' != $spam ? "&spam=$spam":'').( '' != $offset ? "&offset=$offset":'').('' != $perpage ? "&perpage=$perpage":'');
	}
}

if( !function_exists('PagedCommentEditing_view_comments') ) {
	
	function PagedCommentEditing_view_comments() {
		// Only run once. Some 1.5 base installs don't like the way I deal with plugins.
		static $already_run = false;
		if( $already_run )
			return;
		$already_run = true;
		require_once( dirname(dirname(dirname(__FILE__))).'/wp-config.php');
		global $wpdb, $tablecomments, $tableposts, $comment, $user_ID;

		if( !isset( $tablecomments ) )
			$tablecomments = $wpdb->comments;
		if( !isset( $tableposts ) )
			$tableposts = $wpdb->posts;

		if (empty($_GET['mode'])) $mode = 'view';
		else $mode = wp_specialchars($_GET['mode'], 1);
		if(empty($_GET['spam'])) $spam = 'exclude';
		else $spam = wp_specialchars($_GET['spam'], 1);

		if ( isset($_GET['offset']) )
			$offset = (int) $_GET['offset'];
		else
			$offset = 0;

		if( isset( $_GET['perpage']) )
			$perpage = (int) $_GET['perpage'];
		else
			$perpage = 20;
		if( empty($_GET['s'])) $s = '';
		else $s = $wpdb->escape($_GET['s']);
?>  
	
<style type="text/css" media="screen">
.spam.alternate {
		background-color: #FFD0D0;
}

.spam {
		background-color: #FFE0E0;
		color: #666;
}

.spam a:link {
		color: #C00;
}

.spam a:visited {
		color: #500;
}

.spam a:hover {
		color: #F22;
}
</style>

<script type="text/javascript">
	<!--
	function checkAll(form)
	{
		for( i = 0, n = form.elements.length; i < n; i++ ) {
			if( form.elements[i].type == "checkbox" ) {
				if( form.elements[i].checked == true )
					form.elements[i].checked = false;
				else
					form.elements[i].checked = true;
			}
		}
	}
	//-->
</script>
<div class="wrap">
	<h2><?php echo ('include' == $spam ? __('Full Comments <em>(including blacklisted comments)</em>'):('only' == $spam ? __('Blacklisted Comments <em>(only displaying blacklisted comments)</em>'):'Comments')); ?></h2>
	<form name="searchform" action="" method="get">
	<input type="hidden" name="page" value="<?php echo plugin_basename( __FILE__ ) ?>" />  
		<fieldset>
			<legend>
<?php _e('Show Comments That Contain...') ?>
			</legend>
			<input type="text" name="s" value="<?php if (isset($_GET['s'])) echo wp_specialchars($_GET['s'], 1); ?>" size="17" />
			<input type="submit" name="submit" value="<?php _e('Search') ?>"  />
			<input type="hidden" name="mode" value="<?php echo $mode; ?>" />
			<input type="hidden" name="spam" value="<?php echo $spam; ?>" />
			<input type="hidden" name="offset" value="<?php echo $offset; ?>" />
			<input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
  <?php _e('(Searches within comment text, e-mail, URI, and IP address.)') ?>
  
		</fieldset>
	</form>
	<p>
	<a href="<?php echo PagedCommentEditing_construct_url($s,'view',$spam,$offset,$perpage);?>"><?php _e('View Mode') ?></a> | 
	<a href="<?php echo PagedCommentEditing_construct_url($s,'edit',$spam,$offset,$perpage);?>"><?php _e('Mass Edit Mode') ?></a> | 
	<?php if( ( 'include' == $spam ) || ( 'only' == $spam ) ) { ?>
		<a href="<?php echo PagedCommentEditing_construct_url($s,$mode,'exclude',$offset,$perpage);?>"><?php _e('Exclude Spam') ?></a>
	<?php } else { ?>
		<a href="<?php echo PagedCommentEditing_construct_url($s,$mode,'include',$offset,$perpage);?>"><?php _e('Include Spam') ?></a>
	<?php } ?>
	<?php if( 'only' != $spam ) { ?>
		| <a href="<?php echo PagedCommentEditing_construct_url($s,$mode,'only',$offset,$perpage);?>"><?php _e('Only Spam') ?></a>
	<?php } ?>
	</p>
<?php
	 if ( !empty( $_POST['checked_comments'] ) ) :
		 $i = 0;
	 foreach ($_POST['checked_comments'] as $comment) : // Check the permissions on each
		 $comment = (int) $comment;
	 $post_id = $wpdb->get_var("SELECT comment_post_ID FROM $tablecomments WHERE comment_ID = $comment");
	 $authordata = get_userdata( $wpdb->get_var("SELECT post_author FROM $tableposts WHERE ID = $post_id") );
	 if ( user_can_delete_post_comments($user_ID, $post_id) ) :
		if( !empty( $_POST['Approve'] ) ) {
			$wpdb->query("UPDATE $tablecomments SET comment_approved = 1 WHERE comment_ID = $comment"); 
		} else {
			$wpdb->query("DELETE FROM $tablecomments WHERE comment_ID = $comment");
		}
	 ++$i;
	 endif;
	 endforeach;
	if( !empty( $_POST['Approve'] ) ) {
		echo "<div class='wrap'><p>" . sprintf(__('%s comments approved.'), $i) . "</p></div>";
	} else {
		echo "<div class='wrap'><p>" . sprintf(__('%s comments deleted.'), $i) . "</p></div>";
	}
	 endif;

	 if ( '' != $s ) {
		$and_spam = ( 'include' == $spam ? ' ': ('exclude' == $spam ? " AND comment_approved != 'spam'" : " AND comment_approved = 'spam'") );
		$where_clause = " WHERE (comment_author LIKE '%$s%' OR
			comment_author_email LIKE '%$s%' OR
			comment_author_url LIKE ('%$s%') OR
			comment_author_IP LIKE ('%$s%') OR
			comment_content LIKE ('%$s%') )
			$and_spam";
	 } else {
		 $where_clause = ( 'include' == $spam ? ' ': ('exclude' == $spam ? " WHERE comment_approved != 'spam'" : " WHERE comment_approved = 'spam'") );
	 }

	 // paging code inspired by the lovely and talented Scripty Goddess
	 $pages = $wpdb->get_var("SELECT COUNT(*) FROM $tablecomments $where_clause");
	 $pages = (int)( $pages / $perpage )+1;
	 if( ($offset+1) > $pages )
		 $offset = $pages - 1;
	 $page_string = '<p><form name="numperpage" action="" method="get">';
	 if($pages > 1)
	 {
		 $lowest_displayed = ($offset+1) - 3;
		 $highest_displayed = ($offset+1) + 3;
		 
		 $page_string .= __('Page: ');

		 // Enhancement - let's put some easy 'next'/'previous' links up there. 
		 if( $offset ) {
			 $page_string .= ' (<a href="'.PagedCommentEditing_construct_url($s,$mode,$spam,$offset-1,$perpage).'">&laquo; '.__('Previous').'</a> | ';
		 } else {
			 $page_string .= ' (&laquo; '.__('Previous').' | ';
		 }
		 if( $offset != $pages-1 ) {
			 $page_string .= '<a href="'.PagedCommentEditing_construct_url($s,$mode,$spam,$offset+1,$perpage).'">'.__('Next').' &raquo;</a>)';
		 } else {
			 $page_string .= __('Next &raquo;');
		 }
		 $page_string .= '&nbsp;&nbsp;';

		 for($i=1; $i <= $pages; $i++)
		 {
			 if( ($i == 1) || ( $i == $pages ) || ( $i > $lowest_displayed && $i < $highest_displayed ) )
			 {
				 if( $i != 1 )
					 $page_string .= '|';
				 if( $i == ( $offset + 1 ) )
					 $page_string .= "<strong>$i</strong>"; 
				 else
					 $page_string .= "<a href='".PagedCommentEditing_construct_url($s,$mode,$spam,$i-1,$perpage)."'>$i</a>";
				 $ellipsis_inserted = false;
			 }
			 else
			 {
				 if( !$ellipsis_inserted )
				 {
					 $page_string .= '|...';
					 $ellipsis_inserted = true;
				 }
			 }
		 }
		 $page_string .= '&nbsp;&nbsp;&nbsp;';
	 }

	 $page_string .= 
		 '<input type="hidden" name="page" value="'.plugin_basename( __FILE__ ).'" />'.
		 '<input type="submit" name="view" value="'.__('Show:').'"  />'.
		 '<input type="text" name="perpage" value="'.$perpage.'" size="3" />'.
		 '<input type="hidden" name="mode" value="'.$mode.'" />'.
		 '<input type="hidden" name="spam" value="'.$spam.'" />'.
		 '<input type="hidden" name="offset" value="'.$offset.'" />'.
		 '<input type="hidden" name="s" value="'.$s.'" />'.
		 __('comments per page.').'</p>'.
		 '</form>';
	 $page_string .= '</p>';
	 echo "$page_string";
		 
	 $offset = (int) $offset * $perpage;
	 $comments = $wpdb->get_results("SELECT * FROM $tablecomments $where_clause ORDER BY comment_date DESC LIMIT $offset,$perpage");

	 if ('view' == $mode) {
		 if ($comments) {
			 if ($offset)
				 $start = " start='$offset'";
			 else
				 $start = '';
			 
			 echo "<ol class='commentlist' $start>";
			 $i = 0;
			 foreach ($comments as $comment) {
				 ++$i; $class = '';
				 $authordata = get_userdata($wpdb->get_var("SELECT post_author FROM $tableposts WHERE ID = $comment->comment_post_ID"));
				 $comment_status = wp_get_comment_status($comment->comment_ID);
				 if ('spam' == $comment_status)
					 $class .= ' spam';
				 else if ('unapproved' == $comment_status) 
					 $class .= ' unapproved';
				 if ($i % 2)
					 $class .= ' alternate';
				 echo "<li class='$class'>";
?>			
		
	<p>
	<strong><?php _e('Name:') ?></strong> <?php comment_author() ?> <?php if ($comment->comment_author_email) { ?>| 
	<strong><?php _e('E-mail:') ?></strong> <?php comment_author_email_link() ?> <?php } if ($comment->comment_author_url) { ?> 
	| <strong><?php _e('URI:') ?></strong> <?php comment_author_url_link() ?> <?php } ?>| 
	<strong><?php _e('IP:') ?></strong> <a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput=<?php comment_author_IP() ?>"><?php comment_author_IP() ?></a>
	</p>

		<?php comment_text() ?>
	
	<p>
<?php _e('Posted'); echo ' '; comment_date('M j, g:i A');  
			if ( user_can_edit_post_comments($user_ID, $comment->comment_post_ID) ) {
				echo " | <a href=\"post.php?action=editcomment&amp;comment=".$comment->comment_ID."\">" . __('Edit Comment') . "</a>";
			}
			if ( user_can_delete_post_comments($user_ID, $comment->comment_post_ID) ) {
				echo " | <a href=\"post.php?action=deletecomment&amp;p=".$comment->comment_post_ID."&amp;comment=".$comment->comment_ID."\" onclick=\"return confirm('" . sprintf(__("You are about to delete this comment by \'%s\'\\n  \'Cancel\' to stop, \'OK\' to delete."), $comment->comment_author) . "')\">" . __('Delete Comment') . "</a> &#8212; ";
			} // end if any comments to show
			// Get post title
			if ( user_can_edit_post($user_ID, $comment->comment_post_ID) ) {
				$post_title = $wpdb->get_var("SELECT post_title FROM $tableposts WHERE ID = $comment->comment_post_ID");
				$post_title = ('' == $post_title) ? "# $comment->comment_post_ID" : $post_title;
				?> <a href="post.php?action=edit&amp;post=<?php echo $comment->comment_post_ID; ?>"><?php printf(__('Edit Post &#8220;%s&#8221;'), stripslashes($post_title)); ?></a>
				<?php } ?>
				 | <a href="<?php echo get_permalink($comment->comment_post_ID); ?>"><?php _e('View Post') ?></a>
	</p>
	</li>

	<?php } // end foreach ?>
	</ol>

	<?php
		 echo "$page_string";
		 } else {
			 
		?>
		
	<p>
	<strong><?php _e('No comments found.') ?></strong>
	</p>

		<?php
	} // end if ($comments)
} elseif ('edit' == $mode) {

	if ($comments) {
		echo '<form name="deletecomments" id="deletecomments" action="" method="post"> 
		<table width="100%" cellpadding="3" cellspacing="3">
  <tr>
	<th scope="col">*</th>
	<th scope="col">' .  __('Name') . '</th>
	<th scope="col">' .  __('E-mail') . '</th>
	<th scope="col">' . __('IP') . '</th>
	<th scope="col">' . __('Comment Excerpt') . '</th>
	<th scope="col" colspan="3">' .  __('Actions') . '</th>
  </tr>';
		foreach ($comments as $comment) {
		$authordata = get_userdata($wpdb->get_var("SELECT post_author FROM $tableposts WHERE ID = $comment->comment_post_ID"));
		$class = (strpos($class,'alternate') !== false) ? '' : 'alternate';
		$comment_status = wp_get_comment_status($comment->comment_ID);
		if ('spam' == $comment_status)
			$class .= ' spam';
?>
  
	<tr class='<?php echo $class; ?>'>
		<td><?php if (user_can_delete_post_comments($user_ID, $comment->comment_post_ID) ) { ?>
			<input type="checkbox" name="checked_comments[]" value="<?php echo $comment->comment_ID; ?>" /><?php } ?></td>
		<td><?php comment_author_link() ?></td>
		<td><?php comment_author_email_link() ?></td>
		<td><a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput=<?php comment_author_IP() ?>"><?php comment_author_IP() ?></a></td>
		<td><?php comment_excerpt(); ?></td>
		<td><a href="<?php echo get_permalink($comment->comment_post_ID); ?>#comment-<?php comment_ID() ?>" class="edit"><?php _e('View') ?></a></td>
		<td><?php if ( user_can_edit_post_comments($user_ID, $comment->comment_post_ID) ) {
	echo "<a href='post.php?action=editcomment&amp;comment=$comment->comment_ID' class='edit'>" .  __('Edit') . "</a>"; } ?></td>
		<td><?php if ( user_can_delete_post_comments($user_ID, $comment->comment_post_ID) ) {
			echo "<a href=\"post.php?action=deletecomment&amp;p=".$comment->comment_post_ID."&amp;comment=".$comment->comment_ID."\" onclick=\"return confirm('" . sprintf(__("You are about to delete this comment by \'%s\'\\n  \'Cancel\' to stop, \'OK\' to delete."), $comment->comment_author) . "')\"	class='delete'>" . __('Delete') . "</a>"; } ?></td>
	</tr>
		<?php 
		} // end foreach
	?>
	</table>
	<p>
	<a href="javascript:;" onclick="checkAll(document.getElementById('deletecomments')); return false; "><?php _e('Invert Checkbox Selection') ?></a>
	</p>
	<p class="submit">
	<input type="submit" name="Approve" value="<?php _e('Approve Checked Comments') ?>" onclick="return confirm('<?php _e("Are you sure you want to approve these comments? \\n \'Cancel\' to stop, \'OK\' to approve.") ?>')" />
	<input type="submit" name="Submit" value="<?php _e('Delete Checked Comments') ?> &raquo;" onclick="return confirm('<?php _e("You are about to delete these comments permanently \\n  \'Cancel\' to stop, \'OK\' to delete.") ?>')" />
	</p>
	</form>
<?php
		echo "<p>$page_string</p>";
	} else {
?>
	<p>
	<strong><?php _e('No results found.') ?></strong>
	</p>
<?php
	} // end if ($comments)

}
	?>

</div>
<?php
	}
}

if( is_plugin_page() )
{
	// A bit of a kludgy work-around for some 1.5 installations with many plugins.
	PagedCommentEditing_view_comments();
}

	add_action('admin_menu', 'PagedCommentEditing_add_submenu'); 
?>
