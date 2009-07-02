<?php
class version_checker
{
	#
	# init()
	#

	function init()
	{
		add_action('load-plugins.php', array('version_checker', 'check_versions'), 20);
	} # init()


	#
	# check_versions()
	#

	function check_versions()
	{
		set_time_limit(0);

		$options = get_option('version_checker');

		if ( $options === false )
		{
			$options = array();
		}

		$plugins = (array) get_plugins();


		# trim deleted plugins

		$user_plugins = array_keys($plugins);

		foreach ( array_keys((array) $options['response']) as $file )
		{
			if ( !in_array($file, $user_plugins) )
			{
				unset($options['response'][$file]);
			}
		}


		# fetch plugin details

		$todo = array();

		foreach ( $plugins as $file => $plugin_data )
		{
			$src = file_get_contents(ABSPATH . PLUGINDIR . '/' . $file);

			if ( !preg_match("/Update Service:(.*)/i", $src, $service) )
			{
				continue;
			}

			$service = trim(end($service));

			if ( strpos($service, '://') === false )
			{
				$service = 'http://' . $service;
			}

			$options['response'][$file]->service = $service;
			$todo[$service][] = $file;

			if ( preg_match("/Update Tag:(.*)/i", $src, $tag) )
			{
				$tag = trim(end($tag));
			}
			else
			{
				$tag = basename($file, '.php');
			}

			$options['response'][$file]->tag = $tag;

			if ( preg_match("/Update URI:(.*)/i", $src, $url) )
			{
				$url = trim(end($url));
			}
			elseif ( preg_match("/Plugin URI:(.*)/i", $src, $url) )
			{
				$url = trim(end($url));
			}
			else
			{
				$url = '';
			}

			$options['response'][$file]->url = $url;

			if ( preg_match("/Version:(.*)/i", $src, $version) )
			{
				$version = trim(end($version));
			}
			else
			{
				$version = 0;
			}

			$options['response'][$file]->version = $version;
		}

		if ( !isset($options['last_checked'])
			|| $options['last_checked'] + 3600 * 24 < time()
			)
		{
			foreach ( $todo as $service => $files )
			{
				if ( count($files) == 1 )
				{
					$file = current($files);
					$tag = $options['response'][$file]->tag;

					$url = trailingslashit($service) . urlencode($tag);
				}
				else
				{
					$url = trailingslashit($service);

					foreach ( $files as $file )
					{
						$tags[] = urlencode($options['response'][$file]->tag);
					}

					$url .= '?tag[]=' . implode('&tag[]', $tags);
				}

				if ( function_exists('curl_init') )
				{
					$ch = curl_init();

					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress');
					curl_setopt($ch, CURLOPT_HEADER, 0);

					$new_version = @ curl_exec($ch);

					curl_close($ch);
				}
				else
				{
					require_once ABSPATH . WPINC . '/class-snoopy.php';

					$snoopy = new snoopy;
					$snoopy->agent = 'WordPress';

					@ $snoopy->fetch($url);

					$new_version = $snoopy->results;
				}

				if ( count($files) == 1 )
				{
					$options['response'][$file]->new_version = $new_version;
				}
				else
				{
					$lines = split("\n", $new_version);

					foreach ( $lines as $line )
					{
						if ( $line )
						{
							list($tag, $new_version) = split(':', $line);
							$tag = trim($tag);
							$new_version = trim($new_version);

							$new_versions[$tag] = $new_version;
						}
					}

					foreach ( $files as $file )
					{
						$options['response'][$file]->new_version = $new_versions[$options['response'][$file]->tag];
					}
				}
			}

			$options['last_checked'] = time();
		}

		update_option('version_checker', $options);


		# merge into update_plugins

		$update_plugins = get_option('update_plugins');

		foreach ( array_keys($options['response']) as $file )
		{
			if ( version_compare(
					$options['response'][$file]->new_version,
					$options['response'][$file]->version,
					'>'
					)
				)
			{
				$update_plugins->response[$file] = $options['response'][$file];
			}
			else
			{
				unset($update_plugins->response[$file]);
			}
		}

		update_option('update_plugins', $update_plugins);
	} # check_versions()
} # version_checker

version_checker::init();
?>