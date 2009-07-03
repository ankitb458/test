<?php
/*
Plugin Name: Kill/Keep <strong>www.</strong>, Kill <strong>/index.php</strong>
Plugin URI: http://txfx.net/2005/05/30/one-permalink-only/
Description: Provides 301 redirects to queries with <strong>/index.php</strong> and enforces your use or non-use of <strong>www.</strong>
Version: 1.4 (fork)
Author: Mark Jaquith
Author URI: http://txfx.net/
*/


if ( !strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')
	&& $_SERVER['REQUEST_URI'] !== ''
	&& $_SERVER['REQUEST_METHOD'] == 'GET'
	)
{

if ( $_SERVER['REQUEST_URI'] == str_replace('http://' . $_SERVER['HTTP_HOST'], '', get_bloginfo('home')) . '/index.php' ) {
header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . get_bloginfo('home') . '/');
exit();
}

if ( strpos($_SERVER['HTTP_HOST'], 'www.') === 0  && strpos(get_bloginfo('home'), 'http://www.') === false ) {
header('HTTP/1.1 301 Moved Permanently');
header('Location: http://' . substr($_SERVER['HTTP_HOST'], 4) . $_SERVER['REQUEST_URI']);
exit();
} elseif ( strpos($_SERVER['HTTP_HOST'], 'www.') !== 0 && strpos(get_bloginfo('home'), 'http://www.') === 0 ) {
header('HTTP/1.1 301 Moved Permanently');
header('Location: http://www.' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
exit();
}

}
?>