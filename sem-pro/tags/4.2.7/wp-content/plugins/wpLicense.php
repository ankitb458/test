<?php
/*
Plugin Name: wpLicense
Plugin URI: http://wiki.creativecommons.org/WpLicense
Description: Allows selection of a <a href="http://creativecommons.org">Creative Commons</a> license for blog content.
Version: 0.7.5
Author: Nathan R. Yergler <nathan@creativecommons.org>
Author URI: http://wiki.creativecommons.org/User:NathanYergler
*/

/*  Copyright 2005-2006,
    Creative Commons (email : software@creativecommons.org),
    Nathan R. Yergler (email : nathan@creativecommons.org)

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
*/

require(dirname(__FILE__) . '/wpLicense/ccwsclient.php');

/* Template Functions */

function licenseRdf($display=1) {
   if ($display == 1) {
      echo get_option('cc_content_license_rdf');
   } else {
      return get_option('cc_content_license_rdf');
   }

} // licenseRdf

function licenseHtml($display=1) {
   if ($display == 1) {
      echo str_replace('\"', '"', get_option('cc_content_license_html'));
   } else {
      return str_replace('\"', '"', get_option('cc_content_license_html'));
   }

} // licenseHtml

function licenseUri() {
   echo get_option('cc_content_license_uri');
} // licenseUri

function isLicensed() {
  // returns True if a license is selected
  return get_option('cc_content_license');
} // isLicensed

function cc_showLicenseHtml() {
  if (get_option('cc_include_footer')) {
     if (isLicensed()) {
        echo '<div class="license_block">'.licenseHtml(0).'</div>';
     }
  }
} // cc_showLicenseHtml

function cc_rss2_ns() {

echo 'xmlns:creativeCommons="http://backend.userland.com/creativeCommonsRssModule"';

} // cc_rss2_ns

function cc_rss2_head() {
     if (isLicensed()) {
     	echo '<creativeCommons:license>'.licenseUri().'</creativeCommons:license>';
     }

} // cc_rss2_head

function cc_atom_head() {
     if (isLicensed()) {
     	echo '<link rel="license" type="text/html" href="'.licenseUri().'" />';
     }

} // cc_atom_head

/* Support functions */

function supportedPHP() {

  if (function_exists('version_compare')) {
     if (version_compare(phpversion(), "4.3.0", ">=")) {
        // you're on 4.3.0 or later
        return TRUE;
     } else {
        // you're not
        return FALSE;
     }
  } else {
     // we're running a version prior to 4.1.0, by definition unsupported
     return FALSE;
  }

} // supportedPHP


/* Admin functions */

