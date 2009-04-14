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
		add_action('wp_print_scripts', array('sem_entry', 'scripts'));

		foreach ( array_keys(sem_entry::get_areas()) as $area )
		{
			add_action('entry_' . $area, array('sem_entry', $area));
			add_action('entry_' . $area . '_control', array('sem_entry_admin', $area));
		}
	} # init()
	
	
	#
	# scripts()
	#
	
	function scripts() {
		if ( is_singular()
			&& comments_open($GLOBALS['wp_query']->get_queried_object_id())
		) {
			wp_enqueue_script('comment-reply');
			wp_enqueue_script('jquery');
		}	
	} # scripts()


	#
	# widgetize()
	#

	function widgetize()
	{
		foreach ( sem_entry::get_areas() as $area => $details )
		{
			$widget_options = array('classname' => 'entry_' . $area, 'description' => $details['desc'] );
			$control_options = array('width' => 450);

			wp_register_sidebar_widget(
				'entry_' . $area,
				'Entry: ' . $details['label'],
				create_function('$args', 'sem_entry::widget(\'' . $area . '\', $args);'),
				$widget_options
				);
			wp_register_widget_control(
				'entry_' . $area,
				'Entry: ' . $details['label'],
				create_function('', 'sem_entry_admin::widget_control(\'' . $area . '\');'),
				$control_options
				);
		}
	} # widgetize()


	#
	# get_areas()
	#

	function get_areas()
	{
		return array(
			'header' => array(
				'label' => __('Header'),
				'desc' => 'Entry: Title and date. Only works in the loop (each entry).',
				),
			'content' => array(
				'label' => __('Content'),
				'desc' => 'Entry: Content. Only works in the loop (each entry).',
				),
			'tags' => array(
				'label' => __('Tags'),
				'desc' => 'Entry: Tags. If placed outside of the loop, it will only display on individual entries.',
				),
			'categories' => array(
				'label' => __('Categories'),
				'desc' => 'Entry: Categories. If placed outside of the loop, it will only display on individual entries.',
				),
			'comments' => array(
				'label' => __('Comments'),
				'desc' => 'Entry: Comments. Only works in the loop (each entry).',
				),
			);
	} # get_areas()


	#
	# widget()
	#

	function widget($area, $args)
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
	} # widget()


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
			case 'optional_date':
				$sem_entry['optional_date'] = the_date('', '', '', false);
				if ( $sem_entry['optional_date'] ) {
					$sem_entry['optional_date'] = '<span>' . $sem_entry['optional_date'] . '</span>';
				}
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
				if ( is_attachment() )
				{
					# strip wpautop junk
					$content = preg_replace("/<br\s*\/>\s+$/", '', $content);
					
					# add image links
					
					global $post;
					$post = get_post($post);
					$attachments = array_values(get_children("post_parent=$post->post_parent&post_type=attachment&post_mime_type=image&orderby=menu_order ASC, ID ASC"));

					foreach ( $attachments as $k => $attachment )
						if ( $attachment->ID == $post->ID )
							break;

					$prev_image = isset($attachments[$k-1])
						? wp_get_attachment_link($attachments[$k-1]->ID, 'thumbnail', true)
						: '';
					$next_image = isset($attachments[$k+1])
						? wp_get_attachment_link($attachments[$k+1]->ID, 'thumbnail', true)
						: '';
					
					if ( $prev_image || $next_image )
					{
						$content .= '<div class="gallery_nav">' . "\n"
							. '<div class="prev_image">' . "\n"
							. $prev_image
							. '</div>' . "\n"
							. '<div class="next_image">' . "\n"
							. $next_image
							. '</div>' . "\n"
							. '<div class="spacer"></div>' . "\n"
							. '</div>' . "\n";
					}
				}
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

				if ( $author_url ) {
					$sem_entry['author'] = '<span class="entry_author">'
						. '<a href="' . htmlspecialchars($author_url) . '">'
						. $sem_entry['author']
						. '</a>'
						. '</span>';
				} else {
					$sem_entry['author'] = '<span class="entry_author">'
						. '<span>'
						. $sem_entry['author']
						. '</span>'
						. '</span>';
				}
				break;

			case 'tags':
				$term_links = array();
				
				if ( ( $terms = get_the_terms(0, 'post_tag') ) && !is_wp_error($terms) )
				{
					foreach ( $terms as $term ) {
						if ( $term->count == 0 ) continue;
						$link = get_term_link( $term, 'post_tag' );
						if ( is_wp_error( $link ) )
							return $link;
						$term_links[] = '<a href="' . $link . '" rel="tag">' . $term->name . '</a>';
					}

					$term_links = apply_filters( "term_links-post_tag", $term_links );
				}
				
				$sem_entry['tags'] = apply_filters('the_tags', join(', ', $term_links));
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
				if ( comments_open() && !( is_front_page() && is_page() ) )
				{
					$sem_entry['comment_link'] = sem_entry::get('permalink')
						. '#respond';
				}
				else
				{
					$sem_entry['comment_link'] = false;
				}
				break;

			case 'num_comments':
				$number = get_comments_number();
				
				if ( $number || comments_open() ) {
					$sem_entry['num_comments'] = $number;
					if ( !$link = sem_entry::get('comments_link') ) {
						$link = sem_entry::get('comment_link');
					}
					$cap = _n('1 Comment', '% Comments', $number);
					$cap = preg_replace("/\s*(?:1|\%)\s*/", '', $cap);
					$sem_entry['num_comments'] = '<a href="' . htmlspecialchars($link) . '">'
						. '<span class="num_comments">'
						. $sem_entry['num_comments']
						. '</span>'
						. '<br />'
						. $cap
						. '</a>';
				} else {
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
		if ( !class_exists('widget_contexts')
			&& get_post_meta(get_the_ID(), '_wp_page_template', true) == 'letter.php'
			)
		{
			return;
		}
		
		global $sem_options;
		global $sem_captions;

		$o = '';
		
		if ( !is_page() )
		{
			if ( $sem_options['show_post_date']
				&& ( $date = sem_entry::get('optional_date') )
				)
			{
				$o .= '<div class="entry_date">'
					. '<div class="pad">'
					. $date
					. '</div>'
					. '</div>' . "\n";
			}
			
		}

		if ( $title = sem_entry::get('title') )
		{
			$o .= '<div class="entry_header">' . "\n"
				. '<div class="entry_header_top"><div class="hidden"></div></div>' . "\n"
				. '<div class="pad">' . "\n"
				. '<h1>'
				. $title
				. '</h1>' . "\n"
				. '</div>' . "\n"
				. '<div class="entry_header_bottom"><div class="hidden"></div></div>' . "\n"
				. '</div>' . "\n";
		}
		
		if ( $o )
		{
			echo # spacer in case a short, previous item contains floating elements
				'<div class="spacer"></div>' . "\n"
				. $o
				# spacer in case the date gets floated
				. '<div class="spacer"></div>' . "\n";
		}
	} # header()


	#
	# content()
	#

	function content($args)
	{
		global $sem_options;
		global $sem_captions;
		$o = '';

		if ( $sem_options['show_excerpts'] && !is_singular() )
		{
			$o .= sem_entry::get('excerpt');
		}
		else
		{
			$o .= sem_entry::get('content')
				. sem_entry::get('paginate');
		}
		
		if ( !is_feed() && !( isset($_GET['action']) && $_GET['action'] == 'print' ) )
		{
			$extra = '';
			
			if ( $edit_link = sem_entry::get('edit_link') ) {
				$extra .= '<span class="edit_entry">'
					. $edit_link
					. '</span>' . "\n";
			}
			
			if ( $num_comments = sem_entry::get('num_comments') ) {
				$extra .= '<span class="comment_box">'
					. $num_comments
					. '</span>' . "\n";
			}
			
			if ( $extra ) {
				$o = '<div class="entry_actions">' . "\n"
					. $extra
					. '</div>' . "\n" . $o;
			}
		}
		
		if ( $o ) {
			echo $args['before_widget'] . "\n"
				. $o
				. '<div class="spacer"></div>' . "\n"
				. $args['after_widget'] . "\n";
		}
	} # content()


	#
	# tags()
	#

	function tags($args)
	{
		if ( !class_exists('widget_contexts')
			&& get_post_meta(get_the_ID(), '_wp_page_template', true) == 'letter.php'
			)
		{
			return;
		}
		
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
	# comments()
	#

	function comments($args)
	{
		if ( is_singular() )
		{
			echo '<div class="entry_comments">' . "\n"
				. '<div class="spacer"></div>' . "\n";
			
			comments_template('/comments.php');
			
			echo '</div>' . "\n";
		}
	} # comments()
} # sem_entry

