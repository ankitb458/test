<?php
#
# save_html2wp()
#

function save_html2wp($post_ID)
{
	global $wpdb;

	if ( !isset($_POST['kill_wysiwyg']) )
	{
		delete_post_meta($post_ID, '_kill_formatting');
	}

	preg_match("/\.([^\.]+)$/i", $_FILES['html2wp']['name'], $match);

	$ext = strtolower(end($match));

	if ( current_user_can('unfiltered_html')
		&& isset($_FILES['html2wp'])
		&& in_array($ext, array('txt', 'text', 'htm', 'html'))
		)
	{
		$html = file_get_contents($_FILES['html2wp']['tmp_name']);

		switch ( $ext )
		{
		case 'html':
		case 'htm':
			$html = preg_replace("/^.+<\s*body(?:\s[^>]*)?>/isx", "", $html);
			$html = preg_replace("/<\s*\/\s*body\s*>.+$/isx", "", $html);
			break;

		case 'txt':
		case 'text':
		default:
			$html = wpautop($html);
			break;
		}

		if ( $html )
		{
			#dump($html);

			$html = preg_replace("/
				<p			# paragraph
				(?:			# maybe attributes
					\s.*
				)?
				>
				\s*
				&nbsp;		# a mere non-breaking space
				\s*
				<\/p>		# end paragraph
				/isUx", "", $html);

			#dump($html);

			$html = preg_replace("/
				<br
				(?:
					\s*\/
				)?
				>
				\s*
				\n
				\s*
				&nbsp;
				/iUx", "", $html);

			#dump($html);
			#die;

			$html = force_balance_tags($html);

			$html = addslashes($html);

			$wpdb->query(
				"UPDATE $wpdb->posts
				SET post_content = '" . $html . "'
				WHERE ID = " . intval($post_ID)
				);

			delete_post_meta($post_ID, '_kill_formatting');
			add_post_meta($post_ID, '_kill_formatting', 1, true);
		}
	}

	return $post_ID;
} # save_html2wp()


add_action('save_post', 'save_html2wp');
?>