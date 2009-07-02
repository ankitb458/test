<?php
/*

 $Id: sitemap.php 3486 2005-09-20 20:47:05Z arnee $

 Sitemap Generator for WordPress
 ==============================================================================

 This generator will create a google compliant sitemap of your WordPress blog.
 Currently homepage, posts, static pages, categories and archives are supported.

 The priority of a post depends on its comments. More comments, higher priority!

 Feel free to visit my website under www.arnebrachhold.de or contact me at
 himself@arnebrachhold.de


 Info for WordPress:
 ==============================================================================
 Plugin Name: Google Sitemaps
 Plugin URI: http://www.arnebrachhold.de/2005/06/05/google-sitemaps-generator-v2-final
 Description: This generator will create a Google compliant sitemap of your WordPress blog.
 Version: 2.17 RC fork
 Author: Arne Brachhold
 Author URI: http://www.arnebrachhold.de/


 Contributors:
 ==============================================================================
 Basic Idea 			Michael Nguyen		http://www.socialpatterns.com/
 SQL Improvements		Rodney Shupe		http://www.shupe.ca/
 Japanse Lang. File		Hirosama			http://hiromasa.zone.ne.jp/
 Spanish lang. File		César Gómez Martín	http://www.cesargomez.org/
 Italian lang. File		Stefano Aglietti	http://wordpress-it.it/
 Trad.Chinese  File		Kirin Lin			http://kirin-lin.idv.tw/
 Simpl.Chinese File		june6				http://www.june6.cn/
 Swedish Lang. File		Tobias Bergius		http://tobiasbergius.se/
 Ping Code Template 1	James				http://www.adlards.com/
 Ping Code Template	2	John				http://www.jonasblog.com/
 Bug Report				Brad				http://h3h.net/
 Bug Report				Christian Aust		http://publicvoidblog.de/

 Code, Documentation, Hosting and all other Stuff:
						Arne Brachhold		http://www.arnebrachhold.de/

 Thanks to all contributors and bug reporters! :)


 Release History:
 ==============================================================================
 2005-06-05		1.0		First release
 2005-06-05		1.1		Added archive support
 2005-06-05		1.2		Added category support
 2005-06-05		0.2		Beta: Real Plugin! Static file generation, Admin UI
 2005-06-05		2.0		Various fixes, more help, more comments, configurable filename
 2005-06-07		2.01	Fixed 2 Bugs: 147 is now _e(strval($i)); instead of _e($i); 344 uses a full < ?php instead of < ?
						Thanks to Christian Aust for reporting this :)
 2005-06-07		2.1		Correct usage of last modification date for cats and archives  (thx to Rodney Shupe (http://www.shupe.ca/))
						Added support for .gz generation
						Fixed bug which ignored different post/page priorities
						Should support now different wordpress/admin directories
 2005-06-07		2.11	Fixed bug with hardcoded table table names instead of the $wpd vars
 2005-06-07		2.12	Changed SQL Statement of the categories to get it work on MySQL 3
 2005-06-08		2.2		Added language file support:
						- Japanese Language Files and code modifications by hiromasa <webmaster@hiromasa.zone.ne.jp> http://hiromasa.zone.ne.jp/
						- German Language File by Arne Brachhold <himself@arnebrachhold.de>
 2005-06-14		2.5		Added support for external pages
						Added support for Google Ping
						Added the minimum Post Priority option
						Added Spanish Language File by César Gómez Martín (http://www.cesargomez.org/)
						Added Italian Language File by Stefano Aglietti (http://wordpress-it.it/)
						Added Traditional Chine Language File by Kirin Lin (http://kirin-lin.idv.tw/)
 2005-07-03		2.6		Added support to store the files at a custom location
						Changed the home URL to have a slash at the end
						Required admin-functions.php so the script will work with external calls, wp-mail for example
						Added support for other plugins to add content to the sitemap via add_filter()
 2005-07-20		2.7		Fixed wrong date format in additional pages
						Added Simplified Chinese Language Files by june6 (http://www.june6.cn/)
						Added Swedish Language File by Tobias Bergius (http://tobiasbergius.se/)

 Maybe Todo:
 ==============================================================================
 - Autogenerate priority of categories (by postcount?)
 - Better priority calculator
 - Your wishes :)

 License:
 ==============================================================================
 Copyright 2005  ARNE BRACHHOLD  (email : himself@arnebrachhold.de)

 This program is free software; you can redistribute it and/or modify
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

 Developer Documentation
 ==============================================================================
 Adding other pages to the sitemap via other plugins
  This plugin uses the filter system of wordpress to allow other plugins
  to add urls to the sitemap. Simply add your function with add_filter to
  the list and the plugin will execute yours every time the sitemap is build.
  Use the sm_addUrl function to add your content.

  Sample:
  function your_pages($str) {
	$str.=sm_addUrl("http://wwww","2005-05-05","daily",0.5);
	return $str;
  }
  add_filter("sm_buildmap","your_pages");

  Please double-check that you are using a correct last modified date. Check out
  this code for samples.

 About the pages storage:
  Every external page is represented in a instance of the sm_page class.
  I use an array to store them in the WordPress options table. Note
  that if you want to serialize a class, it must be available BEFORE you
  call unserialize(). So it's very important to set the autoload property
  of the option to false.

 About the pages editor:
  To store modifications to the pages without using session variables,
  i restore the state of the modifications in hidden fields. Based on
  these, the array with the pages from the database gets overwritten.
  It's very important that you call the sm_apply_pages function on
  every request if modifications to the pages should be saved. If
  you dont't all changes will be lost. (So works the Reset Changes button)

 All other methods are commented with phpDoc style.
 The "#region" tags and "#type $example example_class" comments are helpers which
 may be used by your editor.  #region gives the ability to create custom code
 folding areas, #type are type definitions for auto-complete.
*/

