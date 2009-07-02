<?php
require( dirname(__FILE__) . '/wp-config.php' );

if (function_exists('gravatar_cache_refresh')) {
	list($cached, $updated) = gravatar_cache_refresh();
	$message = "Gravatar Cache Refreshed\n";
	$message .= date(DATE_W3C, time()) . "\n";
	$message .= "Gravatars Updated:  $updated\n";
	if ($updated) {
		$message .= "Images Cached:  $cached\n";
	}
	echo $message;
} else {
	echo "Gravatar Plugin Not Found\n";
}

?>