function license_options() {
   global $post_msg;
echo '
<div class="wrap">
         <div id="statusmsg">'.$post_msg.'</div>
         <h2>Content License</h2>
<p>This page allows you to choose a
<a href="http://creativecommons.org">Creative Commons</a> license
for your content.  If you select "Include License Badge", the default
Creative Commons badge, link and RDF will be included in the standard footer.
</p>';

// make sure we're running on a support PHP configuration
if (supportedPHP() === FALSE) {
   echo '<p style="color:red;"><strong>wpLicense requires PHP 4.3.0 or later;
        you seem to be running version ' . phpversion() . '.</strong></p>';

   // close the div
   echo '</div>';
   return;
}

echo '<p>If you wish to display the license information in a non-standard
way, or in a custom location, you may do so using
functions provided by the plugin
<a href="http://wiki.creativecommons.org/WpLicense_Function_Reference"
   target="_blank">
(function reference)</a>.</p>

         <div id="license_selection" class="wrap">
            <form name="license_options" method="post"
                  action="' . $_SERVER[REQUEST_URI] . '">

            <input name="submitted"    type="hidden" value="wplicense" />
            <input name="license_name" type="hidden"
                   value="'.get_option('cc_content_license').'" />
            <input name="license_uri"  type="hidden"
                   value="'.get_option('cc_content_license_uri').'" />
            <input name="license_rdf"  type="hidden"
                   value="" />
            <input name="license_html" type="hidden"
                   value="" />
            <input name="blog_url" id="blog_url" type="hidden"
                   value="'.get_bloginfo('wpurl').'" />
            <input name="remove_license" type="hidden"
                   value="false" />


            <table>
               <tr><th>Current License:</th><td>
         <a href="'.get_option('cc_content_license_uri').'">'.get_option('cc_content_license').'</a>';

if (!fopenEnabled ()) {

$partner_href = "http://creativecommons.org/license/?partner=wplicense&jurisdiction_choose=1&exit_url=" . ($_SERVER['HTTPS']?"https://":"http://")  . $_SERVER['SERVER_NAME'] .":" . $_SERVER['SERVER_PORT'] . $_SERVER['SCRIPT_NAME'] . "?page=wpLicense.php%26submitted=from_partner%26license_uri=[license_url]%26license_name=[license_name]%26license_image=[license_button]";

echo '
         (<a id="partnerChooser" href="'.$partner_href.'">'.(get_option('cc_content_license')?'change':'select').'</a>)
         (<a id="removeLicense" href="#">remove</a>)

</td></tr>
<tr><td>&nbsp;</td>
<td><em>Your webhost has disabled remote url access via PHP.</em>
<p>
  You can still select a license, but you will be temporarily redirected from the WordPress administrative interface to complete the process.  No personal information will be passed to Creative Commons.</p></td>
</tr>
';

} else {

echo '
         (<a id="showLicenseChooser" href="#">'.(get_option('cc_content_license')?'change':'select').'</a>)
         (<a id="removeLicense" href="#">remove</a>)

               </td></tr>

               <tr><th>&nbsp;</th><td>

<div id="licenseSelector" name="licenseSelector"
     class="wrap" style="display:none;">
<table>
               <tr><th><nobr>Selected&nbsp;License:</nobr></th>
                   <td id="newlicense_name">(none)</td>
               <td>
               <img id="working"
                    src="'.get_bloginfo('wpurl').'/wp-content/plugins/wpLicense/Throbber-small.gif"
                    style="display:none; float:right; margin:0px;padding;0px;"/>
                   </td>
               </tr>
               <tr><th><nobr>License type:</nobr></th>
                 <td colspan="2">
             <select id="licenseClass">
                          <option id="-">(none)</option>
';
    $license_classes = licenseClasses();

    foreach($license_classes as $key => $l_id) {
          echo '<option value="' . $key . '" >' . $l_id . '</option>';
    }; // for each...

  echo '          </select>
</td></tr>
<tr><td>&nbsp;</td>
<td colspan="2">
         <div id="license_options" class="wrap">
         </div>
</td></tr>
</table>
</div>

                 </td></tr>

<!--             <tr><th>Include per-post license information?</th>
                 <td><input type="checkbox" name="perPost" '.(get_option('cc_per_post')=='1'?"checked":"").'" ></td>
             </tr> -->
';
}

echo '

              <tr><td colspan="2">&nbsp;</td></tr>
              <tr><th>Include license badge in default footer?</th>
                  <td><input type="checkbox" name="includeFooter" '.(get_option('cc_include_footer')=='1'?"checked":"").'" ></td>
             </tr>

               <tr><th>&nbsp;</th>
                   <td><input type="submit" value="save" />
                       <input type="reset"  value="cancel" id="cancel" />
                   </td>
               </tr>
               </td></tr>
            </table>
            </form>
         </div>

      </div>
      ';
} // license_options

// Add the Content License link to the options page listing
function cc_addAdminPage() {
	if (function_exists('add_options_page')) {
		add_options_page('Content License', '<img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wpLicense/cc_admin.png" style="padding-right: 3px; position: relative; top: 2px;">Content License', 5, basename(__FILE__), 'license_options');
		}
} // addAdminPage


