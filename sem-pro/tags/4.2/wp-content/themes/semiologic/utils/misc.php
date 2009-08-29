<?php
#
# remove_wysiwyg_junk()
#

function remove_wysiwyg_junk($content)
{
	return preg_replace(
		"~
			<\s*(?:p|div|noscript)	# p, div or noscript tag
			(?:\s[^>]*)?			# optional attributes
			/\s*>					# />
		~ix",
		"",
		$content
		);
} # end remove_wysiwyg_junk()

add_filter('the_content', 'remove_wysiwyg_junk', 1000);


#
# kill_template_host()
#

function kill_template_host($dir)
{
	$dir = preg_replace("/^https?:\/\/" . $_SERVER['HTTP_HOST'] . "/", "", $dir);

	return $dir;
} # end kill_host()

add_filter('template_directory_uri', 'kill_template_host');


#
# fix_br()
#

function fix_br($buffer)
{
	return preg_replace("/<br\s*\/>/i", "<br />", $buffer);
} # end fix_br()


#
# start_fix_br()
#

function start_fix_br()
{
	ob_start('validation_nazi');
} # end start_fix_br()

add_action('template_redirect', 'start_fix_br', -10000);
?>