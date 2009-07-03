<?php
#
# remove_wysiwyg_junk()
#

function remove_wysiwyg_junk($content)
{
	return preg_replace(
		"~
			<\s*(?:p|div)	# <div or <p
			(?:\s[^>]*)?	# optional attributes
			/\s*>			# />
		~ix",
		"",
		$content
		);
} # end remove_wysiwyg_junk()

add_filter('the_content', 'remove_wysiwyg_junk', 1000);


#
# remove_frontpage_junk()
#

function remove_frontpage_junk($content)
{
	return preg_replace(
		"~
			^.*
			<\s*body		# <body
			(?:\s[^>]*)?	# optional attributes
			>				# >
			(.*)
			<\s*/body\s*>	# </body>
			.*$
		~isx",
		"$1",
		$content
		);
} # end remove_frontpage_junk()

add_filter('the_content', 'remove_frontpage_junk', 0);


#
# remove_more_junk()
#

function remove_more_junk($content)
{
	return preg_replace(
		"~
			\#more-\d+
		~ix",
		"",
		$content
		);
} # end remove_more_junk()

add_filter('the_content', 'remove_more_junk', 1000);


#
# fck_tags()
#

function fck_tags($content)
{
	#echo '<pre>';
	#var_dump(htmlspecialchars($content));
	#echo '</pre>';

	if ( function_exists('wpcf_callback') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*contactform\s*-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--contactform-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*contactform\s*-->
				(?:<\/p>)?
			/ix",
			"<!--contactform-->",
			$content
			);

		$content = str_replace(
			"<!--contactform-->",
			"\n\n<div>[CONTACT-FORM]</div>\n\n",
			$content
			);
	}

	if ( function_exists('ap_insert_player_widgets') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*podcast\s*(\#[^>]*)-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--podcast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*podcast\s*(\#[^>]*)-->
				(?:<\/p>)?
			/ix",
			"<!--podcast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				<!--\s*podcast\s*(\#[^>]*)-->
			/ix",
			"\n\n<div>[audio:$1]</div>\n\n",
			$content
			);
	}

	if ( function_exists('wpflv_replace') )
	{
		$content = preg_replace(
			"/
				(?:<br\s*\/>|\n|\r)*
				<!--\s*videocast\s*(\#[^>]*)-->
				(?:<br\s*\/>|\n|\r)*
			/ix",
			"<!--videocast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				(?:<p>)?
				<!--\s*videocast\s*(\#[^>]*)-->
				(?:<\/p>)?
			/ix",
			"<!--videocast$1-->",
			$content
			);

		$content = preg_replace(
			"/
				<!--\s*videocast\s*(\#[^>]*)-->
			/ix",
			"\n\n<div><flv href=\"$1\" /></div>\n\n",
			$content
			);
	}

	return $content;
} # end fck_tags()

add_filter('the_content', 'fck_tags', 0);
?>