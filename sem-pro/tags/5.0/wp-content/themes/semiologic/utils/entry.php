<?php

class sem_entry
{
	#
	# init()
	#

	function init()
	{
		$GLOBALS['sem_entry'] = array();

		add_action('widgets_init', array('sem_entry', 'widgetize'));
		add_action('the_entry', array('sem_entry', 'panel'));

		foreach ( array_keys(sem_entry::get_areas()) as $area )
		{
			add_action('entry_' . $area, array('sem_entry', $area));
			add_action('entry_' . $area . '_control', array('sem_entry_admin', $area));
		}
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		foreach ( sem_entry::get_areas() as $area => $label )
		{
			switch ( $area )
			{
			case 'comments':
				$height = 500;
				break;

			case 'actions':
				$height = 410;
				break;

			default:
				$height = 220;
				break;
			}
			register_sidebar_widget(
				'Entry: ' . $label,
				create_function('$args', 'sem_entry::widget(\'' . $area . '\', $args);'),
				'entry_' . $area
				);
			register_widget_control(
				'Entry: ' . $label,
				create_function('', 'sem_entry_admin::widget_control(\'' . $area . '\');'),
				450,
				$height
				);
		}
	} # widgetize()


	#
	# get_areas()
	#

	function get_areas()
	{
		return array(
			'header' => __('Header'),
			'content' => __('Content'),
			'tags' => __('Tags'),
			'categories' => __('Categories'),
			'actions' => __('Actions'),
			'comments' => __('Comments'),
			);
	} # get_areas()


	#
	# widget()
	#

	function widget($area, $args)
	{
		if ( apply_filters('show_entry_' . $area, true) )
		{
			global $post;
			global $wp_query;
			global $force_loop;

			$force_loop = false;

			if ( !in_the_loop() && is_singular() && in_array($area, array('categories', 'tags')) )
			{
				$force_loop = true;
				$wp_query->in_the_loop = true;
				$post = $wp_query->next_post();
				setup_postdata($post);
			}

			if ( in_the_loop() )
			{
				do_action('entry_' . $area, $args);
			}

			if ( $force_loop )
			{
				$wp_query->rewind_posts();
				$wp_query->in_the_loop = false;
			}
		}
	} # widget()


	#
	# panel()
	#

	function panel()
	{
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( empty($sidebars_widgets['the_entry']) )
		{
			echo '<div style="color: firebrick; padding: 20px; font-weight: bold;">'
				. 'Your "Each Entry" Panel is empty. Browse Presentation / Widgets and add a few widgets to it. In particular, those called "Entry: <i>Something</i>".'
				. '</div>';
		}

		$GLOBALS['sem_entry'] = array();

		dynamic_sidebar('the_entry');
	} # panel()


	#
	# get()
	#

