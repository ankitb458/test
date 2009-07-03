<?php

require_once(TEMPLATEPATH . '/header.php');


do_action('before_the_entries');
do_action('before_the_entry');

?>
<div class="entry now-reading">

		<?php if( can_now_reading_admin() ) : ?>

			<p>Admin: &raquo; <a href="<?php manage_library_url() ?>">Manage Books</a></p>

		<?php endif; ?>

		<?php library_search_form() ?>

		<p><a href="<?php library_url() ?>">&larr; Back to library</a></p>

		<h2>Books by <?php the_book_author() ?></h2>

		<?php if( have_books("author={$GLOBALS['nr_author']}") ) : ?>

			<ul>

			<?php while( have_books("author={$GLOBALS['nr_author']}") ) : the_book(); ?>

				<li>
					<p><a href="<?php book_permalink() ?>"><img src="<?php book_image() ?>" alt="<?php book_title() ?>" /></a></p>
					<p><?php book_title() ?></p>
				</li>

			<?php endwhile; ?>

			</ul>

		<?php else : ?>

			<p>There are no books by this author!</p>

		<?php endif; ?>
</div>
<?php

do_action('after_the_entry');
do_action('after_the_entries');

require_once(TEMPLATEPATH . '/footer.php');
?>