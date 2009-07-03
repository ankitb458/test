******************************
*

	AUTHORS
*******************************

ELLIOTT C. B?CK
elliott@cornell.edu

*******************************
	WORDPRESS HASHCASH 2.2
*******************************

**Introduction**

Taking Matt?s stopgap spam solution, which sends precomputed hashes to be echoed back by the user-agent?s form, I?ve added dynamic generation of the md5 hash. Rather than write it to a hidden field, we wait until the form is submitted to compute the hash. This prevents spammers from automatically scraping the form, because anyone wanting to submit a comment *must* execute the javascript md5.

This plugin used to be called ?Spam Stopgap Extreme.? Now it?s been moved to the WP plugins repository under the new name ?Wordpress Hashcash.? All future development will take place through the plugins repository.

**Features:**

	+ Logging and emailing of spam kills.

	+ Client-side hash required.

	+ The ?Key? is hashed once before output of the html form ?- so spammers can?t make sense of it, and then hashed on the client side, again.

	+ The Key and value are time-dependent and visitor dependent, for more variability. You can?t just compute the right md5 once, because it?s always changing.

	+ NEW:  Visitors without javascript are greeted by friendly warning messages, rather than simply blocked by the script.

	+ NEW:  The value is written into the form only by javascript which never uses the value per se, just an algebraic identity.  Without the right value, the check will fail, but it's nowhere in the code for a spammer to find.

	+ NEW:  The signature of WP-Hashcash 2.x is harder to detect, because javascript is obfuscated and written into the file in random order.

	+ NEW:  The javascript md5 is 61% smaller than before.

	+ NEW:  The .doc readme is now a flat text file, and the md5.js file has been removed.  Just drop in wp-hashcash.php!

	+ Validates as XHTML Transitional 1.0

**Download:**

Please get the latest source code from the plugins repository. Drop the file wp-hashcash.php into your wp-content/plugins/ directory and activate the plugin. An old version of Spam Stopgap Extreme (v1.4) is also available for download.

**Q&A:**

Q: After I copy and paste the php code from the repository, I get the message ?headers already sent.?
A: You have extra whitespace either in front of the <?php or after the ?>

Q: Saving the code from the repository doesn?t save php files that I can use with the admin plugin interface.
A: You need to copy and paste the code from md5.js and wp-hashcash.php to new files with the same names, or use the ?Download in Original Format? at the bottom of the listing for each individual file.

Q: Can I use this with popup comments?
A: Yes. Add ?<?php wp_head(); ?>? before the end head tag (</head>) in wp-popup-comments.php.

Q: Why are there no logs and email reports?
A: You need to enable them by changing the debug definition at the top of the file from false to true.

**Credits:**

Cecil Coupe
C.S. - www.cimmanon.org
Gene Shepherd - www.imporium.org
John F - www.stonegauge.com
Magenson - http://blog.magenson.de/
Matt Mullenweg - photomatt.net
Matt Warden - www.mattwarden.com
Paul Andrew Johnston - pajhome.org.uk


A JavaScript implementation of the RSA Data Security, Inc. MD5 Message Digest Algorithm, as defined in RFC 1321 is included. Version 2.1 Copyright (C) Paul Johnston 1999 - 2002. Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet.  Distributed under the BSD License.  See http://pajhome.org.uk/crypt/md5 for more info.