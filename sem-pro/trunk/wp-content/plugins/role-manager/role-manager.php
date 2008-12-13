<?php
/*
Plugin Name: Role Manager
Plugin URI: http://www.im-web-gefunden.de/wordpress-plugins/role-manager/
Description: Role Management for WordPress 2.0.x, 2.1.x, 2.2.x and 2.3.x..
Version: 2.2.2 fork
Author: Thomas Schneider
Author URI: http://www.im-web-gefunden.de/
Update Server:  http://www.im-web-gefunden.de/
Min WP Version: 2.0
Max WP Version: 2.3
License: MIT License - http://www.opensource.org/licenses/mit-license.php

Original coding by David House and Owen Winkler
Icons were provided by http://www.famfamfam.com/lab/icons/silk/ under
a Creative Commons Attribution 2.5 license.
 
*/

if ( is_admin() ) :

$inc_path = dirname(__FILE__) . '/';

include $inc_path . 'role-manager.inc.php';

endif;
?>