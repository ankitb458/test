<?php
/**
 * sem_widgets
 *
 * @package Semiologic Reloaded
 **/

add_action('widgets_init', array('sem_widgets', 'register'));

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
		register_widget('header_nav');
		register_widget('footer_nav');
		register_widget('header');
	} # register()
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
		
		$this->WP_Widget('entry_header', $widget_name, $widget_ops);
	} # entry_header()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_entry;
		
		if ( !$the_entry || !class_exists('widget_contexts') && is_letter() )
			return;
		
		global $sem_options;
		global $sem_captions;
		extract($args, EXTR_SKIP);
		
		if ( $sem_options['show_post_date'] && ( is_single() || !is_singular() ) ) {
			$date = the_date('', '', '', false);
		}
		
		$title = the_title('', '', false);
		
		if ( $title ) {
			if ( !is_singular() ) {
				$permalink = apply_filters('the_permalink', get_permalink());
				$title = '<a href="' . clean_url($permalink) . '" title="' . esc_attr($title) . '">'
					. $title
					. '</a>';
			}
		}
		
		if ( $date || $title ) {
			echo '<div class="spacer"></div>' . "\n";
			
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
			
			echo '<div class="spacer"></div>' . "\n";
		}
	} # widget()
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
		
		$this->WP_Widget('entry_content', $widget_name, $widget_ops);
	} # entry_content()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_entry;
		
		if ( !$the_entry )
			return;
		
		global $sem_options;
		global $sem_captions;
		global $post;
		extract($args, EXTR_SKIP);
		
		$title = the_title('', '', false);
		
		if ( $sem_options['show_excerpts'] && !is_singular() ) {
			$content = apply_filters('the_excerpt', get_the_excerpt());
		} else {
			$more_link = str_replace('%title%', $title, $sem_captions['more_link']);

			$content = get_the_content($more_link, 0, '');
			
			if ( is_attachment() ) {
				# strip wpautop junk
				$content = preg_replace("/<br\s*\/>\s+$/", '', $content);

				# add gallery links
				$attachments = array_values(
					get_children(array(
						'post_parent' => $post->post_parent,
						'post_type' => 'attachment',
						'post_mime_type' => 'image',
						'order_by' => 'menu_order ASC, ID ASC',
						))
					);

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
			$content = str_replace(']]>', ']]&gt;', $content);
			
			$content .= wp_link_pages(
				array(
					'before' => '<div class="entry_nav"> ' . $sem_captions['paginate'] . ': ',
					'after' => '</div>' . "\n",
					'echo' => 0,
					)
				);
		}
		
		$actions = '';
		
		if ( !isset($_GET['action']) || $_GET['action'] != 'print' ) {
			global $post;

			if ( $post->post_type == 'page' && current_user_can('edit_page', $post->ID)
				|| $post->post_type == 'post' && current_user_can('edit_post', $post->ID)
			) {
				$edit_link = '<a class="post-edit-link"'
					. ' href="' . clean_url(get_edit_post_link($post->ID)) . '"'
					. ' title="' . esc_attr(__('Edit')) . '">'
					. __('Edit')
					. '</a>';
				$edit_link = apply_filters('edit_post_link', $edit_link, $post->ID);
				
				$actions .= '<span class="edit_entry">'
					. $edit_link
					. '</span>' . "\n";
			}
			
			$num_comments = (int) get_comments_number();
			
			if ( $num_comments || comments_open() ) {
				$comments_link = apply_filters('the_permalink', get_permalink());
				$comments_link .= $num_comments ? '#comments' : '#respond';
				
				$caption = _n('1 Comment', '% Comments', $num_comments);
				$caption = preg_replace("/\s*(?:1|\%)\s*/", '', $caption);
				
				$actions .= '<span class="comment_box">'
					. '<a href="' . clean_url($comments_link) . '">'
					. '<span class="num_comments">'
					. $num_comments
					. '</span>'
					. '<br />'
					. $caption
					. '</a>'
					. '</span>' . "\n";
			}
			
			if ( $actions ) {
				$actions = '<div class="entry_actions">' . "\n"
					. $actions
					. '</div>' . "\n";
			}
		}
		
		if ( $actions || $content ) {
			echo $before_widget
				. $actions
				. $content
				. '<div class="spacer"></div>' . "\n"
				. $after_widget;
		}
	} # widget()
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
			'description' => __('The entry\'s categories. Will only display on individual posts if placed outside of the loop.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('entry_categories', $widget_name, $widget_ops);
	} # entry_categories()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_entry;
		
		if ( is_admin() || is_singular() && !is_single() ) {
			return;
		} elseif ( !$the_entry ) {
			if ( !is_single() )
				return;
			
			global $post;
			$post = $wp_the_query->get_queried_object();
			setup_postdata($post);
		}
		
		global $sem_captions;
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$categories = get_the_category_list(', ');
		
		$author = get_the_author();
		$author_url = apply_filters('the_author_url', get_the_author_meta('url'));
		
		if ( $author_url && $author_url != 'http://' ) {
			$author = '<span class="entry_author">'
				. '<a href="' . clean_url($author_url) . '" rel="external">'
				. $author
				. '</a>'
				. '</span>';
		} else {
			$author = '<span class="entry_author">'
				. '<span>' . $author . '</span>'
				. '</span>';
		}
		
		echo $before_widget
			. ( !$the_entry && $title
				? $before_title . $title . $after_title
				: ''
				)
			. '<p>'
			. str_replace(
				array('%categories%', '%author%'),
				array($categories, $author),
				$sem_captions['filed_under']
				)
			. '</p>' . "\n"
			. $after_widget;
	} # widget()
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
			'description' => __('The entry\'s tags. Will only display on individual entries if placed outside of the loop.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('entry_tags', $widget_name, $widget_ops);
	} # entry_tags()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_entry;
		
		if ( is_admin() ) {
			return;
		} elseif ( !in_the_loop() ) {
			if ( !$the_entry )
				return;
			
			global $post;
			$post = $wp_the_query->get_queried_object();
			setup_postdata($post);
		}
		
		if ( !class_exists('widget_contexts') && is_letter() )
			return;
		
		global $sem_captions;
		extract($args, EXTR_SKIP);
		extract($instance, EXTR_SKIP);
		
		$term_links = array();
		$terms = get_the_terms(0, 'post_tag');
		
		if ( $terms && !is_wp_error($terms) ) {
			foreach ( $terms as $term ) {
				if ( $term->count == 0 )
					continue;
				$tag_link = get_term_link( $term, 'post_tag' );
				if ( is_wp_error( $tag_link ) )
					continue;
				$term_links[] = '<a href="' . clean_url($tag_link) . '" rel="tag">' . $term->name . '</a>';
			}

			$term_links = apply_filters( "term_links-post_tag", $term_links );
		}
		
		$tags = apply_filters('the_tags', join(', ', $term_links));
		
		if ( $tags ) {
			echo $before_widget
				. ( !$the_entry && $title
					? $before_title . $title . $after_title
					: ''
					)
				. '<p>'
				. str_replace(
					'%tags%',
					$tags,
					$sem_captions['tags']
					)
				. '</p>' . "\n"
				. $after_widget;
		}
	} # widget()
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
		
		$this->WP_Widget('entry_comments', $widget_name, $widget_ops);
	} # entry_comments()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_entry;
		
		if ( !$the_entry || !is_singular() || !get_comments_number() && !comments_open() )
			return;
		
		if ( !class_exists('widget_contexts') && is_letter() )
			return;
		
		echo '<div class="spacer"></div>' . "\n"
			. '<div class="entry_comments">' . "\n";
		
		comments_template('/comments.php');
		
		echo '</div>' . "\n";
	} # widget()
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
		
		$this->WP_Widget('blog_header', $widget_name, $widget_ops);
	} # blog_header()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $before_the_entries;
		
		if ( !$before_the_entries || !is_archive() && !is_search() && !is_404() )
			return;
		
		global $sem_captions;
		
		$desc = '';
		
		extract($args, EXTR_SKIP);
		
		echo $before_widget;
		
		echo '<h1>';

		if ( is_category() ) {
			single_cat_title();
			$desc = wpautop(category_description());
		} elseif ( is_tag() ) {
			single_tag_title();
			$desc = wpautop(tag_description());
		} elseif ( is_month() ) {
			single_month_title(' ');
		} elseif ( is_author() ) {
			global $wp_the_query;
			$user = new WP_User($wp_the_query->get_queried_object_id());
			echo $user->display_name;
			$desc = wpautop($user->description);
		} elseif ( is_search() ) {
			echo str_replace('%query%', get_search_query(), $sem_captions['search_title']);
		} elseif ( is_404() ) {
			echo $sem_captions['404_title'];
			$desc = $sem_captions['404_desc'];
		} else {
			echo $sem_captions['archives_title'];
		}

		echo '</h1>' . "\n";
		
		echo $desc;
		
		echo $after_widget;
	} # widget()
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
		
		$this->WP_Widget('blog_footer', $widget_name, $widget_ops);
	} # blog_footer()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $after_the_entries;
		global $wp_the_query;
		
		if ( !$after_the_entries || is_singular() || $wp_the_query->max_num_pages <= 1 )
			return;
		
		global $sem_captions;
		extract($args, EXTR_SKIP);
		
		echo $before_widget;
		
		posts_nav_link(
			' &bull; ',
			'&larr;&nbsp;' . $sem_captions['prev_page'],
			$sem_captions['next_page'] . '&nbsp;&rarr;'
			);
		
		echo $after_widget;
	} # widget()
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
		global $the_header;
		
		if ( !$the_header )
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
		global $the_footer;
		
		if ( !$the_footer )
			return;
		
		sem_panels::display('the_footer_boxes');
	} # widget()
} # footer_boxes


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
		echo '<span>menu</span>';
	} # widget()
} # sem_nav_menu


