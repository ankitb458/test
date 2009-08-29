<?php
/*
 * Multipart Entry Form
 * Author: Denis de Bernardy <http://www.mesoconcepts.com>
 * Version: 1.0
 */

/**
 * ob_multipart_entry()
 *
 * @param string $buffer Output buffer
 * @return string $buffer Modified buffer
 **/

function ob_multipart_entry($buffer) {
	return str_replace(
		'<form name="post"',
		'<form enctype="multipart/form-data" name="post"',
		$buffer
		);
} # ob_multipart_entry()


if ( !function_exists('add_max_file_size') ) :
/**
 * add_max_file_size()
 *
 * @return void
 **/

function add_max_file_size() {
	$bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
	
	echo  "\n" . '<input type="hidden" name="MAX_FILE_SIZE" value="' . esc_attr($bytes) .'" />' . "\n";
} # add_max_file_size()
endif;

ob_start('ob_multipart_entry');

add_action('edit_form_advanced', 'add_max_file_size');
add_action('edit_page_form', 'add_max_file_size');
?>