// Include the necessary java-script libraries
function license_js_header() {

  if (strpos($_SERVER['REQUEST_URI'], "wpLicense") === FALSE) return;

  $url = get_bloginfo("wpurl");
  $scripts = array('/wp-content/plugins/wpLicense/prototype.js',
              '/wp-content/plugins/wpLicense/effects.js',
              '/wp-content/plugins/wpLicense/dragdrop.js',
              '/wp-content/plugins/wpLicense/controls.js',
              '/wp-content/plugins/wpLicense/behaviour.js',
              '/wp-content/plugins/wpLicense/tw-sack.js',
              '/wp-content/plugins/wpLicense/admin.js',
             );

  foreach ($scripts as $script) {
    echo '<script type="text/javascript" src="'.$url.$script.'"> </script>';
  }

} // license_js_header

// Initialize the WordPress content variables
function init_content_license($reset=false) {

  // call non-destructive add for each option
  add_option('cc_content_license', '');
  add_option('cc_content_license_uri', '');
  add_option('cc_content_license_rdf', '');
  add_option('cc_content_license_html', '');

  add_option('cc_copyright_holder', '');
  add_option('cc_creator', '');
  add_option('cc_include_work', '0');
  add_option('cc_per_post', '0');

  add_option('cc_include_footer', '1');

  // if reset is True, destructively reset the values
  if ($reset == true) {
     update_option('cc_content_license', '');
     update_option('cc_content_license_uri', '');
     update_option('cc_content_license_rdf', '');
     update_option('cc_content_license_html', '');

     update_option('cc_copyright_holder', '');
     update_option('cc_creator', '');
     update_option('cc_include_work', '0');
     update_option('cc_per_post', '0');

     update_option('cc_include_footer', '1');

  } // if resetting

} // init_content_license

function post_form() {
    global $post_msg;

    // check for standard return (using web services
    if ( (isset($_POST['submitted'])) && ($_POST['submitted'] == 'wplicense')) {
        // check if the license should be removed
        if ($_POST['remove_license'] == '__remove') {
           init_content_license(true);

           $post_msg = "<h3>License information removed.</h3>";
	   return;
        } // remove license

        // check if the license was changed
	if ($_POST['license_uri'] != get_option('cc_content_license_uri')) {
           // store the new license information
           update_option('cc_content_license', $_POST['license_name']);
           update_option('cc_content_license_uri', $_POST['license_uri']);
           update_option('cc_content_license_rdf', $_POST['license_rdf']);
           update_option('cc_content_license_html', $_POST['license_html']);
        }

        // store the settings

        if (isset($_POST['perPost'])) {
           update_option('cc_per_post', '1');
        } else {
           update_option('cc_per_post', '0');
        }
        if (isset($_POST['includeFooter'])) {
           update_option('cc_include_footer', '1');
        } else {
           update_option('cc_include_footer', '0');
        }

        $post_msg = "<h3>License information updated.</h3>";
    } // standard web services post

    // check for return from partner interface
    if ( (isset($_GET['submitted'])) && ($_GET['submitted'] == 'from_partner')){

	if ($_GET['license_uri'] != get_option('cc_content_license_uri')) {

	   // construct the HTML block
	   $html='<a rel="license" href="' . $_GET['license_uri'] . '"><img alt="Creative Commons License" border="0" src="' .$_GET['license_image'] . '"/></a><br/>This work is licensed under a <a rel="license" href="' .$_GET['license_uri'] . '">Creative Commons ' .$_GET['license_name'] . ' License</a>.';

           // store the new license information
           update_option('cc_content_license', $_GET['license_name']);
           update_option('cc_content_license_uri', $_GET['license_uri']);
           update_option('cc_content_license_rdf', '');
           update_option('cc_content_license_html', $html);
        }

        $post_msg = "<h3>License information updated.</h3>";
    } // partner interface return

} // post_form

/* admin interface action registration */
add_action('admin_menu', 'cc_addAdminPage');
add_action('admin_head', 'license_js_header');
add_action('admin_head', 'post_form');

/* content action/filter registration */

// show global RDF + HTML, if turned on
add_action('wp_footer', 'cc_showLicenseHtml');

// feed licensing
add_action('rss2_ns', 'cc_rss2_ns');
add_action('rss2_head', 'cc_rss2_head');
add_action('atom_head', 'cc_atom_head');

// widget support
require(dirname(__FILE__) . '/wpLicense/widget.php');


?>