<?php
/*
Copyright (c) 2006
http://www.gnu.org/licenses/gpl.txt

This file is part of WordPress.
WordPress is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('FALBUM_VERSION', '0.6.7');

define('FALBUM_PATH', dirname(__FILE__));

define('FALBUM_DO_NOT_CACHE', 0);
define('FALBUM_CACHE_EXPIRE_SHORT', 3600); //How many seconds to wait between refreshing cache (default = 3600 seconds - hour)
define('FALBUM_CACHE_EXPIRE_LONG', 604800); //How many seconds to wait between refreshing cache (default = 604800 seconds - 1 week)

define('FALBUM_DOMAIN', '/falbum/lang/falbum');

define('FALBUM_API_KEY', 'e746ede606c9ebb66ef79605ec834c07');
define('FALBUM_SECRET', '46d7a532dd766c9e');

define('FALBUM_FLICKR_URL_IMAGE', 'http://static.flickr.com');

class FAlbum {

	var $options = none;

	var $can_edit;
	var $show_private;

	var $has_error;
	var $error_detail;

	var $logger;

	function FAlbum() {

		require_once FALBUM_PATH.'/lib/Log.php';

		$this->has_error = false;
		$this->error_detail = null;

		$this->options = $this->get_options();

		$this->can_edit = $this->_can_edit();
		$this->show_private = $this->_show_private();

		if ($this->can_edit == true) {
			$conf = array ('title' => 'FAlbum Log Output');
			//$this->logger = & Log :: factory('fwin', 'LogWindow', '', $conf);
			//$this->logger = & Log :: factory('display', 'LogWindow', '', $conf);
			$this->logger = & Log :: factory('null', 'LogWindow');
		} else {
			$this->logger = & Log :: factory('null', 'LogWindow');
		}

		//$mask = Log::UPTO(PEAR_LOG_INFO);
		//$this->logger->setMask($mask);

	}

	/* The main function - called in falbum-wp.php, and can be called in any WP template. */
	function show_photos() {

		$album = $_GET['album'];
		$photo = $_GET['photo'];
		$page = $_GET['page'];
		$tags = $_GET['tags'];
		$show = $_GET['show'];

		$output = '';
		$continue = true;
		if (!is_null($show)) {
			if ($show == 'tags') {
				$output = $this->show_tags();
				$continue = false;
			}
			elseif ($show == 'recent') {
				$tags = '';
			}
		}

		if ($continue) {
			// Show list of albums/photosets (none have been selected yet)
			if (is_null($album) && is_null($tags) && is_null($photo)) {
				$output = $this->show_albums($page);
			}
			// Show list of photos in the selected album/photoset
			elseif (!is_null($album) && is_null($photo)) {
				$output = $this->show_album_thumbnails($album, $page);
			}
			// Show list of photos of the selected tags
			elseif (!is_null($tags) && is_null($photo)) {
				$output = $this->show_tags_thumbnails($tags, $page);
			}
			// Show the selected photo in the slected album/photoset
			elseif ((!is_null($album) || !is_null($tags)) && !is_null($photo)) {
				$output = $this->show_photo($album, $tags, $photo, $page);
			}
		}

		if ($this->has_error) {
			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('error', $this->options['style']);
			$tpl->set('message', $this->error_detail);
			$output = $tpl->fetch();
		}

		echo $output;
	}

	/* Shows list of all albums - this is the first thing you see */
	function show_albums($page = 1) {

		$this->logger->info("show_albums($page)");

		if ($page == '') {
			$page = 1;
		}

		$output = $this->_get_cached_data("showAlbums-$page");

		if (!isset ($output)) {

			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('albums', $this->options['style']);

			$output = '';

			$count = 0;
			$albums_list = array ();

			if ($this->options['number_recent'] != 0) {
				$count ++;

				if ($page == 1) {

					$xpath = $this->_call_flickr('flickr.photos.search', array ("user_id" => $this->options['nsid'], "per_page" => '1', "sort" => 'date-taken-desc'));
					if (!isset ($xpath)) {
						return;
					}

					$server = $xpath->getData('/rsp/photos/photo/@server');
					$secret = $xpath->getData('/rsp/photos/photo/@secret');
					$photo_id = $xpath->getData('/rsp/photos/photo/@id');
					$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$photo_id}_{$secret}_".$this->options['tsize'].".jpg"; // Build URL to thumbnail

					$data['tsize'] = $this->options['tsize'];
					$data['url'] = $this->create_url("show/recent/");
					$data['title'] = __('Recent Photos', FALBUM_DOMAIN);
					$data['title_d'] = __('View all recent photos', FALBUM_DOMAIN);
					$data['tags_url'] = $this->create_url("show/tags/");
					$data['tags_title'] = __('Tags', FALBUM_DOMAIN);
					$data['tags_title_d'] = __('Tags', FALBUM_DOMAIN);
					$data['description'] = __('See the most recent photos posted, regardless of which photo set they belong to.', FALBUM_DOMAIN);
					$data['thumbnail'] = $thumbnail;

					$albums_list[] = $data;

				}
			}

			$xpath = $this->_call_flickr('flickr.photosets.getList', array ("user_id" => $this->options['nsid']));
			if (!isset ($xpath)) {
				return;
			}

			$result = $xpath->match('/rsp/photosets/photoset');
			$countResult = count($result);

			$photo_title_array = array ();
			for ($i = 0; $i < $countResult; $i ++) {

				if (($this->options['albums_per_page'] == 0) || (($count >= ($page -1) * $this->options['albums_per_page']) && ($count < $page * $this->options['albums_per_page']))) {

					$photos = $xpath->getData($result[$i].'/@photos');

					if ($photos != 0) {
						$data = null;

						$id = $xpath->getData($result[$i].'/@id');
						$server = $xpath->getData($result[$i].'/@server');
						$primary = $xpath->getData($result[$i].'/@primary');
						$secret = $xpath->getData($result[$i].'/@secret');
						$title = $this->_unhtmlentities($xpath->getData($result[$i].'/title'));
						$description = $this->_unhtmlentities($xpath->getData($result[$i].'/description'));
						$link_title = $this->_get_link_title($title, $id, $photo_title_array);
						$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$primary}_{$secret}_".$this->options['tsize'].".jpg"; // Build URL to small square thumbnail

						$data['tsize'] = $this->options['tsize'];
						$data['url'] = $this->create_url("album/$link_title/");
						$data['title'] = $title;
						$data['title_d'] = strtr(__('View all pictures in #title#', FALBUM_DOMAIN), array ("#title#" => $title));
						$data['meta'] = strtr(__('This photoset has #num_photots# pictures', FALBUM_DOMAIN), array ("#num_photots#" => $photos));
						$data['description'] = $description;
						$data['thumbnail'] = $thumbnail;

						$albums_list[] = $data;

					} else {
						$count --;
					}
				}
				$count ++;
			}

			$tpl->set('albums', $albums_list);

			if ($this->options['albums_per_page'] != 0) {
				$pages = ceil($count / $this->options['albums_per_page']);
				if ($pages > 1) {
					$tpl->set('top_paging', $this->_build_paging($page, $pages, 'page/', 'top'));
					$tpl->set('bottom_paging', $this->_build_paging($page, $pages, 'page/', 'bottom'));
				}
			}
			
			$tpl->set('css_type_thumbnails', $this->options['display_dropshadows']);

			$output = $tpl->fetch();

			$this->_set_cached_data("showAlbums-$page", $output);

		}

		return $output;
	}

	/* Shows Thumbnails of all photos in selected album */
	function show_album_thumbnails($album, $page = 1) {

		$this->logger->info("show_album_thumbnails($album, $page)");

		if ($page == '') {
			$page = 1;
		}

		$output = $this->_get_cached_data("showAlbumThumbnails-$album-$page");
		if (!isset ($output)) {

			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('album-thumbnails', $this->options['style']);

			list ($album_id, $album_title) = $this->_get_album_info($album);

			$xpath = $this->_call_flickr('flickr.photosets.getPhotos', array ("photoset_id" => $album_id));
			if (!isset ($xpath)) {
				return;
			}

			$result = $xpath->match('/rsp/photoset/photo');
			$countResult = count($result);

			$photo_title_array = array ();
			$thumbnails_list = array ();

			$count = 0;
			for ($i = 0; $i < $countResult; $i ++) {

				if (($this->options['photos_per_page'] == 0) || (($count >= ($page -1) * $this->options['photos_per_page']) && ($count < ($page * $this->options['photos_per_page'])))) {
					$photo_id = $xpath->getData($result[$i].'/@id');
					$photo_title = $xpath->getData($result[$i].'/@title');
					$photo_link = $this->_get_link_title($photo_title, $photo_id, $photo_title_array);
					$server = $xpath->getData($result[$i].'/@server');
					$secret = $xpath->getData($result[$i].'/@secret');
					$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$photo_id}_{$secret}_".$this->options['tsize'].".jpg"; // Build URL to thumbnail

					$data['tsize'] = $this->options['tsize'];
					$data['url'] = $this->create_url("album/$album/page/$page/photo/$photo_link");
					$data['title'] = $photo_title;
					$data['thumbnail'] = $thumbnail;

					$thumbnails_list[] = $data;
				}
				$count ++;
			}

			if ($this->options['photos_per_page'] != 0) {
				$pages = ceil($countResult / $this->options['photos_per_page']);

				if ($pages > 1) {
					$tpl->set('top_paging', $this->_build_paging($page, $pages, 'album/'.$album.'/page/', 'top'));
					$tpl->set('bottom_paging', $this->_build_paging($page, $pages, 'album/'.$album.'/page/', 'bottom'));
				}
			}

			$tpl->set('url', $this->create_url());
			$tpl->set('album_title', $album_title);
			$tpl->set('album_id', $album_id);
			$tpl->set('photos_label', __('Photos', FALBUM_DOMAIN));
			$tpl->set('slide_show_label', __('View as a slide show', FALBUM_DOMAIN));
			$tpl->set('thumbnails', $thumbnails_list);

			$tpl->set('css_type_thumbnails', $this->options['display_dropshadows']);

			$output = $tpl->fetch();

			$this->_set_cached_data("showAlbumThumbnails-$album-$page", $output);

		}
		return $output;
	}

	/* Shows thumbnails for all Recent and Tag thumbnails */
	function show_tags_thumbnails($tags, $page = 1) {

		$this->logger->info("show_tags_thumbnails($tags, $page)");

		if ($page == '') {
			$page = 1;
		}

		$output = $this->_get_cached_data("show_tags_thumbnails-$tags-$page");
		if (!isset ($output)) {

			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('tag-thumbnails', $this->options['style']);

			$output = '';

			if ($tags == '') {
				// Get recent photos
				if ($this->options['number_recent'] == -1) {
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
				} else {
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['number_recent'], 'page' => '1'));
				}
			} else {
				$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
			}

			if (!isset ($xpath)) {
				return;
			}

			$result = $xpath->match('/rsp/photos/photo');
			$countResult = count($result);

			if ($tags == '') {
				$urlPrefix = 'show/recent/page/';
				$tpl->set('recent_label', __('Recent Photos', FALBUM_DOMAIN));
			} else {
				$urlPrefix = "tags/$tags/page/";

				$tpl->set('tag_url', $this->create_url('show/tags'));
				$tpl->set('tags_label', __('Tags', FALBUM_DOMAIN));
				$tpl->set('tags', $tags);
			}

			$photo_title_array = array ();
			$thumbnails_list = array ();
			$count = 0;

			for ($i = 0; $i < $countResult; $i ++) {

				if (($this->options['photos_per_page'] == 0) || $tags != '' || $this->options['number_recent'] == -1 || (($count >= ($page -1) * $this->options['photos_per_page']) && ($count < ($page * $this->options['photos_per_page'])))) {

					$server = $xpath->getData($result[$i].'/@server');
					$secret = $xpath->getData($result[$i].'/@secret');
					$photo_id = $xpath->getData($result[$i].'/@id');
					$photo_title = $xpath->getData($result[$i].'/@title');
					$photo_link = $this->_get_link_title($photo_title, $photo_id, $photo_title_array);
					$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$photo_id}_{$secret}_".$this->options['tsize'].".jpg"; // Build URL to thumbnail

					$data['tsize'] = $this->options['tsize'];
					$data['url'] = $this->create_url($urlPrefix."$page/photo/$photo_link");
					$data['title'] = $photo_title;
					$data['thumbnail'] = $thumbnail;

					$thumbnails_list[] = $data;

				}
				$count ++;
			}

			if ($this->options['photos_per_page'] != 0) {

				$this->logger->info("tags($tags)");
				$this->logger->info("number_recent->".$this->options['number_recent']);

				if ($tags == '' && $this->options['number_recent'] != -1) {

					$this->logger->info("here");

					$pages = ceil($this->options['number_recent'] / $this->options['photos_per_page']);
				} else {
					$pages = $xpath->getData("/rsp/photos/@pages");
				}

				$this->logger->info("pages($pages)");

				if ($pages > 1) {
					$tpl->set('top_paging', $this->_build_paging($page, $pages, $urlPrefix, 'top'));
					$tpl->set('bottom_paging', $this->_build_paging($page, $pages, $urlPrefix, 'bottom'));
				}
			}

			$tpl->set('thumbnails', $thumbnails_list);
			$tpl->set('url', $this->create_url());
			$tpl->set('photos_label', __('Photos', FALBUM_DOMAIN));
			
			$tpl->set('css_type_thumbnails', $this->options['display_dropshadows']);
			
			$output = $tpl->fetch();

			$this->_set_cached_data("show_tags_thumbnails-$tags-$page", $output);

		}
		return $output;
	}

	/* Shows the selected photo */
	function show_photo($album, $tags, $photo, $page = 1) {

		$this->logger->info("show_photo($album, $tags, $photo, $page)");

		if ($page == '') {
			$page = 1;
		}
		if ($album == '') {
			$album = null;
		}

		$in_photo = $photo;
		$in_album = $album;

		$output = $this->_get_cached_data("show_photo-$in_album-$tags-$in_photo-$page");

		if (!isset ($output)) {

			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('photo', $this->options['style']);
			
			//$tpl->set('page_title', $this->get_page_title());
			
			$tpl->set('album', $album);
			$tpl->set('in_tags', $tags);

			$output = '';

			// Get Prev and Next Photos

			if (!is_null($album) && $album != '') {
				$url_prefix = "album/$album";
				list ($album_id, $album_title) = $this->_get_album_info($album);
				$xpath = $this->_call_flickr('flickr.photosets.getPhotos', array ('photoset_id' => $album_id));
			} else {
				if ($tags == '') {
					$url_prefix = 'show/recent';
					$album_title = __('Recent Photos', FALBUM_DOMAIN);
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
				} else {
					$url_prefix = "tags/$tags";
					$album_title = __('Tags', FALBUM_DOMAIN);
					$album_title = $tags;
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
				}
			}

			//Get navigation info
			if (!isset ($xpath)) {
				return;
			}

			$photo = $this->_get_photo_id($xpath, $photo);
			if (!is_null($album)) {
				$result = $xpath->match('/rsp/photoset/photo');
			} else {
				$total_pages = $xpath->getData('/rsp/photos/@pages');
				$total_photos = $xpath->getData('/rsp/photos/@total');
				$result = $xpath->match('/rsp/photos/photo');
			}

			$prev = $tmp_prev = $next = $photo;
			$prevPage = $nextPage = $page;

			$control = 1;

			$photo_title_array = array ();
			$tmp_prev_title = '';

			$countResult = count($result);

			for ($i = 0; $i < $countResult; $i ++) {
				$photo_id = $xpath->getData($result[$i].'/@id');
				$photo_title = $xpath->getData($result[$i].'/@title');
				$secret = $xpath->getData($result[$i].'/@secret');
				$server = $xpath->getData($result[$i].'/@server');

				$photo_title = $this->_get_link_title($photo_title, $photo_id, $photo_title_array);

				if ($control == 0) {
					// Selected photo was the last one, so this one is the next one
					$next = $photo_id; // Set ID of the next photo
					$next_title = $photo_title;
					$next_sec = $secret; // Set ID of the next photo
					$next_server = $server; // Set ID of the next photo
					break; // Break out of the foreach loop
				}

				if ($photo_id == $photo) {

					// This is the selected photo
					$prev = $tmp_prev; // Set ID of the previous photo
					$prev_title = $tmp_prev_title;
					$control --; // Decrement control variable to tell next iteration of loop that the selected photo was found

					if (is_null($album)) {
						if ($i == 0 && $page > 1) {
							$findPrev = true;
						}
						if (($i == ($countResult -1)) && ($page < $total_pages)) {
							$findNext = true;
						}
					} else {
						if ($this->options['photos_per_page'] > 0) {
							$pages = ($countResult / $this->options['photos_per_page']);

							if ($page > 1 && ($i % $this->options['photos_per_page']) == 0) {
								$prevPage = $prevPage -1;
							}

							if ($page < $pages && (($i +1) % $this->options['photos_per_page']) == 0) {
								$nextPage = $nextPage +1;
							}
						} else {
							$pages = $prevPage = $nextPage = 1;
						}
					}

				}
				$tmp_prev = $photo_id; // Keep the last photo in a temporary variable in case next photo is the selected on
				$tmp_prev_title = $photo_title;
			}

			if ($findPrev) {
				$prevPage = $prevPage -1;

				if ($tags == '') {
					$url_prefix = 'show/recent';
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $prevPage));
				} else {
					$url_prefix = "tags/$tags";
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $prevPage));
				}
				if (!isset ($xpath)) {
					return;
				}

				$result = $xpath->match('/rsp/photos/photo');
				$countResult = count($result);

				$photo_title_array = array ();
				for ($i = 0; $i < $countResult; $i ++) {
					$prev = $xpath->getData($result[$i].'/@id');
					$prev_title = $xpath->getData($result[$i].'/@title');
					$prev_title = $this->_get_link_title($prev_title, $prev, $photo_title_array);
				}
			}

			if ($findNext) {

				$nextPage = $nextPage +1;

				if ($tags == '') {
					$url_prefix = 'show/recent';
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $nextPage));
				} else {
					$url_prefix = "tags/$tags";
					$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $nextPage));
				}
				if (!isset ($xpath)) {
					return;
				}

				$result = $xpath->match('/rsp/photos/photo');

				$next = $xpath->getData($result[0].'/@id'); // Set ID of the next photo
				$next_title = $xpath->getData($result[0].'/@title');
				$next_sec = $xpath->getData($result[0].'/@secret'); // Set ID of the next photo
				$next_server = $xpath->getData($result[0].'/@server'); // Set ID of the next photo

				$photo_title_array = array ();
				$next_title = $this->_get_link_title($next_title, $next, $photo_title_array);
			}

			$xpath = null;

			if ($this->options['friendly_urls'] == 'title') {
				$nav_next = sanitize_title($next_title);
				$nav_prev = sanitize_title($prev_title);
			} else {
				$nav_next = $next;
				$nav_prev = $prev;
			}

			// Display Photo
			$xpath = $this->_call_flickr('flickr.photos.getInfo', array ('photo_id' => $photo));

			if (!isset ($xpath)) {
				return;
			}

			$server = $xpath->getData('/rsp/photo/@server');
			$secret = $xpath->getData('/rsp/photo/@secret');
			$photo_id = $xpath->getData('/rsp/photo/@id');
			$title = $this->_unhtmlentities($xpath->getData('/rsp/photo/title'));
			$date_taken = $xpath->getData('/rsp/photo/dates/@taken');
			$description = $this->_unhtmlentities(nl2br($xpath->getData('/rsp/photo/description')));
			$comments = $xpath->getData('/rsp/photo/comments');

			$imagex = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$photo}_{$secret}";
			$image = $imagex.".jpg"; // Build URL to medium size image
			$original = $imagex."_o.jpg"; // Build URL to original size image

			$next_image = FALBUM_FLICKR_URL_IMAGE."/{$next_server}/{$next}_{$next_sec}.jpg"; // Build URL to medium size image

			$tpl->set('next_image', $next_image);

			//Get Size Data
			$xpath_sizes = $this->_call_flickr('flickr.photos.getSizes', array ('photo_id' => $photo));
			if (!isset ($xpath_sizes)) {
				return;
			}

			$orig_w_m = null;
			$sizes_list = array ();
			if ($xpath_sizes->match("/rsp/sizes/size[@label='Square']/@width")) {
				$width = $xpath_sizes->getData("/rsp/sizes/size[@label='Square']/@width");
				$height = $xpath_sizes->getData("/rsp/sizes/size[@label='Square']/@height");

				$data['image'] = $imagex.'_s.jpg';
				$data['display'] = __('SQ', FALBUM_DOMAIN);
				$data['title'] = __('Square', FALBUM_DOMAIN)." ({$width}x{$height})";
				$data['value'] = 'sq';
				$sizes_list[] = $data;
			}
			if ($xpath_sizes->match("/rsp/sizes/size[@label='Thumbnail']/@width")) {
				$width = $xpath_sizes->getData("/rsp/sizes/size[@label='Thumbnail']/@width");
				$height = $xpath_sizes->getData("/rsp/sizes/size[@label='Thumbnail']/@height");

				$data['image'] = $imagex.'_t.jpg';
				$data['display'] = __('T', FALBUM_DOMAIN);
				$data['title'] = __('Thumbnail', FALBUM_DOMAIN)." ({$width}x{$height})";
				$data['value'] = 't';
				$sizes_list[] = $data;
			}
			if ($xpath_sizes->match("/rsp/sizes/size[@label='Small']/@width")) {
				$width = $xpath_sizes->getData("/rsp/sizes/size[@label='Small']/@width");
				$height = $xpath_sizes->getData("/rsp/sizes/size[@label='Small']/@height");

				$data['image'] = $imagex.'_m.jpg';
				$data['display'] = __('S', FALBUM_DOMAIN);
				$data['title'] = __('Small', FALBUM_DOMAIN)." ({$width}x{$height})";
				$data['value'] = 's';
				$sizes_list[] = $data;
			}
			if ($xpath_sizes->match("/rsp/sizes/size[@label='Medium']/@width")) {
				$width = $xpath_sizes->getData("/rsp/sizes/size[@label='Medium']/@width");
				$height = $xpath_sizes->getData("/rsp/sizes/size[@label='Medium']/@height");
				$orig_w_m = $width;

				$data['image'] = $image;
				$data['display'] = __('M', FALBUM_DOMAIN);
				$data['title'] = __('Medium', FALBUM_DOMAIN)." ({$width}x{$height})";
				$data['value'] = 'm';
				$sizes_list[] = $data;
			}
			if ($xpath_sizes->match("/rsp/sizes/size[@label='Large']/@width")) {
				$width = $xpath_sizes->getData("/rsp/sizes/size[@label='Large']/@width");
				$height = $xpath_sizes->getData("/rsp/sizes/size[@label='Large']/@height");

				$data['image'] = $imagex.'_b.jpg';
				$data['display'] = __('L', FALBUM_DOMAIN);
				$data['title'] = __('Large', FALBUM_DOMAIN)." ({$width}x{$height})";
				$data['value'] = 'l';
				$sizes_list[] = $data;
			}
			if ($xpath_sizes->match("/rsp/sizes/size[@label='Original']/@width")) {
				$width = $xpath_sizes->getData("/rsp/sizes/size[@label='Original']/@width");
				$height = $xpath_sizes->getData("/rsp/sizes/size[@label='Original']/@height");

				$data['image'] = $imagex.'_o.jpg';
				$data['display'] = __('O', FALBUM_DOMAIN);
				$data['title'] = __('Original', FALBUM_DOMAIN)." ({$width}x{$height})";
				$data['value'] = 'o';
				$sizes_list[] = $data;
			}

			$tpl->set('sizes', $sizes_list);

			$tpl->set('home_url', $this->create_url());
			$tpl->set('home_label', __('Photos', FALBUM_DOMAIN));
			$tpl->set('title_url', $this->create_url("$url_prefix/page/{$page}/"));
			$tpl->set('title_label', $album_title);
			$tpl->set('title', $title);

			//Date Taken
			$tpl->set('date_taken', strtr(__('Taken on: #date_taken#', FALBUM_DOMAIN), array ("#date_taken#" => $date_taken)));

			//Tags
			$result = $xpath->match('/rsp/photo/tags/tag');
			$countResult = count($result);
			if ($countResult > 0) {
				$tpl->set('tags_url', $this->create_url('show/tags'));
				$tpl->set('tags_label', __('Tags', FALBUM_DOMAIN));
				$tags_list = array ();
				for ($i = 0; $i < $countResult; $i ++) {
					$value = $xpath->getData($result[$i]);
					$data['url'] = $this->create_url('tags/'.$value.'/');
					$data['tag'] = $value;
					$tags_list[] = $data;
				}
				$tpl->set('tags', $tags_list);
			}

			//Notes
			$result = $xpath->match('/rsp/photo/notes/note');
			$notes_countResult = count($result);
			if ($notes_countResult > 0) {
				if ($this->options['max_photo_width'] > 0 && $this->options['max_photo_width'] < $orig_w_m) {
					$scale = $this->options['max_photo_width'] / $orig_w_m; // Notes are relative to Medium Size
				} else {
					$scale = 1;
				}
				$notes_list = array ();
				for ($i = 0; $i < $notes_countResult; $i ++) {
					$value = nl2br($xpath->getData($result[$i]));
					$x = 5 + $xpath->getData($result[$i].'/@x') * $scale;
					$y = 5 + $xpath->getData($result[$i].'/@y') * $scale;
					$w = $xpath->getData($result[$i].'/@w') * $scale;
					$h = $xpath->getData($result[$i].'/@h') * $scale;

					$data['title'] = htmlentities($value);
					$data['coords'] = ($x).','. ($y).','. ($x + $w -1).','. ($y + $h -1);
					$notes_list[] = $data;
				}
				$tpl->set('notes', $notes_list);
			}

			//Photo			
			if ($next != $photo) {
				$tpl->set('photo_url', $this->create_url("$url_prefix/page/$nextPage/photo/$nav_next/"));
				$tpl->set('photo_title_label', __('Click to view next image', FALBUM_DOMAIN));

			} else {
				$tpl->set('photo_url', $this->create_url("$url_prefix/page/$page/"));
				$tpl->set('photo_title_label', __('Click to return to album', FALBUM_DOMAIN));
			}

			$tpl->set('image', $image);

			if ($notes_countResult > 0) {
				$tpl->set('usemap', " usemap='imgmap'");
			}

			if ($this->options['max_photo_width'] != '0' && $this->options['max_photo_width'] < $orig_w_m) {
				$tpl->set('photo_width', $this->options['max_photo_width']);
			} else {
				$tpl->set('photo_width', $orig_w_m);
			}

			// Navigation		
			if ($prev != $photo) {
				$tpl->set('prev_button', $this->_create_button('pageprev', $this->create_url("$url_prefix/page/$prevPage/photo/$nav_prev/"), "&laquo; ".__('Previous', FALBUM_DOMAIN), __('Previous Photo', FALBUM_DOMAIN), 1));
				
				$tpl->set('prev_page', $prevPage);
				$tpl->set('prev_id', $nav_prev);			
			}
			if ($next != $photo) {
				$tpl->set('next_button', $this->_create_button('pagenext', $this->create_url("$url_prefix/page/$nextPage/photo/$nav_next/"), "&nbsp;&nbsp; ".__('Next', FALBUM_DOMAIN)." &raquo;&nbsp;&nbsp;", __('Next Photo', FALBUM_DOMAIN), 1));
				
				$tpl->set('next_page', $nextPage);
				$tpl->set('next_id', $nav_next);
			}
			$tpl->set('return_button', $this->_create_button('return', $this->create_url("$url_prefix/page/$page/"), __('Album Index', FALBUM_DOMAIN), __('Return to album index', FALBUM_DOMAIN), 1));

			//Description
			$tpl->set('description_orig', $description);
			if (trim($description) == '') {
				$tpl->set('no_description_text', __('click here to add a description', FALBUM_DOMAIN));
				$tpl->set('description', '&nbsp;&nbsp;');
			} else {
				$tpl->set('description', $description);
			}

			//Meta Information		
			//Sizes
			if ($this->options['display_sizes'] == 'true') {
				$tpl->set('sizes_label', __('Available Sizes', FALBUM_DOMAIN));
			}

			// Flickr / Comments				
			if ($comments > 0) {

				$xpath_comments = $this->_call_flickr('flickr.photos.comments.getList', array ('photo_id' => $photo));
				if (isset ($xpath_comments)) {

					$result = $xpath_comments->match('/rsp/comments/comment');
					$notes_countResult = count($result);

					$comments_list = array ();
					for ($i = 0; $i < $notes_countResult; $i ++) {
						$value = nl2br($xpath_comments->getData($result[$i]));
						$author = $xpath_comments->getData($result[$i].'/@author');

						//flickr.people.getInfo
						$xpath_info = $this->_call_flickr('flickr.people.getInfo', array ('user_id' => $author));
						if (isset ($xpath_info)) {
							$data['author_name'] = $xpath_info->getData('/rsp/person/username');
							$data['author_url'] = $xpath_info->getData('/rsp/person/photosurl');
							$data['author_location'] = $xpath_info->getData('/rsp/person/location');
						}

						$data['text'] = $this->_unhtmlentities($value);

						$comments_list[] = $data;
					}  
					$tpl->set('comments', $comments_list);

				}

			}

			$tpl->set('nsid', $this->options['nsid']);
			$tpl->set('photo', $photo);

			$tpl->set('flickr_label', __('See this photo on Flickr', FALBUM_DOMAIN));

			//TODO: dynamicaly get url
			$remote_url = get_settings('siteurl')."/wp-content/plugins/falbum/falbum-remote.php";
			$tpl->set('remote_url', $remote_url);
			$tpl->set('photo_id', $photo_id);
			
			//Exif
			if (strtolower($this->options['display_exif']) == 'true') {
				$tpl->set('exif_data', "{$photo_id}','{$secret}','{$remote_url}");
				$tpl->set('exif_label', __('Show Exif Data', FALBUM_DOMAIN));
			}

			$tpl->set('can_edit', $this->can_edit);

			//Post Helper
			$post_value = '[fa:p:';
			if ($tags != '') {
				$post_value .= "t=$tags,";
			} else
				if ($album != '') {
					$post_value .= "a=$album,";
				}
			if ($page != '' and $page != 1) {
				$post_value .= "p=$page,";
			}
			$post_value .= "id=$photo_id,j=l,s=s,l=p]";
			$tpl->set('post_value', $post_value);
			
			$tpl->set('css_type_photo', $this->options['display_dropshadows']);

			$output = $tpl->fetch();

			$this->_set_cached_data("show_photo-$in_album-$tags-$in_photo-$page", $output);

		}

		return $output;
	}

	/* Shows all the tag cloud */
	function show_tags() {

		$this->logger->info("show_tags()");

		$output = $this->_get_cached_data('show_tags');

		if (!isset ($output)) {

			$xpath = $this->_call_flickr('flickr.tags.getListUserPopular', array ('count' => '500', user_id => $this->options['nsid']));

			if (!isset ($xpath)) {
				return;
			}

			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('tags', $this->options['style']);

			$tpl->set('home_url', $this->create_url());
			$tpl->set('home_label', __(Photos, FALBUM_DOMAIN));
			$tpl->set('tags_label', __('Tags', FALBUM_DOMAIN));

			$result = $xpath->match('/rsp/who/tags/tag');
			$countResult = count($result);

			$tagcount = 0;
			$maxcount = 0;
			for ($i = 0; $i < $countResult; $i ++) {
				$tagcount = $xpath->getData($result[$i].'/@count');
				if ($tagcount > $maxcount) {
					$maxcount = $tagcount;
				}
			}

			$tags_list = array ();

			for ($i = 0; $i < $countResult; $i ++) {

				$tagcount = $xpath->getData($result[$i].'/@count');
				$tag = $xpath->getData($result[$i]);

				if ($tagcount <= ($maxcount * .1)) {
					$tagclass = 'falbum-tag1';
				}
				elseif ($tagcount <= ($maxcount * .2)) {
					$tagclass = 'falbum-tag2';
				}
				elseif ($tagcount <= ($maxcount * .3)) {
					$tagclass = 'falbum-tag3';
				}
				elseif ($tagcount <= ($maxcount * .5)) {
					$tagclass = 'falbum-tag4';
				}
				elseif ($tagcount <= ($maxcount * .7)) {
					$tagclass = 'falbum-tag5';
				}
				elseif ($tagcount <= ($maxcount * .8)) {
					$tagclass = 'falbum-tag6';
				} else {
					$tagclass = 'falbum-tag7';
				}

				$data['url'] = $this->create_url("tags/$tag");
				$data['class'] = $tagclass;
				$data['title'] = $tagcount." ".__('photos', FALBUM_DOMAIN);
				$data['name'] = $tag;

				$tags_list[] = $data;
			}

			$tpl->set('tags', $tags_list);

			$output = $tpl->fetch();

			$this->_set_cached_data('show_tags', $output);

		}
		return $output;
	}

	/* Return EXIF data for the selected photo */
	function show_exif($photo_id, $secret) {

		$this->logger->info("show_exif($photo_id, $secret)");

		$output = $this->_get_cached_data("get_exif-$photo_id-$secret");
		if (!isset ($output)) {

			require_once (FALBUM_PATH.'/Template.class.php');
			$tpl = new Template('exif', $this->options['style']);

			$exif_xpath = $this->_call_flickr('flickr.photos.getExif', array ('photo_id' => $photo_id, 'secret' => $secret), FALBUM_CACHE_EXPIRE_LONG);
			if (!isset ($exif_xpath)) {
				return;
			}

			$result = $exif_xpath->match('//exif');
			$countResult = count($result);

			$exif_list = array ();

			for ($i = 0; $i < $countResult; $i ++) {
				$label = $exif_xpath->getData($result[$i].'/@label');

				if ($i % 2 == 0) {
					$data['class'] = 'even';
				} else {
					$data['class'] = 'odd';
				}

				$data['label'] = $label;

				$r1 = $exif_xpath->match($result[$i].'/clean');
				if (count($r1) > 0) {
					$data['data'] = htmlentities($exif_xpath->getData($result[$i].'/clean'));
				} else {
					$data['data'] = htmlentities($exif_xpath->getData($result[$i].'/raw'));
				}

				$exif_list[] = $data;
			}

			$tpl->set('exif', $exif_list);

			$output = $tpl->fetch();

			$this->_set_cached_data("get_exif-$photo_id-$secret", $output);
		}

		return $output;
	}

	function update_metadata($photo_id, $title, $description) {

		$this->logger->info("update_metadata($photo_id, $title, $description)");

		if ($this->_can_edit()) {

			$xpath = $this->_call_flickr('flickr.photos.setMeta', array ('photo_id' => $photo_id, 'title' => $title, 'description' => $description), FALBUM_DO_NOT_CACHE, true);
			if (!isset ($xpath)) {
				return;
			}

			$this->_clear_cached_data();

			$xpath = $this->_call_flickr('flickr.photos.getInfo', array ('photo_id' => $photo_id));

			if (!isset ($xpath)) {
				return;
			}

			$data['title'] = $xpath->getData('/rsp/photo/title');
			$data['description'] = nl2br($xpath->getData('/rsp/photo/description'));

		}

		return $data;
	}

	/* Function to show recent photos - commonly used in the sidebar */
	function show_recent($num = 5, $style = 0, $size = '') {

		$this->logger->info("show_recent($num, $style, $size)");

		if ($size == '') {
			$size = $this->options['tsize'];
		}

		$output = $this->_get_cached_data("show_recent-$num-$style-$size");

		if (!isset ($output)) {

			$output = '';

			$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'per_page' => $num, 'sort' => 'date-taken-desc'));
			if (!isset ($xpath)) {
				return;
			}

			if ($style == 0) {
				$output .= "<ul class='falbum-recent'>\n";
			} else {
				$output .= "<div class='falbum-recent'>\n";
			}

			$result = $xpath->match('/rsp/photos/photo');
			$countResult = count($result);

			for ($i = 0; $i < $countResult; $i ++) {
				$server = $xpath->getData($result[$i].'/@server');
				$secret = $xpath->getData($result[$i].'/@secret');
				$photo_id = $xpath->getData($result[$i].'/@id');
				$photo_title = $xpath->getData($result[$i].'/@title');

				$photo_link = $photo_id;

				if ($style == 0) {
					$output .= "<li>\n";
				} else {
					$output .= "<div class='falbum-thumbnail".$this->options['display_dropshadows']."'>";
				}

				$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$photo_id}_{$secret}_".$size.".jpg"; // Build URL to thumbnail

				$output .= "<a href='".$this->create_url("show/recent/photo/$photo_link/")."'>";

				$output .= "<img src='$thumbnail' alt=\"".htmlentities($photo_title)."\" title=\"".htmlentities($photo_title)."\" />";
				$output .= "</a>\n";

				if ($style == 0) {
					$output .= "</li>\n";
				} else {
					$output .= "</div>\n";
				}
			}
			if ($style == 0) {
				$output .= "</ul>\n";
			} else {
				$output .= "</div>\n";
			}

			$this->_set_cached_data("show_recent-$num-$style-$size", $output);
		}
		return $output;
	}

	/* Function to show a random selection of photos - commonly used in the sidebar */
	function show_random($num = 5, $tags = '', $style = 0, $size = '') {

		$this->logger->info("show_random($num, $tags, $style, $size)");

		if ($size == '') {
			$size = $this->options['tsize'];
		}

		$output = '';
		$page = 1;

		if ($tags == '') {
			$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page']));
		} else {
			$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
		}

		if (!isset ($xpath)) {
			return;
		}

		$totalPages = $xpath->getData('/rsp/photos/@pages');
		$total = $xpath->getData('/rsp/photos/@total');

		$no_dups = ($total - $num >= 0);

		if ($style == 0) {
			$output .= "<ul class='falbum-random'>\n";
		} else {
			$output .= "<div class='falbum-random'>\n";
		}

		$rand_array = array ();

		for ($j = 0; $j < $num; $j ++) {
			$page = mt_rand(1, $totalPages);
			if ($tags == '') {
				$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
			} else {
				$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
			}

			if (!isset ($xpath)) {
				return;
			}
			$result = $xpath->match('/rsp/photos/photo');
			$countResult = count($result);

			$randPhoto = mt_rand(0, $countResult -1);

			$photo_id = $xpath->getData($result[$randPhoto].'/@id');

			$dup = false;
			if ($no_dups) {
				if (in_array($photo_id, $rand_array)) {
					$dup = true;
					$j --;
				} else {
					$rand_array[] = $photo_id;
				}
			}

			$this->logger->debug("dup->". ($dup ? 't' : 'f'));

			if (!$dup) {

				$server = $xpath->getData($result[$randPhoto].'/@server');
				$secret = $xpath->getData($result[$randPhoto].'/@secret');
				$photo_title = $xpath->getData($result[$randPhoto].'/@title');

				$photo_link = $photo_id;

				if ($style == 0) {
					$output .= "<li>\n";
				} else {
					$output .= "<div class='falbum-thumbnail".$this->options['display_dropshadows']."'>";
				}

				$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$photo_id}_{$secret}_".$size.".jpg"; // Build URL to thumbnail

				if ($tags != '') {
					$output .= "<a href='".$this->create_url("tags/$tags/page/$page/photo/$photo_link/")."'>";
				} else {
					$output .= "<a href='".$this->create_url("show/recent/page/$page/photo/$photo_link/")."'>";
				}

				$output .= "<img src='$thumbnail' alt=\"".htmlentities($photo_title)."\" title=\"".htmlentities($photo_title)."\" class='falbum-recent-thumbnail' />";
				$output .= "</a>\n";
				if ($style == 0) {
					$output .= "</li>\n";
				} else {
					$output .= "</div>\n";
				}
			}

		}
		if ($style == 0) {
			$output .= "</ul>\n";
		} else {
			$output .= "</div>\n";
		}

		return $output;
	}

	function show_album_tn($album, $size = 'm') {

		$this->logger->info("show_album_tn($album)");

		$output = $this->_get_cached_data("show_album_tn-$album");

		if (!isset ($output)) {

			$output = '';

			$xpath = $this->_call_flickr('flickr.photosets.getList', array ("user_id" => $this->options['nsid']));
			if (!isset ($xpath)) {
				return;
			}

			$result = $xpath->match("/rsp/photosets/photoset[@id=$album]");

			$result = $result[0];

			$photos = $xpath->getData($result.'/@photos');

			if ($photos > 0) {
				$id = $xpath->getData($result.'/@id');
				$server = $xpath->getData($result.'/@server');
				$primary = $xpath->getData($result.'/@primary');
				$secret = $xpath->getData($result.'/@secret');
				$title = $this->_unhtmlentities($xpath->getData($result.'/title'));
				$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$primary}_{$secret}_".$size.".jpg";

				$url = $this->create_url("album/$album/");

				$output .= '	<div class=\'falbum-thumbnail'.$this->options['display_dropshadows'].'\'>';
				$output .= "		<a href='$url' title='$title'>";
				$output .= '			<img src="'.$thumbnail.'" alt="" />';
				$output .= '		</a>';
				$output .= '	</div>';
			}

			$this->_set_cached_data("show_album_tn-$album", $output);

		}

		return $output;
	}

	function show_single_photo($album, $tags, $photo, $page, $size, $linkto) {

		$this->logger->info("show_single_photo($album, $tags, $photo, $page, $size, $linkto)");

		$output = $this->_get_cached_data("show_single_photo-$album-$tags-$photo-$page-$size-$linkto");

		if (!isset ($output)) {

			$output = '';

			if ($size == 'sq') {
				$size = '_s';
			}
			elseif ($size == 't') {
				$size = '_t';
			}
			elseif ($size == 's') {
				$size = '_m';
			}
			elseif ($size == 'm') {
				$size = '';
			}
			elseif ($size == 'l') {
				$size = '_b';
			}
			elseif ($size == 'o') {
				$size = '_o';
			}

			// Display Photo
			$xpath = $this->_call_flickr('flickr.photos.getInfo', array ('photo_id' => $photo));
			if (!isset ($xpath)) {
				return;
			}

			$id = $xpath->getData('/rsp/photo/@id');
			$server = $xpath->getData('/rsp/photo/@server');
			$secret = $xpath->getData('/rsp/photo/@secret');
			$title = $this->_unhtmlentities($xpath->getData('/rsp/photo/title'));
			$thumbnail = FALBUM_FLICKR_URL_IMAGE."/{$server}/{$id}_{$secret}".$size.".jpg";

			if ($tags != '') {
				$url_prefix = "tags/$tags";
			} else
				if ($album != '') {
					$url_prefix = "album/$album";
				} else {
					$url_prefix = 'show/recent';
				}

			if (isset ($page)) {
				$url_prefix .= '/page/'.$page;
			}

			if (!($linkto == 'i' || $linkto == 'index')) {			
				$url_prefix .= '/photo/'.$photo;
			}
			
			$url = $this->create_url("$url_prefix");

			$output .= '	<div class=\'falbum-thumbnail'.$this->options['display_dropshadows'].'\'>';
			$output .= "		<a href='$url' title='$title'>";
			$output .= '			<img src="'.$thumbnail.'" alt="" />';
			$output .= '		</a>';
			$output .= '	</div>';

			$this->_set_cached_data("show_single_photo-$album-$tags-$photo-$page-$size-$linkto", $output);

		}

		return $output;

	}

	/* Creates the URLs used in Falbum */
	function create_url($parms = '') {
		if ($parms != '') {
			$element = explode('/', $parms);
			for ($x = 1; $x < count($element); $x ++) {
				$element[$x] = urlencode($element[$x]);
			}
			if (strtolower($this->options['friendly_urls']) == 'false') {
				$parms = '?'.$element[0].'='.$element[1].'&'.$element[2].'='.$element[3].'&'.$element[4].'='.$element[5].'&'.$element[6].'='.$element[7];
				$parms = str_replace('&=', '', $parms);
			} else {
				$parms = implode('/', $element);
			}

			if ($this->options['photos_per_page'] == 0) {
				$parms = preg_replace("`/page/[0-9]+`", "", $parms);
			}

		}
		return htmlspecialchars($this->options['url_root']."$parms");
	}

	function get_page_title($sep = '&raquo;') {

		$this->logger->info("get_page_title($sep)");

		$_GET = array_merge($_POST,$_GET);

		$album = $_GET['album'];
		$photo = $_GET['photo'];
		$page = $_GET['page'];
		$tags = $_GET['tags'];
		$show = $_GET['show'];
		
		$this->logger->info("get_page_title_v($album $photo $page $tags $show)");

		if (!is_null($album)) {
			list ($album_id, $album_title) = $this->_get_album_info($album);
			if (!is_null($photo)) {
				$xpath = $this->_call_flickr('flickr.photosets.getPhotos', array ('photoset_id' => $album_id));
			}
		} else {
			if ($show == 'tags') {
				$album_title = __('Tags', FALBUM_DOMAIN);
			} else
				if ($tags == '') {
					$album_title = __('Recent Photos', FALBUM_DOMAIN);
					if (!is_null($photo)) {
						$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'sort' => 'date-taken-desc', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
					}
				} else {
					$album_title = __('Tags', FALBUM_DOMAIN);
					$album_title = $tags;
					if (!is_null($photo)) {
						$xpath = $this->_call_flickr('flickr.photos.search', array ('user_id' => $this->options['nsid'], 'tags' => $tags, 'tag_mode' => 'all', 'per_page' => $this->options['photos_per_page'], 'page' => $page));
					}
				}
		}

		if (!is_null($photo)) {
			if (!isset ($xpath)) {
				return;
			}
			$photo = $this->_get_photo_id($xpath, $photo);
			//$this->logger->debug("photo-$photo");
			$photo_title = $xpath->getData("//photo[@id='$photo']/@title");
		}

		$title = __(Photos, FALBUM_DOMAIN);
		if (isset ($album_title)) {
			$title .= '&nbsp;'.$sep.'&nbsp;'.$album_title;
		}
		if (isset ($photo_title)) {
			$title .= '&nbsp;'.$sep.'&nbsp;'.$photo_title;
		}

		return $title;
	}

	function get_options() {
		$falbum_options = array ();
		return $falbum_options;
	}

	/* Function that actually makes the flickr calls */
	function _call_flickr($method, $args = array (), $cache_option = FALBUM_CACHE_EXPIRE_SHORT, $post = false) {

		$args = array_merge(array ('method' => $method, 'api_key' => FALBUM_API_KEY), $args);

		if ($this->_show_private() == 'true' || $post == true) {
			$args = array_merge($args, array ('auth_token' => $this->options['token']));
		}

		ksort($args);

		$auth_sig = '';
		foreach ($args as $key => $data) {
			$auth_sig .= $key.$data;
		}

		if ($this->_show_private() == 'true' || $post == true) {
			$api_sig = md5(FALBUM_SECRET.$auth_sig);
		}
	
	    $args = array_merge($args, array ('api_sig' => $api_sig));
	    ksort($args);

		$url = 'http://www.flickr.com/services/rest/';
		if ($post) {
			$resp = $this->_fopen_url($url, $args, $cache_option, true);
		} else {
			$resp = $this->_get_cached_data($url.implode('-', $args), $cache_option);
			if (!isset ($resp)) {
				$resp = $this->_fopen_url($url, $args, $cache_option, false);

				// only cache successful calls to Flickr
				$pos = strrpos($resp, '<rsp stat="ok">');
				if ($pos !== false) {
					$this->_set_cached_data($url.implode('-', $args), $resp, $cache_option);
				}
			}
		}

		$xpath = $this->_parse_xpath($resp);

		return $xpath;
	}

	/* Function that opens the URLS - uses libcurl by default, else falls back to fsockopen */
	function _fopen_url($url, $args = array (), $cache_option = FALBUM_CACHE_EXPIRE_SHORT, $post = false, $fsocket_timeout = 120) {

		$urlParts = parse_url($url);
		$host = $urlParts['host'];
		$port = (isset ($urlParts['port'])) ? $urlParts['port'] : 80;

		if (!extension_loaded('curl')) {
			/* Use fsockopen */
			$this->logger->debug('request - fsockopen<br />'.htmlentities($url));

			$errno = '';
			$errstr = '';

			if (!$fp = @ fsockopen($host, $port, $errno, $errstr, $fsocket_timeout)) {
				$data = __('fsockopen:Flickr server not responding', FALBUM_DOMAIN);
			} else {

				$postdata = implode('&', array_map(create_function('$a', 'return $a[0] . \'=\' . urlencode($a[1]);'), $this->_flattenArray('', $args)));

				$this->logger->debug('request - fsockopen<br />'.htmlentities($url).'<br />'.$postdata);

				//if (isset ($postdata)) {
				$post = "POST $url HTTP/1.0\r\nHost: $host\r\nContent-type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla 4.0\r\nContent-length: ".strlen($postdata)."\r\nConnection: close\r\n\r\n$postdata";
				if (!fwrite($fp, $post)) {
					$data = __('fsockopen:Unable to send request', FALBUM_DOMAIN);
				}
				//} else {
				//	if (!fputs($fp, "GET $url?$postdata	HTTP/1.0\r\nHost:$host\r\n\r\n")) {
				//		$data = __('fsockopen:Unable to send request', FALBUM_DOMAIN);
				//	}
				//}

				$ndata = null;
				stream_set_timeout($fp, $fsocket_timeout);
				$status = socket_get_status($fp);
				while (!feof($fp) && !$status['timed_out']) {
					$ndata .= fgets($fp, 8192);
					$status = socket_get_status($fp);
				}
				fclose($fp);

				// strip headers
				$sData = split("\r\n\r\n", $ndata, 2);
				$ndata = $sData[1];
			}
		} else {
			/* Use curl */
			$this->logger->debug('request - curl<br />'.htmlentities($url));

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_PORT, $port);
			curl_setopt($ch, CURLOPT_TIMEOUT, $fsocket_timeout);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_HEADER, false);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

			$ndata = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);
		}

		$this->logger->debug('response - <br />'.htmlentities($ndata));

		return $ndata;
	}

	/* Function that parses the XML results from the Flickr API (based on torsten@jserver.de's this->$_parse_xpath function found at http://www.php.net/manual/en/ref.xml.php) */
	function _parse_xpath($data) {

		$xPath = null;

		$pos = strpos($data, 'xml');
		if ($pos === false) {
			$this->_error('Invalid response from Flickr:<br />'.$data);
		} else {

			require_once (FALBUM_PATH.'/lib/XPath.class.php');

			$xmlOptions = array (XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => FALSE);
			$xPath = & new XPath(FALSE, $xmlOptions);
			$xPath->setVerbose(0);

			//$xPath->bDebugXmlParse = TRUE;

			if ($xPath->importFromString($data)) {

				$status = $xPath->getData('/rsp/@stat');

				if ($status != "ok") {
					$msg = $xPath->getData('/rsp/err/@msg');
					$code = $xPath->getData('/rsp/err/@code');
					$this->_error("Flickr returned an invalid status code:\n   $code - $msg");
					$xPath = null;
				}

			} else {
				$this->_error('Failed to parse response from Flickr');
			}
		}

		return $xPath;
	}

	/* Function that builds the album pages */
	function _build_paging($page, $pages, $urlPrefix, $pos) {

		$sAlbHeader .= "<div class='falbum-navigationBar' id='pages-$pos'>".__('Page:', FALBUM_DOMAIN)."&nbsp;";

		if ($page > 1 && $pages > 1) {
			$title = strtr(__('Go to previous page (#page#)', FALBUM_DOMAIN), array ("#page#" => $page -1));
			$sAlbHeader .= $this->_create_button('pageprev-', $this->create_url($urlPrefix. ($page -1)), __('Previous', FALBUM_DOMAIN), $title, 0, '_self', true, $pos);
		}

		for ($i = 1; $i <= $pages; $i ++) {
			// We display 1 ... 14 15 16 17 18 ... 29 when there are too many pages
			if ($pages > 10) {

				$mn = $page -3;
				$mx = $page +3;

				if ($i <= $mn) {
					if ($i == 2)
						$sAlbHeader .= "<span class='pagedots'>&nbsp;&hellip;&nbsp;</span>";
					if ($i != 1)
						continue;
				}
				if ($i >= $mx) {
					if ($i == $pages -1)
						$sAlbHeader .= "<span class='pagedots'>&nbsp;&hellip;&nbsp;</span>";
					if ($i != $pages)
						continue;
				}
			}
			$id = "page$i";
			if ($i == $page) {
				$id = 'curpage';
			}

			$sAlbHeader .= $this->_create_button($id, $this->create_url($urlPrefix.$i), $i, '', ($i ? 0 : 1), '_self', true, $pos);
		}
		if ($page < $pages) {
			$title = strtr(__('Go to next page (#page#)', FALBUM_DOMAIN), array ("#page#" => $page +1));
			$sAlbHeader .= $this->_create_button('pagenext', $this->create_url($urlPrefix. ($page +1)), __('Next', FALBUM_DOMAIN), $title, 1, '_self', true, $pos);
		}
		$sAlbHeader .= "</div>\n\n";

		return $sAlbHeader;
	}

	/* Build pretty navigation buttons */
	function _create_button($id, $href, $text, $title, $nSpacer, $target = '_self', $bCallCustom = true, $pos = '') {
		if (substr($id, 0, 1) == '#')
			return '';

		$class = 'buttonLink';
		if ($id == 'curpage') {
			$class = 'curPageLink';
		} else
			if (preg_match('/^page[0-9]+$/', $id)) {
				$class = 'otherPageLink';
			}

		$x = '';

		if ($nSpacer == 1)
			$space = '&nbsp;';
		if ($nSpacer == 2)
			$space = '&nbsp;&nbsp;&nbsp;';

		if (!empty ($space))
			$x .= "<span id='space_{$id}_{$pos}' class='buttonspace'>$space</span>";

		if (!empty ($href))
			$x .= "<a class='$class' href='$href' id='$id-$pos' title='$title'>$text</a>";
		else
			$x .= "<span class='disabledButtonLink' id='$id-$pos' >$text</span>";
		return $x;
	}

	/* Removes all HTML entities - commonly used for the descriptions */
	function _unhtmlentities($string) {
		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'chr(\\1)', $string);
		// replace literal entities
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}

	function _get_link_title($title, $id, & $title_array) {

		if ($this->options['friendly_urls'] == 'title') {

			$s_title = sanitize_title($title);

			if (preg_match("/^[A-Za-z0-9-_]+$/", $s_title)) {
				if (!in_array($s_title, $title_array)) {
					$title_array[$id] = $s_title;
					$link_title = $s_title;
				} else {
					$dup_count = 1;
					while (in_array($s_title.'-'.$dup_count, $title_array)) {
						$dup_count ++;
					}
					$link_title = $s_title.'-'.$dup_count;
					$title_array[$id] = $link_title;
				}
			} else {
				$link_title = $id;
			}

		} else {
			$link_title = $id;
		}
		return $link_title;

	}

	/* et photo id from title if using friendly URLs */
	function _get_photo_id(& $xpath, $photo) {

		if ($this->options['friendly_urls'] == 'title') {

			if ($xpath->match('/rsp/photos')) {
				$result = $xpath->match('/rsp/photos/photo');
			} else {
				$result = $xpath->match('/rsp/photoset/photo');
			}

			$photo_title_array = array ();
			for ($i = 0; $i < count($result); $i ++) {
				$photo_title = sanitize_title($xpath->getData($result[$i].'/@title'));
				if (preg_match("/^[A-Za-z0-9-_]+$/", $photo_title)) {
					$photo_id = $xpath->getData($result[$i].'/@id');
					if (!in_array($photo_title, $photo_title_array)) {
						$photo_title_array[$photo_id] = $photo_title;
					} else {
						$dup_count = 1;
						while (in_array($photo_title.'-'.$dup_count, $photo_title_array)) {
							$dup_count ++;
						}
						$photo_title = $photo_title.'-'.$dup_count;
						$photo_title_array[$photo_id] = $photo_title;
					}
				}
			}
			if (in_array($photo, $photo_title_array)) {
				$photo = array_search($photo, $photo_title_array);
			}
		}

		return $photo;
	}

	/* Get album ID from the album title */
	function _get_album_info($album) {

		$xpath = $this->_call_flickr('flickr.photosets.getList', array ('user_id' => $this->options['nsid']));
		if (!isset ($xpath)) {
			return;
		}

		if ($this->options['friendly_urls'] == 'title') {

			$album_id_array = array ();
			$photosets = $xpath->match('/rsp/photosets/photoset');
			for ($i = 0; $i < count($photosets); $i ++) {

				$album_title = $xpath->getData($photosets[$i].'/title');

				$album_title = sanitize_title($album_title);

				if (preg_match("/^[A-Za-z0-9-_]+$/", $album_title)) {

					$album_id = $xpath->getData($photosets[$i].'/@id');
					if (!in_array($album_title, $album_id_array)) {
						$album_id_array[$album_id] = $album_title;
					} else {
						$count = 1;
						while (in_array($album_title.'-'.$count, $album_id_array)) {
							$count ++;
						}
						$album_id_array[$album_id] = $album_title.'-'.$count;
					}
				}
			}

			if (in_array($album, $album_id_array)) {
				$album_id = array_search($album, $album_id_array);
			} else {
				$album_id = $album;
			}

		} else {
			$album_id = $album;
		}

		$album_title = $xpath->getData("//photoset[@id='$album_id']/title");

		return array ($album_id, $album_title);
	}

	/* Outputs a true or false variable for showing private photos based on the registered user level */
	function _show_private() {
		$PrivateAlbumChoice = false;
		return $PrivateAlbumChoice;
	}

	/* Gets info from Cache Table */
	function _get_cached_data($key, $cache_option = FALBUM_CACHE_EXPIRE_SHORT) {

		require_once (FALBUM_PATH.'/lib/Lite.php');

		$options = array ("cacheDir" => "cache/", "lifeTime" => $cache_option);

		$Cache_Lite = new Cache_Lite($options);
		$data = $Cache_Lite->get($key);

		//$data = null;
		$expired = false;
		return array ($data, $expired);
	}

	/* Function to store the data in the cache table */
	function _set_cached_data($key, $data, $cache_option = FALBUM_CACHE_EXPIRE_SHORT) {

		require_once (FALBUM_PATH.'/lib/Lite.php');

		$options = array ("cacheDir" => "cache/", "lifeTime" => $cache_option);

		$Cache_Lite = new Cache_Lite($options);

		$Cache_Lite->save($data, $key);
	}

	function _clear_cached_data() {

	}

	function _can_edit() {
		return false;
	}

	function _flattenArray($name, $values) {
		if (!is_array($values)) {
			return array (array ($name, $values));
		} else {
			$ret = array ();
			foreach ($values as $k => $v) {
				if (empty ($name)) {
					$newName = $k;
				}
				//elseif ($this->_useBrackets) {
				//	$newName = $name.'['.$k.']';
				//} 
				else {
					$newName = $name;
				}
				$ret = array_merge($ret, $this->_flattenArray($newName, $v));
			}
			return $ret;
		}
	}

	function _error($message) {
		$this->has_error = true;

		$msg .= "<b>$message</b>\n\n";

		$msg .= "Backtrace:\n";
		$backtrace = debug_backtrace();

		foreach ($backtrace as $bt) {
			$args = '';
			if (is_array($bt['args'])) {
				foreach ($bt['args'] as $a) {
					if (!empty ($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
						case 'integer' :
						case 'double' :
							$args .= $a;
							break;
						case 'string' :
							$a = htmlspecialchars(substr($a, 0, 64)). ((strlen($a) > 64) ? '...' : '');
							$args .= "\"$a\"";
							break;
						case 'array' :
							$args .= 'Array('.count($a).')';
							break;
						case 'object' :
							$args .= 'Object('.get_class($a).')';
							break;
						case 'resource' :
							$args .= 'Resource('.strstr($a, '#').')';
							break;
						case 'boolean' :
							$args .= $a ? 'True' : 'False';
							break;
						case 'NULL' :
							$args .= 'Null';
							break;
						default :
							$args .= 'Unknown';
					}
				}
			}

			$file_path = str_replace('\\', '/', $bt['file']);

			$file = substr($file_path, strrpos($file_path, '/') + 1);
			$line = $bt['line'];

			$args = '';

			$msg .= "  $file:{$line} - {$file_path}\n";
			$msg .= "     {$bt['class']}{$bt['type']}{$bt['function']}($args)\n";

		}

		$this->error_detail .= $msg."\n\n";
		$this->logger->err($msg);
	}

	function is_album_page() {
		return defined('FALBUM') && constant('FALBUM');
	}

}
