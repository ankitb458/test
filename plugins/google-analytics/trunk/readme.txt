=== Google Analytics ===
Contributors: Denis-de-Bernardy
Donate link: http://www.semiologic.com/partners/
Tags: semiologic
Requires at least: 2.8
Tested up to: 2.9
Stable tag: trunk

Integrates Google Analytics on your site.


== Description ==

The Google Analytics (GA) plugin for WordPress lets you easily add Google's web tracking script to your site.

Google Analytics is a truly amazing service for marketers: it generates comprehensive site statistics for your site -- at no cost -- that are entirely geared towards website optimization. The only downside is that Google ends up knowing your stats. If you do not wish it to, look into the Hitslink plugin.

To make use of the plugin, browse Settings / Google Analytics and follow the instructions.

Note that site authors are not tracked so as to not pollute your stats.

This plugin contrasts with a number of similar WordPress plugins, in the amount of effort that was put into making it easy to use, and into integrating other plugins so you can make the best of your stats.

= AdSense integration =

AdSense integration is built-in. As soon as you tie your GA account to your AdSense account, GA will start tracking revenues and clicks, on a per keyword and on a per page basis.

To take full advantage of this, filter your GA reports using segments. Oftentimes, these will allow you to discover stunning -- not to mention unintuitive -- things about your site and its visitors.

= Ad Manager integration =

The Ad Manager plugin makes GA track any click on its ad widgets as GA events, provided the click doesn't occur in an iframe tag. (Their provider's AUP generally disallows to change their code.)

= Contact Form integration =

The Contact Form plugin makes GA track contact form usage as GA events.

= Mediacaster integration =

The Mediacaster plugin makes GA track file downloads and media player usage (play, stop, completed video or podcast, and so on) as GA events.

= Newsletter Manager integration =

The Newsletter Form plugin makes GA track subscription form usage as GA events. It will additionally segment users who subscribe to your mailing lists, allow you to track what they do once subscribed in a convenient report.

= 404 error and outbound link tracking =

Page not found errors on your site are tracked as /404/?page=...&from=...

Clicks on outbound links are tracked as /outbound/?to=...

= Search tracking =

This is built into Google Analytics. Edit your site's profile in GA, and add the "s" parameter as a search query parameter.

= Domain-wide tracking =

This feature is still experimental, but nonetheless worth a note. If you wish to track your domain and all of its subdomains in a single report, add a define in your wp-config.php file as follows.

    define('GA_DOMAIN', '.example.com');

At the time of writing, doing so will turn off media usage tracking. Everything else works fine.

To make the best of the resulting reports, customize the way your reports are displayed, in your main domain's profile, so as to be able to distinguish your various subdomains.

= Custom event tracking =

The GA lastly allows to track custom-defined events, in case the need arises. Doing so is relatively easy, too: simply add a ga_event class to your <a>, <div> or <form> tag of interest.

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 4.0.1 =

- Localization enhancements

= 4.0 =

- Complete rewrite
- Localization
- Event tracking
- Improved 404 and outbound link tracking
- Code enhancements and optimizations
