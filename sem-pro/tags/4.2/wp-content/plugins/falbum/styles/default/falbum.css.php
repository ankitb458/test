<?php
define('FALBUM_STANDALONE', true);
require_once(dirname(__FILE__).'/../../falbum.php');

header("Content-type: text/css");

$falbum_options = $falbum->get_options();
$falbum_display_dropshadows = $falbum_options['display_dropshadows'];
?>

/* <?php //echo '$falbum_display_dropshadows -> '.$falbum_display_dropshadows;?>  

   FAlbum Stylesheet
   =================      
   
   This is a sample stylesheet that can be used with FAblum, and is provided so that
   the default page looks half decent under the default Wordpress 1.5 theme - Kubrick
   
   This stylesheet will most probably break in other WP styles, so you can't rely 
   entirely on this stylesheet. Use it as guidance. Ultimately, style issues are not
   the fault of FAlbum.
   
   This stylesheet is commented so you know what each section does, and can customise
   it accordingly, or pull out just the sections you want.
*/

/* ----------- FAlbum Common -------------*/

.falbum {
	padding: 0px;
	margin: 0px;
	clear: both;	
	width: 99%;
	min-width: none;	
}

.falbum p {
  	margin: 8px 0 8px 0;
}

.falbum img {
  	max-width: none;
	border: none;
}

.falbum-album {
	clear: both;
}

.falbum-title {	
	color: #260;
	border-bottom: 1px dashed #CCC;
	margin: 10px 0px 0px 0px;
	padding: 0px 0px 0px 0px;
}

.falbum-meta {
	margin: 0px 0px 5px 0px;
}

.falbum-navigationBar {
	clear: both;
	padding: 5px 0 5px 0;
	margin: 10px 0 10px 0;
}


/* ----------- Album Page -------------*/

.falbum-album-description {
	font-size: 11px;
	margin-top: 10px;
	margin-bottom: 10px;
}


/* ----------- Thumbnails -------------*/
  
.falbum-tn-border-s {
	float: left;
	width: 95px;
	height: 95px;
}

.falbum-tn-border-t {
	float: left;	
	width: 120px;
	height: 125px;		
}

.falbum-tn-border-m {
	float: left;
	width: 260px;
	height: 260px;
}

.falbum-thumbnail {
	float: left;
	<?php if ($falbum_display_dropshadows == '-ds') { ?>
	background: url('images/shadow.gif') no-repeat bottom right;
	margin: 5px 0px 0px 6px;
 	padding: 0px 0px 0px 0px;	
	<?php } else { ?>
	background-color: #fff;
	border: 1px solid #a9a9a9;
	padding: 4px;
	<?php } ?>
}
 
.falbum-thumbnail img {
	<?php if ($falbum_display_dropshadows == '-ds') { ?>
	 background-color: #fff;
	 border: 1px solid #a9a9a9;
	 display: block;
	 margin: -5px 5px 5px -5px;
	 padding: 4px;
	 position: relative;
	<?php } ?>
}

.falbum-thumbnail img:hover {
	background-color: #ccd;
}

.falbum-editable{
     color: #000;
     background-color: #ffffd3;
}

/* ----------- Photo Page -------------*/

.falbum-title2 {
	padding: 0 50px 0 0;
}

.falbum-tags-block {
}

.falbum-tags-label {
	float: left;
	display: block;
}

.falbum-tags {
	float: left;
}

/* -- Image ---------------*/

.falbum-photo-block {
	float: left;
	margin: 10px 5px 10px 5px;
}

.falbum-photo {
	<?php if ($falbum_display_dropshadows == '-ds') { echo "background: url('images/shadow.gif') no-repeat bottom right;";} ?>
	float: left;
}

.falbum-photo img {
	 background-color: #fff;
	 border: 1px solid #a9a9a9;
	 display: block;
	 margin: -5px 5px 5px -5px;
	 padding: 4px;
	 position: relative;
}

.falbum-photo2 {
	padding-right: 20px;
	background-color: #fff;
	border: 1px solid #a9a9a9;
	margin: 2px 2px 2px 2px;
	padding: 4px;
	margin-top:10px;
}

/* -- Navigation ---------------*/
  
.falbum-nav {
	clear: both;	
	margin: 5px 10px 0px -10px;
	text-align:center;
	padding: 8px;	
	width: auto;	
}

.falbum-nav a {
	text-decoration: none;
}
  
/* -- Description ---------------*/

.falbum-description {
	clear: both;
	font-size: 11px;
	margin: 5px 0 5px 0 ;
}

/* -- Photo Sizes ---------------*/

.falbum-photoSizesBlock {
	padding: 10px 0px 3px 0px;
}

a.falbum-photoSizes
{
	background-color: #E4E0D2;
	padding: 2px;
	margin: 1px 3px 1px 3px;
	color: black;
	border:	1px solid #D4D0C2;
	text-align: center;
}

a.falbum-photoSizes:hover 
{
	border:	1px solid #3169C6;
	background-color: #C6D3EF;
}

/* -- EXIF Data ---------------*/

