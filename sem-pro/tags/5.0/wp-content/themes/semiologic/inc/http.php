<?php
class sem_http
{
	#
	# get()
	#

	function get($url)
	{
		global $sem_curl_cookies;

		if ( function_exists('curl_init') && !ini_get('open_basedir') )
		{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress');
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array('sem_http', 'curl_cookies'));

			$out = @ curl_exec($ch);

			curl_close($ch);
		}
		elseif ( ini_get('allow_url_fopen') )
		{
			if ( !class_exists('snoopy') )
			{
				require_once ABSPATH . WPINC . '/class-snoopy.php';
			}

			$snoopy = new snoopy;
			$snoopy->agent = 'WordPress';

			@ $snoopy->fetch($url);

			$out = $snoopy->results;
		}
		else
		{
			echo '<div class="error">'
				. sprintf(__('Cannot access %s'), preg_replace("/\?.*/", '', $url)) . '<br />'
				. __('allow_url_fopen is turned off, and an open_basedir limitation prevent CURLOPT_FOLLOWLOCATION to work. Please contact your host to resolve this issue by having them toggle either setting (or consider <a href="http://www.semiologic.com/resources/wp-basics/wordpress-hosts/">changing hosts</a>.)')
				. '</div>';

			return false;
		}

		return $out;
	} # get()


	#
	# curl_cookies()
	#
	# via http://www.php.net/manual/en/function.curl-setopt.php#70043
	#

	function curl_cookies($ch, $string)
	{
		global $sem_curl_cookies;

		# ^overrides the function param $ch
		# this is okay because we need to
		# update the global $ch with
		# new cookies

		$length = strlen($string);

		if ( !strncmp($string, "Set-Cookie:", 11) )
		{
			# get the cookie
			$cookiestr = trim(substr($string, 11, -1));
			$cookie = explode(';', $cookiestr);
			$cookie = explode('=', $cookie[0]);
			$cookiename = trim(array_shift($cookie));
			$sem_curl_cookies[$cookiename] = trim(implode('=', $cookie));
		}

		if ( trim($string) == "" )
		{
			$cookie = "";

			foreach ( (array) $sem_curl_cookies as $key => $value )
			{
				$cookie .= "$key=$value; ";
			}

			$sem_curl_cookies = array();

			#dump(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), $cookie);

			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}

		return $length;
	} # curl_cookies()
} # sem_http
?>