//Enable for dev
//error_reporting(E_ALL);


/******** Required files ********/

#region wp-admin/admin-functions.php
/*
 Check if ABSPATH and WPINC is defined, this is done in wp-settings.php
 If not defined, we can't guarante that all required functions are available.
 If defined but get_home_path is not defined, the script is not loaded through
 the admin menu and admin-functions.php is not included.
 Maybe we find another way to get the home paths, so we don't need the admin file.
*/
if(defined("ABSPATH") && defined("WPINC")) {
	define("SM_ACTIVE",true);
} else {
	define("SM_ACTIVE",false);
}
#endregion

/******** Needed classes ********/

#region class sm_page
if(!class_exists("sm_page")) {
	/**
	 * Represents an item in the page list
	 * @author Arne Brachhold <himself@arnebrachhold.de>
	 * @since 2005-06-12
	 */
	class sm_page {
		/**
		 * @var bool $_enabled Sets if page is enabled ans hould be listed in the sitemap
		 * @access private
		 */
		var $_enabled;

		/**
		 * @var string $_url Sets the URL or the relative path to the blog dir of the page
		 * @access private
		 */
		var $_url;

		/**
		 * @var float $_priority Sets the priority of this page
		 * @access private
		 */
		var $_priority;

		/**
		 * @var string $_changeFreq Sets the chanfe frequency of the page. I want Enums!
		 * @access private
		 */
		var $_changeFreq;

		/**
		 * @var int $_lastMod Sets the lastMod of the page as a php timestamp.
		 * @access private
		 */
		var $_lastMod;

		/**
		 * Initialize a new page object
		 *
		 * @param bool $enabled bool Should this page be included in thesitemap
		 * @param string $url The URL or path of the file
		 * @param float $priority The Priority of the page 0.0 to 1.0
		 * @param string $changeFreq The change frequency like daily, hourly, weekly
		 * @param int $lastMod The last mod date as a php timestamp
		 */
		function sm_page($enabled=false,$url="",$priority=0.0,$changeFreq="never",$lastMod=0) {
			$this->setEnabled($enabled);
			$this->setUrl($url);
			$this->setProprity($priority);
			$this->setChangeFreq($changeFreq);
			$this->setLastMod($lastMod);
		}


		/**
		 * Returns if the page should be included in the sitemap
		 * @return bool
		 */
		function getEnabled() {
			return $this->_enabled;
		}

		/**
		 * Sets if the page should be included in the sitemap
		 * @param bool $enabled value;
		 */
		function setEnabled($enabled) {
			$this->_enabled=(bool) $enabled;
		}

		/**
		 * Returns the URL of the page
		 * @return string The URL
		 */
		function getUrl() {
			return $this->_url;
		}

		/**
		 * Sets the URL of the page
		 * @param string $url The new URL
		 */
		function setUrl($url) {
			$this->_url=(string) $url;
		}

		/**
		 * Returns the priority of this page
		 * @return float the priority, from 0.0 to 1.0
		 */
		function getPriority() {
			return $this->_priority;
		}

		/**
		 * Sets the priority of the page
		 * @param float $priority The new priority from 0.1 to 1.0
		 */
		function setProprity($priority) {
			$this->_priority=floatval($priority);
		}

		/**
		 * Returns the change frequency of the page
		 * @return string The change frequncy like hourly, weekly, monthly etc.
		 */
		function getChangeFreq() {
			return $this->_changeFreq;
		}

		/**
		 * Sets the change frequency of the page
		 * @param string $changeFreq The new change frequency
		 */
		function setChangeFreq($changeFreq) {
			$this->_changeFreq=(string) $changeFreq;
		}

		/**
		 * Returns the last mod of the page
		 * @return int The lastmod value in seconds
		 */
		function getLastMod() {
			return $this->_lastMod;
		}

		/**
		 * Sets the last mod of the page
		 * @param int $lastMod The lastmod of the page
		 */
		function setLastMod($lastMod) {
			$this->_lastMod=intval($lastMod);
		}
	}
}
#endregion

#region Default configuration values
$sm_options=array();
$sm_options["sm_b_auto_prio"]=false;			//Use automatic priority calculation
$sm_options["sm_b_filename"]="sitemap.xml";		//Name of the Sitemap file
$sm_options["sm_b_debug"]=true;					//Write debug messages in the xml file
$sm_options["sm_b_xml"]=true;					//Create a .xml file
$sm_options["sm_b_gzip"]=true;					//Create a gzipped .xml file(.gz) file
$sm_options["sm_b_ping"]=true;					//Auto ping Google

$sm_options["sm_b_location_mode"]="auto";		//Mode of location, auto or manual
$sm_options["sm_b_filename_manual"]="";			//Manuel filename
$sm_options["sm_b_fileurl_manual"]="";			//Manuel fileurl

$sm_options["sm_in_home"]=true;					//Include homepage
$sm_options["sm_in_posts"]=true;				//Include posts
$sm_options["sm_in_pages"]=true;				//Include static pages
$sm_options["sm_in_cats"]=true;					//Include categories
$sm_options["sm_in_arch"]=true;					//Include archives

$sm_options["sm_cf_home"]="daily";				//Change frequency of the homepage
$sm_options["sm_cf_posts"]="monthly";			//Change frequency of posts
$sm_options["sm_cf_pages"]="weekly";			//Change frequency of static pages
$sm_options["sm_cf_cats"]="weekly";				//Change frequency of categories
$sm_options["sm_cf_arch_curr"]="daily";			//Change frequency of the current archive (this month)
$sm_options["sm_cf_arch_old"]="yearly";			//Change frequency of older archives

