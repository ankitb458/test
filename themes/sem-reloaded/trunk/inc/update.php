<?php
/**
 * sem_update
 *
 * @package Semiologic Reloaded
 **/

class sem_update {
	/**
	 * upgrader_source_selection()
	 *
	 * @param mixed $in
	 * @return $in
	 **/

	function upgrader_source_selection($source, $remote_source, $wp_upgrader) {
		global $wp_filesystem;
		$old = sem_path;
		$new = untrailingslashit($source);
		
		# copy user customizations
		foreach ( array('custom.css', 'custom.php') as $file ) {
			if ( file_exists("$old/$file") )
				$wp_filesystem->copy("$old/$file", "$new/$file");
		}
		
		if ( is_dir("$old/custom") )
			copy_dir("$old/custom", "$new/custom");
		
		$handle = @opendir("$old/skins");
		
		if ( !$handle )
			return $source;
		
		while ( ( $skin = readdir($handle) ) !== false ) {
			if ( in_array($skin, array('.', '..')) )
				continue;
			
			if ( !is_dir("$new/skins/$skin") ) {
				copy_dir("$old/skins/$skin", "$new/skins/$skin");
			} else {
				foreach ( array('custom.css', 'letter.css', 'print.css') as $file ) {
					if ( file_exists("$old/skins/$skin/$file") )
						$wp_filesystem->copy("$old/skins/$skin/$file", "$new/skins/$skin/$file");
				}
				
				if ( is_dir("$old/skins/$skin/custom") )
					copy_dir("$old/skins/$skin/custom", "$new/skins/$skin/custom");
			}
		}
		
		closedir($handle);
		
		return $source;
	} # upgrader_source_selection()
} # sem_update

if ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'upgrade-theme' )
	add_filter('upgrader_source_selection', array('sem_update', 'upgrader_source_selection'), 10, 3);
?>