=== Simple Webstats ===
Contributors: edhicks
Tags: analytics, statistics, webstats, privacy, tracking
Requires at least: 4.6
Tested up to: 6.6
Requires PHP: 7.3
Stable tag: 1.2.3
License: GPLv2 or later

Privacy-focused cookie-free web analytics for WordPress.

== Description ==

Simple Webstats is an easy to use, privacy-focused web analytics solution for WordPress. Gain insights into how your website is used without sacrificing your visitors privacy.

- No personal data collection.
- No cookies.
- No cross-site tracking.

This plugin uses the [country.is](https://country.is) geolocation API to enable you to see visitor data by country. Requests are not logged and no personal data is collected. For more information please refer to the [country.is documentation](https://country.is/).

== Frequently Asked Questions ==

= Is Simple Webstats GDPR/PECR/CCPA Compliant? =

Simple Webstats has been designed with user privacy in mind. It stores no cookies or other data on the users device, and collects no personally identifiable data.

We are developers, not lawyers. It is our understanding that Simple Webstats is compliant with the various different privacy regulations, however this should not be taken as legal advice.

= Do I need to display a cookie warning? =

Simple Webstats does not use cookies, so requires no cookie notices or opt-ins.

Your site may use cookies for other purposes, in which case you should present users with appropriate information and controls.

== Installation ==

= Automatically, from your plugin dashboard =

 1. Navigate to `Plugins > Add New` in your WP Admin dashboard.
 2. Search for `blucube simple webstats`.
 3. Click the `Install` button, then `Activate`.

= Manual installation = 

 1. Search for `blucube simple webstats` in the [WordPress Plugin Directory](https://wordpress.org/plugins/), and download it.
 2. Unzip and upload the `simple-webstats` directory to your `/wp-content/plugins/` directory.
 3. Activate *Simple Webstats* from the Plugins tab of your WP Admin dashboard. 

== Changelog ==

= 1.2.3 =

* Bugfix: "Last 24 hours" view was omitting current hour.

= 1.2.2 =

* Bugfix: Error in average time on site query was causing anomalous results.

= 1.2.1 =

* Bugfix: Dashboard widget was resetting user's default dashboard view setting.

= 1.2.0 =

* Added dashboard widget.
* Added "Last 24 hours" view.
* Added rotation of UID salt to increase data anonymity.

= 1.1.0 =

* Omitted logging unsuccessful requests (e.g. 404s).

= 1.0.0 =

* Initial release.