<?php
/**
 * sem_custom
 *
 * @package Semiologic Reloaded
 **/

class sem_custom {
	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		wp_enqueue_style('farbtastic');
	} # styles()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		wp_enqueue_script('farbtastic');
		wp_enqueue_script('jquery-cookie', sem_url . '/js/jquery.cookie.js', array('jquery'), '1.0');
		wp_enqueue_script('jquery-ui-tabs');
	} # scripts()
	
	
	/**
	 * admin_head()
	 *
	 * @return void
	 **/

	function admin_head() {
		echo <<<EOS

<style type="text/css">
#custom-tabs-nav {
	margin: 0; padding: 0; border: 0; outline: 0; list-style: none;
	float: left;
	position: relative;
	z-index: 1;
	bottom: -1px;
	list-style: none;
	margin-bottom: 20px;
}

#custom-tabs-nav li {
	margin: 0; padding: 0; outline: 0; list-style: none;
	margin-right: .3em;
	float: left;
}

#custom-tabs-nav a {
	line-height: 1.85em;
	font-weight: normal;
	text-decoration: none;
	padding: .5em 1.9em;
	margin: 0px;
}

#custom-tabs-nav input {
	margin-top: 0px;
	margin-bottom: 0px;
}

#custom-tabs-nav li.ui-tabs-selected a, #custom-tabs-nav li.ui-tabs-selected a:hover {
	text-decoration: underline;
}

td.color_picker {
	width: 300px;
	vertical-align: top;
	text-align: center;
}

</style>

