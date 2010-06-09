<?php
/*
(c) Kirk Montgomery
GPL licensed (and heavily hacked)
*/
/*---- Produce a stat report somewhere perhaps? ----- */
function VSI_add_option() {
	if ( function_exists('is_super_admin') && !is_super_admin() )
		return;
	add_management_page('Subscribers', 'Subscribers', 'administrator', __FILE__, 'VSI_function_options');
}
add_action('admin_menu', 'VSI_add_option');

function VSI_function_options() {
	global $wpdb;
	if ( function_exists('is_super_admin') && !is_super_admin() )
		return;

	echo '<div class="wrap">'
		. '<h2>' . __('Subscriber Details') . '</h2>';

	if ( isset($_GET['post_id']) )
	{
		$subscribed = (array) $wpdb->get_results("
			SELECT	SUM( commentor_count + non_commentor_count ) as sub_count,
					subscribers.*
			FROM	(
				SELECT	COUNT( DISTINCT lower(comments.comment_author_email) ) as commentor_count,
						0 as non_commentor_count,
						posts.*,
						lower(comment_author_email) as comment_author_email
				FROM	$wpdb->comments as comments
				INNER JOIN $wpdb->posts as posts
				ON		posts.ID = comments.comment_post_ID
				WHERE	comment_subscribe = 'Y'
				AND		posts.ID = " . intval($_GET['post_id']) . "
				GROUP BY posts.ID
				UNION
				SELECT	0 as commentor_count,
						COUNT( DISTINCT lower(postmeta.meta_value) ) as non_commentor_count,
						posts.*,
						lower(meta_value) as comment_author_email
				FROM	$wpdb->posts as posts
				INNER JOIN $wpdb->postmeta as postmeta
				ON		postmeta.post_id = posts.ID
				WHERE	meta_key = '_sg_subscribe-to-comments'
				AND		posts.ID = " . intval($_GET['post_id']) . "
				GROUP BY posts.ID
				) as subscribers
			GROUP BY subscribers.ID
			ORDER BY subscribers.post_date, subscribers.post_title
			");

		update_post_cache($subscribed);

		echo '<p>'
			. '<a href="' . trailingslashit(site_url()) . 'wp-admin/edit.php?page=wp-subscribed.php">'
			. __('All Posts')
			. '</a>'
			. '</p>';

		$sub = current($subscribed);

		echo '<h3>'
			. '<span style="font-size: 10pt; font-weight: normal;">'
			. date('M d, Y', strtotime($sub->post_date))
			. '</span>'
			. '<br />'
			. '<a href="' . apply_filters('the_permalink', get_permalink($sub->ID)) . '">'
			. $sub->post_title
			. '</a>'
			. '<span style="font-size: 10pt; font-weight: normal;">'
			. ' ('
			. $sub->sub_count
			. ')'
			. '</span>'
			. '</h3>';

		echo '<ul>';

		foreach ( $subscribed as $sub )
		{
			echo '<li>'
				. '<a href="mailto:' . $sub->comment_author_email . '">'
				. $sub->comment_author_email
				. '</a>'
				. '</li>';
		}

		echo '</ul>';

		echo '<h3>' . __('Bulk Email List') . '<h3>'
			. '<textarea style="width: 480px; height: 120px;">';

		foreach ( $subscribed as $sub )
		{
			echo $sub->comment_author_email . "\n";
		}

		echo '</textarea>';
	}
	else
	{
		$subscribed = (array) $wpdb->get_results("
			SELECT	SUM( commentor_count + non_commentor_count ) as sub_count,
					subscribers.*
			FROM	(
				SELECT	COUNT( DISTINCT lower(comments.comment_author_email) ) as commentor_count,
						0 as non_commentor_count,
						posts.*
				FROM	$wpdb->comments as comments
				INNER JOIN $wpdb->posts as posts
				ON		posts.ID = comments.comment_post_ID
				WHERE	comment_subscribe = 'Y'
				GROUP BY posts.ID
				UNION
				SELECT	0 as commentor_count,
						COUNT( DISTINCT lower(postmeta.meta_value) ) as non_commentor_count,
						posts.*
				FROM	$wpdb->posts as posts
				INNER JOIN $wpdb->postmeta as postmeta
				ON		postmeta.post_id = posts.ID
				WHERE	meta_key = '_sg_subscribe-to-comments'
				GROUP BY posts.ID
				) as subscribers
			GROUP BY subscribers.ID
			ORDER BY subscribers.post_date, subscribers.post_title
			");

		update_post_cache($subscribed);

		echo '<h3>' . __('All Posts') . '</h3>'
			. '<ul>';

		foreach ( $subscribed as $sub )
		{
			echo '<li>'
				. '<span style="font-size: 10pt; font-weight: normal;">'
				. date('M d, Y', strtotime($sub->post_date))
				. '</span>'
				. '<br />'
				. '<a href="' . str_replace('/wp-admin/?', '/wp-admin/edit.php?', add_query_arg('post_id', $sub->ID)) . '">'
				. $sub->post_title
				. '</a>'
				. ' (' . $sub->sub_count . ')'
				. '</<li>';
		}

		echo '</ul>';

		$subscribed = (array) $wpdb->get_results("
			SELECT	lower(comment_author_email) as comment_author_email
			FROM	$wpdb->comments
			WHERE	comment_subscribe = 'Y'
			UNION
			SELECT	lower(meta_value) as comment_author_email
			FROM	$wpdb->postmeta
			WHERE	meta_key = '_sg_subscribe-to-comments'
			");

		echo '<h3>' . __('Bulk Email List') . '<h3>'
			. '<textarea style="width: 480px; height: 120px;">';

		foreach ( $subscribed as $sub )
		{
			echo $sub->comment_author_email . "\n";
		}

		echo '</textarea>';
	}

	echo '</div>';
} # VSI_function_options()

function VSI_dash_report() {
	global $wpdb;
  $VSI_sub_counter = $wpdb->get_var("
  		SELECT	COUNT( DISTINCT comment_author_email )
  		FROM	$wpdb->comments
  		WHERE	comment_subscribe = 'Y'
  		");
  $VSI_sub_auth_counter = $wpdb->get_var("
  		SELECT	COUNT( DISTINCT comment_post_ID )
  		FROM	$wpdb->comments
  		WHERE	comment_subscribe = 'Y'
  		");
    if ($VSI_sub_auth_counter != '') {
        echo "<p><strong>Subscriber Stats</strong></p>";
        echo "<p>There are <strong>" . $VSI_sub_auth_counter . "</strong> people subscribed to <strong>" . $VSI_sub_counter ."</strong> different posts.</p>";
    }
}

add_action('activity_box_end','VSI_dash_report');
?>