<?php
# fix a bug we introduced in 6.0 alpha-1

if ( $sem_options['version'] == '5.7.2' ) {
	$wpdb->query("
		DELETE FROM $wpdb->post_meta
		WHERE	meta_key = '_scripts_override'
		AND		post_id IN (
			SELECT	ID
			FROM	$wpdb->posts
			WHERE	post_modified >= '2009-01-01'
			)
		");
}
?>