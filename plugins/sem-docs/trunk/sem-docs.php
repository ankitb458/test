<?php
/*
Plugin Name: Semiologic Documentation
Plugin URI: http://www.semiologic.com/software/sem-docs/
Description: Semiologic Pro Documentation
Version: 2.1 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-docs-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


define('sem_docs_path', dirname(__FILE__));

global $sem_docs_files;
global $sem_docs_admin_files;

$sem_docs_files = array();
$sem_docs_admin_files = array('docs.php');
?>