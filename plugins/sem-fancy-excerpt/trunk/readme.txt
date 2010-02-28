=== Fancy Excerpt ===
Contributors: Denis-de-Bernardy
Donate link: http://www.semiologic.com/partners/
Tags: fancy excerpt, fancy-excerpt, excerpt, semiologic
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: trunk

The fancy excerpt plugin for WordPress replaces the default automatic excerpt generator with a sentence aware excerpt generator.

== Description ==

The fancy excerpt plugin for WordPress replaces the default automatic excerpt generator with a sentence aware excerpt generator.

When automatically generating an excerpt, WordPress will no longer cut your posts after an arbitrary number of characters. Instead, it will keep adding complete paragraphs until 30 words or more are returned. It then completes the excerpt with a More... link.

Before:

> Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Ut in sem eget est pharetra hendrerit. Nulla condimentum venenatis lectus.

> Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Proin lacus. Aenean eget libero. Suspendisse volutpat nonummy magna.

> Fusce pharetra volutpat mi. Fusce auctor cursus arcu. Maecenas feugiat dolor a felis. Vivamus facilisis [...]

After:

> Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Ut in sem eget est pharetra hendrerit. Nulla condimentum venenatis lectus.

> Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Proin lacus. Aenean eget libero. Suspendisse volutpat nonummy magna.

> More...

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= It Doesn't Work When I Send A Trackback =

I'm aware. Last I checked, there wasn't anything I could do about it.


== Change Log ==

= 3.0.1 =

- Improve list handling

= 3.0 =

- Split on paragraphs *and* sentences
- Enhance escape/unescape methods
- Support shortcodes, scripts and object tags in excerpts
- Localization
- Code enhancements and optimizations