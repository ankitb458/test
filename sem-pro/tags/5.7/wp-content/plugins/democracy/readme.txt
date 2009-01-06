=== Democracy ===

Tags: poll,democracy,survey
Contributors: jalenack

Andrew Sutherland
http://blog.jalenack.com

Original Release: June 28, 2005
This release: September 26, 2005

Version: 1.2
	
Released under the CC GPL 2.0
http://creativecommons.org/licenses/GPL/2.0/
    	
== Features ==

 * Uses AJAX for sending and loading the results, but is fully accessible in non-js environments
 * Complete admin panel
 * When someone votes, they receive a cookie with what poll they voted in and what their vote was. It also logs their IP, so the same person can't vote twice even if they delete the cookie
 * Allows users to see current results without voting. Also, detects if there are no votes and announces it...
 * After voting, the choice you made is highlighted.
 * Automatic database table installation.
 * Displays winners of each poll in the admin panel.
 * Delete old polls in the admin panel.
 * Edit existing polls.
 * AJAX is compatible with IE 6, Safari, Firefox, and Opera. All the other browsers, and those with javascript turned off, are supported as well.
 * Select an active poll.
 * jal_democracy() takes poll ID arguments. Use <?php jal_democracy(10); ?> to display poll 10. However, I recommend you use the 'activate' feature instead.
 * Allow your users to add their own choices.
 * Uses the date format you set in the Options panel for the Date Added column.
 * Marks which answers have been added by users, if applicable.
 * Has an archiving function for displaying past polls

== Installation ==

 1. Put the ENTIRE folder labeled "democracy" into your plugins folder (/wp-content/plugins/). There should be 4 files within the democracy folder: democracy.css, admin.js, democracy.php, and js.php
 2. Go to your plugins panel of WordPress in your browser, and activate Democracy. This will automatically create a table in your Wordpress database for the plugin.
 3. Add <?php jal_democracy(); ?> where you want the poll to appear, typically in your sidebar.
 4. Go to Admin Panel > Manage > Democracy to add and edit polls. You'll need to activate the default poll if you want it.

OPTIONAL: 

5. Open the democracy.php file and twiddle with the settings at the top.

6. Use the poll archiving function:

jal_democracy_archives($show_active = FALSE, $before_title = '<h3>',$after_title = '</h3>')

Arguments:
* $show_active -> set to TRUE to show archives of ALL polls, including the current one.
* $before_title -> use any html that will appear before the poll question title
* $after_title -> use any html that will appear after the poll question title

How to use it:

Either get a plugin that will allow you to use PHP within a post/page OR (more secure) make a custom template page that will use the jal_democracy_archives function. For a tutorial, head over to http://www.chrisjdavis.org/2005/05/26/secrets-of-wp-theming-part-1/

All the code you need is <?php jal_democracy_archives(); ?>. If you want to show the current poll on the same page, have a call to <?php jal_democracy(); ?> as well. NOTE: you can never have more than one instance of an active democracy poll in one page.

== Upgrading ==

All versions previous to Democracy RC 1 MUST follow the instructions below. If you have a more recent version than RC 1, you can just overwrite the old Democracy files with the new ones.

Delete the democracy folder in your plugins folder. Then upload the new one provided with this installation.

Next, you must deactivate and reactivate the plugin. This will update the database structure to be compatible with this version. All your previous poll data will be preserved.

Reload your site. Make you are not using cached files from previous versions. To make sure, visit the js.php and democracy.css file and reload them. A common error is when a person upgrades and they fail to empty their cache. This is bad because an old version of the javascript file will be communicating with a new version of the php file, and their communication could be boggled.

== FAQS ==

1. Can you explain step 3 in greater detail?

	My pleasure. Most people will want the poll to show up in their sidebar. Most sidebars for WordPress are unordered lists (<ul>s) and every section of the sidebar has its own <li>. Usually, the code you'll want will look like:
	
	<li><h2>Democracy</h2>
	
		<?php jal_democracy(); ?>
	
	</li>

	Alternatively, you can set the poll question to use <h2> (or anything else). Like this:

	<li>
		<?php jal_democracy(); ?>
	</li>
	
	And then change the options at the top of the democracy.php file. Change $jal_before_question to '<h2 id="poll-question>'; and $jal_after_question to '</h2>';
	
	Note: **NEVER** put <ul> tags directly around <?php jal_democracy(); ?>. Your code will be invalid, and it could cause problems with display.
		

2. The Democracy admin panel clashes with another one of my plugins

	This is a bug in WordPress that has been fixed since 1.5.1.3 . I highly recommend you upgrade to the latest version of WordPress.

3. How do I change the looks of the poll?

	Edit the CSS file. If you'd like to change the colors of the results bars, you'll need to change the border-bottom and background of .democracy-choice. If you need something specific, you can ask me.

4. It does not show status bars, it doesn't load any css or javascript, and there's no AJAX communication.

	This means your theme does not include a vital hook that plugins use to add information to the <head> of your page. To check, View Source of a page and see if there is anything that says <!-- Added By Democracy poll.... If its not there, you need to edit header.php of your theme (/wp-content/themes/**YOURTHEME**/header.php and somewhere in the <head>, add this code: <?php wp_head(); ?> . This code basically allows Plugins to add css and javascript references very easily. MOST themes include it, but some neglect to.

5. WordPress is located in a different directory or uses a subdirectory that mirrors another url, and I get 404 Errors or AJAX problems when I vote.

	I have yet to discover a universal fix for everyone,  but I've made it easier to make the fix. In the jal_add_js() function in democracy.php, $jal_wp_url has two definitions, with the first being commented out. Try uncommenting it and commenting the other, then reloading the page.

6. I want to display multiple polls on a page. Is this possible?

	Not really. The ajax will mess up, so you'll have to disable the javascript by commenting out the <script> in democracy.php . Even then it might not be satisfactory. My only suggestion is to find a way to only have one poll per page.

7. I want to put a poll in a Page or post. Is this possible?

	Yes. You'll have to get a plugin that will allow you to run PHP in posts. There are many plugins for WP that will do this, among them: RunPHP, PHPexec, The Execution of All Things, and CG-QuickPHP. Google 'em! Once you've gotten them installed, add code like <?php jal_democracy(2); ?> in your post, where '2' is the id of the poll you want to show in that post. Note: Don't try to have democracy show up more than once per page. Doing so will cause many errors. Find a way to separate them from the rest of your post so that only one will show up per page. This can be done by using the <!--more--> tag right before calling <?php jal_democracy(); ?>.

== Contact Developer ==

* Email:
	jalenack@gmail.com
* AIM:
	Harc Serf
ICQ:
	305917227
Yahoo:
	jalenack
MSN Messenger:
	jalenack@hotmail.com
* Online Contact form:
	http://blog.jalenack.com/contact.php

* = Most likely venues for a quick response
