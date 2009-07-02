<?php
/*
Plugin Name: Version Checker
Plugin URI: http://www.semiologic.com/software/wp-fixes/version-checker/
Description: Allows to hook into WordPress' version checking API with in a distributed environment.
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.semiologic.com
Update Service: http://version.mesoconcepts.com/wordpress
Update Tag: version_checker
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the BSD license.

http://www.opensource.org/licenses/bsd-license.php
**/

if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false )
{
	include_once dirname(__FILE__) . '/version_checker.php';
}
?>