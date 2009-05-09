<?php
class sem_header
{
	#
	# upgrade()
	#

	function upgrade()
	{
		global $sem_options;

		if ( !defined('GLOB_BRACE') )
		{
			$sem_options['header_mode'] = 'header';
			update_option('sem6_options', $sem_options);
			return;
		}

		$skin = apply_filters('active_skin', $sem_options['active_skin']);

		if ( $header = glob(sem_path . '/skins/' . $skin . '/header{,-background,-bg}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = glob(sem_path . '/header{,-background,-bg}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = glob(sem_path . '/headers/header{,-background,-bg}.{jpg,jpeg,png,gif,swf}', GLOB_BRACE) )
		{
			$header = end($header);
		}
		elseif ( $header = $sem_options['active_header'] )
		{
			$header = sem_path . '/headers/' . $sem_options['active_header'];
		}

		if ( $header )
		{
			$name = basename($header);
			
			preg_match("/\.([^.]+)$/", $name, $ext);
			$ext = end($ext);

			$name = str_replace('.' . $ext, '', $name);

			@mkdir(WP_CONTENT_DIR . '/header');
			@chmod(WP_CONTENT_DIR . '/header', 0777);

			@rename($header, WP_CONTENT_DIR . '/headers/header.' . $ext);
			@chmod(WP_CONTENT_DIR . '/headers/header.' . $ext, 0666);

			switch ( $name )
			{
			case 'header-background':
				$sem_options['header_mode'] = 'header';
				break;

			case 'header-bg':
				$sem_options['header_mode'] = 'background';
				break;

			case 'header':
				switch ( $ext )
				{
				case 'swf':
					$sem_options['header_mode'] = 'background';
					break;

				default:
					$sem_options['header_mode'] = 'logo';
					break;
				}
				break;

			default:
				$sem_options['header_mode'] = 'background';
				break;
			}
		}
		else
		{
			$sem_options['header_mode'] = 'header';
		}

		update_option('sem6_options', $sem_options);
	} # upgrade()
} # sem_header
?>