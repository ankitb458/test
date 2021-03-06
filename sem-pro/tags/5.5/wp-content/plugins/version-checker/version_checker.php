<?php
if ( !class_exists('sem_api_key') )
{
	include dirname(__FILE__) . '/sem-api-key.php';
}

class version_checker
{
	#
	# init()
	#

	function init()
	{
		remove_action('load-plugins.php', 'wp_update_plugins');
		
		add_action('load-plugins.php', array('version_checker', 'do_check_plugins'));
		
		add_filter('option_update_plugins', array('version_checker', 'update_plugins'));
		
		add_action('shutdown', array('version_checker', 'check_sem_pro'));
		add_action('admin_notices', array('version_checker', 'nag_user'));
		add_action('load-wizards_page_sem-wizards/upgrade/wizard', array('version_checker', 'dont_nag_user'));
		
		add_filter('sem_api_key_protected', array('version_checker', 'sem_api_key_protected'));
	} # init()
	
	
	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/wp-tweaks/wp-tweaks/version-checker.zip';
		
		return $array;
	} # sem_api_key_protected()
	

	#
	# get_response()
	#
	
	function get_response($files)
	{
		$todo = array();
		$response = array();

		foreach ( $files as $file => $src )
		{
			$src = file_get_contents($src);
			
			if ( !preg_match("/Update Service:(.*)/i", $src, $service) )
			{
				continue;
			}

			$service = trim(end($service));

			if ( strpos($service, '://') === false )
			{
				$service = 'http://' . $service;
			}

			$response[$file]->service = $service;
			$todo[$service][] = $file;

			if ( preg_match("/Update Tag:(.*)/i", $src, $tag) )
			{
				$tag = trim(end($tag));
			}
			else
			{
				$tag = basename($file, '.php');
			}

			$response[$file]->tag = $tag;

			if ( preg_match("/Update URI:(.*)/i", $src, $url) )
			{
				$url = trim(end($url));
			}
			elseif ( preg_match("/Plugin URI:(.*)/i", $src, $url) )
			{
				$url = trim(end($url));
			}
			elseif ( preg_match("/Theme URI:(.*)/i", $src, $url) )
			{
				$url = trim(end($url));
			}
			else
			{
				$url = '';
			}

			$response[$file]->url = $url;

			if ( preg_match("/Version:(.*)/i", $src, $version) )
			{
				$version = trim(end($version));
			}
			else
			{
				$version = 0;
			}

			$response[$file]->version = $version;

			if ( preg_match("/Update Package:(.*)/i", $src, $package) )
			{
				$package = trim(end($package));
			}
			else
			{
				$package = '';
			}

			$response[$file]->package = $package;
		}
		
		foreach ( $todo as $service => $files )
		{
			if ( count($files) == 1 )
			{
				$file = current($files);
				$tag = $response[$file]->tag;

				$url = trailingslashit($service) . urlencode($tag);
			}
			else
			{
				$url = trailingslashit($service);

				foreach ( $files as $file )
				{
					$tags[] = urlencode($response[$file]->tag);
				}

				$url .= '?tag[]=' . implode('&tag[]=', $tags);
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

				static $snoopy;
				
				if ( !isset($snoopy) )
				{
					$snoopy =& new snoopy;
					$snoopy->agent = 'WordPress';
				}

				@ $snoopy->fetch($url);

				$new_version = $snoopy->results;
			}

			if ( $new_version === false ) continue;
			
			if ( count($files) == 1 )
			{
				$response[$file]->new_version = trim(strip_tags($new_version));
			}
			else
			{
				$lines = split("\n", $new_version);

				foreach ( $lines as $line )
				{
					if ( $line )
					{
						list($tag, $new_version) = split(':', $line);
						$tag = trim(strip_tags($tag));
						$new_version = trim(strip_tags($new_version));

						$new_versions[$tag] = $new_version;
					}
				}

				foreach ( $files as $file )
				{
					$response[$file]->new_version = $new_versions[$response[$file]->tag];
				}
			}
		}

		foreach ( array_keys((array) $response) as $file )
		{
			if ( !version_compare(
					$response[$file]->new_version,
					$response[$file]->version,
					'>'
					)
				)
			{
				unset($response[$file]);
			}

		}
		
		$response = apply_filters('version_checker', $response);
		
		return $response;
	} # get_response()
	
	
	#
	# check_sem_pro()
	#
	
	function check_sem_pro($force = false)
	{
		if ( !defined('sem_version')
			|| !defined('sem_wizards_path')
			) return false;
		
		$options = get_option('sem_versions');
		
		if ( $force
			|| !isset($options['last_checked'])
			|| $options['last_checked'] + 3600 * 24 * 2 < time()
			)
		{
			$url = 'http://version.mesoconcepts.com/sem_pro/';

			if ( function_exists('curl_init') )
			{
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress');
				curl_setopt($ch, CURLOPT_HEADER, 0);

				$lines = @ curl_exec($ch);

				curl_close($ch);
			}
			else
			{
				require_once ABSPATH . WPINC . '/class-snoopy.php';

				static $snoopy;
				
				if ( !isset($snoopy) )
				{
					$snoopy =& new snoopy;
					$snoopy->agent = 'WordPress';
				}

				@ $snoopy->fetch($url);

				$lines = $snoopy->results;
			}
			
			if ( $lines === false)
			{
				$versions = array(
						'stable' => sem_version,
						'bleeding' => sem_version,
					);
			}
			else
			{
				$lines = split("\n", $lines);

				$versions = array();

				foreach ( $lines as $line )
				{
					if ( $line )
					{
						list($tag, $version) = split(':', $line);

						$tag = trim(strip_tags($tag));
						$version = trim(strip_tags($version));

						$versions[$tag] = $version;
					}
				}
			}
			
			$options = array(
				'last_checked' => time(),
				'versions' => $versions,
				);
			
			update_option('sem_versions', $options);
		}
	} # check_sem_pro()
	
	
	#
	# dont_nag_user()
	#
	
	function dont_nag_user()
	{
		remove_action('admin_notices', array('version_checker', 'nag_user'));
	} # dont_nag_user()
	
	
	#
	# nag_user()
	#
	
	function nag_user()
	{
		if ( !defined('sem_version')
			|| !defined('sem_wizards_path')
			|| !current_user_can('administrator')
			) return;
		
		$options = get_option('sem_versions');
		
		if ( !$options ) return;
		
		$versions = $options['versions'];
		
		if ( version_compare(sem_version, $versions['stable'], '<')
			|| version_compare(sem_version, $versions['stable'], '>')
				&& version_compare(sem_version, $versions['bleeding'], '<')
			)
		{
			echo '<div class="updated">'
				. '<p>'
				. sprintf('<strong>Version Checker Notice</strong> - A Semiologic Pro update is available. Browse <a href="%s">Wizards / Upgrade</a> to upgrade your site. <a href="http://www.semiologic.com/resources/wp-basics/why-upgrade/">Why this is important</a>.', trailingslashit(get_option('siteurl')) . 'wp-admin/admin.php?page=sem-wizards/upgrade/wizard.php')
				. '</p>'
				. '</div>';
		}
	} # nag_user()


	#
	# check_plugins()
	#

	function check_plugins()
	{
		$options = get_option('version_checker');

		if ( $options === false )
		{
			$options = array();
		}
		
		if ( !isset($options['plugins']['last_checked'])
			|| $options['plugins']['last_checked'] + 3600 * 24 * 2 < time()
			)
		{
			$files = array();
			
			foreach ( array_keys((array) get_plugins()) as $file )
			{
				$files[$file] = ABSPATH . PLUGINDIR . '/' . $file;
			}
			
			$response = version_checker::get_response($files);
			
			foreach ( array_keys((array) $response) as $file )
			{
				if ( !version_compare(
						$response[$file]->new_version,
						$response[$file]->version,
						'>'
						)
					)
				{
					unset($response[$file]);
				}
			}

			$options['plugins']['response'] = $response;
			$options['plugins']['last_checked'] = time();
			
			update_option('version_checker', $options);
		}
	} # check_plugins()
	
	
	#
	# update_plugins()
	#
	
	function update_plugins($update_plugins)
	{
		if ( !is_object($update_plugins) ) return $update_plugins;
		
		if ( ( $options = get_option('version_checker') ) === false )
		{
			version_checker::check_plugins();
			$options = get_option('version_checker');
		}
		
		if ( $update_plugins->response )
		{
			foreach ( (array) $update_plugins->checked as $plugin => $version )
			{
				if ( strpos($version, 'fork') !== false )
				{
					unset($update_plugins->response[$plugin]);
				}
			}
		}
		
		foreach ( array_keys((array) $options['plugins']['response']) as $file )
		{
			$update_plugins->response[$file] = $options['plugins']['response'][$file];
		}
		
		return $update_plugins;
	} # update_plugins()
	
	
	#
	# do_check_plugins()
	#
	
	function do_check_plugins()
	{
		add_action('shutdown', 'wp_update_plugins');
		add_action('shutdown', array('version_checker', 'check_plugins'));
	} # do_check_plugins()
} # version_checker

version_checker::init();
?>