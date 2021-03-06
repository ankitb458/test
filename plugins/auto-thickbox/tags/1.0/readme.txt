=== Auto Thickbox ===
Contributors: Denis-de-Bernardy, Semiologic
Donate link: http://buy.semiologic.com/drink
Tags: lightbox, thickbox, shadowbox, gallery
Requires at least: 2.5
Tested up to: 2.7
Stable tag: trunk

Automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up).


== Description ==

The Auto Thickbox plugin for WordPress automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up), through the use of WordPress' built-in thickbox library.

In the event you'd like to override this for an individual image, you can disable the behavior by adding the 'nothickbox' class to its anchor tag.


= Thickbox Galleries =

By default, the auto thickbox plugin will bind all images within a post into a single thickbox gallery. That is, thickbox will add next image and previous image links so you can navigate from an image to the next.

The behavior is particularly interesting when you create galleries using WordPress' image uploader. Have the images link to the image file rather than the attachment's post, and you're done.

On occasion, you'll want to split a subset of images into a separate gallery. Auto Thickbox lets you do this as well: add an identical rel attribute to each anchor you'd like to group, and you're done.

(Note: To set the rel attribute using WordPress' image uploader, start by inserting the image into your post. Then, edit that image, browse its advanced settings, and set "Link Rel" in the Advanced Link Attributes.)

= Thickbox Anything =

Note that thickbox works on any link, not merely image links. To enable thickbox on an arbitrary link, set that link's class to thickbox.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).