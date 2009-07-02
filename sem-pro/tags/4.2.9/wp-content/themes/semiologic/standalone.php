<?php
#
# How to use this file
# ---------------------
# This file is provided as an example of how to integrate third party
# software into a Semiologic canvas.
#


#
# Step 0. Rename the template file and the template
# -------------------------------------------------
# e.g. rename to 'my-template.php', and 'My Template', and drop the
# file into your theme directory.
#

/*
Template Name: Stand Alone Template
*/


#
# Step 1. Import DB params
# ------------------------
# WordPress uses the EZSQL library, and defines the DB params into the
# constants: DB_USER, DB_NAME, DB_HOST, DB_PASS. You'll likely want to
# retrieve this information when integrating third party tools, e.g.:
#

// $db_host = DB_HOST;
// $db_name = DB_NAME;
// $db_user = DB_USER;
// $db_pass = DB_PASS;


#
# Step 2. Include depends
# -----------------------
# Include your dependencies here, e.g.:
#

// include_once ABSPATH . '/my-script-folder/my-script-file.php';


#
# Do not edit the few lines below
#

require_once(TEMPLATEPATH . '/header.php');


do_action('before_the_entries');
do_action('before_the_entry');

#
# Do not edit the few lines above
#


#
# Step 3. Call your script
# ------------------------
# The key classes used in entries are used below for reference
#

?><div class="entry">
	<div class="entry_header">
		<h1>3rd party component integration howto</h1>
	</div>
	<div class="entry_body">
		<p>The standalone.php file in your theme folder lets you integrate 3rd party components into the Semiologic theme. For instance, you could integrate a shopping cart module, a forum, etc. within a theme's canvas.</p>
	</div>
</div>
<?php


#
# Do not edit the few lines below
#

do_action('after_the_entry');
do_action('after_the_entries');

require_once(TEMPLATEPATH . '/footer.php');
#
# Do not edit the few lines above
#
?>