sem_entry::init();


#
# sem_postnav_widget()
#

function sem_postnav_widget($args)
{
	if ( !is_single() && !is_page() && !is_404()
		&& !$GLOBALS['disable_next_prev_page_link']
		&& $GLOBALS['after_the_entries']
		&& $GLOBALS['wp_query']->max_num_pages > 1
		)
	{
		global $sem_captions;
		global $wp_query;
		
		if ( !is_singular() ) {
			echo $args['before_widget']
				. '<div class="prev_next_page">' . "\n";
			
			posts_nav_link(
				' &bull; ',
				'&larr;&nbsp;' . $sem_captions['prev_page'],
				$sem_captions['next_page'] . '&nbsp;&rarr;'
				);
			
			echo '</div>' . "\n"
				. $args['after_widget'];
		}
	}
} # sem_postnav_widget()


#
# sem_postnav_widgetize()
#

function sem_postnav_widgetize()
{
	$widget_options = array('classname' => 'next_prev_posts', 'description' => 'Next/Previous Blog Posts Links. Only works after the entries.' );
	$control_options = array('width' => 450);

	wp_register_sidebar_widget(
		'next_prev_posts',
		'Blog: Next/Prev Posts',
		'sem_postnav_widget',
		$widget_options
		);
	wp_register_widget_control(
		'next_prev_posts',
		'Blog: Next/Prev Posts',
		'sem_postnav_widget_control',
		$control_options
		);
} # sem_postnav_widgetize()

