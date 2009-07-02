=== Add From Server ===
Contributors: dd32
Tags: 2.5, admin, media, uploads, post
Requires at least: 2.5
Tested up to: 2.5
Stable tag: 1.4

"Add From Server" is a quick plugin which allows you to import media & files into the WordPress uploads manager from the Webservers filesystem

== Description ==

WordPress 2.5 includes a new Media manager, However, It only knows about files which have been uploaded via the WordPress interface, Not files which have been uploaded via other means(eg, FTP).

So i present, "Add From Server" a WordPress plugin which allows you to browse the filesystem on the webserver and copy any files into the WordPress uploads system, Once "imported" it'll be treated as any other uploaded file, and you can access it via the Media Li

Note: French, Spanish, and German translation are included. I cannot nativly speak these languages, If you find an error, or would like me to add your translation, You can contact me via: http://dd32.id.au/contact-me/

== FAQ ==

 Q. Where is the page for this plugins?
 A. You can find the page for this plugin in the Add-Media dialogue. The Add Media dialogue is accessable via the New Post/New Page screen, They're the Icons beside the Visual/HTML tab option.

 Q. What happens when I import a file?
 A. When a file is imported, It is first Copied to the /wp-content/uploads/ folder and placed in the current months folder. The date will be set to today.

 Q. What happens when I import a file which is allready in the uploads folder?
 A. If a file is allready in the uploads folder, Then it is not copied anywhere, Instead, the file will stay in its current location, and the date for the media manager will be taken from the URL (ie. import a file in /uploads/2008/01/ and it will be stored in the media library as January 2008)

 Q. I'd like to add some files to an old post, But when i add them, they are in the current month's folder! Help! 
 A. This plugin doesnt care for the date of a file, Instead, You may find that my other plugin is useful, It allows you to add attachments and store them in the current months folder, Or a folder based on the post date of the post. dPost Uploads: http://wordpress.org/extend/plugins/dpost-uploads/

== Changelog ==

= 1.0 =
 * Initial Release
= 1.1 =
 * Fixed a bug which causes the original import file to be deleted upon removing from the media library, The file in /uploads/2008/03/ remains however. Will now delete the file in the uploads folder instead of the original imported file, However, Be warned, files previously imported WILL remain as they are, and the original import file will be deleted(if you delete from the media library)
= 1.2 =
 * Fixed filename oddness including old directory names
 * Added a check to see if the file exists in the Media library allready
 * Added a check to see if the file is allready in the uploads folder before importing, and if so, simply add it to the database, do not mash the filesystem
= 1.3 =
 * Internationalisation; French translation (Apologies if not 100% accurate, Please do submit language fixes :))
 * Internationalisation; Spanish translation (Apologies if not 100% accurate, Please do submit language fixes :))
 * Checkbox select all
 * Import into non-post attachment
= 1.3.2 =
 * French translation changes from Ozh & Olivier
 * Fixed the checkbox list for certain unknown browsers.
= 1.4 -
 * German Translation
 * More stuffing around with the checkbox that doesnt work for anyone, yet works on every test system i've tried
 * Set the date on imported files to that of their uploads folder

== Future Features ==
Please note that these are simply features i'd like to do, There is no timeframe, or guarantee that it will be in the next version.
1. Watch folder, New files detected in the watch manager automatically get imported
1. The ability to select a file and switch directly to adding it to the post

== Screenshots ==

1. The import manager