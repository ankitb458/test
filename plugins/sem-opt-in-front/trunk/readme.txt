=== Opt-In Front Page ===
Contributors: Denis-de-Bernardy
Donate link: http://www.semiologic.com/partners/
Tags: asides, opt-in-front-page, opt-in front page, opt-in, semiologic
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

Lets you add posts to your front page on an opt-in basis.


== Description ==

The Opt-In Front Page plugin lets you add posts to your front page on an opt-in basis, rather than remove posts on an opt-out basis. This allows you to manage any number of asides categories.

In short, only posts in your "Blog" (or "News") category will appear on your front page and its RSS feed when you use this plugin.

It changes your main category's url as relevant, and it fallbacks to normal blog mode when no 'blog' (or 'news') category exists or when the latter is empty.

You can safely change your "Blog" or "News" category's name after it is created: The opt-in front page plugin looks for the category with a slug of "blog" or "news".


= Auto-installer =

Please note that Opt-in front page automatically creates/fills in the needed main category when it activates. You can always browse Posts / Categories to delete it.


= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a WordPress Category called 'Blog' or 'News'. You can safely rename it afterwards -- what really counts is a slug of 'blog' or 'news'.


== Change Log ==

= 4.1.1 =

- Enhance memcached support
- Apply filters to permalinks
- Fix the main category cache on new sites

= 4.1 =

- Implement an auto-installer

= 4.0 =

- Use the taxonomy API to fetch the main category
- Support the use of "News" in addition to "Blog"
- Localization
- Code enhancements and optimizations