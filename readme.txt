=== WP Job Manager Client-Side Geocoder ===
Contributors: ninjew
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WTF4HGEYNFF8W
Tags: wp job manager, geocode, Google API, OVER_QUERY_LIMIT
Requires at least: 4.1
Tested up to: 4.2.2
Stable tag: 1.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Use client-side geocoding to overcome the OVER_QUERY_LIMIT ( failed to geocode a location ) issue when updating job's location

== Description ==

WP Job Manager Client-Side Geocoder plugin bypass the geocoder function ( server side ) provided by WP Job Manager plugin and instead uses client-side geocoding system.
By doing so the plugin should overcome the OVER_QUERY_LIMIT issue which you might have been experiencing.

= Features =

* Client-side geocoding system will try to overcome the OVER_QUERY_LIMIT issue which prevents address from being geocoded. 
* New "Geocode" button added under the location field in the "New Job" ( and "New Resume" for those who use the Resume Manager add-on ) page in the back-end. A click on the button will geocode the address entered.
* When creating/updating job ( or resume ) from the front-end the location will be automatically geocoded once the form is submitted. No "geocode" button added to the front-end forms.

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "WP Job Manager Client-side Geocoder" and click Search Plugins. Once you've found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by clicking _Install Now_.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application.

* Download the plugin file to your computer and unzip it
* Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's `wp-content/plugins/` directory.
* Activate the plugin from the Plugins menu within the WordPress admin.

No additional set-up is required. Just activate and enjoy.

== Screenshots ==


== Changelog ==

= 1.0.1 =
* Add street name and street number fields
* sanitize location input fields when updating location

= 1.0.0 =
* Initial release
