<?php
/*
custom.php sample file
======================

Do not use custom.php files. You normally shouldn't need to. Much about anything you could have done with a custom.php file in legacy versions of Semiologic can be done with an exec php widget.

The feature is left around for legacy reasons, and it is slated to be dropped with Semiologic 6.


If you are not familiar with php and with programming, be advised that the custom.php feature is probably not for you. In programming commonspeak, the Semiologic theme is a variable length multi-dimentional array of function pointers, i.e. it about as abstract and complicated as computer programming can get.

If this does not stop you, two points are worth a mention before we go through the example: how the theme works, and the default canvas.


Outline of Semiologic
---------------------

The Semiologic theme wraps WordPress template tags into functions and calls these via custom plugin hooks. The custom.php is then loaded. This allows to unregister the default settings and register custom settings.

The WordPress plugin API has three functions that you need to be familiar with:

- add_action((string) $plugin_hook, (callback) $function [,(int) $priority])
- remove_action((string) $plugin_hook, (callback) $function_name [,(int) $priority])
- do_action((string) $plugin_hook)

The WordPress templating engine has a few more:

- is_front_page() returns true on the front page
- is_home() returns true on the blog's main page
- is_single() returns true on individual posts
- is_page() returns true on static pages
- is_singular() returns true on individual entries (posts, pages, static front page)
- is_archive() returns true in archive listings
- is_search() returns true in search result listings

The Semiologic theme further adds a few more for convenience:

- true() always returns true
- false() always returns true
- reset_plugin_hook((string) $plugin_hook) resets a plugin hook


The default canvas
------------------

The hooks of interest for customization purposes are the following:

do_action('before_the_wrapper');

do_action('the_header');

do_action('before_the_entries');

if anything found then loop on:

  do_action('the_entry');

else:

  do_action('404_error');

do_action('after_the_entries');

do_action('the_footer');

do_action('after_the_wrapper');
*/
?>