/**
 * header_nav
 *
 * @package Semiologic Reloaded
 **/

class header_nav extends sem_nav_menu {
	/**
	 * header_nav()
	 *
	 * @return void
	 **/

	function header_nav() {
		$widget_name = __('Header: Nav Menu', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'header_nav',
			'description' => __('The header\'s navigation menu, with an optional search form. Only works in the header area.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('header_nav', $widget_name, $widget_ops);
	} # header_nav()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_header;
		
		if ( !$the_header )
			return;
		
		global $sem_options;
		global $sem_captions;
		
		echo '<div id="navbar" class="wrapper'
			. ( $sem_options['show_search_form']
				? ' float_nav'
				: ''
				)
				. '"'
			. '>' . "\n";
		
		echo '<div id="navbar_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="navbar_bg">' . "\n";
		
		echo '<div class="wrapper_item">' . "\n";
		
		echo '<div class="pad">' . "\n";
		
		echo '<div id="header_nav" class="header_nav inline_menu">';

		parent::widget($args, $instance);

		echo '</div><!-- header_nav -->' . "\n";

		if ( $sem_options['show_search_form'] ) {
			echo '<div id="search_form" class="search_form">';

			if ( is_search() )
				$search = get_search_query();
			else
				$search = $sem_captions['search_field'];
			
			if ( $search == $sem_captions['search_field'] ) {
				$onfocusblur = ' onfocus="if ( this.value == \'' . addslashes(esc_attr($search)) . '\' )'
							. ' this.value = \'\';"'
					. ' onblur="if ( this.value == \'\' )'
					 	. ' this.value = \'' . addslashes(esc_attr($search)) . '\';"';
			} else {
				$onfocusblur = '';
			}
			
			$go = $sem_captions['search_button'];
			
			if ( $go !== '' )
				$go = '<input type="submit" id="go" class="go button" value="' . esc_attr($go) . '" />';
			
			echo '<form method="get"'
					. ' action="' . clean_url(user_trailingslashit(get_option('home'))) . '"'
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
	} # widget()
} # header_nav


/**
 * footer_nav
 *
 * @package Semiologic Reloaded
 **/

class footer_nav extends sem_nav_menu {
	/**
	 * footer_nav()
	 *
	 * @return void
	 **/

	function footer_nav() {
		$widget_name = __('Footer: Nav Menu', 'sem-reloaded');
		$widget_ops = array(
			'classname' => 'footer_nav',
			'description' => __('The footer\'s navigation menu, with an optional copyright notice. Only works in the footer area.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('footer_nav', $widget_name, $widget_ops);
	} # footer_nav()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_footer;
		
		if ( !$the_footer )
			return;
		
		global $sem_options;
		global $sem_captions;
		
		echo '<div id="footer" class="wrapper'
				. ( $sem_options['float_footer'] && $sem_captions['copyright']
					? ' float_nav'
					: ''
					)
				. '"'
			. '>' . "\n";
		
		echo '<div id="footer_top"><div class="hidden"></div></div>' . "\n";
		
		echo '<div id="footer_bg">' . "\n"
			. '<div class="wrapper_item">' . "\n"
			. '<div class="pad">' . "\n";
		
		echo '<div id="footer_nav" class="inline_menu">';
		
		sem_nav_menu::widget($args, $instance);
		
		echo '</div><!-- footer_nav -->' . "\n";
		
		if ( $copyright_notice = $sem_captions['copyright'] ) {
			global $wpdb;

			$year = date('Y');

			if ( strpos($copyright_notice, '%admin_name%') !== false ) {
				$admin_login = $wpdb->get_var("
					SELECT	user_login
					FROM	wp_users
					WHERE	user_email = '" . $wpdb->escape(get_option('admin_email')) . "'
					ORDER BY user_registered ASC
					LIMIT 1
					");
				$admin_user = get_userdatabylogin($admin_login);

				if ( $admin_user->display_name ) {
					$admin_name = $admin_user->display_name;
				} else {
					$admin_name = preg_replace("/@.*$/", '', $admin_user->user_email);

					$admin_name = preg_replace("/[_.-]/", ' ', $admin_name);

					$admin_name = ucwords($admin_name);
				}

				$copyright_notice = str_replace('%admin_name%', $admin_name, $copyright_notice);
			}

			$copyright_notice = str_replace('%year%', $year, $copyright_notice);

			echo '<div id="copyright_notice">';
			echo $copyright_notice;
			echo '</div><!-- #copyright_notice -->' . "\n";
		}

		echo '<div class="spacer"></div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n"
			. '</div>' . "\n";
		
		echo '<div id="footer_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- footer -->' . "\n";
	} # widget()
} # footer_nav


/**
 * site_header
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
			'description' => __('The site\'s header. Only works in the header area.', 'sem-reloaded'),
			);
		
		$this->WP_Widget('header', $widget_name, $widget_ops);
	} # header()
	
	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		global $the_header;
		
		if ( !$the_header )
			return;
		
		global $sem_options;
		
		$header = header::get();
		
		if ( $header ) {
			$ext = preg_match("/\.[^.]+$/", $header, $ext);
			$ext = end($ext);
			$flash = $ext == 'swf';
		} else {
			$flash = false;
		}
		
		echo '<div id="header" class="wrapper'
				. ( $sem_options['invert_header']
					? ' invert_header'
					: ''
					)
				. '"'
 			. ' title="'
				. esc_attr(get_option('blogname'))
				. ' &bull; '
				. esc_attr(get_option('blogdescription'))
				. '"';
		
		if ( !$flash && !( is_front_page() && !is_paged() ) ) {
			echo ' style="cursor: pointer;"'
				. ' onclick="top.location.href = \''
					. clean_url(user_trailingslashit(get_option('home')))
					. '\'"';
		}

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
					? ( '<a href="' . clean_url(user_trailingslashit(get_option('home'))) . '">' . get_option('blogname') . '</a>' )
					: get_option('blogname')
					)
				. '</div>' . "\n";
			
			if ( $sem_options['invert_header'] ) {
				echo $site_name;
				echo $tagline;
			} else {
				echo $tagline;
				echo $site_name;
			}
			
			echo '</div>' . "\n";
		} else {
			echo header::display();
		}
		
		echo '</div>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '<div id="header_bottom"><div class="hidden"></div></div>' . "\n";
		
		echo '</div><!-- header -->' . "\n";
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
		
		$ext = preg_match("/\.[^.]+$/", $header, $ext);
		$ext = end($ext);
		
		if ( !$ext != 'swf' ) {
			echo '<div id="header_img" class="pad">'
				. '<img src="' . sem_url . '/icons/pixel.gif" height="100%" width="100%" alt="'
					. esc_attr(get_option('blogname'))
					. ' &bull; '
					. esc_attr(get_option('blogdescription'))
					. '" />'
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
			$header = sem_header::get_header();

		if ( !$header )
			return;
		
		list($width, $height) = getimagesize(WP_CONTENT_DIR . '/' . $header);
		
		$header = clean_url(content_url() . '/' . $header);
		
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
		
		list($width, $height) = getimagesize(WP_CONTENT_DIR . '/' . $header);
		
		$header = clean_url(content_url() . '/' . $header);
		
		return __('<a href="http://www.macromedia.com/go/getflashplayer">Get Flash</a> to see this player.')
			. '</div>'
			. '<script type="text/javascript">' . "\n"
			. 'var so = new SWFObject("'. $header . '","header_img","' . $width . '","' . $height . '","7");' . "\n"
			. 'so.write("header_img");' . "\n";
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
		if ( !is_admin() ) {
			switch ( is_singular() ) {
			case true:
				$header = get_post_meta($post_ID, '_sem_header', true);
				if ( !$header ) {
					$header = false;
					break;
				} elseif ( $header != 'default' )
					break;
			default:
				$header = get_transient('sem_header');
			}
		} else {
			$header = false;
		}
		
		if ( $header !== false )
			return $header;
		
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
				update_post_meta($post_ID, '_sem_header', $header);
				return;
			}
		}
		
		switch ( true ) {
		default:
			# skin-specific header
			$active_skin = apply_filters('active_skin', $sem_options['active_skin']);
			$header = glob(sem_path . "/skins/$active_skin/$skin_scan", $scan_type);
			if ( $header )
				break;
			
			# uploaded header
			$header = glob(WP_CONTENT_DIR . "/header/$header_scan", $scan_type);
			if ( $header )
				break;
			
			# no header
			$header = false;
			break;
		}
		
		if ( is_singular() )
			update_post_meta($post_ID, '_sem_header', 'default');
		
		if ( $header ) {
			$header = current($header);
			$header = str_replace(WP_CONTENT_DIR, '', $header);
			set_transient('sem_header', $header);
		} else {
			set_transient('sem_header', '0');
		}
		
		return $header;
	} # get()
} # header
?>