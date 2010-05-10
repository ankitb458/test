<?php
/**
 * sem_widgets
 *
 * @package Semiologic Reloaded
 **/

class sem_widgets {
	/**
	 * register()
	 *
	 * @return void
	 **/

	function register() {
		register_widget('entry_header');
		register_widget('entry_content');
		register_widget('entry_categories');
		register_widget('entry_tags');
		register_widget('entry_comments');
		register_widget('blog_header');
		register_widget('blog_footer');
		register_widget('header_boxes');
		register_widget('footer_boxes');
		register_widget('header');
		register_widget('navbar');
		register_widget('footer');
	} # register()
	
	
	/**
	 * admin_scripts()
	 *
	 * @return void
	 **/

	function admin_scripts() {
		if ( !class_exists('nav_menu') ) {
			$folder = sem_url . '/js';
			wp_enqueue_script('nav-menus', $folder . '/admin.js', array('jquery-ui-sortable'),  '20090903', true);

			add_action('admin_footer', array('sem_nav_menu', 'admin_footer'));
		}
	} # admin_scripts()
	
	
	/**
	 * admin_styles()
	 *
	 * @return void
	 **/

	function admin_styles() {
		$folder = sem_url . '/css';
		wp_enqueue_style('nav-menus', $folder . '/admin.css', null, '20090903');
	} # admin_styles()
	
	
	/**
	 * tag_cloud_args()
	 *
	 * @param array $args
	 * @return array $args
	 **/

	function tag_cloud_args($args) {
		$args = wp_parse_args($args, array('smallest' => '.8', 'largest' => '1.6', 'unit' => 'em'));
		return $args;
	} # tag_cloud_args()
	
	
	/**
	 * widget_display_callback()
	 *
	 * @param array $instance widget settings
	 * @param object $widget
	 * @param array $args sidebar settings
	 * @return array $instance
	 **/

	function widget_display_callback($instance, $widget, $args) {
		if ( $instance === false )
			return $instance;
		
		switch ( get_class($widget) ) {
		case 'WP_Widget_Calendar':
			return sem_widgets::calendar_widget($instance, $args);
		case 'WP_Widget_Search':
			return sem_widgets::search_widget($instance, $args);
		default:
			return $instance;
		}
	} # widget_display_callback()
	
	
	/**
	 * calendar_widget()
	 *
	 * @param array $instance widget args
	 * @param array $args sidebar args
	 * @return false
	 **/

	function calendar_widget($instance, $args) {
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		ob_start();
		get_calendar();
		$calendar = ob_get_clean();
		
		$calendar = str_replace('<table id="wp-calendar"', '<table class="wp-calendar"', $calendar);
		
		$title = apply_filters('widget_title', $title);
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo $calendar;
		
		echo $after_widget;
		
		return false;
	} # calendar_widget()
	
	
	/**
	 * search_widget()
	 *
	 * @param array $instance widget args
	 * @param array $args sidebar args
	 * @return false
	 **/

	function search_widget($instance, $args) {
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		if ( is_search() )
			$query = apply_filters('the_search_form', get_search_query());
		else
			$query = '';
		
		$title = apply_filters('widget_title', $title);
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo '<form method="get"'
				. ' action="' . esc_url(user_trailingslashit(get_option('home'))) . '"'
				. ' class="searchform" name="searchform"'
				. '>'
			. '<input type="text" class="s" name="s"'
				. ' value="' . esc_attr($query) . '"'
				. ' />'
			. ( in_array($args['id'], array('sidebar-1', 'sidebar-2') )
				? "<br />\n"
				: ''
				)
			. '<input type="submit" class="go button submit" value="' . esc_attr__('Search', 'sem-reloaded') . '" />'
			. '</form>';
		
		echo $after_widget;
		
		return false;
	} # search_widget()
} # sem_widgets


/**
 * entry_header
 *
 * @package Semiologic Reloaded
 **/

class entry_header extends WP_Widget {
	/**
	 * entry_header()
	 *
	 * @return void
	 **/