add_action('widgets_init', 'sem_postnav_widgetize');


#
# disable_next_prev_page_link()
#

function disable_next_prev_page_link($data)
{
	$GLOBALS['disable_next_prev_page_link'] = true;

	return $data;
} # disable_next_prev_page_link()

add_action('get_books', 'disable_next_prev_page_link');
add_action('get_single_book', 'disable_next_prev_page_link');


#
# display_archive_header()
#

function display_archive_header()
{
	global $sem_options;
	global $sem_captions;
	
	$desc = '';
	
	echo '<h1>';

	if ( is_category() )
	{
		single_cat_title();
		$desc = category_description();
	}
	elseif ( is_tag() )
	{
		single_tag_title();
		$desc = tag_description();
	}
	elseif ( is_month() )
	{
		single_month_title(' ');
		$desc = wpautop($user->description);
	}
	elseif ( is_author() )
	{
		global $wp_query;
		
		$user = new WP_User($wp_query->get_queried_object_id());
		
		echo $user->display_name;
	}
	elseif ( is_search() )
	{
		global $wp_query;
		
		echo str_replace('%query%', implode(' ', $wp_query->query_vars['search_terms']), $sem_captions['search_title']);
	}
	elseif ( is_404() )
	{
		echo $sem_captions['404_title'];
		$desc = $sem_captions['404_desc'];
	}
	else
	{
		echo $sem_captions['archives_title'];
	}

	echo '</h1>' . "\n";
		
	if ( $desc && trim($desc) != '<br />' )
	{
		echo $desc;
	}
} # display_archive_header()


#
# archive_header_widget()
#

function archive_header_widget($args)
{
	if ( !is_archive() && !is_search() && !is_404() )
	{
		return;
	}

	if ( $GLOBALS['before_the_entries'] )
	{
		echo $args['before_widget'];
		display_archive_header();
		echo $args['after_widget'];
	}
} # archive_header_widget()


#
# archive_header_widgetize()
#

function archive_header_widgetize()
{
	$widget_options = array('classname' => 'archives_header', 'description' => 'Header for blog category, tag and archives pages. Only works before the entries.' );
	$control_options = array('width' => 450);

	wp_register_sidebar_widget(
		'archives_header',
		'Blog: Archives Header',
		'archive_header_widget',
		$widget_options
		);

	wp_register_widget_control(
		'archives_header',
		'Blog: Archives Header',
		'archive_header_widget_control',
		$control_options
		);
} # archive_header_widgetize()

add_action('widgets_init', 'archive_header_widgetize');
?>