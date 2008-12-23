<?php

#
# Step 1
# ------
# Deactivate new plugins
#

if ( ( $active_plugins = get_option('active_plugins') ) === false )
{
	$active_plugins = array();
}

$extra_plugins = array(
	'extended-comment-options/commentcontrol.php',
	'ozh-absolute-comments/wp_ozh_absolutecomments.php',	
	'order-categories/category-order.php',
	'page-tags/page-tags.php',	
	'smart-update-pinger.php',
	'simple-trackback-validation.php',
	'favicon-head.php'
	);

$active_plugins = array_diff($active_plugins, $extra_plugins);

update_option('active_plugins', $active_plugins);


#
# Clean up options
#

include_once sem_path . '/inc/upgrade/options.php';


#
# Debug
#

#dump($sem_options);
#die;
?>