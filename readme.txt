=== WP-LatestPhotos ===
Contributors: andddd
Donate link: http://codeispoetry.ru/
Tags: latest photos, fresh photos, photos, attachment, images
Requires at least: 2.8
Tested up to: 3.3.2
Stable tag: 1.0.4

WP-LatestPhotos is a WordPress plugin which extends your media library and gives the ability to highlight some of your latest photos.

== Description ==

WP-LatestPhotos is a WordPress plugin which extends your media library and gives the ability to highlight some of your latest photos. Selected images can be shown in your sidebar—or wherever you need them to be—through the shortcode or inline PHP code.

= Features =

 * Sidebar widget and shortcode support
 * Photo randomizer
 * Highly customizable through CSS. Plugin provides example CSS which can be used for demo purposes and can be switched off in the plugin settings. This way you can define your own CSS for your thumbnail galleries without being stuck with the excessive styles which most plugins come with, slowing down your website.
 * Plugin provides the ability to include Thickbox bundled with your WordPress installation but you also can switch it off and use any other external library. It supports: Thickbox, Fancybox, Lightbox, Shadowbox. If you would like to use any other media viewer you’re should install an appropriate plugin.
 * Thumbnails are located in the default image folder and named according to the WordPress convention.
 * No performance bottlenecks. Thumbnails are generated only once—when marked.
 * AJAXified thumbnail image cache gets rebuilt in case you change thumbnail size in the settings. Re-generating 10 images per request occurs with a little delay, so your server won’t be overloaded and scripts won’t stop working with a timeout error.

= Usage =

Use WP-LatestPhotos shortcode to add your photos to your post.

`[WP-LatestPhotos limit="6"]`

Also you can do it using these lines of PHP:

`$options = array('limit' => 6, 'echo' => 1, 'link' => 'thickbox');
wp_latestphotos($options);`



= Options =

 * `limit` – number of images to display (6 by default)
 * `id` – you can specify an ID for the unordered list of images
 * `link` – one or more values that determine the URL of where a clicked image takes the user to


Possible values for __link option__ are:

 * `post_parent` – URL of the post to which the image is attached (it works only if image is actually attached, doh)
 * `attachment` – attachment page URL (works only if page is attached to a post)
 * `full` – full-size image URL
 * `thickbox, fancybox, lightbox or shadowbox` – media viewer dependent link
 * empty value means no link


You can mix these values up too. For example, you can use a sequence like this: post_parent, thickbox. If an image is attached to some post it uses a link to this post, otherwise it uses a Thickbox-dependent link.

 * `before, after` – additional HTML before and after an IMG tag of each photo
 * `echo` – 0 or 1, automatically outputs generated HTML; useful when applying a function inside PHP templates


== Installation ==

WP-LatestPhotos requires WordPress 2.8 or higher.

 * Download and extract the plugin files onto your hard drive
 * Copy the extracted folder into your WP plugin directory (usually `wp-content/plugins`)
 * Activate the plugin

== Frequently Asked Questions ==

n/a

== Screenshots ==

1. In action (custom CSS)
2. Settings page
3. Manage directly from media library

== Changelog ==

= 1.0.4 =
* Fixed thickbox bug

= 1.0.3 =
* Fixed image cache bugs

= 1.0.2 =
* Image randomizer bug fix

= 1.0.1 =
* Image generation major bug fix

= 1.0 =
* Initial release

== Upgrade Notice ==

n/a
