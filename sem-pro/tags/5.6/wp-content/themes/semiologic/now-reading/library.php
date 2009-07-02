<?php

include sem_path . '/header.php';


do_action('before_the_entries');
do_action('before_the_entry');

?><div class="entry now-reading">

		<?php if( can_now_reading_admin() ) : ?>
			<p>Admin: &raquo; <a href="<?php manage_library_url() ?>">Manage Books</a></p>

		<?php endif; ?>
		<p><?php total_books() ?> overall; <?php books_read_since('1 year') ?> read in the last year; <?php books_read_since('1 month') ?> read in the last month. That's <?php average_books('month'); ?>.</p>

		<?php library_search_form() ?>
		<h2>Planned books (<?php echo total_books('unread', 0) ?>):</h2>

		<?php if( have_books('status=unread&num=-1') ) : ?>
			<ul>

			<?php while( have_books('status=unread&num=-1') ) : the_book(); ?>
				<li>
					<p><a href="<?php book_permalink() ?>" class="noicon"><img src="<?php book_image() ?>" alt="<?php book_title() ?>" /></a></p>
					<p><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <a href="<?php book_author_permalink() ?>"><?php book_author() ?></a></p>
				</li>

			<?php endwhile; ?>
			</ul>

		<?php else : ?>
			<p>None</p>

		<?php endif; ?>
		<h2>Current books (<?php echo total_books('reading', 0) ?>):</h2>

		<?php if( have_books('status=reading&num=-1') ) : ?>
			<ul>

			<?php while( have_books('status=reading&num=-1') ) : the_book(); ?>
				<li>
					<p><a href="<?php book_permalink() ?>" class="noicon"><img src="<?php book_image() ?>" alt="<?php book_title() ?>" /></a></p>
					<p><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <a href="<?php book_author_permalink() ?>"><?php book_author() ?></a></p>
				</li>

			<?php endwhile; ?>
			</ul>

		<?php else : ?>
			<p>None</p>

		<?php endif; ?>
		<h2>Recent books (<?php echo total_books('read', 0) ?>):</h2>

		<?php if( have_books('status=read&orderby=finished&order=desc&num=-1') ) : ?>
			<ul>

			<?php while( have_books('status=read&orderby=finished&order=desc&num=-1') ) : the_book(); ?>
				<li>
					<p><a href="<?php book_permalink() ?>" class="noicon"><img src="<?php book_image() ?>" alt="<?php book_title() ?>" /></a></p>
					<p><a href="<?php book_permalink() ?>"><?php book_title() ?></a> by <a href="<?php book_author_permalink() ?>"><?php book_author() ?></a></p>
				</li>

			<?php endwhile; ?>
			</ul>

		<?php else : ?>
			<p>None</p>

		<?php endif; ?></div>
<?php

do_action('after_the_entry');
do_action('after_the_entries');

include sem_path . '/footer.php';
?>