$sm_options["sm_pr_home"]=1.0;					//Priority of the homepage
$sm_options["sm_pr_posts"]=0.8;					//Priority of posts (if auto prio is disabled)
$sm_options["sm_pr_posts_min"]=0.1;				//Minimum Priority of posts, even if autocalc is enabled
$sm_options["sm_pr_pages"]=0.8;					//Priority of static pages
$sm_options["sm_pr_cats"]=0.6;					//Priority of categories
$sm_options["sm_pr_arch"]=0.3;					//Priority of archives

$sm_options["sm_full_auto"]=1;				//Full auto mode
#endregion

#region Load configuration
//Addition sites
$sm_pages=array();

$sm_storedpages=get_option("sm_cpages");

if($sm_storedpages) {
	$sm_pages=$sm_storedpages;
} else {
	//Add the option, Note the autoload=false because when the autoload happens, our class sm_page doesn't exist
	add_option("sm_cpages",$sm_pages,"Storage for custom pages of the sitemap plugin",false);
}

/**
 * @var array Contains all valid values for change frequency
 */
$sm_freq_names=array("always", "hourly", "daily", "weekly", "monthly", "yearly","never");
#endregion

/******** Path and URL functions ********/

function sm_getHomePath() {
	$res="";
	//Check if we are in the admin area -> get_home_path() is available
	if(function_exists("get_home_path")) {
		$res = get_home_path();
	} else {
		//get_home_path() is not available, but we can't include the admin
		//libraries because many plugins check for the "check_admin_referer"
		//function to detect if you are on an admin page. So we have to copy
		//the get_home_path function in our own...
		$home = get_option('home');
		if ( $home != '' && $home != get_option('siteurl') ) {
			$home_path = parse_url($home);
			$home_path = $home_path['path'];
			$root = str_replace($_SERVER["PHP_SELF"], '', $_SERVER["SCRIPT_FILENAME"]);
			$home_path = trailingslashit($root . $home_path);
		} else {
			$home_path = ABSPATH;
		}
		$res = $home_path;
	}
	return $res;
}


#region sm_getXmlUrl
if(!function_exists("sm_getXmlUrl")) {
	/**
	* Returns the URL for the sitemap file
	* @param bool $forceAuto Force the return value to the autodetected value.
	*
	* @return The URL to the Sitemap file
	*/
	function sm_getXmlUrl($forceAuto=false) {
		global $sm_options;

		if(!$forceAuto && $sm_options["sm_b_location_mode"]=="manual") {
			return $sm_options["sm_b_fileurl_manual"];
		} else {
			//URL comes without /
			return get_bloginfo('siteurl') . "/" . sm_go("sm_b_filename");
		}
	}
}
#endregion

#region sm_getZipUrl
if(!function_exists("sm_getZipUrl")) {
	/**
	* Returns the URL for the gzipped sitemap file
	* @param bool $forceAuto Force the return value to the autodetected value.
	*
	* @return The URL to the gzipped Sitemap file
	*/
	function sm_getZipUrl($forceAuto=false) {
		return sm_getXmlUrl($forceAuto) . ".gz";
	}
}
#endregion

#region sm_getXmlPath
if(!function_exists("sm_getXmlPath")) {
	/**
	* Returns the file system path to the sitemap file
	* @param bool $forceAuto Force the return value to the autodetected value.
	*
	* @return The file system path;
	*/
	function sm_getXmlPath($forceAuto=false) {
		global $sm_options;

		if ( file_exists(sm_getHomePath() . sm_go("sm_b_filename")) )
		{
			return sm_getHomePath() . sm_go("sm_b_filename");
		}
		elseif(!$forceAuto && $sm_options["sm_b_location_mode"]=="manual")
		{
			return $sm_options["sm_b_filename_manual"];
		}
		else
		{
			//ABSPATH has a slash
			return sm_getHomePath()
				. 'wp-content/sitemaps/'
				. sm_go("sm_b_filename");
		}
	}
}
#endregion

#region sm_getZipPath
if(!function_exists("sm_getZipPath")) {
	/**
	* Returns the file system path to the gzipped sitemap file
	* @param bool $forceAuto Force the return value to the autodetected value.
	*
	* @return The file system path;
	*/
	function sm_getZipPath($forceAuto=false) {
		return sm_getXmlPath($forceAuto) . ".gz";
	}
}
#endregion

/******** Helper functions ********/

#region sm_go
if(!function_exists("sm_go")) {
	/**
	* Returns the option value for the given key
	*
	* @param $key string The Configuration Key
	* @return mixed The value
	*/
	function sm_go($key) {
		global $sm_options;
		return $sm_options[$key];
	}
}
#endregion

#region sm_freq_names
if(!function_exists("sm_freq_names")) {
	/**
	* Echos option fields for an select field containing the valid change frequencies
	*
	* @param $currentVal The value which should be selected
	* @return all valid change frequencies as html option fields
	*/
	function sm_freq_names($currentVal) {
		global $sm_freq_names;
		foreach($sm_freq_names AS $v) {
			echo "<option value=\"$v\" " . ($v==$currentVal?"selected=\"selected\"":"") .">";
			echo ucfirst(__($v,'sitemap'));
			echo "</option>";
		}
	}
}
#endregion

