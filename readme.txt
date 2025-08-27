=== This Day In History ===
Contributors: BrokenCrust
Tags: history, today in history, on this day, this day in history, birthday, events, thought, word, of the day
Requires at least: 4.4
Tested up to: 6.2
Stable tag: 3.10.1
License: GPLv2 or later

This "On This Day" plugin allows you to enter historic or future events and display them via the included widget and shortcodes.


== Description ==

This plugin allows you to enter and manage different kinds of events that you then display in via a widget or shortcodes.


== Installation ==

Installing is pretty easy takes only a minute or two.

1. Upload 'this-day-in-history' directory to your '/wp-content/plugins/' directory.

2. Activate the plugin through the 'Plugins' screen in WordPress.

3. On the 'Widgets' sub-menu of 'Appearance' you will find a new widget type called 'This Day In History'; alternatively use a shortcode in a post or page.

4. Add this widget, to your themes widget display areas, select a title and click Save.

5. Enter historic events via the new 'Historic Events' admin page.  These will be automatically displayed by the widget on the anniversary of the day that they occurred.


== Changelog ==

= 3.10.1 =
* Fix issue with last week of year using period = 'c' (bug fix)

= 3.10 =
* Added show_age to `[tdih_tab]` shortcode

= 3.9 =
* Replaced depreciated function like_escape()

= 3.8.2 =
* Fixed php enoding bug in the event template (bug fix)

= 3.8.1 =
* Fixed sort order of `[tdih_tab]` shortcode when using period_days to be fixed as month-day-year (bug fix)

= 3.8 =
* Added order_dmy to tdih_day to allow sorting by day-month-year rather than year-day-month which is the default

= 3.7.3 =
* Fixed date order issue with tdih_tab (bug fix)

= 3.7.2 =
* Fixed events per page issue in admin screen (bug fix)
* Fixed search pagination issue in admin screen (bug fix)

= 3.7.1 =
* Fixed header error on event deletion in admin screen (bug fix)
* Fixed bulk deletion error in admin screen (bug fix)
* Altered At a Glance dashboard widget to unsure output is on a single line

= 3.7 =
* Added date_format to tdih_tab shortcode to allow custom format of the date field
* Added event and event type counts to the At a Glance dashboard widget

= 3.6 =
* Added ability to prefix year with some text in the widget
* Added period_days to tdih_tab shortcode to display of more than one day for the t, m, y periods
* Added max_rows to the tdih shortcode documentation to help screen and plugin page
* Changed default type selection to all types for a new widget (bug fix)
* Fixed issue with View link on admin page for domains in a sub-directory (bug fix)

= 3.5.1 =
* Fixed depreciation warnings for the add/edit event form (bug fix)

= 3.5 =
* Fixed depreciation warnings for php 7.4 (bug fix)

= 3.4.1 =
* Fixed missing callback error in admin screen (bug fix)

= 3.4 =
* Removed CSS include from the front end
* Fixed html5 validation issue with missing dt element (bug fix)

= 3.3 =
* Changed tdih shortcode and the widget to use dl list type
* Added widget_text filter to event title text

= 3.2 =
* Added ability to exclude events types in the widget

= 3.1.4 =
* Fixed issue when filtering widget by type (bug fix)

= 3.1.3 =
* Fixed issue with view link in admin screen (bug fix)

= 3.1.2 =
* Fixed issue choosing only one event in the widget (bug fix)
* Fixed checkboxes not saving correctly in the widget (bug fix)

= 3.1.1 =
* Fixed issue with admin order by when not using DD-MON-YYYY (bug fix)
* Minor CSS update for the admin screen (bug fix)

= 3.1 =
* Added period type a to list all events
* Fixed issue with a php7.2 depreciation during plugin activation (bug fix)

= 3.0 =
* Updated admin screens and added a column showing last modified date
* Fixed issue with list urls after editing an event (bug fix)
* Added default template for showing an event
* Added ability to use the more tag to have an extended event desciption
* Removed no event message from shortcodes if not set in settings (bug fix)
* Added class option to tdih shortcode (renamed classes option to class in tdih_tab shortcode)
* Added support for BC dates (range is now 9999 BC until 9999 AD)
* Events are now optionally searchable
* Event ordering in the admin screens in now fixed to year and so the date order option has been removed
* Added ISO week as a period to the tdih_tab shortcode
* Added day of week as an option to the tdih_tab shortcode
* Added the day and month parameters to the tdih shortcode to allow choosing any day of the year
* Added max_rows option to tdih shortcode and widget to limit the number of events shown

= 2.2.1 =
* Fixed Issue with current month in the shortcode (bug fix)

= 2.2 =
* Added the ability to use c for the current month in the tdih_tab shortcode
* Added the option to include the event age (useful for birthdays)
* Fixed the date sorting issue in the admin screen (bug fix)

= 2.1.2 =
* Updated for 4.5 compatibility (add_menu_page)

= 2.1.1 =
* Fixed issue with accessing Event Types in WordPress 4.4

= 2.1 =
* Added support for entry of early dates without leading zeros (e.g. 4-10-20 becomes 0004-10-20)
* Updated shortcode help screen text

