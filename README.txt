=== CREA-Listings ===
Contributors: Sprytechies <contact@sprytechies.com>
Tags: CREA listings,canada, crea, property, real estate
Requires at least: 3.5.1
Tested up to: 4.0
Stable tag: 1.0.0

This plugin fetches property listings from CREA (Canadian Real Estate Assoiciation) based upon the access details entered.

== Description ==

This plugin allows users to fetch their property listings from CREA (http://www.crea.ca/) based on their access details. 

You can add multiple user accounts and fetch their respective listings from CREA (Canadian Real Estate Assoiciation) and display them with the help of shortcodes on their sites. 

Use shortcode:
 
   1. `[list-properties user='username']` for listings all properties related to a user. 
   2. `[property user='username' mlsid='mls id']` for listings a single property. MLS id of the property list must be provided.

You need to register with http://tools.realtorlink.ca for using this plugin, we accept the http://tools.realtorlink.ca CREA's Data Distribution Facility API details for initializing this plugin. 

= Features =

1. Simple to setup.
2. Responsive design.
3. Works with any WordPress theme.
4. Cron runs hourly to update property.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload folder CREA-Listings  to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Fill in the entries in Settings->CREA Listings page.
4. Details regarding more than 1 user can be entered.
5. Use shortcode [list-properties user='username']  for listings all properties related to a user. 
   Use shortcode [property user='username' mlsid='mls id'] for listings a single property. MLS id of the property list must be provided.

= You are required to register for a data feed a http://tools.realtorlink.ca. An email containing user name and password is sent to the email address submitted as Technical Contact. = 

== Frequently Asked Questions ==

= Will all the properties and related images will get listed after at once? =

No, system will initially fetch only 100 properties and related images of 20 properties for reducing load on the system. Thereafter
a cron will run every hour and fetch rest of the properties and images in slots.

= What if the properties get updated at CREA and we already have a records in the system? =

There is a cron which runs daily and fetches updated properties from CREA and updates system listings accordingly.

= Will user can hide/show the properties from listing? =

Yes, user can hide/show property by changing the property status from admin panel View-Listings page.

= Will user can update the number of properties listing on single page? =

Yes, user can update it from admin panel Settings->CREA Listings page users section.

== Screenshots ==

1. Screenshot of settings screen is place with filled up example entries.

   `/screenshot.png`

== Changelog ==

= 1.0 =
* awesome plugin is up and running