#region sm_prio_names
if(!function_exists("sm_prio_names")) {
	/**
	* Echos option fields for an select field containing the valid priorities (0- 1.0)
	*
	* @param $currentVal string The value which should be selected
	* @return 0.0 - 1.0 as html option fields
	*/
	function sm_prio_names($currentVal) {
		$currentVal=(float) $currentVal;
		for($i=0.0; $i<=1.0; $i+=0.1) {
			echo "<option value=\"$i\" " . ("$i" == "$currentVal"?"selected=\"selected\"":"") .">";
			_e(strval($i));
			echo "</option>";
		}
	}
}
#endregion

/******** Admin Page functions ********/

#region sm_apply_pages
if(!function_exists("sm_apply_pages")) {
	/**
	* This method will create new page objcts based on the input fields int he POST request
	*/
	function sm_apply_pages() {;
		// Array with all page URLs
		$sm_pages_ur=(!isset($_POST["sm_pages_ur"]) || !is_array($_POST["sm_pages_ur"])?array():$_POST["sm_pages_ur"]);

		//Array with all priorities
		$sm_pages_pr=(!isset($_POST["sm_pages_pr"]) || !is_array($_POST["sm_pages_pr"])?array():$_POST["sm_pages_pr"]);

		//Array with all change frequencies
		$sm_pages_cf=(!isset($_POST["sm_pages_cf"]) || !is_array($_POST["sm_pages_cf"])?array():$_POST["sm_pages_cf"]);

		//Array with all lastmods
		$sm_pages_lm=(!isset($_POST["sm_pages_lm"]) || !is_array($_POST["sm_pages_lm"])?array():$_POST["sm_pages_lm"]);

		//Array where the new pages are stored
		$pages=array();

		//Loop through all defined pages and set their properties into an object
		if(isset($_POST["sm_pages_mark"]) && is_array($_POST["sm_pages_mark"])) {
			for($i=0; $i<count($_POST["sm_pages_mark"]); $i++) {

				$site_url = get_option('home');
				$site_url = preg_replace("/^https?:\/\//i", "", $site_url);
				$site_url = preg_replace("/^www\.|\/+$/i", "", $site_url);
				//var_dump($site_url, $sm_pages_ur[$i], strpos($sm_pages_ur[$i], $site_url));
				if ( strpos($sm_pages_ur[$i], $site_url) !== false )
				{
					//Create new object
					$p=new sm_page();
					if(substr($sm_pages_ur[$i],0,4)=="www.") $sm_pages_ur[$i]="http://" . $sm_pages_ur[$i];
					$p->setUrl($sm_pages_ur[$i]);
					$p->setProprity($sm_pages_pr[$i]);
					$p->setChangeFreq($sm_pages_cf[$i]);
					//Try to parse last modified, if -1 (note ===) automatic will be used (0)
					$lm=(!empty($sm_pages_lm[$i])?strtotime($sm_pages_lm[$i],time()):-1);
					if($lm===-1) $p->setLastMod(0);
					else $p->setLastMod($lm);

					//Add it to the array
					$pages[count($pages)]=$p;
				}
			}
		}
		//Return it, cause I don't care about PHP4 references...
		return $pages;
	}
}
#endregion

#region sm_array_remove
if(!function_exists("sm_array_remove")) {
	/**
	* Removes an element of an array and reorders the indexes
	*
	* @param array $array The array with the values
	* @param object $indice The key which vallue should be removed
	* @return array The modified array
	*/
	function sm_array_remove ($array, $indice) {
		if (array_key_exists($indice, $array)) {
			$temp = $array[0];
			$array[0] = $array[$indice];
			$array[$indice] = $temp;
			array_shift($array);

			for ($i = 0 ; $i < $indice ; $i++)
			{
				$dummy = $array[$i];
				$array[$i] = $temp;
				$temp = $dummy;
			}
		}
		return $array;
	}
}
#endregion

