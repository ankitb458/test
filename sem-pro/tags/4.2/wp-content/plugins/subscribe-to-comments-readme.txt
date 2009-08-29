=== Subscribe to Comments ===
Tags: comments, subscription
Contributors: markjaquith

Subscribe to Comments 2 is a WordPress plugin that allows commenters on an entry to subscribe to e-mail notifications for subsequent comments. It does NOT e-mail you new blog posts. For that functionality, use Skippy's Subscribe2 plugin.

== Installation ==

1. Put subscribe-to-comments.php into [wordpress_dir]/wp-content/plugins/
2. Put wp-subscription-manager.php into your blog's root WordPress directory (the directory where wp-config.php resides)
3. Go into the WordPress admin interface and activate the plugin
4. Optional: if your WordPress 1.5 theme doesn't have the comment_form hook, or if you would like to manually determine where in your comments form the subscribe checkbox appears, enter this where you would like it: <?php show_subscription_checkbox(); ?>
5. Optional: If you would like to enable users to subscribe to comments without having to leave a comment, place this somewhere in your template, but make sure it is <strong>outside the comments form</strong>.  A good place would be right after the ending </form> tag for the comments form: <?php show_manual_subscription_form(); ?>

== Frequently Asked Questions ==

= How can I tell if it's working? =

1. Log out of WordPress
2. Leave a comment on an entry and check the comment subscription box, using an e-mail that is NOT the WP admin e-mail address or the e-mail address of the author of the post.
3. Leave a second comment (don't have to subscribed) using any old e-mail address
4. This should trigger a notification to the first address you used.

= I'd like the subscription checkbox to be checked by default.  Can I do that? =

By default, the "subscribe" checkbox is unchecked, but you can change that in the options (i.e. so that it is checked by default).