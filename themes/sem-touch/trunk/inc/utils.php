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
		foreach ( array('reset', 'layout', 'text', 'skin', 'icons') as $file ) {
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
.header,
.header a,
.header a:hover,
.header a:active {
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
	
	/**
	 * Overrides the default background CSS
	 *
	 * @return void
	 **/
	static function background_css() {
		ob_start();
		_custom_background_cb();
		$o = ob_get_clean();
		echo str_replace('body {', 'html {', $o);
	}
	
	/**
	 * Append a monocolumn class to the body tag
	 *
	 * @param array $classes
	 * @return array $classes
	 **/
	static function monocolumn($classes) {
		$classes[] = 'monocolumn';
		return $classes;
	}
} // END class sem_utils

add_action('wp_print_styles', array('sem_utils', 'styles'));
?>