#region sm_options_page
if(!function_exists("sm_options_page")) {
	/**
	* Generated the admin option page and saves the configuration
	*/
	function sm_options_page() {
			//#type $sm_options array
			global $sm_options;
			//#type $sm_pages array
			global $sm_pages;

			//All output should go in this var which get printed at the end
			$message="";

			//Pressed Button: Rebuild Sitemap
			if(!empty($_POST["doRebuild"])) {
				check_admin_referer('google_sitemap');

				$msg = sm_buildSitemap();

				if($msg && is_array($msg)) {
					foreach($msg AS $ms) {
						$message.=$ms . "<br /><br />";
					}
				}
			}
			//Pressed Button: Update Config
    		else if (!empty($_POST['info_update'])) {
				check_admin_referer('google_sitemap');

				foreach($sm_options as $k=>$v) {
					//Check vor values and convert them into their types, based on the category they are in
					if(!isset($_POST[$k])) $_POST[$k]=""; // Empty string will get false on 2bool and 0 on 2float

					//Options of the category "Basic Settings" are boolean, except the filename
					if(substr($k,0,5)=="sm_b_") {
						if($k=="sm_b_filename" || $k=="sm_b_fileurl_manual" || $k=="sm_b_filename_manual") $sm_options[$k]=(string) $_POST[$k];
						else if($k=="sm_b_location_mode") {
							$tmp=(string) $_POST[$k];
							$tmp=strtolower($tmp);
							if($tmp=="auto" || $tmp="manual") $sm_options[$k]=$tmp;
							else $sm_options[$k]="auto";
						} else $sm_options[$k]=(bool) $_POST[$k];
					//Options of the category "Includes" are boolean
					} else if(substr($k,0,6)=="sm_in_") {
						$sm_options[$k]=(bool) $_POST[$k];
					//Options of the category "Change frequencies" are string
					} else if(substr($k,0,6)=="sm_cf_") {
						$sm_options[$k]=(string) strip_tags($_POST[$k]);
					//Options of the category "Priorities" are float
					} else if(substr($k,0,6)=="sm_pr_") {
						$sm_options[$k]=(float) $_POST[$k];
					}
				}

				update_option("sm_options",$sm_options);
				$message.=__('Configuration updated', 'sitemap');

			//Pressed Button: New Page
			} else if(!empty($_POST["sm_pages_new"])) {
				check_admin_referer('google_sitemap');

				//Apply page changes from POST
				$sm_pages=sm_apply_pages();

				//Add a new page to the array with default values
				$p=new sm_page(true,"",0.0,"never",0);
				$sm_pages[count($sm_pages)]=$p;
				$message.=__('A new page was added. Click on &quot;Save page changes&quot; to save your changes.','sitemap');

			//Pressed Button: Save pages
			} else if(!empty($_POST["sm_pages_save"])) {
				check_admin_referer('google_sitemap');

				//Apply page changes from POST
				$sm_pages=sm_apply_pages();

				//Store in the database
				if(update_option("sm_cpages",$sm_pages)) $message.=__("Pages saved",'sitemap');
				else $message.=__("Error while saving pages",'sitemap');

			//Pressed Button: Delete page
			} else if(!empty($_POST["sm_pages_del"])) {
				check_admin_referer('google_sitemap');

				//Apply page changes from POST
				$sm_pages=sm_apply_pages();

				//the selected page is stored in value of the radio button
				$i=intval($_POST["sm_pages_action"]);

				//Remove the page from the array
				$sm_pages= sm_array_remove($sm_pages,$i);
				$message.=__('The page was deleted. Click on &quot;Save page changes&quot; to save your changes.','sitemap');

			//Pressed Button: Clear page Changes
			} else if(!empty($_POST["sm_pages_undo"])) {
				check_admin_referer('google_sitemap');

				//In all other page changes, we do the sm_apply_pages functions. Here we don't, so we got the original settings from the db

				$message.=__('You changes have been cleared.','sitemap');
			}

			//Print out the message to the user, if any
			if($message!="") {
				?>				<div class="updated"><strong><p><?php
				echo $message;
				?></p></strong></div><?php
			}
			?>
		<div class=wrap>
			<form method="post" action="">
				<h2><?php _e('Sitemap Generator', 'sitemap') ?></h2>

				<!-- Rebuild Area -->
				<fieldset name="sm_rebuild" class="options">
					<legend><?php _e('Manual rebuild', 'sitemap') ?></legend>
					<?php _e('If you want to build the sitemap without editing a post, click on here!', 'sitemap') ?><br />
					<input type="submit" id="doRebuild" name="doRebuild" Value="<?php _e('Rebuild Sitemap','sitemap'); ?>" />
				</fieldset>

				<!-- Pages area -->
				<fieldset name="sm_pages"  class="options">
					<legend><?php _e('Additional pages', 'sitemap') ?></legend>
					<?php
					_e('Here you can specify files or URLs which should be included in the sitemap, but do not belong to your Blog/WordPress.<br />For example, if your blog is www.foo.com and you\'ve a separate page located at www.foo.com/page.htm you might want to include it.','sitemap');
					echo "<ul><li>";
					echo "<strong>" . __('Note','sitemap'). "</strong>: ";
					_e("You cannot add pages that are NOT in the blog directory! Invalid pages will be ignored.",'sitemap');
					echo "</li><li>";
					echo "<strong>" . __('URL to the page','sitemap'). "</strong>: ";
					_e("Enter the URL to the page. Examples: http://www.foo.com/index.html or www.foo.com/home ",'sitemap');
					echo "</li><li>";
					echo "<strong>" . __('Priority','sitemap') . "</strong>: ";
					_e("Choose the priority of the page relative to the other pages. For example, your homepage might have a higher priority than your imprint.",'sitemap');
					echo "</li><li>";
					echo "<strong>" . __('Last Changed','sitemap'). "</strong>: ";
					_e("Enter the date of the last change as YYYY-MM-DD (2005-12-31 for example) (optional).",'sitemap');

					echo "</li></ul>";
					?>					<table width="100%" cellpadding="3" cellspacing="3">
						<tr>
							<th scope="col"><?php _e('URL to the page','sitemap'); ?></th>
							<th scope="col"><?php _e('Priority','sitemap'); ?></th>
							<th scope="col"><?php _e('Change Frequency','sitemap'); ?></th>
							<th scope="col"><?php _e('Last Changed','sitemap'); ?></th>
							<th scope="col"><?php _e('#','sitemap'); ?></th>
						</tr>
						<?php
							if(count($sm_pages)>0) {
								$class="";
								for($i=0; $i<count($sm_pages); $i++) {
									$v=$sm_pages[$i];
									//#type $v sm_page
									$class = ('alternate' == $class) ? '' : 'alternate';
									echo "<input type=\"hidden\" name=\"sm_pages_mark[$i]\" value=\"true\" />";
									echo "<tr class=\"$class\">";
									echo "<td><input type=\"textbox\" name=\"sm_pages_ur[$i]\" style=\"width:95%\" value=\"" . $v->getUrl() . "\" /></td>";
									echo "<td width=\"150\"><select name=\"sm_pages_pr[$i]\" style=\"width:95%\">";
									echo sm_prio_names($v->getPriority());
									echo "</select></td>";
									echo "<td width=\"150\"><select name=\"sm_pages_cf[$i]\" style=\"width:95%\">";
									echo sm_freq_names($v->getChangeFreq());
									echo "</select></td>";
									echo "<td width=\"150\"><input type=\"textbox\" name=\"sm_pages_lm[$i]\" style=\"width:95%\" value=\"" . ($v->getLastMod()>0?date("Y-m-d",$v->getLastMod()):"") . "\" /></td>";
									echo "<td width=\"5\"><input type=\"radio\" name=\"sm_pages_action\" value=\"$i\" /></td>";
									echo "</tr>";
								}
							} else {
								?><tr>
									<td colspan="5"><?php _e('No pages defined.','sitemap') ?></td>
								</tr><?php
							}
						?>					</table>
					<div>
						<div style="float:left; width:70%">
							<input type="submit" name="sm_pages_new" value="<?php _e("Add new page",'sitemap'); ?>" />
							<input type="submit" name="sm_pages_save" value="<?php _e("Save page changes",'sitemap'); ?>" />
							<input type="submit" name="sm_pages_undo" value="<?php _e("Undo all page changes",'sitemap'); ?>" />
						</div>
						<div style="width:30%; text-align:right; float:left;">
							<input type="submit" name="sm_pages_del" value="<?php _e("Delete marked page",'sitemap'); ?>" />
						</div>
					</div>
				</fieldset>
				<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('google_sitemap'); ?>
			</form>
		</div> <?php
	}
}
#endregion