EOS;
	} # admin_head()
	
	
	/**
	 * admin_footer()
	 *
	 * @return void
	 **/

	function admin_footer() {
		echo <<<EOS

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#custom-tabs").tabs({ cookie: { expires: 3600 } });
	
	if ( !jQuery("#custom-tabs").size() )
		return;
	
	var cookie = jQuery("#custom-tabs").tabs('option', 'cookie');
	
	jQuery("#custom-tabs").tabs('option', 'cookie', { expires: 3600 });
	
	jQuery("td.color_picker div").each(function() {
		var p = jQuery(this);
		var i = p.parents("table:first").find("input.color_picker");
		var f = p.parents("table:first").find("input.color_picker:first");
		
		i.each(function() {
			var t = jQuery(this);
			var c = t.val();
			
			if ( !c.match(/^(#[0-9a-f]{6}|#[0-9a-f]{3})$/i) )
				return;
			
			jQuery.farbtastic(p).setColor(c);
			
			t.css('background-color', c);
			if ( jQuery.farbtastic(p).hsl[2] <= .6 ) {
				t.css('color', '#fff');
			} else {
				t.css('color', '#000');
			}
		});
		
		jQuery.farbtastic(p).setColor('#000');
		
		jQuery.farbtastic(p).linkTo(function(color) {
				f.val(color);
				f.css('background-color', color);
				if ( jQuery.farbtastic(p).hsl[2] <= .6 ) {
					f.css('color', '#fff');
				} else {
					f.css('color', '#000');
				}
		});
		
		f.css('border-color', '#666');
		if ( f.val() )
			jQuery.farbtastic(p).setColor(f.val());
			
		
		p.show();
	});
	
	jQuery("input.color_picker").focus(function() {
		var t = jQuery(this);
		var p = t.parents("table:first").find('td.color_picker div');
		var i = p.parents("table:first").find("input.color_picker");
		var c = t.val();
		
		jQuery.farbtastic(p).linkTo(function(color) {});
		
		i.not(t).css('border-color', '');
		i.not(t).unbind('keyup', jQuery.farbtastic(p).updateValue);
		t.css('border-color', '#666');
		
		if ( c.match(/^(#[0-9a-f]{6}|#[0-9a-f]{3})$/i) )
			jQuery.farbtastic(p).setColor(c);
		
		jQuery.farbtastic(p).linkTo(function(color) {
				t.val(color);
				t.css('background-color', color);
				if ( jQuery.farbtastic(p).hsl[2] < .6 ) {
					t.css('color', '#fff');
				} else {
					t.css('color', '#000');
				}
		});
		
		t.bind('keyup', jQuery.farbtastic(p).updateValue);
	});
	
	jQuery("input.color_picker").blur(function() {
		var t = jQuery(this);
		var p = t.parents("table:first").find('td.color_picker div');
		var f = p.parents("table:first").find("input.color_picker:first");
		
		var c = t.val();
		
		jQuery.farbtastic(p).linkTo(function(color) {});
		
		if ( !c.match(/^(#[0-9a-f]{6}|#[0-9a-f]{3})$/i) ) {
			t.css('background-color', '#fff');
			t.css('color', '#000');
		}
		
		t.not(f).css('border-color', '');
		t.not(f).unbind('keyup', jQuery.farbtastic(p).updateValue);
		f.css('border-color', '#666');
		
		jQuery.farbtastic(p).linkTo(function(color) {
				f.val(color);
				f.css('background-color', color);
				if ( jQuery.farbtastic(p).hsl[2] < .6 ) {
					f.css('color', '#fff');
				} else {
					f.css('color', '#000');
				}
		});
		
		f.bind('keyup', jQuery.farbtastic(p).updateValue);
	});
	
});
</script>

EOS;
	} # admin_footer()
	
	
	/**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST || !current_user_can('switch_themes') )
			return;
		
		check_admin_referer('sem_custom');
		
		global $sem_options;
		$saved = false;
		$restored = false;
		$publish = !empty($_REQUEST['publish']);
		$published = false;
		$fs_error = false;
		
		if ( !empty($_POST['reset']) ) {
			update_option('sem_custom', array());
			$saved = true;
		} elseif ( isset($_POST['custom']) ) {
			$custom = stripslashes_deep($_POST['custom']);

			foreach ( $custom as $css => $vals ) {
				if ( $css == 'extra' ) {
					$custom['extra'] = stripslashes($_POST['custom']['extra']);
					if ( empty($custom['extra']) )
						unset($custom['extra']);
					continue;
				}
				foreach ( $vals as $key => $val ) {
					if ( empty($val) ) {
						unset($custom[$css][$key]);
						continue;
					}

					switch ( $key ) {
					case 'font_family':
						if ( !in_array($val, array_keys(sem_custom::get_fonts())) )
							unset($custom[$css][$key]);
						break;

					case 'font_size':
						if ( !intval($val) )
							unset($custom[$css][$key]);
						elseif ( $val < 9 || $val > 24 )
							unset($custom[$css][$key]);
						break;

					case 'font_color':
					case 'link_color':
					case 'hover_color':
						if ( !preg_match("/(inherit|#[0-9a-f]{6}|#[0-9a-f]{3})/i", $val) )
							unset($custom[$css][$key]);
						break;

					case 'font_weight':
					case 'link_weight':
					case 'hover_weight':
						if ( !in_array($val, array_keys(sem_custom::get_font_weights())) )
							unset($custom[$css][$key]);
						break;

					case 'font_style':
						if ( !in_array($val, array_keys(sem_custom::get_font_styles())) )
							unset($custom[$css][$key]);
						break;

					case 'link_decoration':
					case 'hover_decoration':
						if ( !in_array($val, array_keys(sem_custom::get_font_decorations())) )
							unset($custom[$css][$key]);
						break;

					default:
						unset($custom[$css][$key]);
						break;
					}
				}

				if ( empty($custom[$css]) )
					unset($custom[$css]);
			}

			update_option('sem_custom', $custom);
			$saved = true;
		}
		
		if ( !empty($_POST['restore']) ) {
			$published_css = get_option('sem_custom_published');
			$restore_css = $published_css[$sem_option['active_skin']]
				? is_array($published_css[$sem_option['active_skin']])
				: array();
			update_option('sem_custom', $restore_css);
			$restored = true;
		} elseif ( !empty($_REQUEST['publish']) ) {
			global $wp_filesystem;
			
			$url = wp_nonce_url('themes.php?page=custom&publish=1', 'sem_custom');
			$credentials = request_filesystem_credentials($url, '', false);
			
			if ( $credentials ) {
				if ( !WP_Filesystem($credentials) ) {
					$error = true;
					if ( is_object($wp_filesystem) && $wp_filesystem->errors->get_error_code() )
						$error = $wp_filesystem->errors;
					request_filesystem_credentials($url, '', $error);
				} else {
					$fs_error = false;
					switch ( true ) {
					default:
						if ( !$wp_filesystem->find_folder(sem_path . '/skins/' . $sem_options['active_skin']) ) {
							$fs_error = sprintf(__('Publish Failed: Could not locate your active skin\'s folder (<code>%s</code>).', 'sem-reloaded'), 'wp-content/themes/sem-reloaded/skins/' . $sem_options['active_skin']);
							break;
						}
						
						$file = sem_path . '/skins/' . $sem_options['active_skin'] . '/custom.css';
						
						if ( $wp_filesystem->exists($file) ) {
							if ( !$wp_filesystem->is_file($file) ) {
								$fs_error = sprintf(__('Publish Failed: A custom.css <strong>folder<strong> is located in your skin\'s folder (<code>%s</code>). Please delete it and try again.', 'sem-reloaded'), 'wp-content/themes/sem-reloaded/skins/' . $sem_options['active_skin']);
								break;
							} elseif ( !$wp_filesystem->is_writable($file) ) {
								$fs_error = sprintf(__('Publish Failed: Cannot overwrite your skin\'s custom.css file (<code>%s</code>). Please check its permissions and try again.', 'sem-reloaded'), 'wp-content/themes/sem-reloaded/skins/' . $sem_options['active_skin'] . '/custom.css');
								break;
							}
							
							$new_css = $wp_filesystem->get_contents($file);
							$new_css = explode('/* == Stop Editing Here! == */', $new_css);
							$new_css = array_shift($new_css);
						} elseif ( !$wp_filesystem->is_writable(dirname($file)) ) {
							$fs_error = sprintf(__('Publish Failed: Cannot write to your skin folder (<code>%s</code>). Please check its permissions and try again.', 'sem-reloaded'), 'wp-content/themes/sem-reloaded/skins/' . $sem_options['active_skin']);
							break;
						} else {
							$new_css = '';
						}
						
						$new_css = rtrim($new_css);
						
						if ( $new_css )
							$new_css .= "\n\n";
						
						$new_css .= '/* == Stop Editing Here! == */' . "\n"
							. '/* Anything beneath the above line will be deleted if you publish CSS under Appearance / Custom CSS. If you want to manually insert additional CSS, place it further up. */' . "\n\n"
							. sem_custom::get_css();
						
						$wp_filesystem->delete($file);
						$_dir = dirname($file);
						$_file = basename($file);
						$_dir = $wp_filesystem->find_folder($_dir);
						$file = $_dir . '/' . $_file;
						$published = $wp_filesystem->put_contents($file, $new_css);
						
						if ( !$published ) {
							$chdir = $wp_filesystem->chdir($_dir);
							if ( !$chdir && is_a($wp_filesystem, 'WP_Filesystem_FTPext') )
								$chdir = ftp_chdir($wp_filesystem->link, $_dir);
							if ( $chdir )
								$published = $wp_filesystem->put_contents($_file, $new_css);
						}
						
						if ( $published ) {
							# store latest revision
							$published_css = get_option('sem_custom_published');
							$published_css[$sem_options['active_skin']] = get_option('sem_custom');
							update_option('sem_custom_published', $published_css);
							do_action('flush_cache');
						} else {
							$fs_error = sprintf(__('Publish Failed: A WP filesystem error occurred (probably related to <a href="%1$s">this WP bug</a>) occurred. Paste the following code in %2$s:<pre>%3$s</pre>', 'sem-reloaded'), 'http://core.trac.wordpress.org/ticket/10889', 'wp-content/themes/sem-reloaded/skins/' . $sem_options['active_skin'] . '/custom.css',  $new_css);
							break;
						}
					}
				}
			}
		}
		
		if ( $restored ) {
			echo '<div class="updated fade">'
				. '<p>'
				. __('Settings Restored.', 'sem-reloaded')
				. '</p>'
				. '</div>';
		} elseif ( !$publish ) {
			echo '<div class="updated fade">'
				. '<p>'
				. sprintf(__('Settings Saved. <a href="%s">Preview Changes</a>.', 'sem-reloaded'), user_trailingslashit(get_option('home')) . '?preview=custom-css')
				. '</p>'
				. '</div>';
		} elseif ( $fs_error ) {
			if ( $saved ) {
				echo '<div class="updated fade">'
					. '<p>'
					. __('Settings Saved.', 'sem-reloaded')
					. '</p>'
					. '</div>';
			}
			
			echo '<div class="error">'
				. '<p>'
				. $fs_error
				. '</p>'
				. '</div>';
		} elseif ( $published ) {
			echo '<div class="updated fade">'
				. '<p>'
				. sprintf(__('Settings Saved and Published. <a href="%s">View Changes</a>.', 'sem-reloaded'), user_trailingslashit(get_option('home')))
				. '</p>'
				. '</div>';
		} elseif ( $saved ) {
			echo '<div class="updated fade">'
				. '<p>'
				. __('Settings Saved.', 'sem-reloaded')
				. '</p>'
				. '</div>';
		}
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	function edit_options() {
		global $wp_filesystem;
		global $sem_options;
		
		if ( !empty($_REQUEST['publish']) && !is_object($wp_filesystem)
			|| is_object($wp_filesystem) && $wp_filesystem->errors->get_error_code() )
			return;
		
		$custom = get_option('sem_custom');
		$published_css = get_option('sem_custom_published');
		$restore_css = isset($published_css[$sem_options['active_skin']]) &&
			is_array($published_css[$sem_options['active_skin']])
			? $published_css[$sem_options['active_skin']]
			: array();
		
		echo '<div class="wrap">' . "\n"
			. '<form method="POST" action="themes.php?page=custom">' . "\n";
		
		screen_icon();
		
		echo '<h2>'
			. __('Manage Custom CSS', 'sem-reloaded')
			. '</h2>' . "\n";
		
		wp_nonce_field('sem_custom');
		
		echo '<p>'
			. sprintf(__('This screen allows to customize your skin. Up to a certain point, anyway: backgrounds and borders are not supported because the latter are managed using images<span style="hide-if-no-js"> (for <a href="#" onclick="%s">good reasons</a>)</span>. To customize the rest, select an area below, and customize it to your liking.', 'sem-reloaded'), "jQuery('#good_reasons').fadeIn('slow'); return false;")
			. '</p>' . "\n";
		
		echo '<p>'
			. sprintf(__('Your changes will not appear on your site until you publish them. You can <a href="%s">preview your changes</a> if you save without publishing.', 'sem-reloaded'), user_trailingslashit(get_option('home')) . '?preview=custom-css')
			. '</p>' . "\n";
		
		echo '<div id="good_reasons" style="display: none;">';
		
		echo '<p>'
			. sprintf(__('The Semiologic Theme\'s canvas and its key cosmetic elements are image-driven. End-users wanted rounded corners and sexy gradients. They got it. But the only means to do so while ensuring IE compatibility is through the use of background images. The recommended method to change your site\'s background colors, and general look and feel, would be to change your skin &#8212; there are <a href="%s">plentiful choices</a>, and more to come.', 'sem-reloaded'), 'http://skins.semiologic.com')
			. '</p>' . "\n";
		
		echo '<p>'
			. __('At least for now, adding an interface to manage background images is not an option. We know users are craving for it. But coming up with an interface to create the needed images that fit all of the layout options and potential look and feels, using php and javascript, is much about impossible. If someone ever writes a free equivalent of photoshop in javascript, we\'ll definitely give it a go. In the meanwhile, we\'ll stick to the manual approach.', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<p>'
			. __('That being said, it definitely <em>is</em> possible to customize the images. Here\'s how:', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<ol>'
			. '<li>' . __('Please keep in mind, at all times, that you\'re doing this on your own, and that you should expect no support when trying to change these images. As much as we\'re documenting this, we\'re making the assumption that, if you try it, you\'re familiar with Photoshop masks, layer effects, etc.', 'sem-reloaded') . '</li>' . "\n"
			. '<li>' . __('Grab the assets of your favorite skin on <a href="http://skins.semiologic.com">skins.semiologic.com</a>.', 'sem-reloaded') . '</li>' . "\n"
			. '<li>' . __('Create a copy of (or rename) your favorite skin, in <code>wp-content/themes/sem-reloaded/skins</code>. This will allow you to edit it as much as you want without needing to worry about losing your changes during upgrades.', 'sem-reloaded') . '</li>' . "\n"
			. '<li>' . __('Edit the images in Photoshop. Save for the web. (The psd files are pre-sliced for your convenience.) ', 'sem-reloaded') . '</li>' . "\n"
			. '<li>' . __('Upload the resulting images into your new skin\'s images folder.', 'sem-reloaded') . '</li>' . "\n"
			. '<li>' . __('We\'re dead serious about the fact that you\'re on your own if you try the above. If you do not understand it all, please stick to the existing skins; or prepare yourself to spend long hours learning Photoshop. We can also put you in touch with a <a href="http://www.semiologic.com/services/">virtual assistant</a>.', 'sem-reloaded')
			. '</ul>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '<div id="custom-tabs">' . "\n";
		
		echo '<ul id="custom-tabs-nav">' . "\n"
			. '<li class="button hide-if-no-js"><a href="#custom-tabs-main">'
				. __('Content', 'sem-reloaded')
				. '</a></li>'
			. '<li class="button hide-if-no-js"><a href="#custom-tabs-sidebar">'
				. __('Sidebars', 'sem-reloaded')
				. '</a></li>'
			. '<li class="button hide-if-no-js"><a href="#custom-tabs-header">'
				. __('Header', 'sem-reloaded')
				. '</a></li>'
			. '<li class="button hide-if-no-js"><a href="#custom-tabs-footer">'
				. __('Footer', 'sem-reloaded')
				. '</a></li>'
			. '<li class="button hide-if-no-js"><a href="#custom-tabs-extra">'
				. __('Extra', 'sem-reloaded')
				. '</a></li>';
		
		if ( $restore_css && $custom != $restore_css ) {
			echo '<li class="submit">'
				. '<input type="submit" name="restore" onclick="return confirm(\'' . esc_js(__('You are about to delete all changes you\'ve done since the last time you\'ve published. Please confirm to continue.', 'sem-reloaded')) . '\');" value="' . esc_attr(__('Restore', 'sem-reloaded')) . '" />'
			. '</li>';
		} else {
			echo '<li class="submit">'
				. '<input type="submit" name="reset" onclick="return confirm(\'' . esc_js(__('You are about to reset all of your custom.css declarations without publishing. Please confirm to continue.', 'sem-reloaded')) . '\');" value="' . esc_attr(__('Reset', 'sem-reloaded')) . '" />'
			. '</li>';
		}
		
		echo '<li class="submit">'
				. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-reloaded')) . '" />'
			. '</li>'
			. '<li class="submit">'
				. '<input type="submit" name="publish" value="' . esc_attr(__('Publish', 'sem-reloaded')) . '" />'
			. '</li>'
			. '</ul>' . "\n";
		
		echo '<div id="custom-tabs-main" class="clear">';
		
		echo '<h3>' . __('Content Area', 'sem-reloaded') . '</h3>';
		
		sem_custom::edit_area('content');
		
		echo '</div>' . "\n";
		
		
		echo '<div id="custom-tabs-sidebar" class="clear">';
		
		echo '<h3>' . __('Sidebar Areas', 'sem-reloaded') . '</h3>';
		
		sem_custom::edit_area('sidebars');
		
		echo '</div>' . "\n";
		
		
		echo '<div id="custom-tabs-header" class="clear">';
		
		echo '<h3>' . __('Header Area', 'sem-reloaded') . '</h3>';
		
		sem_custom::edit_area('header');
		
		echo '</div>' . "\n";
		
		
		echo '<div id="custom-tabs-footer" class="clear">';
		
		echo '<h3>' . __('Footer Area', 'sem-reloaded') . '</h3>';
		
		sem_custom::edit_area('footer');
		
		echo '</div>' . "\n";
		
		
		echo '<div id="custom-tabs-extra" class="clear">';
		
		echo '<h3>' . __('Extra CSS', 'sem-reloaded') . '</h3>';
		
		echo '<p>'
			. __('Anything you want... You\'ll find a CSS cheat sheet below.', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<textarea name="custom[extra]" class="widefat code" rows="24">'
			. esc_html(!empty($custom['extra']) ? $custom['extra'] : '')
			. '</textarea>' . "\n";
		
		echo '<p class="submit">'
			. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-reloaded')) . '" />'
			. '</p>' . "\n";
		
		echo '<h3>'
			. __('Theme ID/Classes Cheat Sheet', 'sem-reloaded')
			. '</h3>' . "\n";
		
		echo '<p>'
			. __('The more commonly used CSS selectors in the Semiologic theme are indicated below, grouped by area. For more examples, please check the CSS code of the built-in skins.', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo <<<EOS
<pre>
- body, #wrapper, #wrapper_bg

  - #header_wrapper

    - #header_top_wrapper, #header_top_wrapper_bg

      - #header_boxes, #header_boxes_bg

        - #header_boxes h2

      - .header_widget, .header_widget_bg

        - .header_widget h2

    - #header, #header_bg

      - #sitename, #tagline

    - #header_middle_wrapper, #header_middle_wrapper_bg

      - #header_boxes, #header_boxes_bg

        - #header_boxes h2

      - .header_widget, .header_widget_bg

        - .header_widget h2

    - #navbar, #navbar_bg

      - #navbar span
      - #navbar a
      - #navbar a:hover, #navbar span.nav_active

    - #header_bottom_wrapper, #header_bottom_wrapper_bg

      - #header_boxes, #header_boxes_bg

        - #header_boxes h2

      - .header_widget, .header_widget_bg

        - .header_widget h2

  - #body, #body_bg

    - #main, #main_bg

      - .entry, .entry_bg

        - .entry_date
        - .entry_header, .entry_header h1
        - .entry_content
        - .entry_categories
        - .entry_tags
        - .entry_comments

      - .main_widget, .main_widget_bg

        - .main_widget h2

    - #sidebar, #sidebar2, .sidebar_bg

      - .widget, .widget_bg, .mm1s .widget, .mm1s .widget_bg

    - #sidebars, #sidebars_bg

      - .wide_sidebar .widget, .wide_sidebar .widget_bg

  - #footer_wrapper

    - #footer_top_wrapper, #footer_top_wrapper_bg

      - #footer_boxes, #footer_boxes_bg

      - .footer_widget, .footer_widget_bg

    - #footer, #footer_bg

      - #footer span
      - #footer a
      - #footer a:hover, #footer span.nav_active

    - #footer_bottom_wrapper, #footer_bottom_wrapper_bg

      - #footer_boxes, #footer_boxes_bg

      - .footer_widget, .footer_widget_bg

- html, #credits, #credits_bg
</pre>
EOS;
		
		echo '<h3>'
			. __('CSS Cheat Sheet', 'sem-reloaded')
			. '</h3>' . "\n";
		
		echo '<p>'
			. __('Many users have some basic understanding of Stylesheets, but have no idea of what a <strong>Cascading</strong> Stylesheet might be. So here goes...', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '<p>'
			. __('There are three basic types of selectors:', 'sem-reloaded')
			. '</p>' . "\n";
		
		foreach ( array(
			'p' => __('Affects all <p> tags, no matter where.', 'sem-reloaded'),
			'.widget' => __('Affects anything in an area with the "widget" class, i.e. <div class="widget">. "Stronger" than the previous if declared after.', 'sem-reloaded'),
			'#body' => __('Affects everything in the area with the "body" ID, i.e. <div id="body">. "Stronger" than the above two if declared after.', 'sem-reloaded'),
			)
			as $selector => $description ) {
			$description = str_replace(array("\r\n", "\n", "\r"), "\n   * ", wordwrap(esc_attr($description), 80));
			echo <<<EOS
<pre>
$selector {
  /*
   * $description
   */
}

</pre>
EOS;
		}
		
		echo '<p>'
			. __('They can be combined and cascaded:', 'sem-reloaded')
			. '</p>' . "\n";
		
		foreach ( array(
			'div.entry_date' => __('Affects <h2> tagsclass="widget_title"> tags, i.e. sidebar widget titles. "Stronger" than the previous three (it\'s "more precise").', 'sem-reloaded'),
			'div#sitename' => __('Affects <div id="sitename">, i.e. the site\'s name. "Stronger" than the previous (it\'s "more precise"), but generally useless since IDs are unique.', 'sem-reloaded'),
			'.mm1s .widget' => __('Affects anything within an area with the "widget" class, itself within an area with the "mm1s" class, i.e. widgets when using a "Wide Content, Sidebar" layouts. "Stronger" than all of the above.'),
			'#top_sidebar .widget' => __('Affects anything within an area with the "widget" class, itself within an area with the "top_sidebar" ID, i.e. widgets in the top sidebar when using a "Content, Wide Sidebar" layout. "Stronger" than the previous if declared after.'),
			'.widget_title h2' => __('Affects <h2> tags within an area with the "widget_title" class, i.e. <h2> tags in sidebar widgets. "Stronger" than the previous if declared after.'),
			)
			as $selector => $description ) {
			$description = str_replace(array("\r\n", "\n", "\r"), "\n   * ", wordwrap(esc_attr($description), 80));
			echo <<<EOS
<pre>
$selector {
  /*
   * $description
   */
}

</pre>
EOS;
		}
		
		echo '<p>'
			. sprintf(__('There are, of course, many more <a href="%s">CSS selectors</a>, but they\'re not universally supported...', 'sem-reloaded'), 'http://kimblim.dk/css-tests/selectors/')
			. '</p>' . "\n";
		
		echo '<p>'
			. __('The gist, here, is to remember that the C in CSS stands for cascading and that CSS is applied on a &quot;last strongest declaration gets used&quot; basis (i.e. mind the order). `foo`, `.foo`, and `#foo` are interchangeable in CSS-compliant browsers; `foo.bar` is stronger than `foo`, and `foo bar` is even stronger. In other words, to affect a particular area, use `.foo` or `#foo`; to affect `bar` within a particular area, use `.foo bar` or `#foo bar`.')
			. '</p>' . "\n";
		
		echo '<p>'
			. __('In some rare cases, you may also need a knock-out declaration that overrides everything regardless of the order it\'s declared in. This is where the `!important` keyword, placed immediately before the semi-colon, comes in:', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo <<<EOS
<pre>
p {
	line-height: 150% !important;
}
</pre>
EOS;
		
		echo '<p>'
			. __('The latter declaration overrides absolutely everything.', 'sem-reloaded')
			. '</p>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '</div>' . "\n";
		
		echo '</form>' . "\n"
			. '</div>' . "\n";
	} # edit_options()
	
	
	/**
	 * get_area()
	 *
	 * @param string $area
	 * @return array $areas
	 **/

	function get_area($area = null) {
		$areas = array();
		
		$areas['content'] =  array(
			'#main' => __('Entries', 'sem-reloaded'),
			'#main h1' => __('Entry Titles', 'sem-reloaded'),
			'#main h2, #main .widget_calendar caption' => __('Entry Subtitles', 'sem-reloaded'),
			'#main .entry_date' => __('Entry Dates', 'sem-reloaded'),
			'#main .entry_categories' => __('Entry Categories', 'sem-reloaded'),
			'#main .entry_tags' => __('Entry Tags', 'sem-reloaded'),
			'#main .comment_date' => __('Comment Dates', 'sem-reloaded'),
			'#main .comment_header' => __('Comment Header', 'sem-reloaded'),
			'#main .comment_content' => __('Comment Content', 'sem-reloaded'),
			);
		
		$areas['sidebars'] = array(
			'.sidebar' => __('Sidebar Widgets', 'sem-reloaded'),
			'.sidebar h2, .sidebar .widget_calendar caption' => __('Sidebar Widget Titles', 'sem-reloaded'),
			'.sidebar h3' => __('Sidebar Widget Subtitles', 'sem-reloaded'),
			'.sidebar .wp-calendar' => __('Sidebar Calendar', 'sem-reloaded'),
			'.sidebar .wp-calendar thead' => __('Sidebar Calendar Header', 'sem-reloaded'),
			'.sidebar .wp-calendar tfoot' => __('Sidebar Calendar Footer', 'sem-reloaded'),
			);
		
		$areas['header'] = array(
			'#sitename' => __('Site Name', 'sem-reloaded'),
			'#tagline' => __('Tagline', 'sem-reloaded'),
			'#navbar' => __('Navigation Menu', 'sem-reloaded'),
			'.header_widget' => __('Header Widgets', 'sem-reloaded'),
			'.header_widget h2, .header_widget .widget_calendar caption' => __('Header Widget Titles', 'sem-reloaded'),
			'.header_widget h3' => __('Header Widget Subtitles', 'sem-reloaded'),
			'#header_boxes' => __('Header Bar Widgets', 'sem-reloaded'),
			'#header_boxes h2, #header_boxes .widget_calendar caption' => __('Header Bar Widget Titles', 'sem-reloaded'),
			'#header_boxes h3' => __('Header Bar Widget Subtitles', 'sem-reloaded'),
			);
		
		$areas['footer'] = array(
			'#footer' => __('Footer Nav Menu &amp; Copyright Notice', 'sem-reloaded'),
			'#credits, .footer_scripts' => __('Credits &amp; Footer Scripts', 'sem-reloaded'),
			'.footer_widget' => __('Footer Widgets', 'sem-reloaded'),
			'.footer_widget h2, .footer_widget .widget_calendar caption' => __('Footer Widget Titles', 'sem-reloaded'),
			'.footer_widget h3' => __('Footer Widget Subtitles', 'sem-reloaded'),
			'#footer_boxes' => __('Footer Bar Widgets', 'sem-reloaded'),
			'#footer_boxes h2, #footer_boxes .widget_calendar caption' => __('Footer Bar Widget Titles', 'sem-reloaded'),
			'#footer_boxes h3' => __('Footer Bar Widget Subtitles', 'sem-reloaded'),
			);
		
		if ( $area ) {
			return isset($areas[$area]) ? $areas[$area] : array();
		} else {
			return $areas;
		}
	} # get_area()
	
	
	/**
	 * edit_area()
	 *
	 * @param string $area
	 * @return void
	 **/

	function edit_area($area) {
		static $color_picker = 0;
		$color_picker++;
		$custom = get_option('sem_custom');
		
		foreach ( sem_custom::get_area($area) as $css => $name ) {
			echo '<h4>' . $name . '</h4>' . "\n";
			
			echo '<table class="form-table">' . "\n";
			
			echo '<tr>' . "\n"
				. '<th scope="row">' . "\n"
				. __('Font', 'sem-reloaded')
				. '</th>' . "\n"
				. '<td>' . "\n";
			
			$font_family = isset($custom[$css]['font_family']) ? $custom[$css]['font_family'] : '';
			$font_size = isset($custom[$css]['font_size']) ? $custom[$css]['font_size'] : '';
			$font_color = isset($custom[$css]['font_color']) ? $custom[$css]['font_color'] : '';
			$font_weight = isset($custom[$css]['font_weight']) ? $custom[$css]['font_weight'] : '';
			$font_style = isset($custom[$css]['font_style']) ? $custom[$css]['font_style'] : '';
			
			$link_color = isset($custom[$css]['link_color']) ? $custom[$css]['link_color'] : '';
			$link_weight = isset($custom[$css]['link_weight']) ? $custom[$css]['link_weight'] : '';
			$link_decoration = isset($custom[$css]['link_decoration']) ? $custom[$css]['link_decoration'] : '';
			
			$hover_color = isset($custom[$css]['hover_color']) ? $custom[$css]['hover_color'] : '';
			$hover_weight = isset($custom[$css]['hover_weight']) ? $custom[$css]['hover_weight'] : '';
			$hover_decoration = isset($custom[$css]['hover_decoration']) ? $custom[$css]['hover_decoration'] : '';
			
			
			echo '<select name="custom[' . $css . '][font_family]">' . "\n";
			
			foreach ( sem_custom::get_fonts() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $font_family, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][font_size]">' . "\n";
			
			echo '<option value=""' . selected('', $font_size, false) . '>'
				. '-'
				. '</option>';
			
			for ( $i = 9; $i <= 24; $i++ )
				echo '<option value="' . $i . '"'
					. selected($i, $font_size, false)
					. '>' . sprintf(__('%dpt', 'sem-reloaded'), $i) . '</option>' . "\n";
			
			echo '</select>' . "\n";
			
			
			
			echo '</td>' . "\n";
			
			echo '<td rowspan="5" class="color_picker">'
				. '<div id="color_picker-' . $color_picker . '" style="display: none;"></div>'
				. '</td>' . "\n";
			
			echo '</tr>' . "\n";
			
			
			echo '<tr>' . "\n"
				. '<th scope="row">' . "\n"
				. __('Font Style', 'sem-reloaded')
				. '</th>' . "\n"
				. '<td>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][font_weight]">' . "\n";
			
			foreach ( sem_custom::get_font_weights() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $font_weight, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][font_style]">' . "\n";
			
			foreach ( sem_custom::get_font_styles() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $font_style, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			echo '<input type="text" size="12" class="color_picker"'
				. ' id="font_color_picker-' . $color_picker . '"'
				. ' name="custom[' . $css . '][font_color]"'
				. ' value="' . esc_attr($font_color) . '"'
				. ' />' . "\n";
			
			
			echo '</td>' . "\n"
				. '</tr>' . "\n";
			
			echo '<tr>' . "\n"
				. '<th scope="row">' . "\n"
				. __('Links', 'sem-reloaded')
				. '</th>' . "\n"
				. '<td>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][link_weight]">' . "\n";
			
			foreach ( sem_custom::get_font_weights() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $link_weight, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][link_decoration]">' . "\n";
			
			foreach ( sem_custom::get_font_decorations() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $link_decoration, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			
			echo '<input type="text" size="12" class="color_picker"'
				. ' id="link_color_picker-' . $color_picker . '"'
				. ' name="custom[' . $css . '][link_color]"'
				. ' value="' . esc_attr($link_color) . '"'
				. ' />' . "\n";
			
			
			echo '</td>'
				. '</tr>' . "\n";
			
			echo '<tr>' . "\n"
				. '<th scope="row">' . "\n"
				. __('Hovered Links', 'sem-reloaded')
				. '</th>' . "\n"
				. '<td>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][hover_weight]">' . "\n";
			
			foreach ( sem_custom::get_font_weights() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $hover_weight, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			
			echo '<select name="custom[' . $css . '][hover_decoration]">' . "\n";
			
			foreach ( sem_custom::get_font_decorations() as $k => $v ) {
				echo '<option value="' . $k . '"'
					. selected($k, $hover_decoration, false)
					. '>' . $v . '</option>' . "\n";
			}
			
			echo '</select>' . "\n";
			
			
			echo '<input type="text" size="12" class="color_picker"'
				. ' id="hover_color_picker-' . $color_picker . '"'
				. ' name="custom[' . $css . '][hover_color]"'
				. ' value="' . esc_attr($hover_color) . '"'
				. ' />' . "\n";
			
			
			echo '</td>'
				. '</tr>' . "\n";
			
			echo '<tr>'
				. '<td colspan="2">';
			
			echo '<p class="submit">'
				. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-reloaded')) . '" />'
				. '</p>' . "\n";
			
			echo '</td>'
				. '</tr>' . "\n";
			
			echo '</table>' . "\n";
		}
	} # edit_area()
	
	
	/**
	 * get_fonts()
	 *
	 * @return array $fonts
	 **/

	function get_fonts() {
		return array(
			'' =>  __('- Default Font Family -', 'sem-reloaded'),
			'arial' => __('Arial stack / Sans-Serif', 'sem-reloaded'),
			'tahoma' => __('Tahoma stack / Sans-Serif', 'sem-reloaded'),
			'trebuchet' => __('Trebuchet stack / Sans-Serif', 'sem-reloaded'),
			'verdana' => __('Verdana stack / Sans-Serif', 'sem-reloaded'),
			'antica' => __('Antica stack / Serif', 'sem-reloaded'),
			'georgia' => __('Georgia stack / Serif', 'sem-reloaded'),
			'times' => __('Times stack / Serif', 'sem-reloaded'),
			'courier' => __('Courier stack / Monospace', 'sem-reloaded'),
			);
	} # get_fonts()
	
	
	/**
	 * get_font_weights()
	 *
	 * @return array $font_weights
	 **/

	function get_font_weights() {
		return array(
			'' => __('- Default -', 'sem-reloaded'),
			'bold' => __('Bold', 'sem-reloaded'),
			'normal' => __('Normal', 'sem-reloaded'),
			);
	} # get_font_weights()
	
	
	/**
	 * get_font_styles()
	 *
	 * @return array $font_styles
	 **/

	function get_font_styles() {
		return array(
			'' => __('- Default -', 'sem-reloaded'),
			'italic' => __('Italic', 'sem-reloaded'),
			'normal' => __('Normal', 'sem-reloaded'),
			);
	} # get_font_styles()
	
	
	/**
	 * get_font_decorations()
	 *
	 * @return array $font_decorations
	 **/

	function get_font_decorations() {
		return array(
			'' => __('- Default -', 'sem-reloaded'),
			'none' => __('None', 'sem-reloaded'),
			'underline' => __('Underline', 'sem-reloaded'),
			);
	} # get_font_decorations()
	
	
	/**
	 * get_css()
	 *
	 * @return string $css
	 **/

	function get_css() {
		$css = array();
		$custom = get_option('sem_custom');
		
		$font_families = array(
			'arial' => 'Arial, "Liberation Sans", "Nimbus Sans L", "DejaVu Sans", Sans-Serif',
			'tahoma' => 'Tahoma, "Nimbus Sans L", "DejaVu Sans", Sans-Serif',
			'trebuchet' => '"Trebuchet MS", "Nimbus Sans L", "DejaVu Sans", Sans-Serif',
			'verdana' => 'Verdana, "Nimbus Sans L", "DejaVu Sans", Sans-Serif',
			'antica' => '"Palatino, "Book Antica", "Palatino Linotype", "URW Palladio L", Palladio, Georgia, "DejaVu Serif", Serif',
			'georgia' => 'Georgia, "New Century Schoolbook", "Century Schoolbook L", "DejaVu Serif", Serif',
			'times' => '"Times New Roman", Times, "Liberation Serif", "DejaVu Serif Condensed", Serif',
			'courier' => '"Courier New", "Liberation Mono", "Nimbus Mono L", Monospace',
			);
		$font_sizes = array();
		for ( $i = 9; $i <= 24; $i++ )
			$font_sizes[$i] = $i . 'pt';
		$font_weights = array(
			'bold' => 'bold',
			'normal' => 'normal',
			);
		$font_styles = array(
			'italic' => 'italic',
			'normal' => 'normal',
			);
		$font_decorations = array(
			'none' => 'none',
			'underline' => 'underline',
			);
		
		foreach ( $custom as $pointer => $defs ) {
			if ( $pointer == 'extra' )
				continue;
			foreach ( $defs as $k => $v ) {
				switch ( $k ) {
				case 'font_family':
					if ( !$v || !isset($font_families[$v]) )
						continue;
					$css[$pointer][] = 'font-family: ' . $font_families[$v] . ';';
					break;
				
				case 'font_size':
					if ( !$v || !isset($font_sizes[$v]) )
						continue;
					$css[$pointer][] = 'font-size: ' . $font_sizes[$v] . ';';
					break;
				
				case 'font_weight':
					if ( !$v || !isset($font_weights[$v]) )
						continue;
					$css[$pointer][] = 'font-weight: ' . $font_weights[$v] . ';';
					break;
				
				case 'font_style':
					if ( !$v || !isset($font_styles[$v]) )
						continue;
					$css[$pointer][] = 'font-style: ' . $font_styles[$v] . ';';
					break;
				
				case 'font_color':
					if ( !$v || !preg_match("/^(inherit|#[0-9a-f]{6}|#[0-9a-f]{3})$/i", $v) )
						continue;
					$css[$pointer][] = 'color: ' . $v . ';';
					break;
				
				case 'link_weight':
					if ( !$v || !isset($font_weights[$v]) )
						continue;
					$pointers = explode(',', $pointer);
					$pointers = array_map('trim', $pointers);
					$_pointer = array();
					foreach ( $pointers as $p )
						$_pointer[] = $p . ' a';
					$css[implode(', ', $_pointer)][] = 'font-weight: ' . $font_weights[$v] . ';';
					break;
				
				case 'link_decoration':
					if ( !$v || !isset($font_decorations[$v]) )
						continue;
					$pointers = explode(',', $pointer);
					$pointers = array_map('trim', $pointers);
					$_pointer = array();
					foreach ( $pointers as $p )
						$_pointer[] = $p . ' a';
					$css[implode(', ', $_pointer)][] = 'text-decoration: ' . $font_decorations[$v] . ';';
					break;
				
				case 'link_color':
					if ( !$v || !preg_match("/^(inherit|#[0-9a-f]{6}|#[0-9a-f]{3})$/i", $v) )
						continue;
					$pointers = explode(',', $pointer);
					$pointers = array_map('trim', $pointers);
					$_pointer = array();
					foreach ( $pointers as $p )
						$_pointer[] = $p . ' a';
					$css[implode(', ', $_pointer)][] = 'color: ' . $v . ';';
					break;
				
				case 'hover_weight':
					if ( !$v || !isset($font_weights[$v]) )
						continue;
					$pointers = explode(',', $pointer);
					$pointers = array_map('trim', $pointers);
					$_pointer = array();
					foreach ( $pointers as $p )
						$_pointer[] = $p . ' a:hover';
					$css[implode(', ', $_pointer)][] = 'font-weight: ' . $font_weights[$v] . ';';
					break;
				
				case 'hover_decoration':
					if ( !$v || !isset($font_decorations[$v]) )
						continue;
					$pointers = explode(',', $pointer);
					$pointers = array_map('trim', $pointers);
					$_pointer = array();
					foreach ( $pointers as $p )
						$_pointer[] = $p . ' a:hover';
					$css[implode(', ', $_pointer)][] = 'text-decoration: ' . $font_decorations[$v] . ';';
					break;
				
				case 'hover_color':
					if ( !$v || !preg_match("/^(inherit|#[0-9a-f]{6}|#[0-9a-f]{3})$/i", $v) )
						continue;
					$pointers = explode(',', $pointer);
					$pointers = array_map('trim', $pointers);
					$_pointer = array();
					foreach ( $pointers as $p )
						$_pointer[] = $p . ' a:hover';
					$css[implode(', ', $_pointer)][] = 'color: ' . $v . ';';
					break;
				}
			}
		}
		
		$o = '';
		
		foreach ( $css as $pointer => $defs ) {
			$o .= $pointer . ' {' . "\n";
			
			foreach ( $defs as $def )
				$o .= "\t" . $def . "\n";
			
			$o .= '}' . "\n\n";
		}
		
		if ( !empty($custom['extra']) )
			$o .= $custom['extra'] . "\n\n";
		
		return rtrim($o);
	} # get_css()
	
	
	/**
	 * wp_print_scripts()
	 *
	 * @return void
	 **/

	function wp_print_scripts() {
		if ( empty($_GET['preview']) || $_GET['preview'] != 'custom-css' || !current_user_can('switch_themes') )
			return;
		
		wp_enqueue_script('jquery');
	} # wp_print_scripts()
	
	
	/**
	 * wp_head()
	 *
	 * @return void
	 **/

	function wp_head() {
		if ( empty($_GET['preview']) || $_GET['preview'] != 'custom-css' || !current_user_can('switch_themes') )
			return;
		
		global $cache_enabled;
		global $super_cache_enabled;
		
		$cache_enabled = false;
		$super_cache_enabled = false;
		
		if ( method_exists('static_cache', 'disable') )
			static_cache::disable();
		
		echo '<style type="text/css">' . "\n";
		
		echo sem_custom::get_css() . "\n";
		
		echo '</style>' . "\n";
	} # wp_head()
	
	
	/**
	 * wp_footer()
	 *
	 * @return void
	 **/

	function wp_footer() {
		if ( empty($_GET['preview']) || $_GET['preview'] != 'custom-css' || !current_user_can('switch_themes') )
			return;
		
		$home_url = '^' . preg_quote(get_option('home'), '/') . '(?:$|\\/)';
		$admin_url = '^' . preg_quote(untrailingslashit(admin_url()), '/') . '(?:$|\\/)';
		$login_url = '^' . preg_quote(untrailingslashit(wp_login_url()), '/') . '(?:$|\\?)';
		
		echo <<<EOS

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('a').each(function() {
		var href = jQuery(this).attr('href');
		
		if ( !href.match(/$home_url/) || href.match(/$admin_url|$login_url/) )
			return;
		
		anchor = href.match(/#.*/);
		href = href.replace(/#.*/, '');
		
		if ( href.match(/\?/) )
			jQuery(this).attr('href', href + '&preview=custom-css' + ( anchor ? anchor : '' ) );
		else
			jQuery(this).attr('href', href + '?preview=custom-css' + ( anchor ? anchor : '' ) );
	});
});
</script>

EOS;
	} # wp_footer()
} # sem_custom

add_option('sem_custom', array(), false, 'no');
add_option('sem_custom_published', array(), false, 'no');

if ( is_admin() ) {
	add_action('admin_print_styles', array('sem_custom', 'styles'));
	add_action('admin_print_scripts', array('sem_custom', 'scripts'));
	add_action('admin_head', array('sem_custom', 'admin_head'));
	add_action('admin_footer', array('sem_custom', 'admin_footer'));
	add_action('appearance_page_custom', array('sem_custom', 'save_options'), 0);
} else {
	add_action('wp_print_scripts', array('sem_custom', 'wp_print_scripts'));
	add_action('wp_head', array('sem_custom', 'wp_head'));
	add_action('wp_footer', array('sem_custom', 'wp_footer'));
}
?>