	function entry_header() {
		$widget_name = __('Entry: Header', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'entry_header',
			'description' => __('The entry\'s title and date. Must be placed in the loop (each entry).', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('entry_header', $widget_name, $widget_ops, $control_ops);
	} # entry_header()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_entry' || !class_exists('widget_contexts') && is_letter() )
			return;
		
		$instance = wp_parse_args($instance, entry_header::defaults());
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$date = false;
		if ( $show_post_date && !is_sticky() && ( is_single() || !is_singular() && !is_day() ) )
			$date = the_date('', '', '', false);
		
		$title = the_title('', '', false);
		
		if ( $title && !is_singular() ) {
			$permalink = apply_filters('the_permalink', get_permalink());
			$title = '<a href="' . esc_url($permalink) . '" title="' . esc_attr($title) . '">'
				. $title
				. '</a>';
		}
		
		if ( $date || $title ) {
			if ( $date ) {
				echo '<div class="entry_date">' . "\n"
					. '<div class="pad">' . "\n"
					. '<span>'
					. $date
					. '</span>'
					. '</div>' . "\n"
					. '</div>' . "\n";
			}
			
			if ( $title ) {
				echo '<div class="entry_header">' . "\n"
					. '<div class="entry_header_top"><div class="hidden"></div></div>' . "\n"
					. '<div class="pad">' . "\n"
					. '<h1>'
					. $title
					. '</h1>' . "\n"
					. '</div>' . "\n"
					. '<div class="entry_header_bottom"><div class="hidden"></div></div>' . "\n"
					. '</div>' . "\n";
			}
		}
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['show_post_date'] = isset($new_instance['show_post_date']);
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, entry_header::defaults());
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Config', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
			. ' name="' . $this->get_field_name('show_post_date') . '"'
			. checked($show_post_date, true, false)
			. ' />'
			. '&nbsp;'
			. __('Show post dates.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/

	function defaults() {
		return array(
			'show_post_date' => true,
			);
	} # defaults()
} # entry_header


/**
 * entry_content
 *
 * @package Semiologic Reloaded
 **/

class entry_content extends WP_Widget {
	/**
	 * entry_content()
	 *
	 * @return void
	 **/

	function entry_content() {
		$widget_name = __('Entry: Content', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'entry_content',
			'description' => __('The entry\'s content. Must be placed in the loop (each entry).', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('entry_content', $widget_name, $widget_ops, $control_ops);
		
		if ( class_exists('fancy_excerpt') )
			add_filter('the_content_more_link', array(&$this, 'more_link'), 0);
	} # entry_content()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_entry' )
			return;
		
		global $post;
		$instance = wp_parse_args($instance, entry_content::defaults());
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$title = the_title('', '', false);
		
		if ( $show_excerpts && !is_singular() ) {
			$content = apply_filters('the_excerpt', get_the_excerpt());
		} else {
			$more_link = sprintf($more_link, $title);
			
			$content = get_the_content($more_link, 0, '');
			
			if ( is_attachment() && $post->post_parent && preg_match("/^image\//i", $post->post_mime_type) ) {
				# strip wpautop junk
				$content = preg_replace("/<br\s*\/>\s+$/", '', $content);
				
				# add gallery links
				$attachments = get_children(array(
						'post_parent' => $post->post_parent,
						'post_type' => 'attachment',
						'post_mime_type' => 'image',
						'order_by' => 'menu_order ID',
						));
				
				foreach ( $attachments as $k => $attachment )
					if ( $attachment->ID == $post->ID )
						break;
				
				$prev_image = isset($attachments[$k-1])
					? wp_get_attachment_link($attachments[$k-1]->ID, 'thumbnail', true)
					: '';
				$next_image = isset($attachments[$k+1])
					? wp_get_attachment_link($attachments[$k+1]->ID, 'thumbnail', true)
					: '';
				
				if ( $prev_image || $next_image ) {
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
			
			$content = apply_filters('the_content', $content);
			
			$content .= wp_link_pages(
				array(
					'before' => '<div class="entry_nav"> ' . $paginate . ' ',
					'after' => '</div>' . "\n",
					'echo' => 0,
					)
				);
		}
		
		$actions = '';
		
		if ( !isset($_GET['action']) || $_GET['action'] != 'print' ) {
			global $post;
			
			$edit_link = get_edit_post_link($post->ID, 'raw');
			if ( $edit_link ) {
				$edit_link = '<a class="post-edit-link"'
					. ' href="' . esc_url($edit_link) . '"'
					. ' title="' . esc_attr(__('Edit', 'sem-reloaded')) . '">'
					. __('Edit', 'sem-reloaded')
					. '</a>';
				$edit_link = apply_filters('edit_post_link', $edit_link, $post->ID);
				
				$actions .= '<span class="edit_entry">'
					. $edit_link
					. '</span>' . "\n";
			}
			
			$num_comments = (int) get_comments_number();
			
			if ( $show_comment_box && ( $num_comments || comments_open() ) ) {
				$comments_link = apply_filters('the_permalink', get_permalink());
				$comments_link .= $num_comments ? '#comments' : '#respond';
				
				$actions .= '<span class="comment_box">'
					. '<a href="' . esc_url($comments_link) . '">'
					. $num_comments
					. '</a>'
					. '</span>' . "\n";
			}
			
			if ( $actions ) {
				$actions = '<div class="entry_actions">' . "\n"
					. $actions
					. '</div>' . "\n";
			}
		}
		
		$thumbnail = '';
		if ( function_exists('get_the_post_thumbnail') ) {
			add_filter('image_downsize', array('entry_content', 'thumbnail_downsize'), 10, 3);
			$thumbnail = get_the_post_thumbnail();
			remove_filter('image_downsize', array('entry_content', 'thumbnail_downsize'), 10, 3);
		}
		
		if ( $thumbnail ) {
			$thumbnail = '<div class="wp_thumbnail">'
				. $thumbnail
				. '</div>' . "\n";
		}
		
		if ( $actions || $content ) {
			echo '<div class="entry_content">' . "\n"
				. '<div class="pad">' . "\n"
				. $actions
				. $thumbnail
				. $content
				. '<div class="spacer"></div>' . "\n"
				. $after_widget;
		}
	} # widget()
	
	
	/**
	 * thumbnail_downsize()
	 *
	 * @param mixed $in
	 * @param int $id
	 * @param string $size
	 * @return False on failure, array on success
	 **/

	function thumbnail_downsize($in, $id, $size) {
		if ( $in || $size != 'post-thumbnail' )
			return $in;
		return image_downsize($id, 'thumbnail');
	} # thumbnail_downsize()
	
	
	/**
	 * more_link()
	 *
	 * @param string $more
	 * @return string $more
	 **/

	function more_link($more) {
		if ( !$this->number )
			return $more;
		
		$instance = $this->get_settings();
		$instance = $instance[$this->number];
		$instance = wp_parse_args($instance, entry_content::defaults());
		
		$more = '<a href="' . apply_filters('the_permalink', get_permalink()) . '#more-' . get_the_ID() . '" class="more-link">'
			. sprintf($instance['more_link'], get_the_title())
			. '</a>';
		
		return $more;
	} # more_link()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['show_comment_box'] = isset($new_instance['show_comment_box']);
		$instance['show_excerpts'] = isset($new_instance['show_excerpts']);
		$instance['more_link'] = trim(strip_tags($new_instance['more_link']));
		$instance['paginate'] = trim(strip_tags($new_instance['paginate']));
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, entry_content::defaults());
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Config', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
			. ' name="' . $this->get_field_name('show_comment_box') . '"'
			. checked($show_comment_box, true, false)
			. ' />'
			. '&nbsp;'
			. __('Display the box with the number of comments.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
			. ' name="' . $this->get_field_name('show_excerpts') . '"'
			. checked($show_excerpts, true, false)
			. ' />'
			. '&nbsp;'
			. __('Use the post\'s excerpt on blog and archive pages.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('Read more on %s...', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
			. ' name="' . $this->get_field_name('more_link') . '"'
			. ' value="' . esc_attr($more_link) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('Pages:', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
			. ' name="' . $this->get_field_name('paginate') . '"'
			. ' value="' . esc_attr($paginate) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'show_comment_box' => true,
			'show_excerpts' => false,
			'more_link' => __('Read more on %s...', 'sem-reloaded'),
			'paginate' => __('Pages:', 'sem-reloaded'),
			);
	} # defaults()
} # entry_content


/**
 * entry_categories
 *
 * @package Semiologic Reloaded
 **/

class entry_categories extends WP_Widget {
	/**
	 * entry_categories()
	 *
	 * @return void
	 **/
	
	function entry_categories() {
		$widget_name = __('Entry: Categories', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'entry_categories',
			'description' => __('The entry\'s categories. Will only display on individual posts if placed outside of the loop (each entry).', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('entry_categories', $widget_name, $widget_ops, $control_ops);
	} # entry_categories()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( is_admin() || is_singular() && !is_single() ) {
			return;
		} elseif ( $args['id'] != 'the_entry' ) {
			if ( !is_single() )
				return;
			
			global $post, $wp_the_query;
			$post = $wp_the_query->get_queried_object();
			setup_postdata($post);
		}
		
		$instance = wp_parse_args($instance, entry_categories::defaults());
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		if ( !$filed_under_by )
			return;
		
		$categories = get_the_category_list(', ');
		
		$author = get_the_author();
		$author_url = apply_filters('the_author_url', get_the_author_meta('url'));
		
		if ( $author_url && $author_url != 'http://' ) {
			$author = '<span class="entry_author">'
				. '<a href="' . esc_url($author_url) . '" rel="external">'
				. $author
				. '</a>'
				. '</span>';
		} else {
			$author = '<span class="entry_author">'
				. '<span>' . $author . '</span>'
				. '</span>';
		}
		
		$date = apply_filters('the_time', get_the_time(__('M jS, Y', 'sem-reloaded')), __('M jS, Y', 'sem-reloaded'));
		
		if ( !is_day() )
			$date = '<a href="' . esc_url(get_month_link(get_the_time('Y'), get_the_time('m'))) . '">' . $date . '</a>';
		
		$date = '<span class="entry_date">' . $date . '</span>';
		
		$comments = '';
		$num = get_comments_number();
		if ( $num && !is_single() ) {
			if ( $num > 1 ) {
				$comments = str_replace(' ', '&nbsp;', sprintf($n_comments, number_format_i18n($num)));
				$anchor = '#comments';
				$class = 'entry_replies';
			} elseif ( $num ) {
				$comments = str_replace(' ', '&nbsp;', $one_comment);
				$anchor = '#comments';
				$class = 'entry_replies';
			}
		} elseif ( comments_open() && ( $num && is_single() || !is_single() ) ) {
			$comments = $add_comment;
			$anchor = '#respond';
			$class = 'leave_reply';
		}
		
		if ( $comments ) {
			$comments = '<span class="' . $class . '">'
				. '<a href="' . esc_url(apply_filters('the_permalink', get_permalink()) . $anchor) . '">'
				. $comments
				. '</a>'
				. '</span>';
		}
		
		$link = '&nbsp;'
			. '<span class="link_entry">'
			. '<a href="' . esc_url(apply_filters('the_permalink', get_permalink())) . '" title="#">'
			. '<img src="' . sem_url . '/icons/pixel.gif' . '" width="14" height="12" class="no_icon" alt="#" />'
			. '</a>'
			. '</span>' . "\n";
		
		$title = $args['id'] != 'the_entry' && $title
			? apply_filters('widget_title', $title)
			: false;
			
		echo $before_widget
			. ( $title
				? $before_title . $title . $after_title
				: ''
				)
			. '<p>'
			. str_replace('. .', '.', sprintf($filed_under_by, $categories, $author, $date, $comments))
			. $link
			. '</p>' . "\n"
			. $after_widget;
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		foreach ( array_keys(entry_categories::defaults()) as $field )
			$instance[$field] = trim(strip_tags($new_instance[$field]));
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/
	
	function form($instance) {
		$instance = wp_parse_args($instance, entry_categories::defaults());
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'sem-reloaded')
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. __('This widget\'s title is displayed only when this widget is placed out of the loop (each entry).', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('Filed under %1$s by %2$s on %3$s. %4$s.', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' name="' . $this->get_field_name('filed_under_by') . '"'
				. ' value="' . esc_attr($filed_under_by) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('1 Comment', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
			. ' name="' . $this->get_field_name('one_comment') . '"'
			. ' value="' . esc_attr($one_comment) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('%d Comments', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
			. ' name="' . $this->get_field_name('n_comments') . '"'
			. ' value="' . esc_attr($n_comments) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('Comment', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
			. ' name="' . $this->get_field_name('add_comment') . '"'
			. ' value="' . esc_attr($add_comment) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'title' => __('Categories', 'sem-reloaded'),
			'filed_under_by' => __('Filed under %1$s by %2$s on %3$s. %4$s.', 'sem-reloaded'),
			'one_comment' => __('1 Comment', 'sem-reloaded'),
			'n_comments' => __('%d Comments', 'sem-reloaded'),
			'add_comment' => __('Comment', 'sem-reloaded'),
			);
	} # defaults()
} # entry_categories


/**
 * entry_tags
 *
 * @package Semiologic Reloaded
 **/

class entry_tags extends WP_Widget {
	/**
	 * entry_tags()
	 *
	 * @return void
	 **/

	function entry_tags() {
		$widget_name = __('Entry: Tags', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'entry_tags',
			'description' => __('The entry\'s tags. Will only display on individual entries if placed outside of the loop (each entry).', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('entry_tags', $widget_name, $widget_ops, $control_ops);
	} # entry_tags()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( is_admin() ) {
			return;
		} elseif ( !in_the_loop() ) {
			if ( !is_singular() )
				return;
			
			global $post, $wp_the_query;
			$post = $wp_the_query->get_queried_object();
			setup_postdata($post);
		}
		
		if ( !class_exists('widget_contexts') && is_letter() )
			return;
		
		$instance = wp_parse_args($instance, entry_tags::defaults());
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$term_links = array();
		$terms = get_the_terms(get_the_ID(), 'post_tag');
		
		if ( $terms && !is_wp_error($terms) ) {
			foreach ( $terms as $term ) {
				if ( $term->count == 0 )
					continue;
				$tag_link = get_term_link( $term, 'post_tag' );
				if ( is_wp_error( $tag_link ) )
					continue;
				$term_links[] = '<a href="' . esc_url($tag_link) . '" rel="tag">' . $term->name . '</a>';
			}

			$term_links = apply_filters( "term_links-post_tag", $term_links );
		}
		
		$_tags = apply_filters('the_tags', join(', ', $term_links));
		
		if ( $_tags ) {
			$title = apply_filters('widget_title', $title);
			
			echo $before_widget
				. ( $args['id'] != 'the_entry' && $title
					? $before_title . $title . $after_title
					: ''
					)
				. '<p>'
				. sprintf($tags, $_tags)
				. '</p>' . "\n"
				. $after_widget;
		}
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		foreach ( array_keys(entry_tags::defaults()) as $field )
			$instance[$field] = trim(strip_tags($new_instance[$field]));
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, entry_tags::defaults());
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'sem-reloaded')
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. __('This widget\'s title is displayed only when this widget is placed out of the loop (each entry).', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<code>' . __('Tags: %s.', 'sem-reloaded') . '</code>'
			. '<br />' . "\n"
			. '<input type="text" class="widefat"'
				. ' name="' . $this->get_field_name('tags') . '"'
				. ' value="' . esc_attr($tags) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'title' => __('Tags', 'sem-reloaded'),
			'tags' => __('Tags: %s.', 'sem-reloaded'),
			);
	} # defaults()
} # entry_tags


/**
 * entry_comments
 *
 * @package Semiologic Reloaded
 **/

class entry_comments extends WP_Widget {
	/**
	 * entry_comments()
	 *
	 * @return void
	 **/

	function entry_comments() {
		$widget_name = __('Entry: Comments', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'entry_comments',
			'description' => __('The entry\'s comments. Must be placed in the loop (each entry).', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('entry_comments', $widget_name, $widget_ops, $control_ops);
	} # entry_comments()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_entry' || !is_singular() || !get_comments_number() && !comments_open() )
			return;
		
		if ( !class_exists('widget_contexts') && is_letter() )
			return;
		
		echo '<div class="entry_comments">' . "\n";
		
		global $comments_captions;
		$comments_captions = wp_parse_args($instance, entry_comments::defaults());
		
		comments_template('/comments.php');
		
		echo '</div>' . "\n";
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		foreach ( array_keys(entry_comments::defaults()) as $field ) {
			switch ( $field ) {
			case 'policy':
				if ( current_user_can('unfiltered_html') )
					$instance[$field] = $new_instance[$field];
				else
					$instance[$field] = $old_instance[$field];
				break;
				
			default:
				$instance[$field] = trim(strip_tags($new_instance[$field]));
				break;
			}
		}
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = entry_comments::defaults();
		$instance = wp_parse_args($instance, $defaults);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		foreach ( $defaults as $field => $default ) {
			switch ( $field ) {
			case 'policy':
				echo '<p>'
					. '<label>'
					. __('Comment Policy', 'sem-reloaded')
					. '<br />' . "\n"
					. '<textarea class="widefat" rows="4"'
						. ' name="' . $this->get_field_name($field) . '"'
						. ' >'
						. esc_html($$field)
						. '</textarea>'
					. '</label>'
					. '</p>' . "\n";
				break;
			
			default:
				echo '<p>'
					. '<label>'
					. '<code>' . $default . '</code>'
					. '<br />' . "\n"
					. '<input type="text" class="widefat"'
						. ' name="' . $this->get_field_name($field) . '"'
						. ' value="' . esc_attr($$field) . '"'
						. ' />'
					. '</label>'
					. '</p>' . "\n";
				break;
			}
		}
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'pings_on' => __('Pings on %s', 'sem-reloaded'),
			'comments_on' => __('Comments on %s', 'sem-reloaded'),
			'leave_comment' => __('Leave a Comment', 'sem-reloaded'),
			'reply_link' => __('Reply', 'sem-reloaded'),
			'policy' => '',
			'login_required' => __('You must be logged in to post a comment. %s.', 'sem-reloaded'),
			'logged_in_as' => __('You are logged in as %1$s. %2$s.', 'sem-reloaded'),
			'name_field' => __('Name:', 'sem-reloaded'),
			'email_field' => __('Email:', 'sem-reloaded'),
			'url_field' => __('Website:', 'sem-reloaded'),
			'required_fields' => __('Fields marked by an asterisk (*) are required.', 'sem-reloaded'),
			'submit_field' => __('Submit Comment', 'sem-reloaded'),
			);
	} # defaults()
} # entry_comments


/**
 * blog_header
 *
 * @package Semiologic Reloaded
 **/

class blog_header extends WP_Widget {
	/**
	 * blog_header()
	 *
	 * @return void
	 **/

	function blog_header() {
		$widget_name = __('Blog: Header', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'blog_header archives_header',
			'description' => __('The title and description that appear on category, tag, search, 404 and date archive pages. Must be placed before each entry.', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('blog_header', $widget_name, $widget_ops, $control_ops);
	} # blog_header()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'before_the_entries' || !is_archive() && !is_search() && !is_404() )
			return;
		
		$desc = '';
		
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, blog_header::defaults());
		extract($instance, EXTR_SKIP);
		
		echo $before_widget;
		
		echo '<h1>';

		if ( is_category() ) {
			single_cat_title();
			$desc = trim(category_description());
		} elseif ( is_tag() ) {
			single_tag_title();
			$desc = trim(tag_description());
		} elseif ( is_date() ) {
			if ( is_year() )
				$date = date_i18n(__('Y', 'sem-reloaded'), strtotime(get_query_var('year') . '-01-01 GMT'), true);
			elseif ( is_month() )
				$date = single_month_title(' ', false);
			else
				$date = date_i18n(__('M jS, Y', 'sem-reloaded'), strtotime(get_query_var('year') . '-' . zeroise(get_query_var('monthnum'), 2) . '-' . zeroise(get_query_var('day'), 2) . ' GMT'), true);
			
			echo sprintf(trim($archives_title), $date);
			$desc = '<div class="posts_nav">'
				. blog_footer::date_nav()
				. '</div>' . "\n";
		} elseif ( is_author() ) {
			global $wp_the_query;
			$user = new WP_User($wp_the_query->get_queried_object_id());
			echo $user->display_name;
			$desc = trim($user->description);
		} elseif ( is_search() ) {
			echo sprintf($search_title, apply_filters('the_search_query', get_search_query()));
		} elseif ( is_404() ) {
			echo $title_404;
			$desc = $desc_404;
		}

		echo '</h1>' . "\n";
		
		if ( $desc )
			echo wpautop(apply_filters('widget_text', $desc));
		
		echo $after_widget;
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		foreach ( array_keys(blog_header::defaults()) as $field ) {
			switch ( $field ) {
			case 'desc_404':
				if ( current_user_can('unfiltered_html') )
					$instance[$field] = trim($new_instance[$field]);
				else
					$instance[$field] = $old_instance[$field];
				break;
			default:
				$instance[$field] = trim(strip_tags($new_instance[$field]));
			}
		}
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = blog_header::defaults();
		$instance = wp_parse_args($instance, $defaults);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		foreach ( $defaults as $field => $default ) {
			switch ( $field ) {
			case 'desc_404':
				echo '<p>'
					. '<label for="' . $this->get_field_id($field) . '">'
					. '<code>' . htmlspecialchars($default, ENT_QUOTES, get_option('blog_charset')) . '</code>'
					. '</label>'
					. '<br />' . "\n"
					. '<textarea class="widefat" cols="20" rows="3"'
						. ' id="' . $this->get_field_id($field) . '"'
						. ' name="' . $this->get_field_name($field) . '"'
						. ' >'
						. esc_html($$field)
						. '</textarea>'
					. '</p>' . "\n";
				break;
			default:
				echo '<p>'
					. '<label>'
					. '<code>' . $default . '</code>'
					. '<br />' . "\n"
					. '<input type="text" class="widefat"'
						. ' name="' . $this->get_field_name($field) . '"'
						. ' value="' . esc_attr($$field) . '"'
						. ' />'
					. '</label>'
					. '</p>' . "\n";
			}
		}
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'title_404' => __('404: Not Found', 'sem-reloaded'),
			'desc_404' => __('The page you\'ve requested was not found.', 'sem-reloaded'),
			'archives_title' => __('%s Archives', 'sem-reloaded'),
			'search_title' => __('Search: %s', 'sem-reloaded'),
			);
	} # defaults()
} # blog_header


/**
 * blog_footer
 *
 * @package Semiologic Reloaded
 **/

class blog_footer extends WP_Widget {
	/**
	 * blog_footer()
	 *
	 * @return void
	 **/

	function blog_footer() {
		$widget_name = __('Blog: Footer', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'blog_footer next_prev_posts',
			'description' => __('The next/previous blog posts links. Must be placed after each entry.', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('blog_footer', $widget_name, $widget_ops, $control_ops);
	} # blog_footer()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $wp_the_query;
		$max_num_pages = (int) $wp_the_query->max_num_pages;
		
		if ( $args['id'] != 'after_the_entries' || is_singular() || ( !is_date() && $max_num_pages <= 1 ) )
			return;
		
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, blog_footer::defaults());
		extract($instance, EXTR_SKIP);
		
		$paged = (int) get_query_var('paged');
		if ( !$paged )
			$paged = 1;
		
		$pages = array();
		
		if ( $max_num_pages > 1 ) {
			if ( $paged >= 2 )
				$pages[] = get_previous_posts_link(trim('&laquo; ' . $previous));
			
			$range = array($paged);

			for ( $i = 1; $i <= 3; $i++ ) {
				array_unshift($range, $paged - $i);
				array_push($range, $paged + $i);
			}

			while ( end($range) > $max_num_pages ) {
				reset($range);
				$i = current($range) - 1;
				array_unshift($range, $i);
				array_pop($range);
			}

			reset($range);
			while ( current($range) < 1 ) {
				$i = end($range) + 1;
				if ( $i <= $max_num_pages )
					array_push($range, $i);
				array_shift($range);
				reset($range);
			}

			if ( current($range) != 1 )
				$pages[] = '...';

			foreach ( $range as $i ) {
				if ( $i == $paged ) {
					$pages[] = '<strong>' . $i . '</strong>';
				} else {
					$pages[] = '<a href="' . get_pagenum_link($i) . '">'
						. $i
						. '</a>';
				}
			}

			if ( end($range) != $max_num_pages )
				$pages[] = '...';

			if ( $paged < $max_num_pages ) {
				$pages[] = get_next_posts_link(trim($next . ' &raquo;'));
			}
		}
		
		$pages = implode(' ', $pages);
		
		$dates = blog_footer::date_nav();
		
		$o = array();
		
		foreach ( array('pages', 'dates') as $var ) {
			if ( $$var )
				$o[] = $$var;
		}
		
		$o = "<p>" . implode("</p>\n<p>", $o) . "</p>\n";
		
		echo $before_widget
			. $o
			. $after_widget;
	} # widget()
	
	
	/**
	 * date_nav()
	 *
	 * @return string $nav
	 **/

	function date_nav() {
		if ( !is_date() )
			return false;
		
		$dates = array();
		
		global $wpdb;
		
		$y = get_query_var('year');
		$m = get_query_var('monthnum');
		$d = get_query_var('day');
		
		$m = $m ? zeroise($m, 2) : false;
		$d = $d ? zeroise($d, 2) : false;
		
		if ( $d )
			$stop = "$y-$m-$d";
		elseif ( $m )
			$stop = "$y-$m-01";
		else
			$stop = "$y-01-01";
		
		$sql = "
			SELECT	MAX(post_date)
			FROM	$wpdb->posts
			WHERE	post_date < '$stop'
			AND		post_type = 'post'
			AND		post_status = 'publish'
			";
		$cache_id = md5($sql);
		
		$date = wp_cache_get($cache_id, 'widget_queries');
		if ( $date === false ) {
			$date = $wpdb->get_var($sql);
			if ( !$date )
				$date = 0;
			wp_cache_add($cache_id, $date, 'widget_queries');
		}
		
		if ( $date ) {
			$date = strtotime("$date GMT");
			
			if ( $d ) {
				$dates[] = '<a href="' . get_day_link(gmdate('Y', $date), gmdate('m', $date), gmdate('d', $date)) . '">'
					. '&laquo; ' . date_i18n(__('M jS, Y', 'sem-reloaded'), $date, true)
					. '</a>';
			} elseif ( $m ) {
				$dates[] = '<a href="' . get_month_link(gmdate('Y', $date), gmdate('m', $date)) . '">'
					. '&laquo; ' . date_i18n(__('M, Y', 'sem-reloaded'), $date, true)
					. '</a>';
			} else {
				$dates[] = '<a href="' . get_year_link(gmdate('Y', $date)) . '">'
					. '&laquo; ' . date_i18n(__('Y', 'sem-reloaded'), $date, true)
					. '</a>';
			}
		}
		
		if ( $d ) {
			$dates[] = '<a href="' . get_month_link($y, $m) . '">'
				. date_i18n(__('M, Y', 'sem-reloaded'), strtotime("$y-$m-$d GMT"), true)
				. '</a>';
		}
		
		if ( $d )
			$stop = gmdate('Y-m-d', strtotime("$y-$m-$d GMT + 1 day"));
		elseif ( $m )
			$stop = gmdate('Y-m-d', strtotime("$y-$m-01 GMT + 1 month"));
		else
			$stop = gmdate('Y-m-d', strtotime("$y-01-01 GMT + 1 year"));
		
		$sql = "
			SELECT	MIN(post_date)
			FROM	$wpdb->posts
			WHERE	post_date >= '$stop'
			AND		post_type = 'post'
			AND		post_status = 'publish'
			";
		$cache_id = md5($sql);
		
		$date = wp_cache_get($cache_id, 'widget_queries');
		if ( $date === false ) {
			$date = $wpdb->get_var($sql);
			if ( !$date )
				$date = 0;
			wp_cache_add($cache_id, $date, 'widget_queries');
		}
		
		if ( $date ) {
			$date = strtotime("$date GMT");
			
			if ( $d ) {
				$dates[] = '<a href="' . get_day_link(gmdate('Y', $date), gmdate('m', $date), gmdate('d', $date)) . '">'
					. date_i18n(__('M jS, Y', 'sem-reloaded'), $date, true) . ' &raquo;'
					. '</a>';
			} elseif ( $m ) {
				$dates[] = '<a href="' . get_month_link(gmdate('Y', $date), gmdate('m', $date)) . '">'
					. date_i18n(__('M, Y', 'sem-reloaded'), $date, true) . ' &raquo;'
					. '</a>';
			} else {
				$dates[] = '<a href="' . get_year_link(gmdate('Y', $date)) . '">'
					. date_i18n(__('Y', 'sem-reloaded'), $date, true) . ' &raquo;'
					. '</a>';
			}
		}
		
		$dates = implode(' &bull; ', $dates);
		
		return $dates;
	} # date_nav()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		foreach ( array_keys(blog_footer::defaults()) as $field )
			$instance[$field] = trim(strip_tags($new_instance[$field]));
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = blog_footer::defaults();
		$instance = wp_parse_args($instance, $defaults);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		foreach ( $defaults as $field => $default ) {
			echo '<p>'
				. '<label>'
				. '<code>' . $default . '</code>'
				. '<br />' . "\n"
				. '<input type="text" class="widefat"'
					. ' name="' . $this->get_field_name($field) . '"'
					. ' value="' . esc_attr($$field) . '"'
					. ' />'
				. '</label>'
				. '</p>' . "\n";
		}
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'next' => __('Next', 'sem-reloaded'),
			'previous' => __('Previous', 'sem-reloaded'),
			);
	} # defaults()
} # blog_footer


/**
 * header_boxes
 *
 * @package Semiologic Reloaded
 **/

class header_boxes extends WP_Widget {
	/**
	 * header_boxes()
	 *
	 * @return void
	 **/

	function header_boxes() {
		$widget_name = __('Header: Boxes Bar', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'header_boxes',
			'description' => __('Lets you decide where the Footer Boxes Bar panel goes. Must be placed in the header area.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('header_boxes', $widget_name, $widget_ops);
	} # header_boxes()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_header' )
			return;
		
		sem_panels::display('the_header_boxes');
	} # widget()
} # header_boxes


/**
 * footer_boxes
 *
 * @package Semiologic Reloaded
 **/

class footer_boxes extends WP_Widget {
	/**
	 * footer_boxes()
	 *
	 * @return void
	 **/

	function footer_boxes() {
		$widget_name = __('Footer: Boxes Bar', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'footer_boxes',
			'description' => __('Lets you decide where the Footer Boxes Bar panel goes. Must be placed in the footer area.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('footer_boxes', $widget_name, $widget_ops);
	} # footer_boxes()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_footer' )
			return;
		
		sem_panels::display('the_footer_boxes');
	} # widget()
} # footer_boxes


/**
 * header
 *
 * @package Semiologic Reloaded
 **/

class header extends WP_Widget {
	/**
	 * header()
	 *
	 * @return void
	 **/

	function header() {
		$widget_name = __('Header: Site Header', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'header',
			'description' => __('The site\'s header. Must be placed in the header area.', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('header', $widget_name, $widget_ops, $control_ops);
	} # header()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_header' )
			return;
		
		$instance = wp_parse_args($instance, header::defaults());
		extract($instance, EXTR_SKIP);
		
		$header = header::get();
		
		if ( $header ) {
			preg_match("/\.([^.]+)$/", $header, $ext);
			$ext = strtolower(end($ext));
			$flash = $ext == 'swf';
		} else {
			$flash = false;
		}
		
		echo '<div id="header" class="wrapper'
				. ( $invert_header
					? ' invert_header'
					: ''
					)
				. '"'
 			. ' title="'
				. esc_attr(get_option('blogname'))
				. ' &bull; '
				. esc_attr(get_option('blogdescription'))
				. '"';

		echo '>' . "\n";
		
		echo '<div id="header_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="header_bg">' . "\n";
		
		echo '<div class="wrapper_item">' . "\n";
		
		if ( !$header ) {
			echo '<div id="header_img" class="pad">' . "\n";

			$tagline = '<div id="tagline" class="tagline">'
				. get_option('blogdescription')
				. '</div>' . "\n";

			$site_name = '<div id="sitename" class="sitename">'
				. ( !( is_front_page() && !is_paged() )
					? ( '<a href="' . esc_url(user_trailingslashit(get_option('home'))) . '">' . get_option('blogname') . '</a>' )
					: get_option('blogname')
					)
				. '</div>' . "\n";
			
			if ( $invert_header ) {
				echo $site_name;
				echo $tagline;
			} else {
				echo $tagline;
				echo $site_name;
			}
			
			echo '</div>' . "\n";
		} else {
			echo header::display($header);
		}
		
		echo '</div>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '<div id="header_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- header -->' . "\n";
		
		global $did_header;
		global $did_navbar;
		$did_header = intval($did_navbar) + 1;
	} # widget()
	
	
	/**
	 * display()
	 *
	 * @param string $header
	 * @return string $html
	 **/

	function display($header = null) {
		if ( !$header )
			$header = header::get();
		
		if ( !$header )
			return;
		
		preg_match("/\.([^.]+)$/", $header, $ext);
		$ext = strtolower(end($ext));
		
		if ( $ext != 'swf' ) {
			list($width, $height) = wp_cache_get('sem_header', 'sem_header');
			
			$header = '<img src="' . sem_url . '/icons/pixel.gif"'
				. ' height="' . intval($height) . '" width="100%"'
				. ' alt="'
					. esc_attr(get_option('blogname'))
					. ' &bull; '
					. esc_attr(get_option('blogdescription'))
					. '"'
				. ' />';
			
			if ( !( is_front_page() && !is_paged() ) ) {
				$header = '<a'
				. ' href="' . esc_url(user_trailingslashit(get_option('home'))) . '"'
				. ' title="'
					. esc_attr(get_option('blogname'))
					. ' &bull; '
					. esc_attr(get_option('blogdescription'))
					. '"'
				. '>' . $header . '</a>';
			}
			
			echo '<div id="header_img" class="pad">'
				. $header
				. '</div>' . "\n";
		} else {
			echo '<div id="header_img">'
				. header::display_flash($header)
				. '</div>' . "\n";
		}
	} # display()
	
	
	/**
	 * display_image()
	 *
	 * @param string $header
	 * @return string $html
	 **/

	function display_image($header = null) {
		if ( !$header )
			$header = header::get_header();

		if ( !$header )
			return;
		
		list($width, $height) = wp_cache_get('sem_header', 'sem_header');
		
		$header = esc_url(content_url() . $header);
		
		return '<img src="' . $header . '" height="' . $height . '" width="' . $width . '" alt="'
			. esc_attr(get_option('blogname'))
			. ' &bull; '
			. esc_attr(get_option('blogdescription'))
			. '" />';
	} # display_image()
	
	
	/**
	 * display_flash()
	 *
	 * @param string $header
	 * @return string $html
	 **/

	function display_flash($header = null) {
		if ( !$header )
			$header = header::get_header();

		if ( !$header )
			return;
		
		list($width, $height) = wp_cache_get('sem_header', 'sem_header');
		
		static $i = 0;
		$player = esc_url(content_url() . $header);
		$player_id = 'header_img_' . md5($i++ . $header);
		
		return <<<EOS

<div style="width: {$width}px; height: {$height}px;"><object id="$player_id" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="$width" height="$height"><param name="movie" value="$player" /><param name="allowfullscreen" value="false" /><param name="allowscriptaccess" value="true" /><embed src="$player" pluginspage="http://www.macromedia.com/go/getflashplayer" width="$width" height="$height" allowfullscreen="false" allowscriptaccess="true" /></object></div>

<script type="text/javascript">
swfobject.embedSWF("$player", "$player_id", "$width", "$height", "9.0.0");
</script>

EOS;
	} # display_flash()
	
	
	/**
	 * letter()
	 *
	 * @param int $post_ID
	 * @return void
	 **/

	function letter() {
		$header = header::get();
		
		if ( !$header || $header != get_post_meta(get_the_ID(), '_sem_header', true) )
			return;
		
		echo header::display($header);
	} # letter()
	
	
	/**
	 * get()
	 *
	 * @return void
	 **/

	function get() {
		static $header;
		
		if ( !is_admin() && isset($header) )
			return $header;
		
		global $sem_options;
		
		# try post specific header
		if ( is_singular() ) {
			global $wp_the_query;
			$post_ID = intval($wp_the_query->get_queried_object_id());
		} else {
			$post_ID = false;
		}
		
		# try cached header
		if ( !is_admin() && !sem_header_cache_debug ) {
			switch ( is_singular() ) {
			case true:
				$header = get_post_meta($post_ID, '_sem_header', true);
				if ( !$header ) {
					$header = false;
					break;
				} elseif ( $header != 'default' ) {
					break;
				}
			default:
				$header = get_transient('sem_header');
			}
		} else {
			$header = false;
		}
		
		if ( $header !== false ) {
			$header_size = @getimagesize(WP_CONTENT_DIR . $header);
			if ( $header_size ) {
				wp_cache_set('sem_header', $header_size, 'sem_header');
				return $header;
			}
		}
		
		if ( defined('GLOB_BRACE') ) {
			$header_scan = "header{,-*}.{jpg,jpeg,png,gif,swf}";
			$skin_scan = "header.{jpg,jpeg,png,gif,swf}";
			$scan_type = GLOB_BRACE;
		} else {
			$header_scan = "header-*.jpg";
			$skin_scan = "header.jpg";
			$scan_type = false;
		}
		
		if ( is_singular() ) {
			# entry-specific header
			$header = glob(WP_CONTENT_DIR . "/header/$post_ID/$header_scan", $scan_type);
			if ( $header ) {
				$header = current($header);
				$header = str_replace(WP_CONTENT_DIR, '', $header);
				$header_size = @getimagesize(WP_CONTENT_DIR . $header);
				if ( $header_size ) {
					if ( get_post_meta($post_ID, '_sem_header', true) != $header )
						update_post_meta($post_ID, '_sem_header', $header);
					wp_cache_set('sem_header', $header_size, 'sem_header');
					return $header;
				}
			}
		}
		
		switch ( true ) {
		default:
			# uploaded header
			$header = glob(WP_CONTENT_DIR . "/header/$header_scan", $scan_type);
			if ( $header )
				break;
			
			# skin-specific header
			$active_skin = $sem_options['active_skin'];
			$header = glob(sem_path . "/skins/$active_skin/$skin_scan", $scan_type);
			if ( $header )
				break;
			
			# no header
			$header = false;
			break;
		}
		
		if ( is_singular() && get_post_meta($post_ID, '_sem_header', true) != 'default' )
			update_post_meta($post_ID, '_sem_header', 'default');
		
		if ( $header ) {
			$header = current($header);
			$header = str_replace(WP_CONTENT_DIR, '', $header);
			$header_size = @getimagesize(WP_CONTENT_DIR . $header);
			if ( $header_size ) {
				wp_cache_set('sem_header', $header_size, 'sem_header');
				set_transient('sem_header', $header);
				return $header;
			}
		}
		
		set_transient('sem_header', '0');
		return false;
	} # get()
	
	
	/**
	 * wire()
	 *
	 * @param object &$wp
	 * @return void
	 **/

	function wire(&$wp) {
		$header = header::get();
		
		if ( !$header )
			return;
		
		preg_match("/\.([^.]+)$/", $header, $ext);
		$ext = strtolower(end($ext));
		
		if ( $ext == 'swf' ) {
			wp_enqueue_script('swfobject');
		} else {
			add_action('wp_head', array('header', 'css'), 30);
		}
	} # wire()
	
	
	/**
	 * css()
	 *
	 * @return void
	 **/

	function css() {
		$header = header::get();
		
		list($width, $height) = wp_cache_get('sem_header', 'sem_header');
		
		if ( !$height )
			return;
		
		$header = esc_url(content_url() . $header);
		
		echo <<<EOS

<style type="text/css">
.skin #header_img {
	background: url(${header}) no-repeat top center;
	height: ${height}px;
	border: 0px;
	position: relative;
	padding: 0px;
	margin: 0px auto;
}
</style>

EOS;
	} # css()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['invert_header'] = isset($new_instance['invert_header']);
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = header::defaults();
		$instance = wp_parse_args($instance, $defaults);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Config', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="' . $this->get_field_name('invert_header') . '"'
				. checked($invert_header, true, false)
				. ' />'
			. '&nbsp;'
			. __('Output the site\'s name before the tagline.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array(
			'invert_header' => false,
			);
	} # defaults()
} # header


/**
 * sem_nav_menu
 *
 * @package Semiologic Reloaded
 **/

class sem_nav_menu extends WP_Widget {
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, sem_nav_menu::defaults());
		extract($instance, EXTR_SKIP);
		if ( is_admin() )
			return;
		
		if ( is_page() ) {
			global $_wp_using_ext_object_cache;
			global $wp_the_query;
			$page_id = $wp_the_query->get_queried_object_id();
			$cache_id = "_$widget_id";
			if ( $_wp_using_ext_object_cache )
				$o = wp_cache_get($page_id, $widget_id);
			else
				$o = get_post_meta($page_id, $cache_id, true);
		} else {
			$cache_id = "$widget_id";
			if ( is_home() && !is_paged() ) {
				$context = 'home';
			} elseif ( !is_search() && !is_404() ) {
				$context = 'blog';
			} else {
				$context = 'search';
			}
			$cache = get_transient($cache_id);
			$o = isset($cache[$context]) ? $cache[$context] : false;
		}
		
		if ( !sem_widget_cache_debug && !is_preview() && $o ) {
			echo $o;
			return;
		}
		
		sem_nav_menu::cache_pages();
		
		if ( !$items ) {
			$items = call_user_func(array(get_class($this), 'default_items'));
		}
		
		$root_pages = wp_cache_get(0, 'page_children');
		
		ob_start();
		
		echo '<div>' . "\n";
		
		$did_first = false;
		
		foreach ( $items as $item ) {
			if ( $sep && $did_first )
				echo '<span>|</span>' . "\n";
			
			switch ( $item['type'] ) {
			case 'home':
				sem_nav_menu::display_home($item);
				$did_first = true;
				break;
			case 'url':
				sem_nav_menu::display_url($item);
				$did_first = true;
				break;
			case 'page':
				if ( in_array($item['ref'], $root_pages) ) {
					sem_nav_menu::display_page($item);
					$did_first = true;
				}
				break;
			}
		}
		
		echo '</div>' . "\n";
		
		$o = ob_get_clean();
		
		if ( !is_preview() ) {
			if ( is_page() ) {
				if ( $_wp_using_ext_object_cache )
					wp_cache_set($page_id, $o, $widget_id);
				else
					update_post_meta($page_id, $cache_id, $o);
			} else {
				$cache[$context] = $o;
				set_transient($cache_id, $cache);
			}
		}
		
		echo $o;
	} # widget()
	
	
	/**
	 * display_home()
	 *
	 * @param array $item
	 * @return void
	 **/

	function display_home($item) {
		if ( get_option('show_on_front') == 'page' && get_option('page_on_front') ) {
			$item['type'] = 'page';
			$item['ref'] = get_option('page_on_front');
			return sem_nav_menu::display_page($item);
		}
		
		extract($item, EXTR_SKIP);
		if ( !isset($label) || (string) $label === '' )
			$label = __('Home', 'sem-reloaded');
		$url = esc_url(user_trailingslashit(get_option('home')));
		
		$classes = array('nav_home');
		$link = $label;
		
		if ( !is_front_page() || is_front_page() && is_paged() )
			$link = '<a href="' . $url . '" title="' . esc_attr(get_option('blogname')) . '">'
				. $link
				. '</a>';
		if ( !is_search() && !is_404() && !is_page() )
			$classes[] = 'nav_active';
		
		echo '<span class="' . implode(' ', $classes) . '">'
			. $link;
		
		echo '</span>' . "\n";
	} # display_home()
	
	
	/**
	 * display_url()
	 *
	 * @param array $item
	 * @return void
	 **/

	function display_url($item) {
		extract($item, EXTR_SKIP);
		if ( !isset($label) || (string) $label === '' )
			$label = __('Untitled', 'sem-reloaded');
		$url = esc_url($ref);
		if ( !$url || $url == 'http://' )
			return;
		
		if ( rtrim($url, '/') == rtrim(get_option('home'), '/') )
			return sem_nav_menu::display_home($item);
		
		if ( !sem_nav_menu::is_local_url($url) ) {
			$classes = array('nav_url');
		} else {
			$bits = parse_url($url);
			if ( !empty($bits['query']) )
				$classes = array('nav_leaf');
			elseif ( empty($bits['path']) || substr($bits['path'], -1) == '/' )
				$classes = array('nav_branch');
			elseif ( strpos(basename($bits['path']), '.') !== false )
				$classes = array('nav_leaf');
			else
				$classes = array('nav_branch');
		}
		
		$link = '<a href="' . $url . '" title="' . esc_attr($label) . '">'
			. $label
			. '</a>';
		
		echo '<span class="' . implode(' ', $classes) . '">'
			. $link
			. '</span>' . "\n";
	} # display_url()
	
	
	/**
	 * display_page()
	 *
	 * @param array $item
	 * @return void
	 **/

	function display_page($item) {
		extract($item, EXTR_SKIP);
		$ref = (int) $ref;
		$page = get_page($ref);
		
		if ( !$page || $page->post_parent != 0 && get_post_meta($page->ID, '_widgets_exclude', true) )
			return;
		
		if ( is_page() ) {
			global $wp_the_query;
			$page_id = $wp_the_query->get_queried_object_id();
		} elseif ( get_option('show_on_front') == 'page' ) {
			$page_id = (int) get_option('page_for_posts');
		} else {
			$page_id = 0;
		}
		
		if ( !isset($label) || (string) $label === '' )
			$label = get_post_meta($page->ID, '_widgets_label', true);
		if ( (string) $label === '' )
			$label = $page->post_title;
		if ( (string) $label === '' )
			$label = __('Untitled', 'sem-reloaded');
		
		$url = esc_url(apply_filters('the_permalink', get_permalink($page->ID)));
		
		$ancestors = $page_id ? wp_cache_get($page_id, 'page_ancestors') : array();
		$children = wp_cache_get($page->ID, 'page_children');
		
		$classes = array();
		$link = $label;
		
		if ( get_option('show_on_front') == 'page' && get_option('page_on_front') == $page->ID ) {
			$classes[] = 'nav_home';
			if ( !is_front_page() || is_front_page() && is_paged() )
				$link = '<a href="' . user_trailingslashit($url) . '" title="' . esc_attr($label) . '">'
					. $link
					. '</a>';
			if ( is_front_page() || in_array($page->ID, $ancestors) )
				$classes[] = 'nav_active';
		} elseif ( get_option('show_on_front') == 'page' && get_option('page_for_posts') == $page->ID ) {
			$classes[] = 'nav_blog';
			if ( !is_home() || is_home() && is_paged() )
				$link = '<a href="' . $url . '" title="' . esc_attr($label) . '">'
					. $link
					. '</a>';
			if ( !is_search() && !is_404() && ( !is_page() || in_array($page->ID, $ancestors) ) )
				$classes[] = 'nav_active';
		} else {
			if ( $children )
				$classes[] = 'nav_branch';
			else
				$classes[] = 'nav_leaf';
			
			if ( $page->ID != $page_id )
				$link = '<a href="' . $url . '" title="' . esc_attr($label) . '">'
					. $link
					. '</a>';
			
			$classes[] = 'nav_page-' . sanitize_html_class($page->post_name, $page->ID);
			if ( $page->ID == $page_id || in_array($page->ID, $ancestors) )
				$classes[] = 'nav_active';
		}
		
		echo '<span class="' . implode(' ', $classes) . '">'
			. $link;
		
		echo '</span>' . "\n";
	} # display_page()
	
	
	/**
	 * cache_pages()
	 *
	 * @return void
	 **/

	function cache_pages() {
		if ( is_page() ) {
			global $wp_the_query;
			$page_id = (int) $wp_the_query->get_queried_object_id();
			$page = get_page($page_id);
		} else {
			$page_id = 0;
			$page = null;
		}
		
		if ( get_option('show_on_front') == 'page' ) {
			$front_page_id = (int) get_option('page_on_front');
			$front_page = get_page($front_page_id);
			$blog_page_id = (int) get_option('page_for_posts');
			$blog_page = $blog_page_id ? get_page($blog_page_id) : null;
		} else {
			$front_page_id = 0;
			$front_page = null;
			$blog_page_id = 0;
			$blog_page = null;
		}
		
		$ancestors = $page_id ? wp_cache_get($page_id, 'page_ancestors') : array();
		if ( $ancestors === false ) {
			$ancestors = array();
			while ( $page && $page->post_parent != 0 ) {
				$ancestors[] = (int) $page->post_parent;
				$page = get_page($page->post_parent);
			}
			$ancestors = array_reverse($ancestors);
			wp_cache_set($page_id, $ancestors, 'page_ancestors');
		}
		
		$front_page_ancestors = $front_page_id ? wp_cache_get($front_page_id, 'page_ancestors') : array();
		if ( $front_page_ancestors === false ) {
			$front_page_ancestors = array();
			while ( $front_page && $front_page->post_parent != 0 ) {
				$front_page_ancestors[] = (int) $front_page->post_parent;
				$front_page = get_page($front_page->post_parent);
			}
			$front_page_ancestors = array_reverse($front_page_ancestors);
			wp_cache_set($front_page_id, $front_page_ancestors, 'page_ancestors');
		}
		
		$blog_page_ancestors = $blog_page_id ? wp_cache_get($blog_page_id, 'page_ancestors') : array();
		if ( $blog_page_ancestors === false ) {
			$blog_page_ancestors = array();
			while ( $blog_page && $blog_page->post_parent != 0 ) {
				$blog_page_ancestors[] = (int) $blog_page->post_parent;
				$blog_page = get_page($blog_page->post_parent);
			}
			$blog_page_ancestors = array_reverse($blog_page_ancestors);
			wp_cache_set($blog_page_id, $blog_page_ancestors, 'page_ancestors');
		}
		
		$parent_ids = array_merge($ancestors, $front_page_ancestors, $blog_page_ancestors);
		array_unshift($parent_ids, 0);
		if ( $page_id )
			$parent_ids[] = $page_id;
		if ( $front_page_id )
			$parent_ids[] = $front_page_id;
		if ( $blog_page_id )
			$parent_ids[] = $blog_page_id;
		$parent_ids = array_map('intval', $parent_ids);
		$parent_ids = array_unique($parent_ids);
		sort($parent_ids);
		
		$cached = true;
		foreach ( $parent_ids as $parent_id ) {
			$children_ids = wp_cache_get($parent_id, 'page_children');
			$cached = is_array($children_ids);
			if ( $cached === false )
				break;
			foreach ( $children_ids as $children_id ) {
				$cached = is_array(wp_cache_get($children_id, 'page_children'));
				if ( $cached === false )
					break 2;
			}
		}
		
		if ( $cached )
			return;
		
		global $wpdb;
		
		$root_ids = array();
		if ( $page_id ) {
			$parent_page = get_post($page_id);
			while ( $parent_page->post_parent ) {
				$root_ids[] = $parent_page->post_parent;
				$parent_page = get_post($parent_page->post_parent);
			}
		}
		$root_ids = array_merge($root_ids, array(0, $page_id, $front_page_id, $blog_page_id));
		$root_ids = array_map('intval', $root_ids);
		$root_ids = array_unique($root_ids);
		sort($root_ids);
		
		$roots = (array) $wpdb->get_col("
			SELECT	posts.ID
			FROM	$wpdb->posts as posts
			WHERE	posts.post_type = 'page'
			AND		post_status IN ( 'publish', 'private' )
			AND		posts.post_parent IN ( " . implode(',', $root_ids) . " )
			");
		
		$parent_ids = array_merge($parent_ids, $roots, array($page_id, $front_page_id, $blog_page_id));
		$parent_ids = array_map('intval', $parent_ids);
		$parent_ids = array_unique($parent_ids);
		sort($parent_ids);
		
		$pages = (array) $wpdb->get_results("
			SELECT	posts.*
			FROM	$wpdb->posts as posts
			WHERE	posts.post_type = 'page'
			AND		posts.post_status = 'publish'
			AND		posts.post_parent IN ( " . implode(',', $parent_ids) . " )
			ORDER BY posts.menu_order, posts.post_title
			");
		
		$children = array();
		$to_cache = array();
		
		foreach ( $parent_ids as $parent_id )
			$children[$parent_id] = array();
		
		foreach ( $pages as $page ) {
			$children[$page->post_parent][] = $page->ID;
			$to_cache[] = $page->ID;
		}
		
		$all_ancestors = array();
		
		foreach ( $children as $parent => $child_ids ) {
			foreach ( $child_ids as $key => $child_id )
				$all_ancestors[$child_id][] = $parent;
			wp_cache_set($parent, $child_ids, 'page_children');
		}
		
		foreach ( $all_ancestors as $child_id => $parent_ids ) {
			while ( $parent_ids[0] && $all_ancestors[$parent_ids[0]] )
				$parent_ids = array_merge($all_ancestors[$parent_ids[0]], $parent_ids);
			wp_cache_set($child_id, $parent_ids, 'page_ancestors');
		}
		
		foreach ( array_keys($pages) as $k ) {
			$ancestors = wp_cache_get($pages[$k]->ID, 'page_ancestors');
			array_shift($ancestors);
			$pages[$k]->ancestors = $ancestors;
		}

		update_post_cache($pages);
		update_postmeta_cache($to_cache);
	} # cache_pages()
	
	
	/**
	 * is_local_url()
	 *
	 * @param string $url
	 * @return bool $is_local_url
	 **/

	function is_local_url($url) {
		if ( in_array(substr($url, 0, 1), array('?', '#')) || strpos($url, '://') === false )
			return true;
		elseif ( preg_match("~/go(/|\.)~i", $url) )
			return false;
		
		static $site_domain;
		
		if ( !isset($site_domain) ) {
			$site_domain = get_option('home');
			$site_domain = parse_url($site_domain);
			$site_domain = $site_domain['host'];
			$site_domain = preg_replace("/^www\./i", '', $site_domain);
			
			# The following is not bullet proof, but it's good enough for a WP site
			if ( $site_domain != 'localhost' && !preg_match("/\d+(\.\d+){3}/", $site_domain) ) {
				if ( preg_match("/\.([^.]+)$/", $site_domain, $tld) ) {
					$tld = end($tld);
				} else {
					$site_domain = false;
					return false;
				}
				
				$site_domain = substr($site_domain, 0, strlen($site_domain) - 1 - strlen($tld));
				
				if ( preg_match("/\.([^.]+)$/", $site_domain, $subtld) ) {
					$subtld = end($subtld);
					if ( strlen($subtld) <= 4 ) {
						$site_domain = substr($site_domain, 0, strlen($site_domain) - 1 - strlen($subtld));
						$site_domain = explode('.', $site_domain);
						$site_domain = array_pop($site_domain);
						$site_domain .= ".$subtld";
					} else {
						$site_domain = $subtld;
					}
				}
				
				$site_domain .= ".$tld";
			}
			
			$site_domain = strtolower($site_domain);
		}
		
		if ( !$site_domain )
			return false;
		
		$link_domain = parse_url($url);
		$link_domain = $link_domain['host'];
		$link_domain = preg_replace("/^www\./i", '', $link_domain);
		$link_domain = strtolower($link_domain);
		
		if ( $site_domain == $link_domain ) {
			return true;
		} elseif ( function_exists('is_multisite') && is_multisite() ) {
			return false;
		} else {
			$site_elts = explode('.', $site_domain);
			$link_elts = explode('.', $link_domain);
			
			while ( ( $site_elt = array_pop($site_elts) ) && ( $link_elt = array_pop($link_elts) ) ) {
				if ( $site_elt !== $link_elt )
					return false;
			}
			
			return empty($link_elts) || empty($site_elts);
		}
	} # is_local_url()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = sem_nav_menu::defaults();
		$instance['sep'] = isset($new_instance['sep']);
		foreach ( array_keys((array) $new_instance['items']['type']) as $key ) {
			$item = array();
			$item['type'] = $new_instance['items']['type'][$key];
			
			if ( !in_array($item['type'], array('home', 'url', 'page')) ) {
				continue;
			}
			
			$label = trim(strip_tags($new_instance['items']['label'][$key]));
			
			switch ( $item['type'] ) {
				case 'home':
					$item['label'] = $label;
					break;
				case 'url':
					$item['ref'] = trim(strip_tags($new_instance['items']['ref'][$key]));
					$item['label'] = $label;
					break;
				case 'page':
					$item['ref'] = intval($new_instance['items']['ref'][$key]);
					$page = get_post($item['ref']);
					if ( $page->post_title != $label ) {
						update_post_meta($item['ref'], '_widgets_label', $label);
					} else {
						delete_post_meta($item['ref'], '_widgets_label');
					}
					break;
			}
			
			$instance['items'][] = $item;
		}
		
		sem_nav_menu::flush_cache();
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, sem_nav_menu::defaults());
		$pages = wp_cache_get('nav_menu_roots', 'nav_menu_roots');
		
		if ( $pages === false ) {
			global $wpdb;
			$pages = $wpdb->get_results("
				SELECT	posts.*
				FROM	$wpdb->posts as posts
				WHERE	posts.post_type = 'page'
				AND		posts.post_status = 'publish'
				AND		posts.post_parent = 0
				ORDER BY posts.menu_order, posts.post_title
				");
			update_post_cache($pages);
			$to_cache = array();
			foreach ( $pages as $page )
				$to_cache[] = $page->ID;
			update_postmeta_cache($to_cache);
			wp_cache_set('nav_menu_roots', $pages, 'nav_menu_roots');
		}
		
		extract($instance, EXTR_SKIP);
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="' . $this->get_field_name('sep') . '"'
				. checked($sep, true, false)
				. ' />'
			. '&nbsp;'
			. __('Split navigation menu items with a |.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
		
		
		echo '<div class="hide-if-no-js">' . "\n";
		
		echo '<h3>' . __('Menu Items', 'sem-reloaded') . '</h3>' . "\n";
		
		
		echo '<div class="nav_menu_items">' . "\n";
		
		echo '<input type="hidden" class="nav_menu_base"'
			. ' value="' . $this->get_field_name('items') . '" />' . "\n";
		
		
		echo '<div class="nav_menu_items_controller">' . "\n";
		
		echo '<select class="nav_menu_item_select">' . "\n"
			. '<option value="">'
				. esc_attr(__('- Select a menu item -', 'sem-reloaded'))
				. '</option>' . "\n"
			. '<optgroup label="' . esc_attr(__('Special', 'sem-reloaded')) . '">' . "\n"
			. '<option value="home" class="nav_menu_item_home">'
				. __('Home', 'sem-reloaded')
				. '</option>' . "\n"
			. '<option value="url" class="nav_menu_item_url">'
				. __('Url', 'sem-reloaded')
				. '</option>' . "\n"
			. '</optgroup>' . "\n"
			. '<optgroup class="nav_menu_item_pages"'
				. ' label="' . esc_attr(__('Pages', 'sem-reloaded')) . '"'
				. '>' . "\n"
			;
		
		foreach ( $pages as $page ) {
			$label = get_post_meta($page->ID, '_widgets_label', true);
			if ( $label === '' )
				$label = $page->post_title;
			if ( $label === '' )
				$label = __('Untitled', 'sem-reloaded');
			$label = strip_tags($label);
			echo '<option value="page-' . $page->ID . '">'
				. esc_attr($label)
				. '</option>' . "\n";
		}
		
		echo '</optgroup>' . "\n";
		
		echo '</select>';
		
		echo '&nbsp;';
		
		echo '<input type="button" class="nav_menu_item_add" value="&nbsp;+&nbsp;" />' . "\n";
		
		echo '</div>' . "\n"; # controller
		
		echo '<p>'
			. __('Drag and drop menu items to rearrange them.', 'sem-reloaded')
			. '</p>' . "\n";
		
		
		echo '<div class="nav_menu_item_sortables">' . "\n";
		
		foreach ( $items as $item ) {
			$label = $item['label'];
			$type = $item['type'];
			switch ( $type ) {
			case 'home':
				$ref = 'home';
				$url = user_trailingslashit(get_option('home'));
				$handle = 'home';
				break;
			case 'url':
				$ref = $item['ref'];
				$url = $ref;
				$handle = 'url';
				break;
			case 'page':
				$ref = $item['ref'];
				$page = get_post($ref);
				if ( !$page )
					continue 2;
				$url = apply_filters('the_permalink', get_permalink($ref));
				$handle = 'page-' . $ref;
				$label = get_post_meta($page->ID, '_widgets_label', true);
				if ( $label === '' )
					$label = $page->post_title;
				if ( $label === '' )
					$label = __('Untitled', 'sem-reloaded');
				break;
			}
			
			echo '<div class="nav_menu_item nav_menu_item-' . $handle . ' button">' . "\n"
				. '<div class="nav_menu_item_data">' ."\n"
				. '<input type="text" class="nav_menu_item_label"'
					. ' onchange="navMenus.onLabelChange(this);"'
					. ' name="' . $this->get_field_name('items') . '[label][]"'
					. ' value="' . esc_attr($label) . '"'
					. ' />' . "\n"
				. '&nbsp;'
				. '<input type="button" class="nav_menu_item_remove" value="&nbsp;-&nbsp;" />' . "\n"
					. '<input type="hidden"'
						. ' name="' . $this->get_field_name('items') . '[type][]"'
						. ' value="' . $type . '"'
						. ' />' . "\n"
				. '<input type="' . ( $handle == 'url' ? 'text' : 'hidden' ) . '"'
					. ' class="nav_menu_item_ref"'
					. ( $handle == 'url' ? ' onchange="navMenus.onRefChange(this);"' : '' )
					. ' name="' . $this->get_field_name('items') . '[ref][]"'
					. ' value="' . $ref . '"'
					. ' />' . "\n"
				. '</div>' . "\n" # data
				. '<div class="nav_menu_item_preview">' . "\n"
				. '&rarr;&nbsp;<a href="' . esc_url($url) . '"'
					. ' target="_blank">'
					. $label
					. '</a>'
				. '</div>' . "\n" # preview
				. '</div>' . "\n"; # item
		}
		
		if ( !$items ) {
			echo '<div class="nav_menu_item_blank">' . "\n"
				. '<p>' . __('Empty Navigation Menu. Leave it empty to populate it automatically.', 'sem-reloaded') . '</p>' . "\n"
				. '</div>' . "\n";
		} elseif ( defined('DOING_AJAX') ) {
			echo <<<EOS
<script type="text/javascript">
jQuery('div.nav_menu_item_sortables:has(.nav_menu_item)').sortable({});
</script>
EOS;
		}
		
		echo '</div>' . "\n"; # sortables
		
		echo '</div>' . "\n"; # items
		
		echo '</div>' . "\n"; # hide-if-no-js
	} # form()
	
	
	/**
	 * admin_footer()
	 *
	 * @return void
	 **/

	function admin_footer() {
		$pages = wp_cache_get('nav_menu_roots', 'nav_menu_roots');
		
		if ( $pages === false ) {
			global $wpdb;
			$pages = $wpdb->get_results("
				SELECT	posts.*
				FROM	$wpdb->posts as posts
				WHERE	posts.post_type = 'page'
				AND		posts.post_status = 'publish'
				AND		posts.post_parent = 0
				ORDER BY posts.menu_order, posts.post_title
				");
			update_post_cache($pages);
			$to_cache = array();
			foreach ( $pages as $page )
				$to_cache[] = $page->ID;
			update_postmeta_cache($to_cache);
			wp_cache_set('nav_menu_roots', $pages, 'nav_menu_roots');
		}
		
		echo '<div id="nav_menu_item_defaults" style="display: none;">' . "\n";
		
		echo '<div class="nav_menu_item_blank">' . "\n"
			. '<p>' . __('Empty Navigation Menu. Leave it empty to populate it automatically.', 'sem-reloaded') . '</p>' . "\n"
			. '</div>' . "\n";
		
		$default_items = array(
			array(
				'type' => 'home',
				'label' => __('Home', 'sem-reloaded'),
				),
			array(
				'type' => 'url',
				'ref' => 'http://',
				'label' => __('Url Label', 'sem-reloaded'),
				),
			);
		
		foreach ( $pages as $page ) {
			$label = get_post_meta($page->ID, '_widgets_label', true);
			if ( $label === '' )
				$label = $page->post_title;
			if ( $label === '' )
				$label = __('Untitled', 'sem-reloaded');
			$label = strip_tags($label);
			$default_items[] = array(
				'type' => 'page',
				'ref' => $page->ID,
				'label' => $label,
				);
		}
		
		foreach ( $default_items as $item ) {
			$label = $item['label'];
			$type = $item['type'];
			switch ( $type ) {
			case 'home':
				$ref = 'home';
				$url = user_trailingslashit(get_option('home'));
				$handle = 'home';
				break;
			case 'url':
				$ref = $item['ref'];
				$url = $ref;
				$handle = 'url';
				break;
			case 'page':
				$ref = $item['ref'];
				$url = apply_filters('the_permalink', get_permalink($ref));
				$handle = 'page-' . $ref;
				$page = get_post($ref);
				$label = get_post_meta($page->ID, '_widgets_label', true);
				if ( $label === '' )
					$label = $page->post_title;
				if ( $label === '' )
					$label = __('Untitled', 'sem-reloaded');
				$label = strip_tags($label);
				break;
			}
			
			echo '<div class="nav_menu_item nav_menu_item-' . $handle . ' button">' . "\n"
				. '<div class="nav_menu_item_data">' ."\n"
				. '<input type="text" class="nav_menu_item_label"'
					. ' onchange="navMenus.onLabelChange(this)"'
					. ' name="[label][]"'
					. ' value="' . esc_attr($label) . '"'
					. ' />' . "\n"
				. '&nbsp;'
				. '<input type="button" class="nav_menu_item_remove"'
					. ' value="&nbsp;-&nbsp;" />' . "\n"
				. '<input type="hidden"'
					. ' name="[type][]"'
					. ' value="' . $type . '"'
					. ' />' . "\n"
				. '<input type="' . ( $handle == 'url' ? 'text' : 'hidden' ) . '"'
					. ' class="nav_menu_item_ref"'
					. ( $handle == 'url' ? ' onchange="navMenus.onRefChange(this)"' : '' )
					. ' name="[ref][]"'
					. ' value="' . $ref . '"'
					. ' />' . "\n"
				. '</div>' . "\n" # data
				. '<div class="nav_menu_item_preview">' . "\n"
				. '&rarr;&nbsp;<a href="' . esc_url($url) . '"'
					. ' target="_blank">'
					. $label
					. '</a>'
				. '</div>' . "\n" # preview
				. '</div>' . "\n"; # item
		}
		
		echo '</div>' . "\n"; # defaults
	} # admin_footer()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance default options
	 **/

	function defaults() {
		return array(
			'sep' => false,
			'items' => array(),
			);
	} # defaults()
	
	
	/**
	 * default_items()
	 *
	 * @return array $items
	 **/

	function default_items() {
		$items = array(array('type' => 'home'));
		
		$roots = wp_cache_get(0, 'page_children');
		
		if ( !$roots )
			return $items;
		
		$front_page_id = get_option('show_on_front') == 'page'
			? (int) get_option('page_on_front')
			: 0;

		foreach ( $roots as $root_id ) {
			if ( $root_id == $front_page_id )
				continue;
			if ( get_post_meta($root_id, '_widgets_exclude', true) )
				continue;
			
			$items[] = array(
				'type' => 'page',
				'ref' => $root_id,
				);
		}
		
		return $items;
	} # default_items()
	
	
	/**
	 * pre_flush_post()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function pre_flush_post($post_id) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return;
		
		$post = get_post($post_id);
		if ( !$post || $post->post_type != 'page' || wp_is_post_revision($post_id) )
			return;
		
		$old = wp_cache_get($post_id, 'pre_flush_post');
		if ( $old === false )
			$old = array();
		
		$update = false;
		foreach ( array(
			'post_title',
			'post_status',
			) as $field ) {
			if ( !isset($old[$field]) ) {
				$old[$field] = $post->$field;
				$update = true;
			}
		}
		
		if ( !isset($old['permalink']) ) {
			$old['permalink'] = apply_filters('the_permalink', get_permalink($post_id));
			$update = true;
		}
		
		foreach ( array(
			'widgets_label',
			'widgets_exclude',
			) as $key ) {
			if ( !isset($old[$key]) ) {
				$old[$key] = get_post_meta($post_id, "_$key", true);
				$update = true;
			}
		}
		
		
		if ( $update )
			wp_cache_set($post_id, $old, 'pre_flush_post');
	} # pre_flush_post()
	
	
	/**
	 * flush_post()
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function flush_post($post_id) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return;
		
		# prevent mass-flushing when the permalink structure hasn't changed
		remove_action('generate_rewrite_rules', array('sem_nav_menu', 'flush_cache'));
		
		$post = get_post($post_id);
		if ( !$post || $post->post_type != 'page' || wp_is_post_revision($post_id) )
			return;
		
		$old = wp_cache_get($post_id, 'pre_flush_post');
		
		if ( $post->post_status != 'publish' && ( !$old || $old['post_status'] != 'publish' ) )
			return;
		
		if ( $old === false )
			return sem_nav_menu::flush_cache();
		
		extract($old, EXTR_SKIP);
		foreach ( array_keys($old) as $key ) {
			switch ( $key ) {
			case 'widgets_label':
			case 'widgets_exclude':
				if ( $$key != get_post_meta($post_id, "_$key", true) )
					return sem_nav_menu::flush_cache();
				break;
			
			case 'permalink':
				if ( $$key != apply_filters('the_permalink', get_permalink($post_id)) )
					return sem_nav_menu::flush_cache();
				break;
			
			case 'post_title':
			case 'post_status':
				if ( $$key != $post->$key )
					return sem_nav_menu::flush_cache();
			}
		}
	} # flush_post()
	
	
	/**
	 * flush_cache()
	 *
	 * @param mixed $in
	 * @return mixed $in
	 **/
	
	function flush_cache($in = null) {
		static $done = false;
		if ( $done )
			return $in;
		
		$done = true;
		$cache_ids = array();
		
		foreach ( array('navbar', 'footer') as $option_name ) {
			$widgets = get_option("widget_$option_name");
			
			if ( !$widgets )
				continue;
			
			unset($widgets['_multiwidget']);
			unset($widgets['number']);
			
			if ( !$widgets )
				continue;
			
			global $_wp_using_ext_object_cache;
			foreach ( array_keys($widgets) as $widget_id ) {
				$cache_id = "$option_name-$widget_id";
				delete_transient($cache_id);
				delete_post_meta_by_key("_$cache_id");
				if ( $_wp_using_ext_object_cache )
					$cache_ids[] = $cache_id;
			}
		}
		
		if ( $cache_ids ) {
			$page_ids = wp_cache_get('page_ids', 'widget_queries');
			if ( $page_ids === false ) {
				global $wpdb;
				$page_ids = $wpdb->get_col("
					SELECT	ID
					FROM	$wpdb->posts
					WHERE	post_type = 'page'
					AND		post_status IN ( 'publish', 'private' )
					");
				wp_cache_set('page_ids', $page_ids, 'widget_queries');
			}
			foreach ( $cache_ids as $cache_id ) {
				foreach ( $page_ids as $page_id )
					wp_cache_delete($page_id, $cache_id);
			}
		}
		
		return $in;
	} # flush_cache()
} # sem_nav_menu


/**
 * navbar
 *
 * @package Semiologic Reloaded
 **/

class navbar extends sem_nav_menu {
	/**
	 * navbar()
	 *
	 * @return void
	 **/

	function navbar() {
		$widget_name = __('Header: Nav Menu', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'navbar',
			'description' => __('The header\'s navigation menu, with an optional search form. Must be placed in the header area.', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('navbar', $widget_name, $widget_ops, $control_ops);
	} # navbar()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_header' )
			return;
		
		$instance = wp_parse_args($instance, navbar::defaults());
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$navbar_class = '';
		if ( $show_search_form )
			$navbar_class .= ' float_nav';
		if ( $sep )
			$navbar_class .= ' sep_nav';
		
		echo '<div id="navbar" class="wrapper navbar' . $navbar_class . '">' . "\n";
		
		echo '<div id="navbar_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="navbar_bg">' . "\n";
		
		echo '<div class="wrapper_item">' . "\n";
		
		echo '<div class="pad">' . "\n";
		
		echo '<div id="header_nav" class="header_nav inline_menu">';

		parent::widget($args, $instance);

		echo '</div><!-- header_nav -->' . "\n";

		if ( $show_search_form ) {
			echo '<div id="search_form" class="search_form">';

			if ( is_search() )
				$search = apply_filters('the_search_form', get_search_query());
			else
				$search = $search_field;
			
			$search_caption = addslashes(esc_attr($search_field));
			if ( $search_caption ) {
				$onfocusblur = ' onfocus="if ( this.value == \'' . $search_caption . '\' )'
							. ' this.value = \'\';"'
						. ' onblur="if ( this.value == \'\' )'
						 	. ' this.value = \'' . $search_caption . '\';"';
			} else {
				$onfocus_blur = '';
			}
			
			$go = $search_button;
			
			if ( $go !== '' )
				$go = '<input type="submit" id="go" class="go button submit" value="' . esc_attr($go) . '" />';
			
			echo '<form method="get"'
					. ' action="' . esc_url(user_trailingslashit(get_option('home'))) . '"'
					. ' id="searchform" name="searchform"'
					. '>'
				. '&nbsp;'				# force line-height
				. '<input type="text" id="s" class="s" name="s"'
					. ' value="' . esc_attr($search) . '"'
					. $onfocusblur
					. ' />'
				. $go
				. '</form>';
			
			echo '</div><!-- search_form -->';
		}

		echo '<div class="spacer"></div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n";
		
		echo '<div id="navbar_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- navbar -->' . "\n";
		
		global $did_header;
		global $did_navbar;
		$did_navbar = intval($did_header) + 1;
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = parent::update($new_instance, $old_instance);
		$instance['show_search_form'] = isset($new_instance['show_search_form']);
		$instance['search_field'] = trim(strip_tags($new_instance['search_field']));
		$instance['search_button'] = trim(strip_tags($new_instance['search_button']));
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = navbar::defaults();
		$instance = wp_parse_args($instance, $defaults);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		foreach ( array('search_field', 'search_button') as $field ) {
			echo '<p>'
				. '<label>'
				. '<code>' . $defaults[$field] . '</code>'
				. '<br />' . "\n"
				. '<input type="text" class="widefat"'
					. ' name="' . $this->get_field_name($field) . '"'
					. ' value="' . esc_attr($$field) . '"'
					. ' />'
				. '</label>'
				. '</p>' . "\n";
		}
		
		echo '<h3>' . __('Config', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="' . $this->get_field_name('show_search_form') . '"'
				. checked($show_search_form, true, false)
				. ' />'
			. '&nbsp;'
			. __('Show a search form in the navigation menu.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
		
		parent::form($instance);
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array_merge(array(
			'search_field' => __('Search', 'sem-reloaded'),
			'search_button' => __('Go', 'sem-reloaded'),
			'show_search_form' => true,
			), parent::defaults());
	} # defaults()
	
	
	/**
	 * default_items()
	 *
	 * @return array $items
	 **/

	function default_items() {
		$items = array(array('type' => 'home'));
		
		$roots = wp_cache_get(0, 'page_children');
		
		if ( !$roots )
			return $items;
		
		$front_page_id = get_option('show_on_front') == 'page'
			? (int) get_option('page_on_front')
			: 0;

		foreach ( $roots as $root_id ) {
			if ( $root_id == $front_page_id )
				continue;
			if ( get_post_meta($root_id, '_widgets_exclude', true) )
				continue;
			if ( !wp_cache_get($root_id, 'page_children') ) # only sections
				continue;
			
			$items[] = array(
				'type' => 'page',
				'ref' => $root_id,
				);
		}
		
		return $items;
	} # default_items()
} # navbar


/**
 * footer
 *
 * @package Semiologic Reloaded
 **/

class footer extends sem_nav_menu {
	/**
	 * footer_nav()
	 *
	 * @return void
	 **/

	function footer() {
		$widget_name = __('Footer: Nav Menu', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'footer',
			'description' => __('The footer\'s navigation menu, with an optional copyright notice. Must be placed in the footer area.', 'sem-reloaded'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('footer', $widget_name, $widget_ops, $control_ops);
	} # footer()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( $args['id'] != 'the_footer' )
			return;
		
		$instance = wp_parse_args($instance, footer::defaults());
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$footer_class = '';
		if ( $sep )
			$footer_class .= ' sep_nav';
		if ( $float_footer && $copyright ) {
			$footer_class .= ' float_nav';
			if ( $sep )
				$footer_class .= ' float_sep_nav';
		}
		
		echo '<div id="footer" class="wrapper' . $footer_class . '">' . "\n";
		
		echo '<div id="footer_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="footer_bg">' . "\n"
			. '<div class="wrapper_item">' . "\n"
			. '<div class="pad">' . "\n";
		
		echo '<div id="footer_nav" class="inline_menu">';
		
		sem_nav_menu::widget($args, $instance);
		
		echo '</div><!-- footer_nav -->' . "\n";
		
		$year = date('Y');
		$site_name = strip_tags(get_option('blogname'));
		
		$copyright = sprintf($copyright, $site_name, $year);
		
		if ( $copyright ) {
			echo '<div id="copyright_notice">';
			echo $copyright;
			echo '</div><!-- #copyright_notice -->' . "\n";
		}
		
		echo '<div class="spacer"></div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n";
		
		echo '<div id="footer_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- footer -->' . "\n";
		
		global $did_footer;
		$did_footer = true;
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance new widget options
	 * @param array $old_instance old widget options
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = parent::update($new_instance, $old_instance);
		$instance['float_footer'] = isset($new_instance['float_footer']);
		if ( current_user_can('unfiltered_html') ) {
			$instance['copyright'] = trim($new_instance['copyright']);
		} else {
			$instance['copyright'] = $old_instance['copyright'];
		}
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance widget options
	 * @return void
	 **/

	function form($instance) {
		$defaults = footer::defaults();
		$instance = wp_parse_args($instance, $defaults);
		extract($instance, EXTR_SKIP);
		
		echo '<h3>' . __('Captions', 'sem-reloaded') . '</h3>' . "\n";
		
		foreach ( array('copyright') as $field ) {
			echo '<p>'
				. '<label for="' . $this->get_field_id($field) . '">'
				. '<code>' . htmlspecialchars($defaults[$field], ENT_QUOTES, get_option('blog_charset')) . '</code>'
				. '</label>'
				. '<br />' . "\n"
				. '<textarea class="widefat" cols="20" rows="4"'
					. ' id="' . $this->get_field_id($field) . '"'
					. ' name="' . $this->get_field_name($field) . '"'
					. ( !current_user_can('unfiltered_html')
						? ' disabled="disabled"'
						: ''
						)
					. ' >'
				. esc_html($$field)
				. '</textarea>'
				. '</p>' . "\n";
		}
		
		echo '<h3>' . __('Config', 'sem-reloaded') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
				. ' name="' . $this->get_field_name('float_footer') . '"'
				. checked($float_footer, true, false)
				. ' />'
			. '&nbsp;'
			. __('Place the footer navigation menu and the copyright on a single line.', 'sem-reloaded')
			. '</label>'
			. '</p>' . "\n";
		
		parent::form($instance);
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $defaults
	 **/
	
	function defaults() {
		return array_merge(array(
			'copyright' => __('Copyright %1$s, %2$s', 'sem-reloaded'),
			'float_footer' => false,
			), parent::defaults());
	} # defaults()
	
	
	/**
	 * default_items()
	 *
	 * @return array $items
	 **/

	function default_items() {
		$items = array(array('type' => 'home'));
		
		$roots = wp_cache_get(0, 'page_children');
		
		if ( !$roots )
			return $items;
		
		$front_page_id = get_option('show_on_front') == 'page'
			? (int) get_option('page_on_front')
			: 0;

		foreach ( $roots as $root_id ) {
			if ( $root_id == $front_page_id )
				continue;
			if ( get_post_meta($root_id, '_widgets_exclude', true) )
				continue;
			if ( wp_cache_get($root_id, 'page_children') ) # only non-sections
				continue;
			
			$items[] = array(
				'type' => 'page',
				'ref' => $root_id,
				);
		}
		
		return $items;
	} # default_items()
} # footer


add_action('widgets_init', array('sem_widgets', 'register'));

if ( !is_admin() ) {
	add_action('wp', array('header', 'wire'), 20);
} else {
	add_action('admin_print_scripts-widgets.php', array('sem_widgets', 'admin_scripts'));
	add_action('admin_print_styles-widgets.php', array('sem_widgets', 'admin_styles'));
}

foreach ( array(
		'switch_theme',
		'update_option_active_plugins',
		'update_option_show_on_front',
		'update_option_page_on_front',
		'update_option_page_for_posts',
		'update_option_sidebars_widgets',
		'update_option_sem5_options',
		'update_option_sem6_options',
		'generate_rewrite_rules',
		
		'flush_cache',
		'after_db_upgrade',
		) as $hook )
	add_action($hook, array('sem_nav_menu', 'flush_cache'));

add_action('pre_post_update', array('sem_nav_menu', 'pre_flush_post'));

foreach ( array(
		'save_post',
		'delete_post',
		) as $hook )
	add_action($hook, array('sem_nav_menu', 'flush_post'), 1); // before _save_post_hook()

add_action('widget_tag_cloud_args', array('sem_widgets', 'tag_cloud_args'));
add_filter('widget_display_callback', array('sem_widgets', 'widget_display_callback'), 10, 3);

wp_cache_add_non_persistent_groups(array('sem_header'));
wp_cache_add_non_persistent_groups(array('nav_menu_roots', 'page_ancestors', 'page_children'));
wp_cache_add_non_persistent_groups(array('widget_queries', 'pre_flush_post'));
?>