.falbum-exif table{
	border: 1px solid #a9a9a9;
	margin: 0 15px 0 0;
}

.falbum-exif td {
	margin: 5px 5px 5px 5px;
	padding: 1px 5px 1px 5px;
}
.falbum-exif .odd{
	background-color: #f0f0f0
}

.falbum-exif .even{
	background-color: #e0e0e0
}

/* -- Annotations ---------------*/
 
a.annotation {
	position: absolute;
	border: 1px solid white;
	padding: 0;
	display: none;
}
 
a.annotation span {
	display: block;
	width: 100%;
	height: 100%;
	background: white;
	opacity: 0.2;
	-moz-opacity: 0.2;
	filter:alpha(opacity=20);
}
 
a.annotation:hover {
	border-color: yellow;
}

#overDiv {
  font-size: 1em;
}

/* ----------- Tag Cloud Page -------------*/

.falbum-cloud {	
	padding: 10px;	
	line-height:auto;
	text-align:center;
	font-family: 'Lucida Grande', Verdana, Arial, Sans-Serif;
}

.falbum-cloud a {
	text-decoration:none;
}

a.falbum-tag1 {
 font-size:10px;
}

a.falbum-tag2 {
 font-size:12px;
 font-weight:400px;
}

a.falbum-tag3 {
 font-size:16px;
 font-weight:500;
}

a.falbum-tag4 {
 font-size:20px;
 font-weight:600;
}

a.falbum-tag5 {
 font-size:22px;
 font-weight:700;
}

a.falbum-tag6 {
 font-size:28px;
 font-weight:800;
}

a.falbum-tag7 {
 font-size:30px;
 font-weight:900;
}

/* ----------- Random Images -------------*/
.falbum-random ul {
	list-style: none;
	margin: 0;
	padding: 0;
	white-space: nowrap; 
}

.falbum-random li {
	display: inline;
}

/* ----------- Recent Images -------------*/

.falbum-album-recent {	
	margin-bottom: 15px;
}


/* ----------- Annotations -------------*/
.annotation-fontClass {font-family: 'Comic Sans MS'; font-size: 1.3em; text-align: left;}
.annotation-capfontClass {font-family: Arial, sans-serif; font-size: 1.3em; font-weight: bold; color: #ffffff; text-align: left;}
.annotation-capfontClass A {color: #ffffff; font-size: 1.3em;}
.annotation-fgClass {background-color: #FFFFCC;}
.annotation-bgClass {background-color: #FFFF66;}


/* ----------- Link button styles -------------*/
.disabledButtonLink {
	color: Gray;
	text-align:center;
	padding: 2px 15px 2px 15px;
	background-color:	#E4E0D2;
}

a.buttonLink {
	padding: 2px 15px 2px 15px;
	border:	1px solid #D4D0C2;
	background-color:	#E4E0D2;
	text-align:center;
	white-space: nowrap;
	color: black;
}

a.buttonLink:visited {
	color: black;
}

a.buttonLink:active {
	border:	1px solid #3169C6;
	background-color:	#3169C6;
	color: white;
}

a.buttonLink:hover {
	border:	1px solid #3169C6;
	background-color:	#C6D3EF;
	color: black;
}

a.curPageLink , a.curPageLink:visited,a.curPageLink:link, a.otherPageLink , a.otherPageLink:visited,a.otherPageLink:link {
	padding: 2px 4px 2px 4px;
	font-size: 100%;
	color: black;
}

a.otherPageLink , a.otherPageLink:visited,a.otherPageLink:link {
}

a.curPageLink , a.curPageLink:visited,a.curPageLink:link {
	border: 3px double #3169C6;
	font-weight: bold;
}

a.otherPageLink:hover,a.curPageLink:hover {
	text-decoration: underline;
}

/* ----------- Error Page -------------*/

.falbum-error {
	font-size:1.2em;
}
.falbum-error pre{
	width: 99%;
	font-size:1.2em;
	overflow-y: hidden; 
	overflow-x: auto;
}

/* -------------  Post Helper  -------------*/

#falbum-post-helper-switch {
	float: right;
	margin: 0px 0px 0px 0px;
}

#falbum-post-helper-block {
	width: 99%;
	padding: 5px;
	margin: 15px 0px 15px 0px;
	border:	1px solid #D4D0C2;
	background-color: #E4E0D2;
}

#falbum-post-helper-value {
	margin: 8px 5px 5px 8px;
  	font-weight: bold;
}

#falbum-post-helper-block-close {
	float: right;
}

.falbum-post-box {
}

/* -------------  Comments  -------------*/

.falbum-comment-block {
	margin: 15px 0px 5px 0px;
}

.falbum-comment-title {
	font-weight: bold;
}

.falbum-comment-author {
	padding: 5px 5px 0px 10px;
}

.falbum-comment {
	padding: 5px 5px 5px 25px;
	margin: 0px 0px 5px 0px;
}


/* -------------  Misc  -------------*/

.falbum-sidebar-photos {
	overflow: hidden;
}

.falbum-clear {
	clear: both;
}
.falbum-clear-left {
	clear: left;
}
