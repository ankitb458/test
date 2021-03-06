=== Related Widgets ===
Contributors: Denis-de-Bernardy
Donate link: http://www.semiologic.com/partners/
Tags: semiologic
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

A collection of widgets to list related posts and pages.


== Description ==

The Related Widgets plugin for WordPress introduces multi-use widgets that allow you to list related posts or pages.

To use the plugin, browse Appearance / Widgets, insert a Related Widget where you want it to be, and configure it as appropriate.

You can optionally filter the results by category or section.

= On post and page tags =

The plugin builds on your tags to generate lists of related posts and pages. For this reason, it allows to you add tags to your pages, even though they're not otherwise used by WP.

Note that the plugin manages page tags in a manner that does not disrupt WP. As a result, page tags will only display, when using the Semiologic theme, when at least one post also has that tag. The tags are definitely used, however, when scanning for related posts and pages.

That the plugin's algorithm is smart enough to spot related tags. In other words, if post A shares tags with posts B and C, but not post D; and B and C share a tag with D; then the plugin may decide that A is related to D.

At the other end of the spectrum, keep noise tags in mind. Be it signal processing, SEO, or anything else, information comes from differences, not from similarity -- it's difficult to detect a dark gray dot on a black board, whereas it's easy to spot a white dot. If all of your posts share a small set of tags, there is no information to extract and everything becomes noise. And these noise tags end up ignored.

= This post/page in widgets =

This plugin shares options with a couple of other plugins from Semiologic. They're available when editing your posts and pages, in meta boxes called "This post in widgets" and "This page in widgets."

These options allow you to configure a title and a description that are then used by Fuzzy Widgets, Random Widgets, Related Widgets, Nav Menu Widgets, Silo Widgets, and so on. They additionally allow you to exclude a post or page from all of them in one go.

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 3.0.5 =

- WP 3.0 compat

= 3.0.4 =

- Remove php5-specific code
- Further cache improvements (fix priority)

= 3.0.3 =

- Slight algorithm improvement
- Improve caching and memcached support
- Apply filters to permalinks

= 3.0.2 =

- WP 2.9 compat
- Fix hard-coded DB tables

= 3.0.1 =

- Fix an occasional warning

= 3.0 =

- Complete rewrite
- WP_Widget class
- Localization
- Code enhancements and optimizations
