<?php 

define('FALBUM', true);
define('FALBUM_STANDALONE', true);

require_once (dirname(__FILE__).'/../../../../wp-blog-header.php');

require_once (dirname(__FILE__).'/../falbum.php');

if (file_exists(get_template_directory()."/falbum.php")) {
	
	include_once(get_template_directory()."/falbum.php");

} else { 

get_header(); 
?>

<script type="text/javascript" src="<?php echo get_settings('siteurl'); ?>/wp-content/plugins/falbum/res/falbum.js"></script>
<script type="text/javascript" src="<?php echo get_settings('siteurl'); ?>/wp-content/plugins/falbum/res/overlib.js"></script>
<?php if ($falbum->can_edit) { ?><script type="text/javascript" src="<?php echo get_settings('siteurl'); ?>/wp-content/plugins/falbum/res/prototype.js"></script><?php } ?>

<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
	<div id="content" class="narrowcolumn">
		 <?php 
		 
		 $falbum->show_photos(); 
		 
		 ?>
	</div>
<?php 

get_sidebar();

get_footer(); 

}