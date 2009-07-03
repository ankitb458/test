<?php

require_once(TEMPLATEPATH . '/header.php');


do_action('before_the_entries');
do_action('before_the_entry');

?>
<div class="entry now-reading">

	<?php if( can_now_reading_admin() ) : ?>

		<p>Admin: &raquo; <a href="<?php manage_library_url() ?>">Manage Books</a></p>

	<?php endif; ?>

	<p><a href="<?php library_url() ?>">&larr; Back to library</a></p>

	<?php library_search_form() ?>

	<p>Viewing books tagged with &ldquo;<?php the_tag(); ?>&rdquo;:</p>

	<?php if( have_books("tag={$GLOBALS['nr_tag']}") ) : ?>

		<ul>

		<?php while( have_books("tag={$GLOBALS['nr_tag']}") ) : the_book(); ?>

			<li><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <a href="<?php book_author_permalink() ?>"><?php book_author() ?></a></li>

		<?php endwhile; ?>

		</ul>

	<?php else : ?>

		<p>Sorry, but there were no search results for your query.</p>

	<?php endif; ?>
</div>
<?php

do_action('after_the_entry');
do_action('after_the_entries');

require_once(TEMPLATEPATH . '/footer.php');
?>