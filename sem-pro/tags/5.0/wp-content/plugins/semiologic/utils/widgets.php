<?php

function sem_widgets_init()
{
	global $wp_registered_widgets;
	global $sem_widget_callback;
	global $sem_widget_contexts;

	$sem_widget_contexts = get_option('sem_widget_contexts');

	foreach ( $wp_registered_widgets as $widget_id => $widget )
	{
		if ( is_array($widget) )
		{
			if ( $widget['callback'] == 'wp_widget_text' )
			{
				$widget['callback'] = 'sem_widget_text';
			}
			elseif ( $widget['callback'] == 'wp_widget_pages' )
			{
				$widget['callback'] = 'sem_widget_pages';
			}
			elseif ( $widget['callback'] == 'wp_widget_links' )
			{
				$widget['callback'] = 'sem_widget_links';
			}

			$sem_widget_callback[$widget_id] = $widget['callback'];

			$widget['callback'] = create_function('$args, $number = 1', 'sem_widget_callback(\'' . $widget_id . '\', $args, $number);');
		}

		$wp_registered_widgets[$widget_id] = $widget;
	}
} # sem_widgets_init()

add_action('init', 'sem_widgets_init');


function sem_widget_callback($widget_id, $args, $number = 1)
{
	global $sem_widget_callback;
	global $sem_widget_contexts;

	if ( isset($sem_widget_callback[$widget_id]) )
	{
		if ( is_single() || is_article_page() )
		{
			$context = 'post';
		}
		elseif ( is_sales_letter() )
		{
			$context = 'sell';
		}
		elseif ( is_special_page() )
		{
			$context = 'special';
		}
		elseif ( is_page() )
		{
			$context = 'page';
		}
		elseif ( is_home() )
		{
			$context = 'home';
		}
		elseif ( is_search() )
		{
			$context = 'search';
		}
		else
		{
			$context = 'archive';
		}

		if ( !isset($sem_widget_contexts[$widget_id][$context]) || $sem_widget_contexts[$widget_id][$context] )
		{
			call_user_func($sem_widget_callback[$widget_id], $args, $number);
		}
	}
} # sem_widget_callback()




function sem_widget_text($args, $number = 1) {
	extract($args);
	$options = get_option('widget_text');
	$title = $options[$number]['title'];
	$text = apply_filters( 'widget_text', $options[$number]['text'] );
	$text = $options[$number]['filter'] ? wpautop($text) : $text;
?>
		<?php echo $before_widget; ?>
			<?php if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
			<div class="textwidget"><?php echo $text; ?></div>
		<?php echo $after_widget; ?>
<?php
} # sem_widget_text()



function sem_widget_pages( $args ) {
	extract( $args );
	$options = get_option( 'widget_pages' );

	$title = empty( $options['title'] ) ? __( 'Pages' ) : $options['title'];
	$exclude = empty( $options['exclude'] ) ? '' : $options['exclude'];

	if ( is_array($exclude) )
	{
		$options['exclude'] = '';

		foreach ( $exclude as $id )
		{
			$options['exclude'] .= ( $options['exclude'] ? ',' : '' ) . $id;
		}

		$exclude = $options['exclude'];

		update_options('widget_pages', $options);
	}

	$sortby = 'menu_order, post_title';

	$out = wp_list_pages( array('title_li' => '', 'echo' => 0, 'sort_column' => $sortby, 'exclude' => $exclude) );

	if ( !empty( $out ) ) {
?>
	<?php echo $before_widget; ?>
		<?php echo $before_title . $title . $after_title; ?>
		<ul>
			<?php echo $out; ?>
		</ul>
	<?php echo $after_widget; ?>
<?php
	}
} # sem_widget_pages()


function sem_widget_links($args) {
	extract($args, EXTR_SKIP);
	wp_list_bookmarks(array(
		'title_before' => $before_title, 'title_after' => $after_title,
		'category_before' => $before_widget, 'category_after' => $after_widget,
		'show_images' => true, 'class' => 'linkcat widget', 'show_description' => true, 'between' => '<br />'
	));
} # sem_widget_links()

?>