#region sm_reg_admin
if(!function_exists("sm_reg_admin")) {
	/**
	* Add the options page in the admin menu
	*/
	function sm_reg_admin() {
		if ( !function_exists('get_site_option') )
		{
			if (function_exists('add_options_page')) {
				add_options_page('Sitemap Generator', 'Sitemap', 'manage_options', __FILE__, 'sm_options_page');
			}
		}
	}
}
#endregion

/******** Sitemap Builder Helper functions ********/

#region sm_addUrl
if(!function_exists("sm_addUrl")) {
	/**
	Adds a url to the sitemap

	@param $loc string The location (url) of the page
	@param $lastMod string THe last Modification time in ISO 8601 format
	@param $changeFreq string The change frequenty of the page, Valid values are "always", "hourly", "daily", "weekly", "monthly", "yearly" and "never".
	@param $priorty float The priority of the page, between 0.0 and 1.0

	@return string The URL node
	*/
	function sm_addUrl($loc,$lastMod,$changeFreq="monthly",$priority=0.5) {
		global $sm_freq_names;
		$s="";
		$s.= "\t<url>\n";
		$s.= "\t\t<loc>$loc</loc>\n";
		if(!empty($lastMod) && $lastMod!="0000-00-00T00:00:00+00:00") $s.= "\t\t<lastmod>$lastMod</lastmod>\n";
		if(!empty($changeFreq) && in_array($changeFreq,$sm_freq_names)) $s.= "\t\t<changefreq>$changeFreq</changefreq>\n";
		if($priority!==false && $priority!=="") $s.= "\t\t<priority>$priority</priority>\n";
		$s.= "\t</url>\n";
		return $s;
	}
}
#endregion

#region sm_getComments
if(!function_exists("sm_getComments")) {
	/**
	* Retrieves the number of comments of a post in a asso. array
	* The key is the postID, the value the number of comments
	*
	* @return array An array with postIDs and their comment count
	*/
	function sm_getComments() {
		global $wpdb;
		$comments=array();

		//Query comments and add them into the array
		$commentRes=$wpdb->get_results("SELECT `comment_post_ID` as `post_id`, COUNT(comment_ID) as `comment_count`, comment_approved FROM `" . $wpdb->comments . "` GROUP BY `comment_post_ID`");
		if($commentRes) {
			foreach($commentRes as $comment) {
				$comments[$comment->post_id]=$comment->comment_count;
			}
		}
		return $comments;
	}
}
#endregion

#region sm_countComments
if(!function_exists("sm_countComments")) {
	/**
	* Calculates the full number of comments from an sm_getComments() generated array
	*
	* @param $comments array The Array with posts and c0mment count
	* @see sm_getComments
	* @return The full number of comments
	*/
	function sm_countComments($comments) {
		$commentCount=0;
		foreach($comments AS $k=>$v) {
			$commentCount+=$v;
		}
		return $commentCount;
	}
}
#endregion

