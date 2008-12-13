<?php
#delete_option('sem5_options');

if ( !get_option('sem5_options') ) :

update_option('current_theme', 'Semiologic');
update_option('template', 'semiologic');
update_option('stylesheet', 'semiologic');

include ABSPATH . 'wp-content/themes/semiologic/inc/init.php';

endif;
?>