<?php
/*
custom.php sample file
======================

If you are not familiar with php and with programming, be advised that the custom.php feature is probably not for you. In programming commonspeak, the Semiologic theme is a variable length multi-dimentional array of function pointers, i.e. it about as abstract and complicated as computer programming can get.

If this does not stop you, two points are worth a mention before we go through the example: how the theme works, and the default canvas.


Outline of Semiologic
---------------------

The Semiologic theme wraps WordPress template tags into functions and calls these via custom plugin hooks. The custom.php is then loaded. This allows to unregister the default settings and register custom settings.

The WordPress plugin API has three functions that you need to be familiar with:

- add_action((string) $plugin_hook, (callback) $function [,(int) $priority])
- remove_action((string) $plugin_hook, (callback) $function_name [,(int) $priority])
- do_action((string) $plugin_hook)

The WordPress templating engine has a five more:

- is_home() returns true on the home page
- is_single() returns true on individual entries
- is_page() returns true on static pages
- is_archive() returns true in archive listings
- is_search() returns true in search result listings

A custom.php file with the following, for instance, would exchange the site name and the tagline:

<?php
remove_action('display_tagline', 'display_tagline');
remove_action('display_sitename', 'display_sitename');
add_action('display_tagline', 'display_sitename');
add_action('display_sitename', 'display_tagline');
?>
The Semiologic theme adds a reset_plugin_hook() for convenience:

- reset_plugin_hook((string) $plugin_hook)

It resets a plugin hook to no actions, and it is sometimes useful for massive overrides.

A custom.php file with the following, for instance, would make _only_ related entries (via the terms2posts plugin) appear after the entry:

<?php
reset_plugin_hook('after_the_entry');
add_action('after_the_entry', 'display_entry_related_entries', 8);
?>
To take full advantage the custom.php feature, you need to know what functions get hooked where.


The default canvas
------------------

The default hooks are loaded as follows:

add_action('display_header', 'display_header');
  add_action('display_tagline', 'display_tagline');
  add_action('display_sitename', 'display_sitename');

add_action('display_navbar', 'display_navbar');
  add_action('display_header_nav', 'display_header_nav');
  add_action('display_search_form', 'display_search_form');

add_action('display_entry_header', 'display_entry_header');
  add_action('display_entry_date', 'display_entry_date');
  add_action('display_entry_title', 'display_entry_title');
  add_action('display_entry_title_meta', 'display_entry_author_image', 5);
  add_action('display_entry_title_meta', 'display_entry_by_on');

add_action('display_entry_body', 'display_entry_body');
add_action('display_entry_body', 'display_entry_nav', 100);

add_action('display_entry_meta', 'display_entry_filed_under_by');

add_action('display_entry_actions', 'display_entry_actions');

add_action('after_the_entry', 'display_entry_trackback_uri', 5);
add_action('after_the_entry', 'display_entry_follow_ups', 6);
add_action('after_the_entry', 'display_entry_related_entries', 8);

add_action('display_footer', 'display_footer');
  add_action('display_copyright_notice', 'display_copyright_notice');
  add_action('display_footer_nav', 'display_footer_nav');


The hooks of interest for customization purposes are the following:

do_action('before_the_wrapper');

do_action('before_the_header');

do_action('display_header');
  do_action('display_tagline');
  do_action('display_sitename');

do_action('display_navbar');
  do_action('display_header_nav');
  do_action('display_search_form');

do_action('after_the_header');

do_action('before_the_entries');

do_action('before_the_entry');
do_action('display_entry_header');
do_action('display_entry_body');
do_action('display_entry_spacer');
do_action('display_entry_meta');
do_action('display_entry_actions');
do_action('after_the_entry');

do_action('after_the_entries');

do_action('before_the_footer');

do_action('display_footer');
  do_action('display_copyright_notice');
  do_action('display_footer_nav');

do_action('after_the_footer');

do_action('after_the_wrapper');


Time for an example... Drop this file into any skin's folder to see it change the way posts are displayed. Don't forget the <?php (start php) and ?> (end php) if you only copy part of this file or create a new one from scratch.
*/
?><?php

#
# custom_entry_on_by()
#
# Posted on [date/time] by [author]
# You can set the date format via admin area / options
# Set the one called 'Default time format' to 'F j, Y'
#

function custom_entry_on_by()
{
?>Posted on <?php the_time(); ?> by <?php the_author(); ?><?php
} # custom_entry_on_by()


#
# custom_entry_actions()
#
# Filed under [tags] | Permalink | Link | Print | Email | Comments | Edit
#

function custom_entry_actions()
{
?><div class="entry_actions" style="border-top: none; margin: .5em 0px;">
	<span class="entry_tags">Filed under <?php the_category(', '); ?></span>
	<span class="action link_entry">|&nbsp;<a href="<?php the_permalink(); ?>"><?php echo get_caption('permalink'); ?></a></span>
	<span class="action print_entry">|&nbsp;<a href="<?php echo get_print_link(); ?>"><?php echo get_caption('print'); ?></a></span>
	<span class="action email_entry">|&nbsp;<a href="<?php echo get_email_link(); ?>"><?php echo get_caption('email'); ?></a></span>
<?php
	if ( get_comments_number() )
	{
?>	<span class="action entry_comments">&bull;&nbsp;<a href="<?php the_permalink(); ?>#comments"><?php comments_number(get_caption('no_comment'), get_caption('1_comment'), get_caption('n_comments')) ?></a></span>
<?php
	}
	edit_post_link(get_caption('edit'), ' <span class="action admin_link">|&nbsp;', '</span>');
?></div>
<?php
} # end custom_entry_actions()


#
# Override the default hooks except on pages
#

if ( !is_page() )
{
	remove_action('display_entry_date', 'display_entry_date');
	add_action('display_entry_title_meta', 'custom_entry_on_by');
	remove_action('display_entry_meta', 'display_entry_filed_under_by');
	remove_action('display_entry_actions', 'display_entry_actions');
	add_action('display_entry_actions', 'custom_entry_actions');
}
?>