<?php
#
# save_html2wp()
#

function save_html2wp($post_ID)
{
	global $wpdb;

	if ( current_user_can('unfiltered_html')
		&& isset($_FILES['html2wp'])
		&& preg_match("/\.html?$/i", $_FILES['html2wp']['name'])
		)
	{
		$html = file_get_contents($_FILES['html2wp']['tmp_name']);

		$html = preg_replace("/^.+<\s*body(?:\s[^>]*)?>/isx", "", $html);
		$html = preg_replace("/<\s*\/\s*body\s*>.+$/isx", "", $html);

		if ( $html )
		{
			$html = preg_replace("/
				<p(\s[^>]*)?>
				(\s*<[^>]*>\s*)*
				\s*
				&nbsp;
				\s*
				(\s*<[^>]*>\s*)*
				<\/p>
				/ix", "", $html);

			$html = preg_replace("/
				<br(\s*\/)?>
				\s*
				\n
				\s*
				&nbsp;
				/ix", "", $html);

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