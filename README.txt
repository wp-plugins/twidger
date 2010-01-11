=== Twidger ===
Contributors: laurentlasalle, marcboivin
Tags: twitter, avatar, widget, tweet
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 0.3.1

Display messages with associated usernames and avatars from a Twitter search through a widget.

== Description ==

Here's a small WordPress plugin that allows you to display messages with usernames and avatars from a Twitter search. An easy way to display the latest tweets containing specific keywords or usernames.

This plugin reuses code from [Antonio “Woork” Lupetti](http://woork.blogspot.com/2009/06/simple-php-twitter-search-ready-to-use.html), [Ryan Faerman](http://ryanfaerman.com/twittersearch/) and [David Billingham](http://davidbillingham.name).

This plugin requires [cURL](http://en.wikipedia.org/wiki/CURL) to be running on the server. I am NOT a programmer, if you want to fix things, suit yourself. Special thanks to [Marc Boivin](http://mboivin.com).

== Installation ==

Installing this plugin is easy.

1. Upload the `twidger` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Widgets' to add the 'Twidger' widget in your sidebar.
4. Change the settings according to what you want to display.

== Changes ==

= 0.3.1 =
* Added cache functionnality (thanks to Marc Boivin).

= 0.2.1 =
* Bugfix of the missing trailing slash for the stylesheet URL.

= 0.2 =
* Initial version. This plugin is still in beta. Please leave feedback to twidger@laurentlasalle.com
