<?php


# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


// need to declare these global;
global $wpdb, $user_level, $sg_subscribe;

if (!function_exists('sg_subscribe_start')) {
	require('./wp-blog-header.php');

	if (!function_exists('sg_subscribe_start'))
		die (__('You must activate the "Subscribe to Comments" plugin in the WordPress admin panel', 'subscribe-to-comments'));

	sg_subscribe_start();
	$sg_subscribe->form_action = 'wp-subscription-manager.php';
	$sg_subscribe->standalone = true;
	ob_start(create_function('$a', 'return str_replace("<title>", "<title> " . __("Subscription Manager", "subscribe-to-comments") . " &raquo; ", $a);'));
} else {
	sg_subscribe_start();
	$sg_subscribe->form_action = 'edit.php?page=subscribe-to-comments.php';
	$sg_subscribe->standalone = false;
}

$sg_subscribe->manager_init();

get_currentuserinfo();

if ( !$sg_subscribe->validate_key() )
	die ( __('You may not access this page without a valid key.', 'subscribe-to-comments') );

$sg_subscribe->determine_action();

switch ($sg_subscribe->action) :

	case "change_email" :
		if ( $sg_subscribe->change_email() ) {
		$sg_subscribe->add_message(sprintf(__('All notifications that were formerly sent to <strong>%s</strong> will now be sent to <strong>%s</strong>!', 'subscribe-to-comments'), $sg_subscribe->email, $sg_subscribe->new_email));
		// change info to the new email
		$sg_subscribe->email = $sg_subscribe->new_email;
		unset($sg_subscribe->new_email);
		$sg_subscribe->key = $sg_subscribe->generate_key($sg_subscribe->email);
		$sg_subscribe->validate_key();
		}
		break;

	case "remove_subscriptions" :
		$postsremoved = $sg_subscribe->remove_subscriptions($_POST['subscrips']);
		if ( $postsremoved > 0 )
		$sg_subscribe->add_message(sprintf(__('<strong>%s</strong> %s removed successfully.', 'subscribe-to-comments'), $postsremoved, ($postsremoved != 1) ? __('subscriptions', 'subscribe-to-comments') : __('subscription', 'subscribe-to-comments')));
		break;

	case "remove_block" :
		if ($sg_subscribe->remove_block($sg_subscribe->email))
			$sg_subscribe->add_message(sprintf(__('The block on <strong>%s</strong> has been successfully removed.', 'subscribe-to-comments'), $sg_subscribe->email));
		else
			$sg_subscribe->add_error(sprintf(__('<strong>%s</strong> isn\'t blocked!', 'subscribe-to-comments'), $sg_subscribe->email), 'manager');
		break;

	case "email_change_request" :
		if ($sg_subscribe->is_blocked($sg_subscribe->email))
			$sg_subscribe->add_error(sprintf(__('<strong>%s</strong> has been blocked from receiving notifications.  You will have to have the administrator remove the block before you will be able to change your notification address.', 'subscribe-to-comments'), $sg_subscribe->email));
		else
			if ($sg_subscribe->change_email_request($sg_subscribe->email, $sg_subscribe->new_email))
				$sg_subscribe->add_message(sprintf(__('Your change of e-mail request was successfully received.  Please check your old account (<strong>%s</strong>) in order to confirm the change.', 'subscribe-to-comments'), $sg_subscribe->email));
		break;

	case "block_request" :
		if ($sg_subscribe->block_email_request($sg_subscribe->email ))
			$sg_subscribe->add_message(sprintf(__('Your request to block <strong>%s</strong> from receiving any further notifications has been received.  In order for you to complete the block, please check your e-mail and click on the link in the message that has been sent to you.', 'subscribe-to-comments'), $sg_subscribe->email));
		break;

	case "solo_subscribe" :
		$sg_subscribe->add_message(sprintf(__('<strong>%s</strong> has been successfully subscribed to %s', 'subscribe-to-comments'), $sg_subscribe->email, $sg_subscribe->entry_link($_GET['subscribeid'])));
		break;

	case "block" :
		if ($sg_subscribe->add_block($sg_subscribe->email))
			$sg_subscribe->add_message(sprintf(__('<strong>%s</strong> has been added to the "do not mail" list. You will no longer receive any notifications from this site. If this was done in error, please contact the <a href="mailto:%s">site administrator</a> to remove this block.', 'subscribe-to-comments'), $sg_subscribe->email, $sg_subscribe->site_email));
		else
			$sg_subscribe->add_error(sprintf(__('<strong>%s</strong> has already been blocked!', 'subscribe-to-comments'), $sg_subscribe->email), 'manager');
		$sg_subscribe->key = $sg_subscribe->generate_key($sg_subscribe->email);
		$sg_subscribe->validate_key();
		break;

