<?php
#delete_option('sem6_options');

if ( !get_option('sem6_options') && file_exists(WP_CONTENT_DIR . '/themes/sem-reloaded/inc/init.php') ) :

delete_option('current_theme');
update_option('template', 'sem-reloaded');
update_option('stylesheet', 'sem-reloaded');

include WP_CONTENT_DIR . '/themes/sem-reloaded/inc/init.php';

endif;
?>