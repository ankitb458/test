Plugin Name: Contact Form ][ (WP-2.0.x)
Plugin URI: http://chip.cuccio.us/projects/contact-form-II/
Description: Contact Form ][ is a drop-in form that allows site visitors to
             contact you. It can be implemented easily (via QuickTags) within
             any post or page.  This version is *specifically* for WordPress
             2.0 only.  Original code derived from Ryan Duff's
             WP-ContactForm plugin.
Author: Chip Cuccio
Author URI: http://chip.cuccio.us
Version: 2.0.7_wp-2.0.x

------------
README FIRST
------------
Note that Contact Form ][ is a drop-in replacement for Ryan Duff's
"WP-ContactForm" plugin.  By installing Contact Form ][ , you're essentially
upgrading to this plugin.

Please read and adhere to the INSTALLATION and UPGRADING sections carefully.

------------
INSTALLATION
------------
    1. Unpack/upload the "wp-contact-form" directory "wp-content/plugins/".
    2. Activate the plugin from the Admin interface; "Plugins".
    3. Go to "Options -> Contact Form ][ " and update the fields if necessary.
    4. Create a post or page (or edit existing), and press the "Contact Form"
       Quicktag where you'd like the form to be placed. If you don't see the
       Contact Form Quicktag, you can alternatively copy and paste
       [CONTACT-FORM] where you'd like it to appear.

---------
UPGRADING
---------
    *  If you are using an old version of Ryan Duff's "WP-ContactForm" plugin
       (does NOT have a "wp-contact-form" directory in "wp-content/plugins/");
         1. Delete "options-contactform.php" in "wp-admin/" or
            "wp-content/plugins/" and delete "wp-contactform.php" in
            "wp-content/plugins/".
         2. Follow step #1 from "INSTALLATION" section above.
         3. Old options have been saved in the database and will appear when
            you visit "Options > Contact Form ]["
    *  If you are using a newer version of Ryan Duff's "WP-ContactForm" plugin
       (uses a "wp-contact-form" directory in "wp-content/plugins/");
         1. Delete the entire "wp-contact-form" directory (and its contents)
            located in "wp-content/plugins/".
         2. Follow step #1 from "INSTALLATION" section above.
         3. Old options have been saved in the database and will appear when
            you visit "Options > Contact Form ]["

--------------------------------------------------------
FEATURES (and enhancements over Ryan's "WP-ContactForm")
--------------------------------------------------------
    *  Sends browser/OS info in email.
    *  Links IP address to ARIN whois in email (ala WP comments).
    *  User can specify own subject line.
    *  Subject line can have arbitrary text appended to it, ID'ing that email
       originated from contact form.
    *  Email output cleanups.
    *  Auto-populates blog name in subject suffix.
    *  Auto-populates Admin Email from WP DB into destination email address field.
    *  Added subject header to malicious/spam checks.
    *  Decreased message wrap to 76 columns.
    *  Adgustable size of message input text area.
    *  Senders can Cc: themselves on messages.

-------
LICENSE
-------
Contact Form ][ is distributed under the GPL v2. See
<http://www.gnu.org/licenses/gpl.txt> for full license.

-------------------------
Bugs - Features - Support
-------------------------
One word: No.

I offer this plugin as-is (for now).  I don't have time (for now) to fix bugs,
add features, help you, etc.  I don't even have time to accept and merge
patches at this point.  HOWEVER...I may, in the very near future, host
development of this plugin on http://dev.wp-plugins.org.  We shall see.


