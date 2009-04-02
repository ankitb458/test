<?php
#delete_option('sem6_options');

if ( !get_option('sem6_options') ) :

update_option('current_theme', 'Semiologic Reloaded');
update_option('template', 'sem-reloaded');
update_option('stylesheet', 'sem-reloaded');

include ABSPATH . 'wp-content/themes/sem-reloaded/inc/init.php';

endif;
?>