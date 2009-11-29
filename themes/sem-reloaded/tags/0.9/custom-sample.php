<?php
#
# The Semiologic Reloaded theme's custom.php feature allows you to add arbitrary php
# to the theme's template if you ever need to.
#
# To use this file, rename it as custom.php. Then, edit whichever functions below that
# you might need, and comment out (remove the #) the add_action() statement that follows
# each one that you're using
#
# Important: We cannot possibly support users who try to write php without the slightest
# understanding of what they're doing. This feature is provided as a convenience, but
# the rule of thumb is that you're on your own if you decide to use it. If you ask for
# php support in the Semiologic forum, you will be ignored.
#
# If you email support at semiologic dot com, we might help depending on our availability,
# but keep in mind that you will be invoiced (in advance) at our going rate. (Count a $50
# budget for the simplest tasks.) Thanks for understanding.
#


/**
 * my_custom_head()
 *
 * This function will output arbitrary HTML in between the page's <head> and </head> tags
 **/

function my_custom_head() {
?>

<!-- Google Analytics verification code -->
<meta name="verify-v1" content="..." />

<!-- arbitrary script -->
<script type="text/javascript">
/* your script goes here */
</script>

<!-- arbitrary style -->
<style type="text/css">
/* your style goes here */
</style>

<?php
} # my_custom_head()

# Remove the leading pound below to use the above function
# add_action('wp_head', 'my_custom_head');



/**
 * my_custom_footer()
 *
 * This function will output arbitrary HTML before the page's closing </body> tag
 **/

function my_custom_footer() {
?>

<!-- arbitrary script -->
<script type="text/javascript">
// your script goes here
</script>

<?php
} # my_custom_footer()

# Remove the leading pound below to use the above function
# add_action('wp_footer', 'my_custom_footer');



/**
 * my_custom_php()
 *
 * This function will run arbitrary php before anything is output.
 *
 * Important: Do not include the surrounding <?php and ?> tags
 **/

function my_custom_php() {
	// php code goes here, e.g.:
	
	// include yoursite.com/folder/file.php
	include_once ABSPATH . 'folder/file.php';
	
	// include http://www.remotesite.com/folder/file.php (doesn't work on all servers!)
	include_once 'http://www.remotesite.com/folder/file.php';
} # my_custom_php()

# Remove the leading pound below to use the above function
# add_action('wp', 'my_custom_php');



/**
 * my_conditional_php()
 *
 * This function will conditionally run arbitrary php before anything is output.
 *
 * See http://codex.wordpress.org/Conditional_Tags more more details
 *
 * Important: Do not include the surrounding <?php and ?> tags
 **/

function my_conditional_php() {
	if ( is_page("My Page") ) :
	
	// php code goes here, e.g.:
	
	$referrer = urlencode($_SERVER['HTTP_REFERER']);
	
	if ( !$referrer )
		$referrer = 'Unknown';
	
	if ( !$_GET['apflag'] ) {
		wp_redirect("http://www.cprophet.com/ap/go.php?...");
		die;
	}
	
	
	elseif ( is_page("My Other Page") ) :
	
	// more php code could go here...
	
	
	endif;
} # my_conditional_php()

# Remove the leading pound below to use the above function
# add_action('wp', 'my_conditional_php');

