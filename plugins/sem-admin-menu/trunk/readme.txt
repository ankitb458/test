=== Admin Menu ===
Contributors: Denis-de-Bernardy
Donate link: http://www.semiologic.com/partners/
Tags: admin-menu, admin, cms, semiologic
Requires at least: 2.8
Tested up to: 2.8.6
Stable tag: trunk

The admin menu plugin for WordPress sticks key admin menu links to the top of your blog's screen.


== Description ==

The admin menu plugin for WordPress sticks key admin menu links to the top of your blog's screen.

The menu will automatically appear to the top of your blog. Menu items will only be visible when relevant.

It's quite useful for those who run their WP install as a CMS. When you create a static page using its New Page link, it will also set the new page's parent automatically, to the ongoing page.

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Screenshots ==

1. Admin Menu in action


== Frequently Asked Questions ==

= It Doesn't Work =

Make sure your theme has the following in between its `<body>` and `</body>` tags:

    <?php wp_footer(); ?>


= Hiding the Admin Menu to Visitors =

The admin menu will not display to visitors unless you specifically want this to occur.

To configure the behavior, browse Settings / Admin Menu.


== Change Log ==

= 6.0 =

- login/logout now do their best to redirect you to where wherever you were
- Drop broken WPMU support
- Full localization
- Code enhancements and optimizations
