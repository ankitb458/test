<?php
/*
 * Multipart User Form
 * Author: Denis de Bernardy <http://www.mesoconcepts.com>
 * Version: 1.0
 */

/**
 * ob_multipart_user()
 *
 * @param string $buffer Output buffer
 * @return string $buffer Modified buffer
 **/

function ob_multipart_user($buffer) {
	return str_replace(
		'<form id="your-profile"',
		'<form enctype="multipart/form-data" id="your-profile"',
		$buffer
		);
} # ob_multipart_user()

ob_start('ob_multipart_user');
?>