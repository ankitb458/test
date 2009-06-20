<?php
#
# MC URL Library
# --------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class url
{
	#
	# glue()
	# ------
	# Glue an arbitrary url from an array
	#

	public static function glue($url = null)
	{
		return
			# http://
			( isset($url['sheme'])
				? ( $url['sheme'] . '://' )
				: ( 'http://' )
				)
			# http://user:pass@
			. ( isset($url['user'])
				? ( $url['user']
					. ( isset($url['pass'])
						? ( ':' . $url['pass'] )
						: ''
						)
					. '@'
					)
				: ''
				)
			# http://user:pass@host
			. ( isset($url['host'])
				? $url['host']
				: 'localhost'
				)
			# http://user:pass@host:port
			. ( ( isset($url['port'])
				&& !( ( ( !isset($url['sheme']) || $url['sheme'] == 'http' ) && $url['port'] == 80 )
					|| ( $url['sheme'] == 'https' && $url['port'] == 443 )
					)
				)
				? ( ':' . $url['port'] )
				: ''
				)
			# http://user:pass@host:port/path
			. ( isset($url['path'])
				? $url['path']
				: '/'
				)
			# http://user:pass@host:port/path?query
			. ( ( isset($url['query']) && !empty($url['query']) )
				? ( '?' . ( is_array($url['query']) ? http_build_query($url['query']) : $url['query'] ) )
				: ''
				)
			# http://user:pass@host:port/path?query#fragment
			. ( isset($url['fragment'])
				? ( '#' . $url['fragment'] )
				: ''
				);
	} # build()
} # url
?>