	function get($tag)
	{
		global $sem_entry;
		global $sem_options;
		global $sem_captions;

		if ( !isset($sem_entry[$tag]) )
		{
			switch ( $tag )
			{
			case 'date':
				$format = get_option('date_format');
				$sem_entry['date'] = apply_filters('the_date', get_the_time($format), $format);
				break;

			case 'optional_date':
				$sem_entry['optional_date'] = the_date('', '', '', false);
				break;

			case 'title':
				$sem_entry['title'] = the_title('', '', false);

				if ( !is_singular() )
				{
					$sem_entry['title'] =
						'<a href="' . sem_entry::get('permalink') . '"'
							. ' title="' . htmlspecialchars($sem_entry['title']) . '"'
							. '>'
						. $sem_entry['title']
						. '</a>';
				}

				break;

			case 'excerpt':
				$sem_entry['excerpt'] = apply_filters('the_excerpt', get_the_excerpt());
				break;

			case 'content':
				$more_link = $sem_captions['more_link'];
				$title = the_title('', '', false);
				$more_link = str_replace('%title%', $title, $more_link);
				$content = get_the_content($more_link, 0, '');
				$content = apply_filters('the_content', $content);
				$content = str_replace(']]>', ']]&gt;', $content);

				$sem_entry['content'] = $content;
				break;

			case 'paginate':
				$sem_entry['paginate'] = wp_link_pages(
					array(
						'before' => '<div class="entry_nav"> ' . $sem_captions['paginate'] . ': ',
						'after' => '</div>',
						'echo' => 0,
						)
					);
				break;

			case 'categories':
				$sem_entry['categories'] = get_the_category_list(', ');
				break;

			case 'author':
				$author_url = get_the_author_url();
				$sem_entry['author'] = get_the_author();

				if ( $author_url )
				{
					$sem_entry['author'] = '<a href="' . htmlspecialchars($author_url) . '">'
						. $sem_entry['author']
						. '</a>';
				}
				break;

			case 'tags':
				$sem_entry['tags'] = get_the_tag_list('', ', ', '');
				break;

			case 'permalink':
				$sem_entry['permalink'] = apply_filters('the_permalink', get_permalink());
				break;

			case 'print_link':
				$sem_entry['print_link'] = sem_entry::get('permalink');
				$sem_entry['print_link'] = $sem_entry['print_link']
					. ( strpos($sem_entry['print_link'], '?') === false
						? '?'
						: '&'
						)
					. 'action=print';
				break;

			case 'email_link':
				$title = the_title('', '', false);
				$permalink = sem_entry::get('permalink');

				$sem_entry['email_link'] = 'mailto:'
					. '?subject=' . rawurlencode($title)
					. '&body=' . urlencode($permalink);
				break;

			case 'comments_link':
				if ( sem_entry::get('num_comments') )
				{
					$sem_entry['comments_link'] = sem_entry::get('permalink')
						. '#comments';
				}
				else
				{
					$sem_entry['comments_link'] = false;
				}
				break;

			case 'comment_link':
				if ( comments_open() && !( is_home() && is_page() ) )
				{
					$sem_entry['comment_link'] = sem_entry::get('permalink')
						. '#postcomment';
				}
				else
				{
					$sem_entry['comment_link'] = false;
				}
				break;

			case 'num_comments':
				$number = get_comments_number();

				if ( $number > 1 )
				{
					$sem_entry['num_comments'] = str_replace('%num%', $number, $sem_captions['n_comments_link']);
				}
				elseif ( $number )
				{
					$sem_entry['num_comments'] = $sem_captions['1_comment_link'];
				}
				else
				{
					$sem_entry['num_comments'] = false;
				}
				break;

			case 'edit_link':
				global $post;

				if ( $post->post_type == 'page' )
				{
					if ( !current_user_can('edit_page', $post->ID) )
					{
						$sem_entry['edit_link'] = false;
						break;
					}
					else
					{
						$link = '<a href="' . get_edit_post_link($post->ID) . '" title="' . __('Edit') . '">' . __('Edit') . '</a>';
						$sem_entry['edit_link'] = apply_filters('edit_post_link', $link, $post->ID);
						break;
					}
				}
				elseif ( $post->post_type == 'post' )
				{
					if ( !current_user_can('edit_post', $post->ID) )
					{
						$sem_entry['edit_link'] = false;
						break;
					}
					else
					{
						$link = '<a href="' . get_edit_post_link($post->ID) . '" title="' . __('Edit') . '">' . __('Edit') . '</a>';
						$sem_entry['edit_link'] = apply_filters('edit_post_link', $link, $post->ID);
						break;
					}
				}

				$sem_entry['edit_link'] = false;
				break;

			default:
				$sem_entry[$tag] = false;
				break;
			}
		}

		return $sem_entry[$tag];
	} # get()


	#
	# header()
	#

