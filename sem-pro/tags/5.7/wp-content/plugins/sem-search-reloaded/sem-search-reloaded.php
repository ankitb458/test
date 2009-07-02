<?php
# obsolete plugin

if ( get_option('posts_have_fulltext_index') )
{
	global $wpdb;
	
	$wpdb->query("ALTER TABLE `$wpdb->posts` DROP INDEX post_title");
	delete_option('posts_have_fulltext_index');
}

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-search-reloaded/sem-search-reloaded.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

if ( !in_array('search-reloaded/search-reloaded.php', $active_plugins) )
{
	$active_plugins[] = 'search-reloaded/search-reloaded.php';
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);
?>