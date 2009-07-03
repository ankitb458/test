<?php
/*
Plugin Name: Site Unavailable
Plugin URI: http://blog.taragana.com/index.php/archive/wordpress-plugin-to-make-your-blog-temporarily-unavailable-for-maintenance/
Description: Activate this plugin to make the server unavailable to everyone, except admin.
Author: Angsuman Chakraborty
Author URI: http://blog.taragana.com/
Version: 1.0
*/
// Hint to the clients to retry after the time below in minutes
$retry_after = 60; // In minutes

// Message to display to the users of the blog
$message     = '<h3>This blog is currently undergoing scheduled maintenance. Please try after '.$retry_after.' minutes. Sorry for the inconvenience.</h3>';


// DO NOT MODIFY BELOW THIS LINE
if(!strstr($_SERVER['PHP_SELF'], 'feed/') && !strstr($_SERVER['PHP_SELF'], 'wp-admin/')) {
    echo $message.'<br/><a href="http://blog.taragana.com/index.php/archive/wordpress-plugins-provided-by-taraganacom/">Plugin</a> provided by <a href="http://www.taragana.com/">Taragana</a>'; 
    exit();    
} else if(strstr($_SERVER['PHP_SELF'], 'feed/') || strstr($_SERVER['PHP_SELF'], 'trackback/')) {
    $retry_after = $retry_after * 60;
    header("HTTP/1.0 503 Service Unavailable"); 
    header("Retry-After: $retry_after"); 
    exit();    
}
?>