<?php
/**
 * Front-end functionality
 **/
class sem_utils
{
	/**
	 * Registers and enqueues theme styles.
	 *
	 * @return void
	 **/
	static function styles() {
		foreach ( array('reset', 'layout', 'text', 'skin') as $file ) {
			wp_enqueue_style(
				'sem-' . $file,
				sem_url . '/css/' . $file . '.css',
				null,
				sem_debug ? filemtime(sem_path . '/css/' . $file . '.css') : sem_version
				);
		}
	}
	
	/**
	 * Outputs header-related CSS.
	 *
	 * @return void
	 **/
	static function header_css() {
		if ( !get_header_image() )
			return;
		?>
<style type="text/css">
#header {
	background: url(<?php header_image(); ?>) top left no-repeat;
}
<?php
if ( get_header_textcolor() ) :
?>
#sitename {
	color: #<?php header_textcolor() ?>;
}
<?php
else :
?>
#sitename {
	display: none;
}
<?php
endif;
?>
</style>
<?php
	}
} // END class sem_utils

add_action('wp_print_styles', array('sem_utils', 'styles'));
?>