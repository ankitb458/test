<?php
/*
(c) Scott Merrill <http://www.skippy.net/>
GPL Licensed
*/

add_action('comment_post', 'not_to_me');

function not_to_me($ID = 0) {
global $cache_settings;

$comment = get_commentdata($ID, 1, 1);
if (get_settings('admin_email') == $comment['comment_author_email']) {
$foo = get_settings('comments_notify'); // make sure it's cached
$cache_settings->comments_notify = 0;
}
return $ID;
}
?>