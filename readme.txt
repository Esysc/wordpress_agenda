=== ACS Agenda Manager ===
Contributors: esysc
Donate link: https://www.paypal.com/donate?business=KG9SDHM7VUP6Y&currency_code=CHF
Tags: agenda, events, calendar, schedule, workshop
Requires at least: 6.2
Tested up to: 6.9
Stable tag: 3.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing and displaying event agendas. Perfect for workshops, courses, conferences, and event organizers.

== Description ==

ACS Agenda Manager provides an easy way to create and manage an event agenda on your WordPress site. Display upcoming events with dates, locations, images, and descriptions in a clean, responsive layout.

= Features =

* Multi-date support: Events can span multiple days or have multiple occurrences
* Media integration: Upload and display event images via WordPress Media Library
* Location display: Show event locations prominently
* Linked pages: Connect events to detailed WordPress pages
* Automatic expiration: Past events are automatically hidden
* Responsive design: Looks great on all devices
* Internationalization: Supports French and English translations
* Easy administration: Intuitive admin interface with inline editing

= Usage =

Display the agenda on any page or post using the shortcode:

`[agenda]`

The plugin automatically creates an "Agenda" page upon activation with the shortcode included.

= Integration =

This plugin integrates with the ACScontactform plugin to provide event registration forms.

== Installation ==

1. Upload the `ACSagendaManager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Agenda in the admin menu to start adding events

== Frequently Asked Questions ==

= How do I display the agenda? =

Use the `[agenda]` shortcode on any page or post.

= Can events have multiple dates? =

Yes, events can have multiple dates. Use the calendar in the event editor to select multiple dates.

= What happens when an event expires? =

By default, events are hidden after their last date passes. You can control this behavior with the "Partial Attendance" setting.

= Can I customize the appearance? =

Yes, you can override the CSS in your theme or copy the template file to your theme for full customization.

== Screenshots ==

1. Agenda display on the frontend
2. Admin interface for managing events
3. Event editor dialog
4. Settings page

== Changelog ==

= 3.0.0 =
* Complete rewrite with modern PHP 7.4+ code
* New class-based architecture
* Improved security and performance
* Better admin UI
* User guide integrated in admin

= 2.1.1 =
* Added French and Italian translations
* Various bug fixes

= 2.0.0 =
* Initial public release

== Upgrade Notice ==

= 3.0.0 =
Major update with complete code rewrite. Backup your database before upgrading.

== Credits ==

* jQuery UI Multi Dates Picker: Loaded from jsDelivr CDN
* Icons: WordPress Dashicons