= 2.0 =
* The shortcode table date format is set via the TDIH option (bug fix)
* Updated shortcode help screen to remove commas from the example (bug fix)
* Split the shortcode into two (tdih) like the widget and (tdih_tab) table format
* It is now possible to enter a date without a year (enter 0000 for the year)
* Added option to show events for yesterday or tomorrow instead of today
* Added an option to allow sorting of the administration table of events by day, month or year first

= 1.1 =
* Fixed pagination issues with WordPress 4.0
* Moved number of events shown to a screen option
* Overhaul of admin screens
* Removed custom table migration code for early versions

= 1.0 =
* First production release
* Added show_all to the shortcode
* Added Option for the text displayed when there are no events

= 0.9.3 =
* Fixed Admin Bar bug in 3.6

= 0.9.2 =
* Improved function naming for cross plugin compatibility
* Fixed html5 date input issue with Chrome

= 0.9.1 =
* Fixed bug with search on admin list

= 0.9 =
* tdih shortcode added
* help updated

= 0.8.2 =
* Fix for duplicate events when using post types

= 0.8.1 =
* Fix for activation hook not firing on upgrade

= 0.8 =
* Added event types
* Events now stored as posts

= 0.7 =
* Added widget option to show or not show the year

= 0.6 =
* Added options page
* Added option for events per page and date format
* Removed 255 character limit for event names
* Change event name input to a textarea
* Some minor html bug fixes

= 0.5 =
* Fix for editing entries with double quotes (like some html code) - more magic quotes misery

= 0.4 =
* Fix for local (blog time) rather than server time

= 0.3 =
* Fix for miserable magic quotes

= 0.2 =
* Name changed from Today In History
* CSS layout updated
* Help text updated
* Fixed sorting after edit issue

= 0.1 =
* Initial Release

== Shortcodes ==

There are two shortcodes.  `[tdih]` shows output as a list similar to the widget and `[tdih_tab]` shows output as a table.

= tdih =

You can add a `[tdih]` shortcode to any post or page to display a list of events as per the widget.

There are eleven optional attributes for this shortcode

* show_age (0, 1) - 1 shows the age in years of the event in brackets after the title and 0 does not (default).
* show_link (0-2) - 0 shows a more link if there is more to show (default), 1 links the title if there is more to show and 2 always links the title.
* show_type (0, 1) - 1 shows event types (default) and 0 does not.
* show_year (0, 1) - 1 shows the year of the event (default) and 0 does not.

* type - enter a type to show only events of that type. Shows all types by default.
* day (1-31) - enter a day to show only events on that day. Shows all days by default.
* month (1-12, c) - enter a month to show only events in that month. Shows all months by default.
* year (-9999 to 9999, 0) - enter a year to show only events in that year. Shows all years by default.
* period (t, m, y) - show events for today, tomorrow and yesterday. Shows today's events by default.
* classes - enter one or more space separated classes which will be added to the table tag.
* max_rows (1-99) - enter a maximum number of events to show. Shows all events by default.

Example use:

* `[tdih]` - This shows year and event types for all event types for today's events.
* `[tdih show_type=0 type='birth']` - This shows year and event but not type for the event type (slug) of birth.
* `[tdih year=1066 max_rows=5]` - This shows year and event types for up to five events that happened on this day in 1066.

= tdih_tab =

You can add a `[tdih_tab]` shortcode to any post or page to display a table of events.

There are fourteen optional attributes for this shortcode:

* show_date (0, 1) - 1 shows the date (default) and 0 does not.
* show_dow (0, 1) - 1 shows the day of the week and 0 does not (default).
* show_head (0, 1) - 1 shows a header row (default) and 0 does not.
* show_link (0, 1, 2) - 0 shows a more link if there is more to show, 1 links the title if there is more to show and 2 always links the title.
* show_type (0, 1) - 1 shows event types (default) and 0 does not.

* order_dmy (0, 1) - 0 sorts chronologically by year-month-day (default) and 1 sorts by day-month-year.
* type - enter a type to show only events of that type. Shows all types by default.
* day (1-31) - enter a day to show only events on that day. Shows all days by default.
* month (1-12, c) - enter a month to show only events in that month. Shows all months by default.
* year (-9999 to 9999, 0) - enter a year to show only events in that year. Shows all years by default.
* period (a, c, l, m, n, t, w, y) - t, m, y show events for today, tomorrow and yesterday. c, l, n, w show events for current, last, next and ISO week. a show all events. Shows today's events by default.
* period_days (1-99) - enter the number of days to show for t, m, y periods only. Shows only one day by default.
* date_format - enter a custom [php date format](https://www.php.net/manual/en/function.date.php) to display the date. Uses the tdih admin setting by default.
* classes - enter one or more space separated classes which will be added to the table tag.

NB:

* day of the week will never be shown if the date is not shown.
* Setting date_format will override the tdih admin format and the day of the week setting.
* Setting period will override any values for day, month and year.
* day, month and year can be combined.
* year=0 will display events with no year
* month=c will display the current month
* period=c, l or n show a seven day period with the current day as the middle, last or first day.

Example use:

* `[tdih_tab period='a']` - This shows a full list of events in date order and includes the event type.
* `[tdih_tab show_types=0 type='birth' classes='content dark']` - This shows events but not type for the event type (slug) of birth. " content dark" will be added to the table's class.
* `[tdih_tab day=20 month=8 date_format='Y']` -  This shows events on 20th August in any year. Format the date to only show the four digit year.
