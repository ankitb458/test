<?php
#
# MC Argument Manipulation Library
# --------------------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class args
{
	#
	# merge()
	#

	public static function merge(&$args = null, $defaults = null)
	{
		args::parse($args);

		if ( is_array($defaults) )
		{
			$args = array_merge($defaults, $args);
		}
	} # merge()


	#
	# parse()
	#

	public static function parse(&$args = null)
	{
		if ( !is_array($args) )
		{
			if ( is_object($args) )
			{
				$args = (array) $args;
			}
			else
			{
				parse_str($args, $res);

				if ( get_magic_quotes_gpc() )
				{
					$res = gpc::strip($res);
				}

				$args = $res;
			}
		}
	} # parse()
} # args
?>