<?php
/*
Plugin Name: Terms2tags
Plugin URI: http://www.semiologic.com/software/terms2tags/
Description: <a href="http://www.semiologic.com/legal/license/">Terms of use</a> &bull; <a href="http://www.semiologic.com/software/terms2tags/">Doc/FAQ</a> &bull; <a href="http://forum.semiologic.com">Support forum</a> &#8212; Requires the <a href="http://www.semiologic.com/software/extract-terms/">Extract terms plugin</a>. Returns Yahoo! terms as technorati tags. To use, call the_terms2tags(); where you want the terms to appear.
Version: 2.6
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

			foreach ( $terms as $term )
			{
				$o .= "<a href=\""
					. "http://www.technorati.com/tag/" . urlencode($term)
					. "\" rel=\"tag\">"
					. $term
					. "</a>"
					. ( ( ++$i < sizeof($terms) )
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


########################
#
# Backward compatibility
#

function sem_terms2tags($post = null)
{
	the_terms2tags($post);
} # end sem_terms2tags()
?>