<?php

#
# get_caption()
#

function get_caption($caption_id)
{
	$caption = $GLOBALS['semiologic']['captions'][$caption_id];

	if ( !isset($captions) )
	{
		include_once dirname(dirname(__FILE__)) . '/admin/captions.php';

		$GLOBALS['semiologic']['captions'] = array_merge(get_all_captions(), (array) $GLOBALS['semiologic']['captions']);
		update_option('semiologic', $GLOBALS['semiologic']);

		$caption = $GLOBALS['semiologic']['captions'][$caption_id];
	}

	if ( strpos($caption, "%title%") !== false )
	{
		$caption = str_replace("%title%", get_the_title(), $caption);
	}

	return $caption;
} # end get_caption()
?>