#region sm_buildSitemap
if(!function_exists("sm_buildSitemap")) {
	/**
	Builds the sitemap and writes it into a xml file.

	@return array An array with messages such as failed writes etc.
	*/
	function sm_buildSitemap() {
		global $wpdb, $sm_pages;

		//Return messages to the user in frontend
		$messages=array();

		//Debug mode?
		//$debug=sm_go("sm_b_debug");
		$debug = false;

		//Content of the XML file
		$s='<?xml version="1.0" encoding="UTF-8"' . '?' . '>'. "\n";

		//WordPress powered... and me! :D
		//$s.="<!-- generator=\"wordpress/" . get_bloginfo('version') . "\" -->\n";
		//$s.="<!-- sitemap-generator-url=\"http://www.arnebrachhold.de\" sitemap-generator-version=\"2.7.1\"  -->\n";

		//All comments as an asso. Array (postID=>commentCount)
		$comments=(sm_go("sm_b_auto_prio")?sm_getComments():array());

		//Full number of comments
		$commentCount=sm_countComments($comments);

		if($debug && sm_go("sm_b_auto_prio")) {
			$s.="<!-- Debug: Total comment count: " . $commentCount . " -->\n";
		}

		//Go XML!
		$s.='<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">'. "\n";

		//Add the home page (WITH a slash!!)
		if(sm_go("sm_in_home")) {
			$s.=sm_addUrl(trailingslashit(get_bloginfo('url')),mysql2date('Y-m-d\TH:i:s+00:00', get_lastpostmodified('GMT'), false),sm_go("sm_cf_home"),sm_go("sm_pr_home"));
		}

		//Add the posts
		if(sm_go("sm_in_posts")) {
			if($debug) $s.="<!-- Debug: Start Postings -->\n";

			//Retrieve all posts and static pages (if enabled)
			$postRes=$wpdb->get_results("
				SELECT `ID` ,`post_modified`, `post_date`, `post_status`, `post_type`
				FROM `" . $wpdb->posts . "`
				WHERE
				(
				post_status = 'publish'
				" . ( sm_go("sm_in_pages")
					? "AND post_type IN ('post', 'page')"
					: "AND post_type IN ('post')"
					)
				. "
				)
				" . ( ( defined('sem_home_page_id') && sem_home_page_id )
					? ( "
				AND ID <> " . sem_home_page_id )
					: ''
					)
				. "
				ORDER BY post_modified DESC");

			$minPrio=sm_go("sm_pr_posts_min");

			if($postRes) {
				//Count of all posts
				$postCount=count($postRes);

				//Cycle through all posts and add them
				foreach($postRes as $post) {
					//Default Priority if auto calc is disabled
					$prio=0;
					if($post->post_status=="static" || $post->post_type == 'page') {
						//Priority for static pages
						$prio=sm_go("sm_pr_pages");
					} else {
						//Priority for normal posts
						$prio=sm_go("sm_pr_posts");
					}

					//If priority calc is enabled, calc (but only for posts)!
					if(sm_go("sm_b_auto_prio") && ( $post->post_status!="static" && $post->post_type != "page")) {
						//Comment count for this post
						$cmtcnt=(array_key_exists($post->ID,$comments)?$comments[$post->ID]:0);

						//Percentage of comments for this post
						$prio=($cmtcnt>0&&$commentCount>0?round(($cmtcnt*100/$commentCount)/100,1):0);

						if($debug) {
							$s.="<!-- Debug: Priority report of postID " . $post->ID . ": Comments: " . $cmtcnt . " of " . $commentCount . " = " . $prio . " points -->\n";
						}
					}

					if($post->post_status!="static" && $post->post_type!='page' && !empty($minPrio) && $prio<$minPrio) {
						$prio=sm_go("sm_pr_posts_min");
					}

					//Add it
					$s.=sm_addUrl(get_permalink($post->ID),mysql2date('Y-m-d\TH:i:s+00:00', (!empty($post->post_modified) && $post->post_modified!='0000-00-00 00:00:00'?$post->post_modified:$post->post_date), false),sm_go((($post->post_status=="static"||$post->post_type=='page')?"sm_cf_posts":"sm_cf_pages")),$prio);
				}
			}
			if($debug) $s.="<!-- Debug: End Postings -->\n";
		}

		//Add the cats
		if(sm_go("sm_in_cats")) {
			if($debug) $s.="<!-- Debug: Start Cats -->\n";

			//Add Categories... Big thanx to Rodney Shupe (http://www.shupe.ca) for the SQL
			$catsRes=$wpdb->get_results("
				SELECT	term_id AS ID,
						MAX(post_modified) AS last_mod
				FROM `" . $wpdb->posts . "` p
				INNER JOIN `" . $wpdb->term_relationships . "` pc
				ON p.ID = pc.object_id
				INNER JOIN `" . $wpdb->term_taxonomy . "` c
				ON pc.term_taxonomy_id = c.term_taxonomy_id
				AND		c.taxonomy = 'category'
				WHERE	post_status = 'publish'
				AND		post_type = 'post'
				GROUP BY term_id
				");
			if($catsRes) {
				foreach($catsRes as $cat) {
					$s.=sm_addUrl(get_category_link($cat->ID),mysql2date('Y-m-d\TH:i:s+00:00', $cat->last_mod, false),sm_go("sm_cf_cats"),sm_go("sm_pr_cats"));
				}
			}

//
// todo: add tags
//


			if($debug) $s.="<!-- Debug: End Cats -->\n";
		}
		//Add the archives
		if(sm_go("sm_in_arch")) {
			if($debug) $s.="<!-- Debug: Start Archive -->\n";
			$now = current_time('mysql');
			//Add archives...  Big thanx to Rodney Shupe (http://www.shupe.ca) for the SQL
			$arcresults = $wpdb->get_results("
			SELECT DISTINCT
					YEAR(post_date) AS `year`,
					MONTH(post_date) AS `month`,
					MAX(post_date) as last_mod,
					count(ID) as posts
			FROM	$wpdb->posts
			WHERE	post_date < '$now'
			AND		post_status = 'publish'
			AND		post_type = 'post'
			GROUP BY YEAR(post_date), MONTH(post_date)
			ORDER BY post_date DESC
			");
			//$arcresults = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts WHERE post_date < '$now' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC");
			if ($arcresults) {
				foreach ($arcresults as $arcresult) {

					$url  = get_month_link($arcresult->year,   $arcresult->month);
					$changeFreq="";

					//Archive is the current one
					if($arcresult->month==date("n") && $arcresult->year==date("Y")) {
						$changeFreq=sm_go("sm_cf_arch_curr");
					} else { // Archive is older
						$changeFreq=sm_go("sm_cf_arch_old");
					}

					$s.=sm_addUrl($url,mysql2date('Y-m-d\TH:i:s+00:00', $arcresult->last_mod, false),$changeFreq,sm_go("sm_pr_arch"));
				}
			}
			if($debug) $s.="<!-- Debug: End Archive -->\n";
		}

		//Add the custom pages
		if($debug) $s.="<!-- Debug: Start Custom Pages -->\n";
		if($sm_pages && is_array($sm_pages) && count($sm_pages)>0) {
			//#type $page sm_page
			foreach($sm_pages AS $page) {
				$s.=sm_addUrl($page->GetUrl(),($page->getLastMod()>0?date('Y-m-d\TH:i:s+00:00',$page->getLastMod()):0),$page->getChangeFreq(),$page->getPriority());
			}
		}
		if($debug) $s.="<!-- Debug: End Custom Pages -->\n";

		if($debug) $s.="<!-- Debug: Start additional urls -->\n";

		$s = apply_filters("sm_buildmap",$s);

		if($debug) $s.="<!-- Debug: End additional urls -->\n";

		$s.="</urlset>";

		$pingUrl="";

		if ( !file_exists(sm_getHomePath() . 'wp-content/sitemaps') )
		{
			if ( !@mkdir(sm_getHomePath() . 'wp-content/sitemaps') )
			{
				$messages[count($messages)] = __('Could not create cache directory');
			}
			else
			{
				@chmod(sm_getHomePath() . 'wp-content/sitemaps', 0777);
			}
		}


		//Write normal sitemap file
		if(sm_go("sm_b_xml")) {
			$fileName = sm_getXmlPath();

			$f=@fopen($fileName,"w");
			if($f) {
				if(fwrite($f,$s)) {
					$pingUrl=sm_getXmlUrl();
					$messages[count($messages)]=__("Successfully built sitemap file:",'sitemap') . "<br />" . "- " .  __("URL:",'sitemap') . " <a href=\"" . sm_getXmlUrl() . "\">" . sm_getXmlUrl() . "</a><br />- " . __("Path:",'sitemap') . " " . sm_getXmlPath();
				}
				fclose($f);
			} else {
				$messages[count($messages)]=str_replace("%s",sm_getXmlPath(),__("Could not write into %s",'sitemap'));
			}
		}

		//Write gzipped sitemap file
		if(sm_go("sm_b_gzip")===true && function_exists("gzencode")) {
			$fileName = sm_getZipPath();
			$f=@fopen($fileName,"w");
			if($f) {
				if(fwrite($f,gzencode($s))) {
					$pingUrl=sm_getZipUrl();
					$messages[count($messages)]=__("Successfully built gzipped sitemap file:",'sitemap') . "<br />" . "- " .  __("URL:",'sitemap') . " <a href=\"" . sm_getZipUrl() . "\">" . sm_getZipUrl() . "</a><br />- " . __("Path:",'sitemap') . " " . sm_getZipPath();
				}
				fclose($f);
			} else {
				$messages[count($messages)]=str_replace("%s",sm_getZipPath(),__("Could not write into %s",'sitemap'));
			}
		}

		//Ping Google
		if(sm_go("sm_b_ping") && $pingUrl!="") {
			$pingUrl="http://www.google.com/webmasters/sitemaps/ping?sitemap=" . urlencode($pingUrl);
			$pingres=@wp_remote_fopen($pingUrl);

			if($pingres==NULL || $pingres===false) {
				$messages[count($messages)]=str_replace("%s","<a href=\"$pingUrl\">$pingUrl</a>",__("Could not ping to Google at %s",'sitemap'));
			} else {
				$messages[count($messages)]=str_replace("%s","<a href=\"$pingUrl\">$pingUrl</a>",__("Successfully pinged Google at %s",'sitemap'));
			}
		}

		//done...
		return $messages;
	}
}
#endregion

function sm_update_sitemap($post_ID)
{
	if (wp_verify_nonce($_REQUEST['sitemap'], 'sitemap'))
	{
		sm_buildSitemap();
	}
	return ($post_ID);
}

function sitemap_admin_hook()
{
	echo '<input type="hidden" name="sitemap" id="sitemap" value="' . wp_create_nonce('sitemap') . '" />';
}

add_action('edit_form_advanced', 'sitemap_admin_hook');

/******** Other Stuff ********/

#region Register to WordPress API
//Loading language file...
//load_plugin_textdomain('sitemap');
//Hmm, doesn't work if the plugin file has its own directory.
//Let's make it our way... load_plugin_textdomain() searches only in the wp-content/plugins dir.
$sm_locale = get_locale();
$sm_mofile = dirname(__FILE__) . "/sitemap-$sm_locale.mo";
load_textdomain('sitemap', $sm_mofile);

//Register the sitemap creator to wordpress...
add_action('admin_menu', 'sm_reg_admin');

//Register to various events... @WordPress Dev Team: I wish me a 'public_content_changed' action :)
//Only register of the SM_ACTIVE constant is defined!
if(defined("SM_ACTIVE") && SM_ACTIVE===true) {
	//If a new post gets published
	add_action('publish_post', 'sm_update_sitemap');

	//Existing post gets edited (published or not)
	add_action('edit_post', 'sm_update_sitemap');

	//Existing posts gets deleted (published or not)
	add_action('delete_post', 'sm_update_sitemap');
}
#endregion


function sm_serve_sitemap()
{
	if ( strpos($_SERVER['REQUEST_URI'], '/sitemap.xml') !== false )
	{
		$sitemap = preg_replace("/.*\/|\?.*/", '', $_SERVER['REQUEST_URI']);

		$sitemap = ABSPATH
				. 'wp-content/sitemaps/'
				. $sitemap;

		if ( file_exists($sitemap) )
		{
			// Reset WP

			$GLOBALS['wp_filter'] = array();

			while ( @ob_end_clean() );

			ob_start();

			header( 'Content-Type:text/xml; charset=utf-8' ) ;
			readfile($sitemap);
			die;
		}
	}
} # end sm_serve_sitemap()

add_action('init', 'sm_serve_sitemap', 1000);
?>