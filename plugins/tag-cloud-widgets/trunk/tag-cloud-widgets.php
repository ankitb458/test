<?php
/*
Plugin Name: Tag Cloud Widgets
Plugin URI: http://www.semiologic.com/software/widgets/tag-cloud/
Description: Replaces WordPress' default tag widgets with advanced configurable tag widgets
Version: 1.0.4 RC
Author: Mike Koepke
Author URI: http://www.getsemiologic.com
Update Package: https://members.semiologic.com/media/plugins/tag-cloud-widgets/tag-cloud-widgets.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.
http://www.opensource.org/licenses/gpl-2.0.php
	
**/

/*

Adapted from the Configurable Tag Cloud 4.5 plugin ( http://reciprocity.be/ctc/)
by: Keith Solomon (http://reciprocity.be/)

Portions:
	Copyright (c) 2008 Keith Solomon (http://reciprocity.be)
	Configurable Tag Cloud is released under the GNU General Public License (GPL)
	http://www.gnu.org/licenses/gpl.txt
*/


load_plugin_textdomain('tag-cloud-widgets','wp-content/plugins/tag-cloud-widgets');

class tag_cloud_widgets
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('tag_cloud_widgets', 'widgetize'));
		
		foreach ( array(
			'save_post',
			'delete_post',
			'switch_theme',
			'update_option_active_plugins',
			'update_option_show_on_front',
			'update_option_page_on_front',
			'update_option_page_for_posts',
			'generate_rewrite_rules',
			) as $hook)
		{
			add_action($hook, array('tag_cloud_widgets', 'clear_cache'));
		}	
		
		register_activation_hook(__FILE__, array('tag_cloud_widgets', 'clear_cache'));
		register_deactivation_hook(__FILE__, array('tag_cloud_widgets', 'clear_cache'));		
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		# kill/change broken widgets
		global $wp_registered_widgets;
		global $wp_registered_widget_controls;
		
		foreach ( array('tag_cloud') as $widget_id )
		{
			unset($wp_registered_widgets[$widget_id]);
			unset($wp_registered_widget_controls[$widget_id]);
		}

		if ( !( $options = get_option('tag_cloud_widgets') ) )
		{
			$options = array();
			
			foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k )
			{
				if ( !is_array($sidebars[$k]) )
				{
					continue;
				}

				if ( ( $key = array_search('tag_cloud', $sidebars[$k]) ) !== false )
				{
					$options = array( 1 => array() );
					$sidebars[$k][$key] = 'tag_cloud_widget-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
				elseif ( ( $key = array_search('Tag Cloud', $sidebars[$k]) ) !== false )
				{
					$options = array( 1 => array() );
					$sidebars[$k][$key] = 'tag_cloud_widget-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
			}
			
			update_option('tag_cloud_widgets', $options);
		}	

		
		$widget_options = array('classname' => 'tag_cloud', 'description' => __( "Tag Cloud Widget") );
		$control_options = array('width' => 500, 'id_base' => 'tag_cloud_widget');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "tag_cloud_widget-$o";

			wp_register_sidebar_widget($id, __('Tag Cloud'), array('tag_cloud_widgets', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Tag Cloud'), array('tag_cloud_widgets_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "tag_cloud_widget-1";
			wp_register_sidebar_widget($id, __('Tag Cloud'), array('tag_cloud_widgets', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Tag Cloud'), array('tag_cloud_widgets_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	

	// The widget itself
	function display_widget($args, $widget_args = 1) 
	{
		extract( $args, EXTR_SKIP );	
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$number = intval($number);

		# front end: serve cache if available
		if ( !is_admin() )
		{
			$cache = get_option('tag_cloud_widgets_cache');

			if ( isset($cache[$number]) )
			{
				echo $cache[$number];
				return;
			}
		}
		
		# get options
		$options = get_option('tag_cloud_widgets');
		if ( !isset($options[$number]) )
			return;		
		$options = $options[$number];

		# admin area: serve a formatted title
		if ( is_admin() )
		{
			echo $args['before_widget']
				. $args['before_title'] . $options['title'] . $args['after_title']
				. $args['after_widget'];

			return;
		}
		
		$tagcloud = 'smallest=' . $options['smallest'];
		$tagcloud .= '&largest=' . $options['largest'];
		$tagcloud .= '&unit=' . $options['unit'];
		$tagcloud .= '&format=' . $options['format'];
		$tagcloud .= '&number=' . $options['number_tags'];
		$tagcloud .= '&orderby=' . $options['orderby'];
		$tagcloud .= '&order=' . $options['order'];
		$tagcloud .= '&showcount=' . $options['showcount'];
		$tagcloud .= '&showcats=' . $options['showcats'];
		$tagcloud .= '&showempty=' . $options['showempty'];
		//$tagcloud.= count($options['tags']) ? '&exclude='.implode(',', $options['tags']) : '';
		
		# initialize
		$o = '';

		$o .= $before_widget;
		$o .= $before_title . $options['title'] . $after_title;
		$o .= '<div class="tag_cloud_widget">';
		$o .= tag_cloud_widgets::ctc($tagcloud);
		$o .= '</div>';
		$o .= $after_widget;
		
		# cache
		$cache[$number] = $o;
		
		update_option('tag_cloud_widgets_cache', $cache);

		# display
		echo $o;		
	}
	
	// My tag cloud function 
	function ctc( $args ) 
	{
		global $wpdb;

		$defaults = tag_cloud_widgets::default_options();

		$args = wp_parse_args( $args, $defaults );

		extract($args);
		
//		$tags = get_tags( array_merge($args, array('orderby' => 'count', 'order' => 'DESC')) ); // Always query top tags	
	
		$query = "SELECT $wpdb->terms.term_id, $wpdb->terms.name, $wpdb->term_taxonomy.count 
		FROM (($wpdb->term_relationships INNER JOIN $wpdb->posts ON $wpdb->term_relationships.object_id = $wpdb->posts.ID) 
		INNER JOIN $wpdb->term_taxonomy ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) 
		INNER JOIN $wpdb->terms ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id 
		WHERE ((($wpdb->term_taxonomy.taxonomy)='post_tag') 
		AND (($wpdb->posts.post_status)='publish')) 
		AND $wpdb->posts.post_type = 'post'
		GROUP BY $wpdb->terms.name ORDER BY count DESC, $wpdb->terms.name ";
		$query .= ($number) ? "LIMIT 0, $number" : "";
		$tags = $wpdb->get_results($query);
		
		// Now there's categories in the cloud too...
		if ( $showcats ) 
		{
			$hide_empty = '&hide_empty=' . ($showempty) ? '1' : '0';
			
			$cats = get_categories("show_count=1&use_desc_for_title=0&hierarchical=0$hide_empty");

			$tagscats = array_merge($tags, $cats);
		} 
		else 	
		{
			$tagscats = array_merge($tags);
		}

		if ( empty($tagscats) )
			return;

		$result = tag_cloud_widgets::generate_tag_cloud( $tagscats, $args ); // Here's where those top tags get sorted according to $args
		if ( is_wp_error( $return ) )
			return false;
		else 		
			return $result;
 	}

	// generate_tag_cloud() - function to create the links for the cloud based on the args from the ctc() function
	// $tagscats = prefetched tag array ( get_tags() & get_categories() )
	// $args['format'] = 'flat' => whitespace separated, 'list' => UL, 'array' => array()
	// $args['orderby'] = 'name', 'count', 'rand'
	function generate_tag_cloud( $tagscats, $args) 
	{
		global $wp_rewrite;

		extract($args);

		if ( !$tagscats )
			return;
		
		extract($args);
		
		$counts = $tag_links = array();
		
		if ( 'rand' == $orderby )
			shuffle($tagscats);
			
		foreach ( (array) $tagscats as $tag ) 
		{
			$counts[$tag->name] = $tag->count;
			$cat = $tag->taxonomy;
			if ('category' == $cat) 
			{
				$tag_links[$tag->name] = get_category_link( $tag->term_id );
			} 
			else 
			{
				$tag_links[$tag->name] = get_tag_link( $tag->term_id );
			}
			if ( is_wp_error( $tag_links[$tag->name] ) )
				return $tag_links[$tag->name];
				
			$tag_ids[$tag->name] = $tag->term_id;
		}

		$min_count = min($counts);
		$spread = max($counts) - $min_count;
		if ( $spread <= 0 )
			$spread = 1;
		$font_spread = $largest - $smallest;
		if ( $font_spread <= 0 )
			$font_spread = 1;
		$font_step = $font_spread / $spread;

		// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
		if ( 'name' == $orderby )
			uksort($counts, 'strnatcasecmp');
		elseif ( 'count' == $orderby )
			asort($counts);

		if ( 'DESC' == $order )
			$counts = array_reverse( $counts, true );

		$a = array();

		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

		foreach ( $counts as $tag => $count ) {
			if ( $largest == $smallest )
				$tag_weight = $largest;
			else
				$tag_weight = ($smallest+(($count-$min_count)*$font_step));
			$diff = $largest-$smallest;
			if ( $diff <= 0 )
				$diff = 1;
			if ( $showcount )
				$postcount = '('.$count.')';
			$tag_id = $tag_ids[$tag];
			$tag_link = clean_url($tag_links[$tag]);
			$tag = str_replace(' ', '&nbsp;', wp_specialchars( $tag ));
			$a[] = "<a href=\"$tag_link\" class=\"tag-link-$tag_id\" title=\"" 
				. attribute_escape( sprintf( __('%d topics'), $count ) ) 
				. "\"$rel style=\"font-size: " . $tag_weight
				. "$unit;"
				. "\">$tag".($showcount ? " $postcount" : "")
				."</a>";
		}

		switch ( $format ) :
		case 'array' :
			$return =& $a;
			break;
		case 'list' :
			$return = "<ul class='wp-tag-cloud'>\n\t<li>";
			$return .= join("</li>\n\t<li>", $a);
			$return .= "</li>\n</ul>\n";
			break;
		default :
			$return = join("\n", $a);
			break;
		endswitch;

		return apply_filters( 'generate_tag_cloud', $return, $tagscats, $args );
	}

	#
	# clear_cache()
	#

	function clear_cache($in = null)
	{
		update_option('tag_cloud_widgets_cache', array());

		return $in;
	} # clear_cache()
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( !( $o = get_option('tag_cloud_widgets') ) )
		{
			$o = array();
			update_option('tag_cloud_widgets', $o);
		}
		
		return $o;
	} # get_options()	
	
	#
	# default_options()
	#
	
	function default_options()
	{
		return array(
			'title' => __('Tags'), 
			'number_tags' => '', 
			'unit' => 'pt', 
			'smallest' => 8, 
			'largest' => 22, 
			'format' => 'flat', 
			'orderby' => 'name', 
			'order' => 'ASC', 
			'showcount' => false, 
			'showcats' => false, 
			'showempty' => false
			);
	} # default_options()
}

tag_cloud_widgets::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/tag-cloud-widgets-admin.php';
}
?>