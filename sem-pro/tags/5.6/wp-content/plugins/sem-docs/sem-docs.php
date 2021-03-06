<?php
/*
Plugin Name: Semiologic Documentation
Plugin URI: http://www.getsemiologic.com
Description: Semiologic Pro Documentation, Tips, and Features Screen
Author: Denis de Bernardy
Version: 1.12
Author URI: http://www.semiologic.com
Update Service: http://version.semiologic.com/wordpress
Update Tag: sem_docs
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


define('sem_docs_path', dirname(__FILE__));

global $sem_docs_files;
global $sem_docs_admin_files;

$sem_docs_files = array();
$sem_docs_admin_files = array('docs.php', 'features.php');
?>