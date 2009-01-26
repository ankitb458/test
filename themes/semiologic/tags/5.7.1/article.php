<?php
# Obsolete file

delete_post_meta($GLOBALS['wp_query']->get_queried_object_id(), '_wp_page_template');
add_post_meta($GLOBALS['wp_query']->get_queried_object_id(), '_wp_page_template', 'default', true);

include sem_path . '/index.php';
?>