endswitch;



if ($sg_subscribe->standalone) {
if ( !$sg_subscribe->use_wp_style && !empty($sg_subscribe->header) ) {

include ( $sg_subscribe->header );
echo $sg_subscribe->before_manager;
} else { ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php printf(__('%s Comment Subscription Manager', 'subscribe-to-comments'), bloginfo('name')); ?></title>

	<style type="text/css" media="screen">
		@import url( <?php echo get_settings('siteurl'); ?>/wp-admin/wp-admin.css );
	</style>

	<link rel="stylesheet" type="text/css" media="print" href="<?php echo get_settings('siteurl'); ?>/print.css" />

	<meta http-equiv="Content-Type" content="text/plain;
charset=<?php bloginfo('charset'); ?>" />

<?php $sg_subscribe->sg_wp_head(); ?>
</head>
<body>
<?php } ?><?php } ?>

<?php $sg_subscribe->show_messages(); ?>
<?php $sg_subscribe->show_errors(); ?>

<div class="wrap">
<h2><?php bloginfo('name'); ?> <?php _e('Comment Subscription Manager', 'subscribe-to-comments'); ?></h2>

<?php if (!empty($sg_subscribe->ref)) : ?><?php $sg_subscribe->add_message(sprintf(__('Return to the page you were viewing: %s', 'subscribe-to-comments'), $sg_subscribe->entry_link(url_to_postid($sg_subscribe->ref), $sg_subscribe->ref))); ?><?php $sg_subscribe->show_messages(); ?><?php endif; ?>


