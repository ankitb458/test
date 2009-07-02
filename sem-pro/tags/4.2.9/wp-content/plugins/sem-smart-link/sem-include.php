<?php
if ( !defined('sem_include') ) :

define('sem_include', true);

function set_sem_entry_id()
{
	if ( is_single() || is_page() )
	{
		#var_dump($GLOBALS['wp_query']);
		define('sem_entry_id', $GLOBALS['wp_query']->posts[0]->ID);
	}
}

add_action('template_redirect', 'set_sem_entry_id', -1000000);

endif;
?>