<?php
/*
Plugin Name: Related Tags
Plugin URI: http://www.semiologic.com/software/geekery/terms2tags/
Description: Leverages Yahoo!'s term extraction web service to display links to related technorati tags. This has no SEO benefit, but it can be fun.
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


class sem_terms2tags
{
	#
	# constructor
	#

	function sem_terms2tags()
	{
	} # end sem_terms2tags()


	#
	# get_the_post_terms()
	#

	function get_the_post_terms($post = null)
	{
		$terms = get_the_post_terms($post);

		if ( $terms )
		{
			$o = "";
			$i = 0;

			foreach ( $terms as $term )
			{
				if ( ++$i > 5 )
				{
					break;
				}

				$o .= "<a href=\""
					. "http://www.technorati.com/tag/" . urlencode($term)
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
} # end sem_terms2tags

$sem_terms2tags =& new sem_terms2tags();


#
# Template tags
#

function the_terms2tags($post = null)
{
	global $sem_terms2tags;

	$sem_terms2tags->display($post);
} # end the_terms2tags()


#
# display_entry_related_tags()()
#

function display_entry_related_tags()
{
	if ( apply_filters('show_entry_related_tags', is_single())
		&& get_the_post_terms()
		)
	{
		echo '<div class="entry_related_tags">'
			. '<h2>'
			. __('Related Tags')
			. '</h2>'
			. '<p>';

		the_terms2tags();

		echo '</p>'
			. '</div>';
	}
} # end display_entry_related_tags()

add_filter('after_the_entry', 'display_entry_related_tags', 9);


########################
#
# Backward compatibility
#

function sem_terms2tags($post = null)
{
	the_terms2tags($post);
} # end sem_terms2tags()
?>