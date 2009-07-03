<?php
// ** MySQL settings ** //
define('DB_NAME', 'fortben_wrdp1');    // The name of the database
define('DB_USER', 'fortben_wrdp1');     // Your MySQL username
define('DB_PASSWORD', 'o7vZpb2TGYPD'); // ...and password
define('DB_HOST', 'localhost');    // 99% chance you won't need to change this value

// You can have multiple installations in one database if you give each a unique prefix
$table_prefix  = 'wp_';   // Only numbers, letters, and underscores please!

// Change this to localize WordPress.  A corresponding MO file for the
// chosen language must be installed to wp-includes/languages.
// For example, install de.mo to wp-includes/languages and set WPLANG to 'de'
// to enable German language support.
define ('WPLANG', '');


/* use cache */
// define('WP_CACHE', true);

/* save queries */
if ( isset($_GET['action']) && $_GET['action'] == 'debug' )
{
	define ('SAVEQUERIES', true);
}

/* kill spammers */
if ( strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') === false )
{
	require_once dirname(__FILE__) . '/bad-behavior/bad-behavior-generic.php';
}

/* That's all, stop editing! Happy blogging. */

define('ABSPATH', dirname(__FILE__).'/');
require_once(ABSPATH.'wp-settings.php');
?>