	function header($args)
	{
		global $sem_options;
		global $sem_captions;

		$o = '';

		if ( !is_page() )
		{
			if ( $sem_options['show_post_date']
				&& ( $date = sem_entry::get('optional_date') )
				)
			{
				$o .= '<h2>'
					. $date
					. '</h2>' . "\n";
			}
		}

		if ( $title = sem_entry::get('title') )
		{
			$edit_link = sem_entry::get('edit_link');

			if ( $edit_link )
			{
				$edit_link = ' <span class="admin_link">' . $edit_link . '</span>';
			}

			$o .= '<h1>'
				. $title
				. $edit_link
				. '</h1>' . "\n";

			if ( is_article_page() )
			{
				if ( $sem_captions['by_author']
					&& ( $author = sem_entry::get('author') )
					)
				{
					$o .= '<div class="entry_author">'
						. str_replace('%author%', $author, $sem_captions['by_author'])
						. '</div>' . "\n";
				}
				if ( $sem_options['show_article_date']
					&& ( $date = sem_entry::get('date') )
					)
				{
					$o .= '<div class="entry_date">'
						. $date
						. '</div>' . "\n";
				}
			}
		}

		if ( $o )
		{
			echo $args['before_widget'] . "\n" . $o . $args['after_widget'] . "\n";
		}
	} # header()


	#
	# content()
	#

	function content($args)
	{
		global $sem_options;
		$o = '';

		if ( $sem_options['show_excerpts'] && !is_singular() )
		{
			$o .= sem_entry::get('excerpt');
		}
		elseif ( is_page() )
		{
			switch ( get_post_meta(get_the_ID(), '_wp_page_template', true) )
			{
			case 'archives.php':
				echo $args['before_widget'] . "\n";
				display_archives_template();
				echo $args['after_widget'] . "\n";
				return;
				break;

			case 'links.php':
				echo $args['before_widget'] . "\n";
				display_links_template();
				echo $args['after_widget'] . "\n";
				return;
				break;

			case 'sell-page.php':
				$o .= '<div class="sell">'
					. sem_entry::get('content')
					. sem_entry::get('paginate')
					. '</div>';

				if ( $edit_link = sem_entry::get('edit_link') )
				{
					$edit_link = '<p class="admin_link" style="text-align: right;">' . $edit_link . '</p>';

					$o .= $edit_link;
				}
				break;

			default:
				$o .= sem_entry::get('content')
					. sem_entry::get('paginate');
				break;
			}
		}
		else
		{
			$o .= sem_entry::get('content')
				. sem_entry::get('paginate');
		}

		if ( $o )
		{
			echo $args['before_widget'] . "\n" . $o . $args['after_widget'] . "\n";
		}
	} # content()


	#
	# tags()
	#

	function tags($args)
	{
		global $force_loop;
		global $sem_captions;
		$o = '';

		if ( $tags = sem_entry::get('tags') )
		{
			$o = $sem_captions['tags'];

			$o = str_replace('%tags%', $tags, $o);
		}

		if ( $o )
		{
			$o = '<p>' . $o . '</p>';

			echo $args['before_widget'] . "\n"
				. ( $force_loop && $sem_captions['tags_title']
					? ( $args['before_title']
						. $sem_captions['tags_title']
						. $args['after_title']
						. "\n"
						)
					: ''
					)
				. $o
				. $args['after_widget'] . "\n";
		}
	} # tags()


	#
	# categories()
	#

	function categories($args)
	{
		if ( is_page() )
		{
			return;
		}

		global $force_loop;
		global $sem_captions;
		$o = '';

		$categories = sem_entry::get('categories');
		$author = sem_entry::get('author');

		$o = $sem_captions['filed_under'];

		$o = str_replace(array('%categories%', '%author%'), array($categories, $author), $o);

		if ( $o )
		{
			$o = '<p>' . $o . '</p>';

			echo $args['before_widget'] . "\n"
				. ( $force_loop && $sem_captions['cats_title']
					? ( $args['before_title']
						. $sem_captions['cats_title']
						. $args['after_title']
						. "\n"
						)
					: ''
					)
				. $o
				. $args['after_widget'] . "\n";
		}
	} # categories()


