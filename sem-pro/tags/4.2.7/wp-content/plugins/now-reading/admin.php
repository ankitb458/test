<?php

require_once 'now-reading-add.php';
require_once 'now-reading-manage.php';
require_once 'now-reading-options.php';

/**
 * Manages the various admin pages Now Reading uses.
 */
function nr_add_pages() {
	add_submenu_page('post.php', 'Now Reading', 'Now Reading', 9, 'now-reading-add.php', 'now_reading_add');
	add_submenu_page('post-new.php', 'Now Reading', 'Now Reading', 9, 'now-reading-add.php', 'now_reading_add');
	
	add_management_page('Now Reading', 'Now Reading', 9, 'now-reading-manage.php', 'nr_manage');
	
	add_options_page('Now Reading', 'Now Reading', 9, 'now-reading-options.php', 'nr_options');
}
add_action('admin_menu', 'nr_add_pages');

?>