<?php if ($sg_subscribe->is_blocked()) { ?>
<?php if ( current_user_can('manage_options') ) : ?>
	<fieldset class="options">
		<legend><?php _e('Remove Block', 'subscribe-to-comments'); ?></legend>

		<p>
		<?php printf(__('Click the button below to remove the block on <strong>%s</strong>.  This should only be done if the user has specifically requested it.', 'subscribe-to-comments'), $sg_subscribe->email); ?>		</p>

		<form name="removeBlock" method="post" action="<?php echo $sg_subscribe->form_action; ?>">
		<input type="hidden" name="removeBlock" value="removeBlock /">
<?php $sg_subscribe->hidden_form_fields(); ?>
		<p class="submit">
		<input type="submit" name="submit" value="<?php _e('Remove Block &raquo;', 'subscribe-to-comments'); ?>" />
		</p>
		</form>
	</fieldset>

<?php else : ?>
	<fieldset class="options">
		<legend><?php _e('Blocked', 'subscribe-to-comments'); ?></legend>

		<p>
		<?php printf(__('You have indicated that you do not wish to receive any notifications at <strong>%s</strong> from this site. If this is incorrect, or if you wish to have the block removed, please contact the <a href="mailto:%s">site administrator</a>.', 'subscribe-to-comments'), $sg_subscribe->email, $sg_subscribe->site_email); ?>		</p>
	</fieldset>

<?php endif; ?>

<?php } else { ?>

<?php $postlist = $sg_subscribe->subscriptions_from_email(); ?>
<?php if (isset($sg_subscribe->email) && !is_array($postlist) && $sg_subscribe->email != $sg_subscribe->site_email && $sg_subscribe->email != get_bloginfo('admin_email') ) { ?>	<?php if (is_email($sg_subscribe->email)) : ?>		<?php $sg_subscribe->add_error(sprintf(__('<strong>%s</strong> is not subscribed to any posts on this site.', 'subscribe-to-comments'), $sg_subscribe->email)); ?>	<?php else : ?>		<?php $sg_subscribe->add_error(sprintf(__('<strong>%s</strong> is not a valid e-mail address.', 'subscribe-to-comments'), $sg_subscribe->email)); ?>	<?php endif; ?><?php } ?>
<?php $sg_subscribe->show_errors(); ?>



<?php if ( current_user_can('manage_options') ) { ?>
	<fieldset class="options">
		<legend><?php _e('Find Subscriptions', 'subscribe-to-comments'); ?></legend>

		<p>
		<?php _e('Enter an e-mail address to view its subscriptions or undo a block.', 'subscribe-to-comments'); ?>		</p>

		<form name="getemail" method="post" action="<?php echo $sg_subscribe->form_action; ?>">
		<input type="hidden" name="ref" value="<?php echo $sg_subscribe->ref; ?>" />

		<p>
		<input name="email" type="text" id="email" size="40" />
		<input type="submit" value="<?php _e('Search &raquo;', 'subscribe-to-comments'); ?>" />
		</p>
		</form>
	</fieldset>

<?php } ?>
<?php if ( count($postlist) > 0 && is_array($postlist) ) { ?>

<script type="text/javascript">
<!--
function checkAll(form) {
for( i = 0, n = form.elements.length; i < n; i++ ) {
	if( form.elements[i].type == "checkbox" ) {
		if( form.elements[i].checked == true )
			form.elements[i].checked = false;
		else
			form.elements[i].checked = true;
            }
        }
    }
// -->
</script>

	<fieldset class="options">
		<legend><?php _e('Subscriptions', 'subscribe-to-comments'); ?></legend>

			<p>
			<?php printf(__('<strong>%s</strong> is subscribed to the posts listed below. To unsubscribe to one or more posts, click the checkbox next to the title, then click "Remove Selected Subscription(s)" at the bottom of the list.', 'subscribe-to-comments'), $sg_subscribe->email); ?>			</p>

			<form name="removeSubscription" id="removeSubscription" method="post" action="<?php echo $sg_subscribe->form_action; ?>">
			<input type="hidden" name="removesubscrips" value="removesubscrips" />
<?php $sg_subscribe->hidden_form_fields(); ?>
			<ol>
			<?php for ($i = 0; $i < count($postlist); $i++) { ?>				<li><label for="subscrip-<?php echo $i; ?>"><input id="subscrip-<?php echo $i; ?>" type="checkbox" name="subscrips[]" value="<?php echo $postlist[$i]; ?>" /> <?php echo $sg_subscribe->entry_link($postlist[$i]); ?></label></li>
			<?php } ?>			</ol>

			<p>
			<a href="javascript:;" onclick="checkAll(document.getElementById('removeSubscription')); return false; "><?php _e('Invert Checkbox Selection', 'subscribe-to-comments'); ?></a>
			</p>

			<p class="submit">
			<input type="submit" name="submit" value="<?php _e('Remove Selected Subscription(s) &raquo;', 'subscribe-to-comments'); ?>" />
			</p>
			</form>
	</fieldset>
</div>

<div class="wrap">
<h2><?php _e('Advanced Options', 'subscribe-to-comments'); ?></h2>

	<fieldset class="options">
		<legend><?php _e('Block All Notifications', 'subscribe-to-comments'); ?></legend>

			<form name="blockemail" method="post" action="<?php echo $sg_subscribe->form_action; ?>">
			<input type="hidden" name="blockemail" value="blockemail" />
<?php $sg_subscribe->hidden_form_fields(); ?>
			<p>
			<?php printf(__('If you would like <strong>%s</strong> to be blocked from receiving any notifications from this site, click the button below.  This should be reserved for cases where someone is signing you up for notifications without your consent.', 'subscribe-to-comments'), $sg_subscribe->email); ?>			</p>

			<p class="submit">
			<input type="submit" name="submit" value="<?php _e('Block Notifications &raquo;', 'subscribe-to-comments'); ?>" />
			</p>
			</form>
	</fieldset>

	<fieldset class="options">
		<legend><?php _e('Change E-mail Address', 'subscribe-to-comments'); ?></legend>

			<form name="changeemailrequest" method="post" action="<?php echo $sg_subscribe->form_action; ?>">
			<input type="hidden" name="changeemailrequest" value="changeemailrequest" />
<?php $sg_subscribe->hidden_form_fields(); ?>
			<p>
			<?php printf(__('If you would like to change the e-mail address for your subscriptions, enter the new address below.  You will be required to verify this request by clicking a special link sent to your current address (<strong>%s</strong>).', 'subscribe-to-comments'), $sg_subscribe->email); ?>			</p>

			<p>
			<?php _e('New E-mail Address:', 'subscribe-to-comments'); ?>			<input name="new_email" type="text" id="new_email" size="40" />
			</p>

			<p class="submit">
			<input type="submit" name="submit" value="<?php _e('Change E-mail Address &raquo;', 'subscribe-to-comments'); ?>" />
			</p>
			</form>
	</fieldset>

		<?php } ?><?php } //end if not in do not mail ?></div>

<?php if ( $sg_subscribe->standalone ) : ?><?php if ( !$sg_subscribe->use_wp_style ) :
echo $sg_subscribe->after_manager;

if ( !empty($sg_subscribe->sidebar) )
	include_once($sg_subscribe->sidebar);
if ( !empty($sg_subscribe->footer) )
	include_once($sg_subscribe->footer);
?><?php else : ?></body>
</html>
<?php endif; ?><?php endif; ?>