<?php
/*
Plugin Name: Related Searches
Plugin URI: http://www.semiologic.com/software/widgets/terms2search/
Description: Leverages Yahoo!'s term extraction web service to display related search queries. Please note that small sites using this plugin will quickly get unindexed for having duplicate content.
Version: 2.14
Author: Denis de Bernardy
Author URI: http://www.semiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts Ltd, and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.semiologic.com/legal/license/
**/


require_once dirname(__FILE__) . '/sem-extract-terms.php';


class sem_terms2search
{
	#
	# constructor
	#

	function sem_terms2search()
	{
	} # end sem_terms2search()


	#
	# get_the_post_terms()
	#

	function get_the_post_terms($post = null)
	{
		global $wp_rewrite;

		$terms = get_the_post_terms($post);

		if ( $terms )
		{
			$search_permastruct = $wp_rewrite->get_search_permastruct();

			$o = "";
			$i = 0;

			foreach ( $terms as $term )
			{
				if ( ++$i > 5 )
				{
					break;
				}

				$o .= "<a href=\""
					. trailingslashit(get_settings('home'))
					. ( $search_permastruct
						? preg_replace("/%search%/", urlencode($term), $search_permastruct)
						: ( "?s=" . urlencode($term) )
						)
					. "\" rel=\"tag\">"
					. $term
					. "</a>"
					. ( ( $i < sizeof($terms) && $i != 5 )
						? ", "
						: ""
						);
			}

			return $o;
		}
	} # end get_the_post_terms()


	#
	# display()
	#

	function display($post = null)
	{
		echo $this->get_the_post_terms($post);
	} # end display()
} # end sem_terms2search

$sem_terms2search =& new sem_terms2search();


#
# Template tags
#

function the_terms2search($post = null)
{
	global $sem_terms2search;

	$sem_terms2search->display($post);
} # end the_terms2search()


#
# display_entry_related_searches()()
#

function display_entry_related_searches()
{
	if ( apply_filters('show_entry_related_searches', is_single())
		&& get_the_post_terms()
		)
	{
		echo '<div class="entry_related_searches">'
			. '<h2>'
			. __('Related Searches')
			. '</h2>'
			. '<p>';

		the_terms2search();

		echo '</p>'
			. '</div>';
	}
} # end display_entry_related_searches()

add_filter('after_the_entry', 'display_entry_related_searches', 9);


########################
#
# Backward compatibility
#

function sem_terms2search($post = null)
{
	the_terms2search($post);
} # end sem_terms2search()
?>