=== Semiologic Cache ===
Contributors: Denis-de-Bernardy
Tags: performance,caching,wp-cache,wp-super-cache,cache
Tested up to: 2.5
Stable tag: 1.0
Requires at least: 2.5

A cruft-free, very fast WordPress caching engine that produces static html files.

== Description ==

This plugin generates static html files from your dynamic WordPress blog.  After a html file is generated your webserver will serve that file instead of processing the comparatively heavier and more expensive WordPress PHP scripts.

However, because a user's details are displayed in the comment form after they leave a comment, the plugin will only serve static html files to:

1. Users who are not logged in.
2. Users who have not left a comment on your blog.
3. Or users who have not viewed a password protected post. 

The good news is that probably more than 99% of your visitors don't do any of the above! Those users who don't see the static files will still benefit because they will see regular cached files and your server won't be as busy as before.  This plugin should help your server cope with a front page appearance on digg.com or other social networking site.

As this plugin is based on the older WP Cache plugin, caching will still be performed when Super Caching isn't, but every request will require loading the PHP engine. In normal circumstances this isn't that bad, but if your server is underpowered, or you're experiencing heavy traffic you may run into trouble. 

See the [Semiologic Cache](http://www.semiologic.com/software/wp-tweaks/sem-cache/) homepage for further information.


== Installation ==

1. You should have mod mime, mod rewrite and fancy permalinks enabled for super caching. If any of those are missing or off you can still use the slower sem-cache part of the plugin. PHP safe mode must be disabled in any event.
2. If you have WP-cache or WP-Super-cache installed already, disable it before installing. Semiologic Cache will automatically update your config files.
3. Upload this directory to your plugins directory. It will create a 'wp-content/plugins/sem-cache/' directory.
4. If you are using WordPress MU you will need to install this in 'wp-content/mu-plugins/sem-cache/' and the file sem-cache-mu.php must then be moved into the mu-plugins directory.
5. WordPress users should go to their Plugins page and activate "Semiologic Cache".
6. Now go to Settings / Cache and enable the caching.
7. mod_rewrite rules will be inserted into your .htaccess file automatically.


== Frequently Asked Questions ==

= How is Semiologic Cache different from WP Super Cache and WP Cache? =

The plugin is based on the excellent WP Super Cache plugin, which is based on WP Cache, and therefore brings all the benefits of these plugins to WordPress.

On top of that, it fixes a couple of bugs I bumped into while trying to make it work on my own servers, and it trims the admin screen of all of the cruft that I felt would scare my non-techie customers.


= Will comments and other dynamic parts of my blog update immediately? =

Comments will show as soon as they are moderated, depending on the comment policy of the blog owner. Other dynamic elements on a page may not update unless they are written in Javascript, Flash, Java or another client side browser language. The plugin really produces static html pages. No PHP is executed when those pages are served. "Popularity Contest" is one such plugin that will not work.

If you use plugins that are created to work with WP-Cache's semi-static files, super caching will automatically be disabled on pages where their content is displayed on.


= Why can't I set the expiry times and other options that were in WP Super Cache and WP Cache? =

Because most of this is cruft that only an expert would ever want to configure. If you happen to be one, edit the wp-content/sem-cache-config.php file directly.


= What happened to gzip compressed files and the plugin stuff? =

The gzip compression option was dropped in WP 2.5. Which is great, since that was an architecturally incorrect attempt to try to solve a server configuration problem. php is, after all, zillions times slower than C.

Configure it via your php.ini file if you really need must turn this on.

As for the plugins, I saw no use for them. There is a plugin hook that lets you turn super caching on or off on the fly (return true or false on do_createsupercache, with the cookie stuff as input). And you can use the mfunc and mclude features to do dynamic stuff on cached pages (these will automatically disable super caching).


= How do I use the mfunc and mclude features? =

Use like this:

    echo '<!--mclude foo/bar.php--><!--/mclude-->'; // foobar depends
	echo '<!--mfunc foobar()-->';
	foobar();
	echo '<!--/mfunc-->';

The cache module converts the above into something that goes down to:

    <?php include_once ABSPATH . 'foo/bar.php'; ?>
    <?php foobar(); ?>

Note that a mere function call to get_option() within foobar() will require that you load WP almost entirely.


= How do I uninstall Semiologic Cache? =

1. The plugin will clean things up when deactivated.
2. Delete the empty cache folder and the cache config file if you don't need them any longer.
3. Remove the plugin files.


= Troubleshooting =

If things don't work when you installed the plugin here are a few things to check:

1.  Is wp-content writable by the web server?
2.  Is there a wp-content/sem-cache-config.php ? If not, copy the file sem-cache/sem-cache-config-sample.php to wp-content/sem-cache-config.php and make sure sem_cache_path points at the right place. "plugins" should be "mu-plugins" if you're using WordPress MU.
3.  Is there a wp-content/advanced-cache.php ? If not, then you must symlink sem-cache/sem-cache-phase1.php to it with this command while in the wp-content folder. 

    `ln -s plugins/sem-cache/sem-cache-phase1.php advanced-cache.php`
If you can't do that, then copy the file. That will work too.
4.  Make sure the following line is in wp-config.php and it is ABOVE the "require_once(ABSPATH.'wp-settings.php');" line:

    `define( 'WP_CACHE', true );`
5.  Try the Settings / Cache page again and enable cache.
6.  Look in wp-content/cache/supercache/. Are there directories and files there?
7.  Anything in your php error_log?


== Updates ==
Updates to the plugin will be posted to [semiologic.com](http://www.semiologic.com) and the [Semiologic Cache](http://www.semiologic.com/software/wp-tweaks/sem-cache/) homepage will always link to the newest version.

== Thanks ==

Credits go to:

- [Donncha O Caoimh](http://ocaoimh.ie/)
- [John Pozadzides](http://onemansblog.com/)
- James Farmer and Andrew Billits of [Edu Blogs](http://edublogs.org/)
- Ricardo Galli Granada  (gallir at uib dot es)