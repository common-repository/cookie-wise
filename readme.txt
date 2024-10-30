=== Plugin Name ===
Contributors: jeroensmeets
Tags: cookie
Requires at least: 3.6
Tested up to: 4.0
Stable tag: 1.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cookie Wise adds a statusbar to tell a visitor about the use of cookies.

== Description ==

In accordance with european legislation, and especially the Dutch situation, this plugin adds a status bar for visitors. In the status bar, a message is displayed with the option to accept cookies. After the message, a link to a page is added for more information.

= IMPORTANT =

The plugin prevents cookies by the Analytics plugin by Yoast, and ShareThis. Until a visitor accepts cookies, their visits won't show up in Google Analytics!

== Installation ==

1. Install the plugin via `Add Plugin` (or upload the folder to the `/wp-content/plugins/` directory)
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add a page to your site with more background info about the cookies on your site.
1. Go to `Settings` > `Cookie Wise`, select the new page for more info, optionally change texts and colors.

== Frequently Asked Questions ==

= Which cookies are disabled? =

The plugin disables the cookies set by the following plugins:

1. Google Analytics by Yoast
1. ShareThis by ShareThis

Until a visitor accepts cookies, these cookies won't be set. You will not see these visits to your site in Google Analytics.

WordPress cookies are considered functional and therefore not disabled.

= Can I suggest other cookies to disable? =

Sure! Let me know the plugin name in the support forums, and I'll look into it.

= Why does this plugin set a cookie? =

To disable the statusbar, and to remember if a visitor has accepted or rejected cookies, the plugin sets a cookie.

= What's up with the name of this plugin? =

This plugin got its name from greatest film quote ever, from [The Apartment](http://www.imdb.com/title/tt0053604/) (1960) by Billy Wilder:

`That's the way it crumbles -- cookie wise.`

== Changelog ==

= 1.1.4 =
* added warning: until a visitor accepts cookies, you won't see them in Google Analytics if you use Yoast his plugin.

= 1.1.3 =
* fixed a php warning about missing function section_position()

= 1.1.2 =
* added icons for the plugin
* updated 'tested up to:' to WordPress 4.0
* added some extra help info

= 1.1.1 =
* fixed typo in options for screen position

= 1.1 =
* added option to show bar at bottom of screen
* option to specifiy a blogpost in the link for more information
* show defaults in options screen when options not set
* tested in WordPress 3.5 beta 3

= 1.0 =
* Initial version
* supports Google Analytics plugin by Yoast
* supports ShareThis plugin ShareThis
