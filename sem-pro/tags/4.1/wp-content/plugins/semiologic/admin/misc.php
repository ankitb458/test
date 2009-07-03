<?php

function add_fck_buttons()
{
	echo '<script type="text/javascript">' . "\n";

	echo 'document.show_fck_contactform = ' . ( function_exists('wpcf_callback') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_podcast = ' . ( function_exists('ap_insert_player_widgets') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_videocast = ' . ( function_exists('wpflv_replace') ? 'true' : 'false' ) . ';' . "\n";
	echo 'document.show_fck_adunit = ' . ( function_exists('sem_ad_spaces_init') ? 'true' : 'false' ) . ';' . "\n";

	echo '</script>' . "\n";
} # end add_fck_buttons()

add_action('admin_head', 'add_fck_buttons');
?>