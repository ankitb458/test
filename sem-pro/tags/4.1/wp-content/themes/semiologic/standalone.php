<?php
#
# How to use this file
# ---------------------
# The Semiologic theme features a custom.php feature. This allows to hook into
# the template without editing its php files. That way, you won't need to worry
# about loosing your changes when you upgrade your site.
#
# You'll find detailed sample files in the skins directory
#


/*
Template Name: Stand Alone Template
*/

require_once(TEMPLATEPATH . '/header.php');


do_action('before_the_entries');
do_action('before_the_entry');

?>
<div class="entry">
	<div class="entry_header">
		<h1>3rd party component integration</h1>
	</div>
	<div class="entry_body">
		<p>Use this file when you need to integrate 3rd party components into WordPress using the Semiologic theme.</p>
	</div>
</div>
<?php

do_action('after_the_entry');
do_action('after_the_entries');

require_once(TEMPLATEPATH . '/footer.php');
?>