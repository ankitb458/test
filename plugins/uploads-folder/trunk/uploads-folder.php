<?php
/*
Plugin Name: Uploads Folder
Plugin URI: http://www.semiologic.com/software/uploads-folder/
Description: Changes your uploads folders to a more natural uploads/yyyy/mm/post-slug for posts (based on the post's date rather than the current date), and uploads/page-slug/subpage-slug for static pages (based on the page's position in the hierarchy).
Version: 2.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/

if ( !class_exists('uploads_folder') ) {
	include dirname(__FILE__) . '/uploads-folder/uploads-folder.php';
}
?>