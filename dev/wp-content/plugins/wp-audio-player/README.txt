=== WP Audio Player ===
Contributors: tommcfarlin
Tags: mp3, audio, player
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 1.9.1

WP Audio Player is a plugin based on the popular player from the guys at Codrops that aims to make it easy to include an audio player in your post.

== Description ==

WP Audio Player is a plugin that brings the <a href="http://tympanus.net/codrops/2012/12/04/responsive-touch-friendly-audio-player/">Responsive and Touch-Friendly Audio Player</a> from Codrops to WordPress.

It introduces a meta box to each post page that allows you to supply the URL to any audio file and will then append the player to the end of the post. This way, you're able to upload your media using the built in Media Uploader, and then use the provided URL to add the player to your post.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' Plugin Dashboard
2. Select `wp-audio-player.zip` from your computer
3. Upload
4. Activate the plugin on the WordPress Plugin Dashboard

= Using FTP =

1. Extract `wp-audio-player.zip` to your computer
2. Upload the `wp-audio-player` directory to your `wp-content/plugins` directory
3. Activate the plugin on the WordPress Plugins dashboard

== Frequently Asked Questions ==

= Right now, the player only appears at the bottom of the post. Can I change it's position? =

In version 1.0, no; however, this is a planned feature assuming that the plugin is useful for other people.

== Screenshots ==

1. WP Audio Player running in Chrome.

2. WP Audio Player running in fallback mode in Firefox.

3. WP Audio Player rendering in responsive mode.

4. A screenshot of the updated dashboard for how you can retrieve MP3's from your media library.

== Changelog ==

= 1.9.1 =
* Adding a check for the `Mobile_Detect` class to make sure that it doesn't interfere if the class already exists

= 1.9 =
* Disabling the auto-start in browsers that use the default embed element (rather than the responsive player).
* Introducing support for a default player for IE8
* Resolving responsive functionality issues with Firefox 18+, IE9+, Chrome, and Safari
* Making sure the player uses the mobile device's native player for playing the audio. Will implement the responsive player when functionality for major mobile devices has been resolved.

= 1.8 =
* Moving the Firefox warning message to the admin dashboard so that it doesn't display on the public-facing side of the site.

= 1.7 =
* Resolving a bug that prevented the player from displaying on single pages (not just single posts)

= 1.6 =
* Making sure the audio player doesn't display to the public on password protected posts

= 1.5 =
* Improving the way the plugin is started so that it can be programmatically unset
* Fixing a problem with excerpts not properly being displayed in some plugins
* Resolving an issue that didn't allow Firefox users to select an item from their list of MP3's
* Including the development JavaScript and LESS files

= 1.4 =
* Adding a new look and feel compliments of the design by the Codrops theme
* Making sure that embeds from third-party domains work (such as Amazon)
* Improved the security for saving data in the WordPress Dashboard
* Added a dashboard option for selecting files that are already contained in your media library
* Added fallback support for browsers that do not implement the audio element
* Added fallback support for browsers that do not support the MPEG codec

= 1.3 =
* Properly escaping URL's when they are returned to the browser (Thanks <a href="https://github.com/tommcfarlin/wp-audio-player/pull/4">pdewouters</a>!)
* Improving the coding standards by some formatting tweeaks (Thanks <a href="https://github.com/tommcfarlin/wp-audio-player/pull/1">studioromeo</a>!)
* Resolving a problem that prevented the audio player from displaying in Firefox
* Updating the localization files
* Remove the donate link (because this project is now driven by the open source community)

= 1.2 =
* Adding support for custom post types and pages

= 1.1 =
* Making sure the player only displays on the single post page.

= 1.0 =
* Initial release