<?php
# obsolete file

delete_post_meta($GLOBALS['wp_the_query']->get_queried_object_id(), '_wp_page_template');
add_post_meta($GLOBALS['wp_the_query']->get_queried_object_id(), '_wp_page_template', 'letter.php', true);

include sem_path . '/letter.php';
?>