	#
	# actions()
	#

	function actions($args)
	{
		global $sem_options;
		global $sem_captions;
		$o = '';

		if ( is_page() )
		{
			switch ( get_post_meta(get_the_ID(), '_wp_page_template', true) )
			{
			case 'archives.php':
				add_filter('the_permalink', 'add_mon2permalink');
				break;
			case 'links.php':
				add_filter('the_permalink', 'add_cat_id2permalink');
				break;
			}
		}


		if ( $sem_options['show_permalink'] )
		{
			$o .= '<span class="entry_action link_entry">'
				. '<a href="' . htmlspecialchars(sem_entry::get('permalink')) . '">'
				. $sem_captions['permalink']
				. '</a>'
				. '</span>' . "\n";
		}

		if ( $sem_options['show_print_link'] )
		{
			$o .= '<span class="entry_action print_entry">'
				. '<a href="' . htmlspecialchars(sem_entry::get('print_link')) . '">'
				. $sem_captions['print_link']
				. '</a>'
				. '</span>' . "\n";
		}

		if ( $sem_options['show_email_link'] )
		{
			$o .= '<span class="entry_action email_entry">'
				. '<a href="' . htmlspecialchars(sem_entry::get('email_link')) . '">'
				. $sem_captions['email_link']
				. '</a>'
				. '</span>' . "\n";
		}

		if ( $sem_options['show_comment_link'] )
		{
			if ( !is_singular()
				&& ( $comments_link = sem_entry::get('comments_link') )
				)
			{
				$o .= '<span class="entry_action entry_comment">'
					. '<a href="' . htmlspecialchars($comments_link) . '">'
					. sem_entry::get('num_comments')
					. '</a>'
					. '</span>' . "\n";
			}
			elseif ( !( is_home() && is_page() )
				&& ( $comment_link = sem_entry::get('comment_link') )
				)
			{
				$o .= '<span class="entry_action comment_entry">'
					. '<a href="' . htmlspecialchars($comment_link) . '">'
					. $sem_captions['comment_link']
					. '</a>'
					. '</span>' . "\n";
			}
		}

		if ( $edit_link = sem_entry::get('edit_link') )
		{
			$edit_link = '<span class="entry_action admin_link">' . $edit_link . '</span>';

			$o .= $edit_link;
		}

		if ( is_page() )
		{
			remove_filter('the_permalink', 'add_mon2permalink');
			remove_filter('the_permalink', 'add_cat_id2permalink');
		}

		if ( $o )
		{
			echo $args['before_widget'] . "\n" . $o . $args['after_widget'] . "\n";
		}
	} # actions()


	#
	# comments()
	#

	function comments($args)
	{
		if ( !( is_home() && is_page() ) )
		{
			echo $args['before_widget'];
			comments_template('/comments.php');
			echo $args['after_widget'];
		}
	} # comments()
} # sem_entry

sem_entry::init();


#
# is_article_page()
#

function is_article_page()
{
	return is_page() && get_post_meta(get_the_ID(), '_wp_page_template', true) === 'article.php';
} # is_article_page()


#
# is_sales_letter()
#

function is_sales_letter()
{
	return is_page() && get_post_meta(get_the_ID(), '_wp_page_template', true) === 'sell-page.php';
} # is_sales_letter()


#
# is_list_page()
#

function is_list_page()
{
	return is_page()
		&& in_array(
			get_post_meta(get_the_ID(), '_wp_page_template', true),
			array('archives.php', 'links.php')
			);
} # is_list_page()


#
# is_special_page()
#

function is_special_page()
{
	return is_page()
		&& get_post_meta(get_the_ID(), '_wp_page_template', true) === 'raw.php';
} # is_special_page()
?>