<?php
/*
Copyright (c) 2006
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

This file is part of WordPress.
WordPress is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

define('FALBUM_STANDALONE', true);
require_once(dirname(__FILE__).'/falbum.php');

$action = $_REQUEST['action'];


if (isset($action)){
	switch(strtolower($action))
	{
		case  'exif' :
		runExif();
		break;
		
		case  'edit' :
		runEdit();
		break;

		default:
		return '0';
	}
}else{
	echo 'action not found';
	return '0';
}

function runExif() {	
	global $falbum;
	
	$photo_id = $_GET['photo_id'];
	$secret = $_GET['secret'];		
	header('Content-Type: text/html'); 
	
	echo $falbum->show_exif($photo_id,$secret);
}

function runEdit() {	
	global $falbum;
	
	$id = $_POST['id'];
	$photo_id = $_POST['photo_id'];
	$content = stripslashes( urldecode($_POST['content']) );
	     
    if ($id == 'falbum-photo-desc') {    	
    	$o_title = html_entity_decode($_POST['o_title']);
    	$data = $falbum->update_metadata($photo_id,$o_title,$content);
    	echo $data['description'];
    } else {
    	$o_description = html_entity_decode($_POST['o_desc']);
    	$data = $falbum->update_metadata($photo_id,$content,$o_description);
    	echo $data['title'];    	
    }	
}
