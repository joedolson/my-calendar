=== My Calendar ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: calendar, dates, times, event, events, scheduling, schedule, event manager, event calendar, class, concert, venue, location, box office, tickets, registration
Requires at least: 4.4
Tested up to: 4.9
Requires PHP: 5.3
Stable tag: 3.0.12
Text domain: my-calendar
License: GPLv2 or later

Accessible WordPress event calendar plugin. Show events from multiple calendars on pages, in posts, or in widgets.

== Description ==

My Calendar does WordPress event management with richly customizable ways to display events. The plug-in supports individual event calendars within WordPress Multisite, multiple calendars displayed by categories, locations or author, or simple lists of upcoming events.

Easy to use for anybody, My Calendar provides enormous flexibility for designers and developers needing a custom calendar. My Calendar is built with accessibility in mind, so all your users can get equal access and experience in your calendar.

= Premium Event Management =
Looking for more? [Buy My Calendar Pro](https://www.joedolson.com/my-calendar/pro/), the premium extension for My Calendar to add support for user-submitted events, integration between posting and event creation, and import events from outside sources.

= Selling event tickets? =
Do you need to sell tickets for events? [Use My Tickets](https://wordpress.org/plugins/my-tickets/) and sell tickets for your My Calendar events. Set prices, ticket availability, and sell multiple events at the same time using My Tickets.

= Features: =

*	Calendar grid and list views of events
*	Monthly, weekly, or daily view.
*	Mini-calendar for compact displays (as widget or as shortcode)
*	Widgets: today's events, upcoming events, compact calendar, event search
*	Custom templates for event output
*	Limit views by categories, location, author, or host
*	Editable CSS styles and JavaScript behaviors
*	Schedule recurring events.
*	Edit single occurrences of recurring events
*	Rich permissions handling to restrict access to parts of My Calendar
*	Email notification to administrator when events are scheduled or reserved
*	Post to Twitter when events are created (using [WP to Twitter](http://wordpress.org/extend/plugins/wp-to-twitter/))
*	Managing locations
*	Fetch events from a remote database. (Sharing events in a network of sites.)
*	Multisite-friendly
*	Integrated help page
*	Shortcode Generator to create customized views of My Calendar

= What's in My Calendar Pro? =

> * Let your site visitors submit events to your site (pay to post or free!).
> * Let logged-in users edit their events from the front-end.
> * Create events when you publish a blog post
> * Publish a blog post when you create an event
> * Advanced search features
> * Responsive mode
> * Import events from .ics or .csv formats via file or URL.

= Translations =

Visit [Wordpress Translations](https://translate.wordpress.org/projects/wp-plugins/my-calendar) to check progress or contribute to your language.

Translating my plug-ins is always appreciated. Visit <a href="https://translate.wordpress.org/projects/wp-plugins/my-calendar">WordPress translations</a> to help get your language to 100%!

== Installation ==

1. Upload the `/my-calendar/` directory into your WordPress plugins directory.

2. Activate the plugin on your WordPress plugins page

3. Configure My Calendar using the settings pages in the admin panel:

   My Calendar -> Add New Event
   My Calendar -> Manage Events
   My Calendar -> Event Groups
   My Calendar -> Add New Location
   My Calendar -> Manage Locations
   My Calendar -> Manage Categories
   My Calendar -> Style Editor
   My Calendar -> Script Manager
   My Calendar -> Template Editor
   My Calendar -> Settings
   My Calendar -> Help

4. Visit My Calendar -> Help for assistance with shortcode options or widget configuration.

== Changelog ==

= 3.0.12 =

* Bug fix: My Calendar could prevent canonical link from displaying if canonical link being filtered by another application.
* Modernize & improve Akismet integration.
* Add filter to disable Akismet checks.

= 3.0.11 =

* SECURITY: XSS - Canonical URL not properly sanitized. Affects 3.0.0 and up. 

= 3.0.10 =

* Bug fix: invalid method used to sort location lists.
* Bug fix: shortcode generator missing input value
* Bug fix: datepicker did not reflect start of week settings
* Stylesheet CSS change

= 3.0.9 =

* Bug fix: Error thrown if Akismet had previously been configured, then deleted.
* Bug fix: location type was added to params if category key was set.
* Bug fix: remove a couple notices
* Bug fix: category relationships not carried over when recurring events split

= 3.0.8 =

* Bug fix: need to allow <a> elements in mc_strip_tags so calendar linkscan point to non-calendar URLs

= 3.0.7 =

* Bug fix: Case where events ending at midnight (AM) of current day were displayed
* Bug fix: trim spaces from values earlier when parsing filter elements
* Change: don't declare font-family in older stylesheets. 

= 3.0.6 =

* Bug fix: Shortcode for locations forms always rendered as if in a group filter.
* Bug fix: If the default length 1 hr event pushes into next day, adjust length.
* Bug fix: Incorrectly nested parentheses caused math error in months-by-day recurrence

= 3.0.5 =

* Bug fix: If only one event on a day, event title did not show in list view with show title option.
* Bug fix: Incorrect array key for fallback parameter in widget
* Bug fix: custom template query expected 25 chars instead of 32
* Re-allow <br> in event titles.

= 3.0.4 =

* Bug fix: aria-current test was broken for current date
* Bug fix: Private categories not disambiguated in MySQL query when excluded
* Improve: Rewrite my_calendar_copyr backup functions to use WP core functions.

= 3.0.3 =

* Bug fix: Category key needed to use a 'WHERE' not an 'AND'; broke output if limiting by category
* Bug fix: Error thrown in style editor & category editor if custom directory did not exist

= 3.0.2 =

* 3.0.1 did not correct the right error. Correct fix.

= 3.0.1 =

* Bug fix: install error on update.

= 3.0.0 =

* Bug fix: If category deleted, set events with that category to default cat, not cat ID 1.
* Bug fix: Date/time comparison used front-end date value instead of dtstamp in upcoming events.
* Bug fix: Navigation issue if beginning of week is in previous month
* Bug fix: Event conflict didn't catch events 100% contained inside other events.
* Bug fix: Private categories should not be visible to public users in submission forms or category lists
* Bug fix: aria-current key term value was translatable
* Bug fix: If editing single instance, location is removed
* Bug fix: don't show location control notices on front-end
* Bug fix: correcting event recurrence did not always remove meta flag
* Bug fix: Only output map HTML if API key provided
* Bug fix: character set and collation determination on install & update
* Bug fix: When changing recurring events, only change instance IDs if the date of the instance has changed.
* Bug fix: Event post should not change post date on update
* Bug fix: All day events should export correctly to Outlook & Apple Calendar
* Bug fix: Location control accordion was not accessible.
* Bug fix: Term ID was not set in category manager if term already existed.
* Bug fix: Make sure that the 's' query var is not automatically added to My Calendar URLs

* Add: several new filters
* Add: notice to alert users if their calendar configured for remote event source.
* Add: map display to back-end location manager.
* Add: location search in location manager
* Add: ability to filter location lists used to submit data 'mc_get_locations'
* Add: Support for multiple categories on events.
* Add: stylesheet (Twenty Eighteen)
* Add: CSS variables support
* Add: list of problem events in Manage Events sidebar
* Add: add months shown in list view to shortcode parameters
* Add: support for auto-refresh of cache for a variety of caching plug-ins.
* Add: Option to remove event data on uninstall
* Add: filter to define events as private via custom methods
* Add: event preview
* Add: location support to mini calendar widget
* Add: CSS code editor available in Style editing
* Add: HTML code editor available in Template editing
* Add: Schema.org address markup
* Add: Schema.org event markup
* Add: Include event link in 'Add to Google Cal' content.
* Add: date format for multi-day dates in grid view.

* Removed: event open & event closed text settings
* Removed: event_open event status (little used and confusing; replaced by My Tickets)
* Removed: guessing calendar install location
* Removed: event cache code
* Removed: upgrade routines from 1.11.x
* Removed: mc_widget_defaults option
* Removed: user's guide references

* Change: default image sizes from 'medium' to 'large'
* Change: Remove ability to disable event approval; remap "approval" to "draft"
* Change: default number of results to show in advanced event search.
* Change: Switched from image to icon font for close button
* Change: Major changes to event fetching
* Change: Major changes to code organization
* Change: Added caching on database engine query
* Change: if event location set in dropdown, event will always display location as shown in location manager
* Change: changed argument style for major functions to arrays of arguments
* Change: move Location Manager to separate page; add location sorting.
* Change: Move exif_ fallback function into utilities include
* Change: Moved location & category specific settings
* Change: Simplified texts in several locations
* Change: Clearer UI on location input limits
* Change: autotoggle end date minimum input when start date set
* Change: Reorganized input fields
* Change: Generate separate iCal exports for Google Calendar or Outlook
* Change: Constrain tabbing within details pop-up
* Change: Close details pop-up with Esc key
* Change: Audited options to remove unused or unneeded options
* Change: Create a referential template when shortcode generated
* Change: Feeds nav panel now shows subscription links; exports are available in 'exports' panel.

= 2.5.17 =

* Security: Authenticated XSS vulnerability resolved.
* Remove 'create_function' for PHP 7.2 compatibility.
* Updated: Upgrade Notice output.

= 2.5.16 =

* Bug fix: Event deletion action executed when individual instance deleted from front-end
* Updates: due to esc_sql function changes in WordPress 4.8.3

= 2.5.15 =

* Bug fix: Jumpbox rendered October as January due to unneeded character replacement

= 2.5.14 =

* Bug fix: saving setting for main calendar URL from front page doesn't work
* Bug fix: esc_url only in appropriate places
* Bug fix: Recognize month parameter from shortcode in navigation elements
* Bug fix: 404s for deleted events
* Bug fix: Print styles handle date in week view better
* Bug fix: Events not visible in list with list JS disabled
* Bug fix: SQL query for conflict checking threw errors
* New option: list all events in list view with JS

= 2.5.13 =

* Bug fix: Categories can not be part of the md5 hash used to identify unique tables (breaks AJAX nav for categories)
* Bug fix: recurring scheduling for week-days only not functional when 7 days or greater
* Bug fix: Print view location filters broken
* Bug fix: Make AJAX scripting aware of which other scripts are enabled.
* Bug fix: Sort scheduled dates for event by date
* Bug fix: JetPack Grunion Contact form interfered with TinyMCE in contexts outside of post editor (https://github.com/Automattic/jetpack/issues/7598)
* Bug fix: ensure date is retained if datepicker disabled
* Bug fix: archived events filter marked as active when not

= 2.5.12 =

* Bug fix: missing space in conflict identification
* Bug fix: internationalization of string to time created conflict when entering month abbreviations
* Add filter to enable creation of a custom content editor

= 2.5.11 =

* Bug fix: Bottom mass action buttons outside of form
* Bug fix: User select form used 'checked' instead of 'selected'

= 2.5.10 =

* Bug fix: allow parsing of non-English strings through strtotime()
* Bug fix: trim whitespace off array keys in location controller
* Bug fix: Don't display 'Add Event' menu in adminbar if remote event database is enabled
* Bug fix: All day events correctly exported in iCal files
* Bug fix: Footer navigation not shown on single day view
* Bug fix: Execute AJAX navigation from both header and footer containers
* Bug fix: {icon_html} returned broken image if category had no assigned icon
* Removed obsolete PHP 4 compatibility for clone keyword
* Added hook to prevent activation if PHP version below 5.3.0.
* New filter: define custom target calendar URL via 'mc_get_uri' filter
* New action: 'mc_insert_recurring' run while creating event instances
* New filter to customize default event length: 'mc_default_event_length'
* New filter: 'mc_show_week_number' to turn on column indicating displayed week's number. (props Josef Fällman)
* UI Change: Duplicate navigation and search on events list at bottom of list
* Miscellaneous improvements to the My Calendar Filters shortcode: set target URL & change location search type, add as widget
* New widget: My Calendar event filters
* Added inverse color style declaration to category color template tag

= 2.5.9 =

* Bug fix: class .mc-main appeared twice in day view
* Bug fix: iCal output fetches no data on subsites in multisite networks
* Bug fix: broken image upload script due to localization change
* Bug fix: sorting events by category should sort by name, not ID
* Add site name to .ics output file

= 2.5.8 =

* Bug fix: mc-ajax.js referred to a class that did not always exist
* Bug fix: Cases missed in interpreting category class values
* Bug fix: For backwards compatibility, ensure that spaces are replaced with hyphens in category classes
* Bug fix: Check whether templates returned are empty & ensure fallback renders
* Bug fix: revise FOUC implementation to avoid jQuery not defined errors

= 2.5.7 =

* Bug fix: notice in event image field if input disabled
* Bug fix: class setting was based on GMT timestamp according to MySQL
* Bug fix: PHP notice thrown if requested template doesn't exist
* Bug fix: support for embedding videos via iFrame.
* Bug fix: JS refinements to AJAX loading; changing formats can cause panel closing not to fire due to .list/.calendar switching
* Bug fix: JS refinements to AJAX loading; make sure everything works when positioned in the header or are excluded
* Bug fix: always provide a category class that's valid
* Bug fix: If mini calendar links set to open to new page, automatically disable JS
* Bug fix: If special options hidden, always set to 'true' on event save.
* Added: aria-current for current date.
* Improve KSES implementation
* Improved URL building
* Improvements to print CSS
* Improvements to sortable CSS
* New filter: 'mc_category_icon'
* New action: 'mc_print_view_head'

= 2.5.6 =

* New filter: mc_user_can_see_private_events to change criteria for visibility of private events
* New filter: mc_private_categories to tweak which categories are considered private
* Bug fix: PHP warning due to cache query occurring when caching is not enabled
* Bug fix: images entered only as URLs deleted on edit
* Accessibility: aria-expanded attached to wrong element in list view
* Accessibility: ornamental icon fonts exposed to screen readers

= 2.5.5 =

* Bug fix: notices when generating classes for upcoming events
* Bug fix: RSS feed should respect private categories
* Bug fix: Events happening now shortcode should respect private categories
* Bug fix: iCal output should respect private categories
* Bug fix: @ suppressed notices in template tag parsing. props @uscore713
* Bug fix: eliminate two notices in upcoming events class parsing
* New filter: mc_draw_upcoming_event
* New filter: mc_draw_todays_event
* Marked as compatible with 4.7

= 2.5.4 =

* Add New link on Manage Events screen
* Add new link on Edit categories screen
* Add new link on Edit locations screen
* Changed maxlength on recurrence unit field to 2
* Eliminate two notices generate on manage events screen
* Two incorrect method_exists checks; should be property_exists

= 2.5.3 =

* Bug fix: prevent non-object warning in check for notime text
* Bug fix: missing classes from some instances of upcoming events list
* Bug fix: Only show invalid format/time errors if user with permissions.
* Enhancement: Include invalid format/time in error message.
* Performance: In single event shortcode, break out of foreach if list of related events not being produced.

= 2.5.2 =

* Bug fix: Make sure that upcoming events element filters operate in all cases
* Bug fix: Permit {register} template tag to pass additional attributes
* Bug fix: Add class to permitted attributes on span tag

= 2.5.1 =

* Bug fix: Multi-word category titles not hyphenated in event classes
* Bug fix: Add `{related}` template tag to documentation
* Bug fix: Today's events template broken
* Add 'past-event' and 'future-event' classes to related event list & main events lists

= 2.5.0 =

* Update hcalendar structures
* Better handling when updating event taxonomies
* Options to restrict management of events by category / user
* UI Clean up
* Don't display format toggle on mobile if automatic format switching enabled
* Add custom date option to upcoming events shortcode builder
* Improved error message if user creates event with an invalid recurring cycle
* Updated template editor; ability to create custom templates.
* Add option to add new dates for an existing event.
* For single event, show closest available date if no/invalid date ID provided.
* Added first occurrence data to core event object
* New template tag: {related} to list other events in the same group
* New loading indicator for AJAX navigation
* New filter to modify event classes
* New function to generate event classes
* Reduce number of strings in plug-in to reduce burden on translators
* Multisite: ability to display calendar for any site on any other site
* in my_calendar_draw_event(), add filter to hide additional days of events
* Improved HTML filtering to allow input elements and schema.org attributes.
* Add support for Google Maps API key field, now required for use of Google Maps on new sites
* Add 'today' keyword for the upcoming events 'to' attribute
* Updates to Help documentation
* Bug fix: auto assign events with no category to 'General'
* Bug fix: some user select lists overwrote select list options
* Bug fix: new events with no times entered need to be created as all day events
* Bug fix: wrong number of arguments passed to mass delete events hook
* Bug fix: Custom JS incorrectly escaped in Script manager
* Bug fix: removed numerous notices
* Bug fix: improved handling of missing event posts
* Bug fix: allow more HTML elements & attributes
* Bug fix: misc. notices

Breaking Changes:

* Breaking change: minor changes to classes to improve structured data in microformats
* Breaking change: upcoming events widget no longer uses ID 'upcoming-events'; use class '.upcoming-events'
* Breaking change: today's events widget no longer uses ID 'todays-events'; use class '.todays-events'

= 2.4.21 =

* Bug fix: Google Maps format change to latitude/longitude links
* Bug fix: Use short description directly as {excerpt} if provided.

= 2.4.20 =

* Bug fix: PHP warning triggered on type conversion when toggling time views.
* Bug fix: Map template tag returned raw scripts without `<script>` tags.

= 2.4.19 =

* IMPORTANT: SECURITY RELEASE
* Security fix: XSS vulnerability: user who could create or edit an event could insert a XSS attack.
* Security fix: Phishing vulnerability: user who could create or edit an event could insert an iFrame for phishing
* Security fix: Possible to programmatically alter the event being edited to push edits into a different event.
* Security fix: Possible to programmatically alter the author of the edited event.

Other changes in this release:

* Screen-reader-text class was duplicated on mini calendar dates
* New class: 'all-categories' on all categories link
* New filter: filter All Categories text
* New filter: filter Map URL & Map Label
* Bug fix: when sequentially switching from Month to Week to Month & back to Week, Week would revert to first week of month.
* Bug fix: Maintain current view when switching categories

= 2.4.18 =

* Add permalink settings notice to field note for clearer instructions.
* New filter: inner content filter for templates.
* Improve template handling when partial event passed to template
* Add filters to alter wrapper elements in Today's Events & Upcoming Events lists.
* Bug fix: {excerpt} template tag had invalid if/else logic.
* Remove files for Spanish, French, Polish, Portuguese, Japanese, and Czech translations in favor of language packs

= 2.4.17 =

* Bug fix: Google Maps calendar ignored Latitude/Longitude.
* Bug fix: missing ID attribute on form field
* Bug fix: replace an anchor with a button in admin
* Bug fix: missing label in manage events
* Bug fix: missing quote broke id attribute on manage categories
* Bug fix: duplicate IDs in tab UI structure on Settings page
* Add support for selective refresh in customizer
* Updated HTML hierarchy

= 2.4.16 =

* Minor CSS updates in calendar stylesheets
* Bug fix in widgets
* Bug fix in list JS with focus management (accessibility)

= 2.4.15 =

* Increase field length allowed for event location fields
* Picker CSS improvements
* Bug fixes on event search queries

= 2.4.14 =

* Bug fix: possible SQL error if event ID not saved in event post on event creation.
* Bug fix: database didn't allow recurring spacing larger than 9; input allowed up to 12.
* Bug fix: multiple uses of {dtstart format=''} in upcoming events caused repeated data.
* Bug fix: Escaping of address string in Google Maps
* Bug fix: Pass parameter to indicate whether calendar is rendered in widget or shortcode. Only render single view in shortcode.
* Bug fix: Custom stylesheets had to have same names as standard stylesheets
* Bug fix: Print view return link returns to previous page instead of My Calendar URL.
* Bug fix: Potential broken image icon in category manager
* Prep: Eliminate references to add_object_page(), deprecated in 4.5
* Prep: Eliminate referneces to get_currentuserinfo(), deprecated in 4.5
* Add Filter: make time interval filterable ('mc_interval')

= 2.4.13 =

* Allow feeds to show on mini calendar widget
* Bug fix (performance); only check table type for current table of interest.
* Bug fix: Allow mini widget calendar title to be blank.
* Bug fix: Catch some instances where a SQL error could be generated by missing data.
* Bug fix: Removed i18n of calendar day classes to avoid breaking HTML in non-latin languages. [Potentially breaking change]
* Bug fix: Improvement to stylesheet notices when file editing disallowed in WordPress
* Bug fix: Notice could be thrown if template parts not all set.
* Removed: Deprecated remaining parts of the migration path from 1.7.0 and earlier.
* Revalidate RSS feed
* Add filter to enable alerts on ical events.

= 2.4.12 =

* Bug fix: allow / character in permalink formats
* Bug fix: missing reference in CSS for Google Maps images
* Bug fix: Single event delete broken
* Bug fix: if event deleted from Manage Events screen, stay on Manage Events instead of shifting to Add New.
* Bug fix: Improper i18n in events list heading

= 2.4.11 =

* Bug fix: remove category parameter from 'All Categories'
* Bug fix: Invalid closing </th>
* Bug fix: Update gmap3 to version 6.0
* Bug fix: CSS conflict with max-width can cause Google Maps image to fail to render correctly.
* Bug fix: duplicate ID in list view breaking layout.
* I18n fix: Make accessibility strings translatable without requiring filters
* Change: Open list panels with a button.
* Update: Rewrote mc-list.js

= 2.4.10 =

* Bug fix: Better detection of whether or not multisite support is available.
* Bug fix: Stop disabling JS on mobile if format conversion is enabled.
* Bug fix: Pretty permalinks display of event date/time broken on recurring events.
* Bug fix: Handle use of using_index_permalinks() and produce correct URLs.
* New filter: 'mc_use_custom_template': pass a file name or template name to use a custom template for a given event display.
* Language updates: French, Russian, Catalan, Italian

= 2.4.9 =

* Bug fix: Make iCal support elimination of holiday collisions
* Bug fix: Compensate for other plug-ins defining their own tab styles on My Calendar's settings
* Bug fix: Fallback to My Calendar DB images if featured images missing on post.
* Add support: Search events without requiring MyISAM MySQL engine.
* Language updates: Portuguese (Brazil), German

= 2.4.8 =

* Bug fix: Md5 hash on arguments includes format & timeframe, so switching between options broke CID references
* Bug fix: clear undefined index notice occurring only under specific unusual server configurations

= 2.4.7 =

* Update Italian translation
* Bug fix: Ensure that mini calendar widgets have unique IDs
* Eliminate an obsolete variable.

= 2.4.6 =

* Bug fix: I just can't stop making stupid mistakes in print view. Sheesh.

= 2.4.5 =

* Mislabeled form field on date switcher.
* Add primary sort filter to main event function [props @ryanschweitzer]
* New filters on navigation tools.
* Bug fix: Print view loaded when iCal requested [broken in 2.4.4]
* Bug fix: Changes to Upcoming Events widget to better limit upcoming events lists.
* Language updates: Czech, Swedish, Finnish

= 2.4.4 =

* Bug fix: Stray character return in Print view
* Bug fix: Print view did not respect date changes
* Bug fix: Logic error in sort direction switching in admin when setting not configured
* Change: Print view no longer driven by feed API.
* Change: Added option to disable "More" link from settings

= 2.4.3 =

* Bug fix: reversed filter name/value pairing in SQL query.

= 2.4.2 =

* Bug fix: in Upcoming Events shortcode (mismatch between documentation & reality).

= 2.4.1 =

* Bug fix: Missing style in print.css
* Bug fix: Broken <head> in print view.

= 2.4.0 =

New features:

* Set upcoming event class based on time, rather than date.
* Add past/present classes to today's events widget
* Assign Custom All Day label for each event.
* Support hiding 'Host' field as option.
* Made primary sort order of events filterable: 'mc_primary_sort'
* Added action to location saving handling updated locations
* Added arguments to from/to filters in Upcoming Events
* Enabled option to turn on permalinks
* Custom canonical URL for event pages
* Added 'date' parameter to today's events list & shortcode accepting any string usable in strtotime()
* Added 'from' and 'to' parameter to upcoming events list & shortcode accepting any string usable in strtotime
* Added year/month/day parameter to main shortcode to target specific months for initial display.
* Make BCC field filterable
* Add filters to search query parameters
* New option: switch to mini calendar on mobile devices instead of list view.
* Add [day] select field to date switcher if in 'day' view.
* Option to set default sort direction
* Ability to set three separate event title templates: grid, list, and single.
* Added admin-bar link to view calendar.
* Added option to customize permalink slug on permalink page
* Single event pages as permalinks use the same template as main if custom template isn't enabled.
* New template tag: {color_css} and {close_color_css} to wrap a block with the category background color.
* Add category classes to Upcoming & Today's events widgets
* Redirect page to main calendar if event is private
* Improved labeling of cell dates

Bug fixes:

* Stop setting all day events to end at midnight; use 11:59:59 and filter output
* Rewrite iCal output so that the iCal download eliminates Holiday cancellations
* Bug fix: Prevent extraneous variables from leaking into the navigation output.
* Rendering post template in permalinks only applies within Loop.
* Template attribute preg_match could only pick up 2 parameters
* Prevent an invalid mc_id value from returning errors.
* Prevent deprecation notice when getting text_direction
* Default to not showing navigation options in print view.
* Better loading of text domain.
* Prevent mini calendar from switching to list format.
* Change class construction to PHP 5 syntax
* Close button is now a button rather than a link.
* Fixed display of text diff for stylesheet comparisons
* Two different filters with different names.
* mc_after_event filter not running with custom templates.
* With My Tickets active, enter key did not submit Add/Edit event form
* Fixed documentation error with ical template tags.
* Improved efficiency of WP shortcode processing in templates.
* A multi-day event crossing the current day was counted as a future event in upcoming events
* If event instance was split from recurring event, showed same recurring settings as original event.
* If events were mass deleted, the corresponding event post was not also deleted.
* Prevent single event pages from displaying content if the event is in a private category.

Important Changes:

* Removed references to #jd_calendar and generate custom IDs. [breaking change
* Revision of settings page [reorganize settings into tabs]
* Reorganized settings pages.

Other:

* Moved changelog for versions prior to 2.3.0 into changelog.txt

Translations:

* Updated Polish, Portuguese (Portugal), Dutch, Turkish, Slovak, Norwegian, Hungarian, German, Spanish, Persian, Czech, Danish

= 2.3.32 =

* Bug fix: end time for events auto-toggled to midnight, instead of +1 hour when end time omitted.

= 2.3.31 =

* Added escaping in 2.3.30 broke location & category limits (escape placed on wrong string.)

= 2.3.30 =

* Security Fix: Arbitrary File Override
* Security Fix: Reflected XSS
* Thanks for Tim Coen for responsibly disclosing these issues.
* All issues apply for authenticated users with access to My Calendar settings pages.
* Language updates: Updated Polish, Swedish, Galician, Czech, Norwegian, Italian
* Added Slovak, Icelandic, Hebrew

= 2.3.29 =

* Security Fix: XSS issue applying to improper use of add_query_arg(). See https://yoast.com/tools/wrong-use-of-add_query_arg-and-remove_query_arg-causing-xss/

= 2.3.28 =

* Bug fix: Problem saving My Calendar URI if My Calendar is intended for use behind a secured location.
* Update languages: French, German, Catalan

= 2.3.27 =

* Bug fix: Things that happen when you failed to write down a minor change - you don't test it. Couldn't choose a preset location when creating an event in 2.3.26.

= 2.3.26 =

* Typo in aria-labelledby.
* Bug fix: fatal error if wp_remote returns WP_error.
* Bug fix: could not set calendar URI if site is password protected.
* Bug fix: category key fetched icons using a different path generation than main calendar that could result in a broken link.
* Bug fix: ensure that all image template tags exist in the array, even if the event post does not exist.
* Bug fix: make print view respect current category/location filters
* Bug fix: make iCal download respect current category/location filters
* Added class on event data container for root ID of events.
* Added 'current' class for currently selected category in category key if category filter applied.

= 2.3.25 =

* Bug fix: Escape URL for search form request URL
* Bug fix in check whether event had a valid post container.
* Bug fix to handle problem with weeks calculation on the first of the month.
* Bug fix: Display problem in single-page event view in twentyfifteen.css
* Bug fix: If My Calendar URL is invalid, re-check when settings page is loaded.
* Bug fix: Don't display update notice on new installs.
* Change: My Calendar automatically generates calendar page on installation.
* Change to Upcoming Events default template to make usage more robust.
* Change: mc-mini JS to auto close all open panels if a new one is opened.
* Rearrange a few settings for better usability.
* Added ability to use Upcoming Events widget to show the nth future month. (e.g., show events for the 6th month out from today.)
* Deprecated upgrade cycles prior to version 1.11.0.
* Improve accessibility of tab panels used in My Calendar UI.
* Language updates: Updated Russian, Added Afrikaans

= 2.3.24 =

* Bug fix: In mini widget, date is not displayed if only event on date is private
* Bug fix: Improved fix to year rendering (roughly fixed in 2.3.23)
* Bug fix: Improved rendering of structured event data.
* Bug fix: [my_calendar_now] incorrectly checked the current time.
* Bug fix: "Archive" link pointed to wrong location in event manager.
* Bug fix: Was no way to reverse archiving an event; added method
* Bug fix: Shortcode generator produced incorrect Upcoming Events shortcode.
* Bug fix: Overlapping occurrences warning inappropriately showed on events recurring on a month by day basis
* Bug fix: If only event on date is private, don't add class 'has-events'
* Bug fix: Save default values for top/bottom nav on install.
* Bug fix: Restore default template array when plug-in is deleted and re-installed
* Minor style change to twentyfourteen.css
* New default theme: twentyfifteen.css
* Feature add: AJAX control to delete individual instances of a recurring event from the event editor.
* Feature change: Events post type content filter now replaces content instead of repeating. Use 'mc_event_content' filter to override.
* Improvement: Show overlapping occurrences warnings in manage events view.
* Improvement: List/Grid button only shows on month and week views.
* Misc. UI improvements.
* Performance fix: Hide overlapping recurring events on front-end. (They can consume massive amounts of memory.)
* Language updates: French, Spanish, Japanese, Dutch, German, Ukrainian, Swedish

ISSUE: What's causing templates to not be set?

= 2.3.23 =

* Bug fix: Calendar rendering 2014 at beginning of 2015.
* Bug fix: Set Holiday category when adding new categories.
* Bug fix: Search widget title heading HTML not rendered.
* Bug fix: mc-ajax.js was not compatible with heading filter for output.
* Language updates: French, Spanish, Ukrainian

= 2.3.22 =

* Edit: Allow integers up to 12 in the 'every' field for recurring events. (Previously 9)
* Bug fix: Incorrect sprintf call in {recurs} template, effecting recurring events by month.
* Language updates: German, Russian, Portuguese (Portugal), Hungarian, Ukrainian

= 2.3.21 =

* Plug-in conflict fix: CSS override to fix conflict with Ultimate Social Media Icons
* Bug fix: Allow {image_url} to fall back to thumbnail size if no medium / create _url equivalents for each size.
* Bug fix: Allow location controls to be entered with only keys.
* Bug fix: Entering default value for controlled locations is empty value, instead of 'none'.
* Bug fix: If value of location field is 'none', don't display.
* Bug fix: Use Location URL as map link if URL is provided and no other mappable location information
* Bug fix: if editing single instance, delete link will delete just that instance.
* Bug fix: If recurring event fields were hidden, but event recurred, recurrences would be deleted.
* Bug fix: Limiting locations did not work in Upcoming Events using 'events' mode.
* Bug fix: Allow limiting locations but all event location fields.
* Bug fix: Limiting locations accepts numeric values for limiting.
* Bug fix: {recurs} template tag indicates frequency ("Weekly", vs "every 3 weeks")
* Bug fix: fixed templating issue when custom templates used a tag multiple times with different attribute parameters.
* Add filter to modify the title information shown in list view to hint at hidden events ('mc_list_event_title_hint')
* Add filter: number of months shown in list view filterable on 'mc_show_months'
* Feature: Add shortcode/function to display a current event. [my_calendar_now]
* Feature: Add search results page option to calendar search widget.
* Removed all remaining code related to user settings, which are no longer in use.
* Language updates: French, Danish, Russian, Swedish, Portuguese/Brazil, Portuguese/Portugal, Norwegian Bokmal, Hungarian

= 2.3.20 =

* Bug fix: Escaped $ variable in custom JS wrapper
* Bug fix: has-events class appearing in calendar on days after all-day events
* Bug fix: Reset stylesheet applied outside calendar HTML. Eliminated elements not used by MC.
* Bug fix: Missing required argument for My Calendar search form widget
* Bug fix: 'Approve' link broken
* Bug fix: Details link could return expired event links.
* Translation updates: Spanish, Slovenian

= 2.3.19 =

* Bug fix: Could not un-check show today's events in Upcoming Events widget
* Bug fix: Could not turn off event recurrences section in event manager
* Bug fix: stripped HTML tags out of upcoming events & today's events template fields

= 2.3.18 =

* Bug in rendering of custom JS causing visible rendering of code.
* Bug in saving Today's Events widget settings

= 2.3.17 =

* 2.3.16 bug fix was incomplete, triggered new error. Sorry for rushing!

= 2.3.16 =

* Bug fix: Upcoming events did not show for logged-in users if site did not have private categories defined.
* Cleared a PHP notice.

= 2.3.15 =

* Bug fix: Controlled locations not input correctly from Add Event form
* Bug fix: Use force_balance_tags() when saving descriptions & short descriptions to prevent descriptions from breaking layout
* Bug fix: My Calendar reset stylesheet missing .mc-main on buttons; causing display issues with submit buttons.
* Bug fix: shortcode generator produced results in disabled form field; changed to readonly because Firefox does not permit selecting text in disabled fields.
* Bug fix: Widget navigation automatically reset itself if you saved widget form after clearing data
* Bug fix: category classes for multi-day, all-day events showed on termination date
* Bug fix: Checkbox states on JS scripts not retained
* Bug fix: Show default values in upcoming events widget
* Bug fix: Default values not saved on new installation
* Bug fix: Admin event manager should sort by Date/Time instead of Date/Title
* Documented [my_calendar_search] shortcode
* Added 'current' option for author/host to shortcode generator.
* Extensive code clean up
* Feature: Default view next month option in calendar and upcoming events lists.
* Deprecated upgrade cycles prior to version 1.10.0.
* Language updates: Japanese, Dutch, Italian, Spanish, Finnish, Swedish, Norwegian

= 2.3.14 =

* Bug fix: Disabled front-end event editing links for logged-in users.
* Language updates: Spanish, Norwegian, Hungarian

= 2.3.13 =

* Bug fix: Failed to handle "open links to event details" option in updated JS handling.

= 2.3.12 =

* Bug fix: change of option name meant that you couldn't enable/disable scripts.
* Bug fix: shortcode generator generates a 'readonly' textarea instead of disabled so it can be copied in Firefox.
* Accessibility: handle assignment of focus on AJAX navigation

= 2.3.11 =

* Change: Modified default JS saving so that only custom JS gets handled in editor.
* Change: toggle to enable/disable custom JS; default to off
* Change: Moved scripting into files.
* Notice: admin notice to inform users of need to activate JS if using custom
* Bug fix: Modify default JS so wpautop doesn't cause problems with toggles.
* Bug fix: External links displaying is_external boolean instead of classes.
* Bug fix: mysql error if location_type not defined but location_value is.
* Bug fix: page_id unset when default permalinks in use. [Ick. Don't use default permalinks.]
* Bug fix: My Calendar navigation panel could not disable top/bottom navigation.
* Feature: * Add Bcc notification list
* Accessibility: improvements to pop-up event details: focus & closing, ARIA
* Filter: headers filter for My Calendar email notifications.
* Filter: Add detection to pass custom JS from custom directory/theme directory
* Updated French, Spanish translations.
* Removed .po files from repository; reduces file size by over 2 MB.

= 2.3.10 =

* New filter: mc_jumpbox_future_years - alter the number of years into the future shown in the calendar date switcher.
* New filter: mc_add_events_url - alter URL for Add Events in adminbar; return URL
* New filter: mc_locate_events_page: alter menu parent of Add Events in admin menu; return menu slug or null
* Bug fix: ltype and lvalue not passed from shortcode into handler for upcoming events.
* Bug fix: disable comments by default for event post storage.
* Bug fix: misnamed variable in filter; resolves notice on line 239 of my-calendar-output.php
* Bug fix: do search and replace on default scripting as well when script fields are blank
* Bug fix: Check default option for import data from remote database; verify the default will be false
* Added template tag: {linking_title}; same as {link_title}, but falls back to details link if no URL input for event.
* Change default widget template to use {linking_title}.
* Security: Two XSS vulnerabilities fixed. Thanks <a href="http://www.timhurley.net/">Tim Hurley</a>
* Update Translation: Russian

= 2.3.9 =

* Bug fix: Minor event templates ( title, detail, etc. ) were not properly escaped in admin forms.
* Bug fix: use reply-to email header in support messages
* Bug fix: Mass approval of pending events broken.
* Bug fix: {linking} template tag referenced wrong event URL.
* Bug fix: My Calendar API RSS no longer dependent on default RSS data.
* Bug fix: Replace mysql_* functions for PHP 5.5 compatibility.
* Bug fix: Incorrect template tag in Single view template: {gcal} instead of {gcal_link}
* Bug fix: PHP notice on $map
* Language updates: Japanese, German, Italian

= 2.3.8 =

* Added {link_image} to add an image linked to the event URL in templates.
* Bug fix: extended caption value saved but not shown.
* Bug fix: For multi-day events ending at midnight, last date automatically extended one day at save.
* Bug fix: on copy, if start date is changed, but end date isn't, increment end date to match length of original event.
* Change: Eliminate error on empty title fields or invalid recurrence values. Set to default value instead.

= 2.3.7 =

* Did not enqueue jQuery on front-end unless Google Maps was enabled. (Incorrect condition nesting...) Whoops.

= 2.3.6 =

* Error in yesterday's bug fix for upcoming events.
* Bug fix: Email notifications broken.

= 2.3.5 =

* Bug fix: Notice in today's events widget
* Bug fix: Images from pre 2.3.0 configuration did not display in default Single event view.
* Bug fix: Upcoming events list could return too few events.
* Bug fix: Display default date format if format not set.
* Bug fix: Fallback to default JS if custom JS not defined.
* Filter: added filter to Google Maps code; mc_gmap_html
* Option: enabled option to disable Google Maps output.

= 2.3.4 =

* Bug fix: Week date format wouldn't save.
* Bug fix: Event posts & custom field data not saved on copy action
* Bug fix: HTML errors in {hcard} address format.
* Bug fix: Manage events search form overlapped pagination links
* Bug fix: Events ending at midnight in Today's Events lists appeared twice

= 2.3.3 =

* Bug fix: Notice on access_options filter.
* Bug fix: Invalid date values if no parameters set for iCal
* Bug fix: Invalid nonce check in location entry prevented creation of new locations. One missing exclamation point. Sigh.
* Bug fix: If location controls are on, allow old values to be saved, but raise notice that value is not part of controlled set.
* Feature: add sync=true to root iCal URL to connect apps for scheduled syncing. (http://example.com/feeds/my-calendar-ics/?sync=true)
* Updated: Polish translation

= 2.3.2 =

* Bug fix: label change to clarify entry format for location controls
* Bug fix: Missing end tag on <time> element
* Bug fix: my_calendar_search_title can handle missing 2nd argument
* Bug fix: Add "active" class span on time toggle active case.
* Bug fix: Recurring all-day events showing twice
* Bug fix: Non-editable fields for date/time input broke occurrences & restricted time options
* Bug fix: Category filtering broken when holiday categories enabled
* Bug fix: Double check whether categories exist and throw error if not, after attempting to create default category.
* Feature: Mass delete locations

= 2.3.1 =

* Bug fix: PHP warning on event save
* Bug fix: PHP Notices generated on deleted author/host value.
* Bug fix: Pop-up calendar for date entry had incorrect day labels
* Bug fix: Editing individual date instances issues.
* Bug fix: {image} fallback for pre 2.3.0 uploaded images
* Added: secondary sort filter for main calendar views; default event_title ASC. Field and direction must be provided to change.
* Updated my-calendar.pot

= 2.3.0 =

This is a major revision.

* Bug fix: Manage events screen showed no data for users without manage_events permissions.
* Bug fix: if single event set, could not filter to time period views.
* Bug fix: 'single' template ID not passed into template filter.
* Bug fix: events in private categories appeared in time-based upcoming events lists.
* Bug fix: RSS feed encoding.
* Bug fix: Turn-of-year issues with week view.
* Bug fix: Added new locations multiple times if added with multiple occurrences of an event.
* Bug fix: In some browsers, time selector added invalid data.
* Bug fix: List of search results not wrapped in a list element.
* Bug fix: Trim spaces on above/below navigation strings.
* Bug fix: If an event ends at midnight, automatically end tomorrow unless set for a later date.
* Bug fix: Don't show events on both days if they end at midnight.
* Bug fix: Don't attempt to enqueue jquery.charcount.js if WP to Twitter not installed.
* Bug fix: Dates didn't strip links in list view when JS disabled for that view.

* New template tag: {runtime} to show human language version of length of event.
* New template tag: {excerpt} to create autoexcerpt from description field, using shortdesc if it exists.

* New feature: Accessibility features for locations.
* New feature: Specify accessibility services for events.
* New feature: ticketing link field
* New feature: event registration information fields
* New feature: my_calendar_event shortcode can query templates by keyword (list,mini,single,grid).
* New feature: filter events by available accessibility services
* New feature: Combined filter shortcode to group all filters into a single form. [mc_filters show='locations,categories,access']
* New feature: new API for adding custom fields to events.
* New feature: data API to fetch event data in JSON, CSV, or RSS formats.
* New feature: Archive events to hide from admin events list.
* New feature: Control input options for multiple types of location input data.
* New feature: Shortcode generator for primary, upcoming, and today's events shortcodes.
* New feature: admin-side event search
* New feature: category key now acts as quick links to filter by category
* New feature: Option to add title to Event Search widget.

* New filter: mc_date_format for customizing date formats.
* New filter: customize search results page: mc_search_page
* New filter: mc_use_permalinks to enable use of custom post type permalinks for single event pages.
* New filter: mc_post_template to customize template used in single event shortcode automatically inserted into custom post type pages.

* New design: new stylesheet available: twentyfourteen.css

* Updated: added more fields to search on events.
* Updated: updated image uploader to use add media panel and store attachment ID
* Updated: <title> template supports all template tags (but strips HTML.).
* Updated: Various aspects of UI
* Updated: Date/time selectors. See http://amsul.ca/pickadate.js/, MIT license.

* Reorganized default output template code.
* Import all used locations into location manager.
* Removed User settings fields.
* Moved Holiday category assignment to Category Manager.
* Improved get current URL function.
* iCal output in multiple-month view outputs all displayed months.
* {map} template tag to display a Google Map using the Google Maps API. (Not available in pop-up displays.)
* Scheduled removal of showkey, shownav, toggle, and showjump shortcode attributes.
* Removed upgrade support for 1.6.x & 1.7.x series of My Calendar.

= Future Changes =

* Refactor options storage
* Revise month by day input & calculation methods
* Bug: if save generates error, creates ton of notices. [eliminate $submission object and use single object model]
* Add ability to limit by multiple locations (e.g., view all events in Location 1 & Location 2; only on lvalue)
* JS to delete events from front-end when logged-in
* TODO: delete this instance and all subsequent instances

== Frequently Asked Questions ==

= Hey! Why don't you have any Frequently Asked Questions here! =

Because the majority of users end up on my web site asking for help anyway -- and it's simply more difficult to maintain two copies of my Frequently Asked Questions. Please visit [my web site FAQ](http://www.joedolson.com/my-calendar/faq/) to read my Frequently Asked Questions!

= This plug-in is really complicated. Why can't you personally help me figure out how to use it? =

I can! But not in person. Take a look at my [documentation website for My Calendar](http://docs.joedolson.com/my-calendar/) before making your request, and consider [making a donation](https://www.joedolson.com/donate.php)!

= Can my visitors or members submit events? =

I've written a paid plug-in that adds this feature to My Calendar, called My Calendar Pro. [Buy it today](https://www.joedolson.com/my-calendar/pro/)!

= Is there an advanced search feature? =

The search feature in My Calendar is pretty basic; but buying My Calendar Pro gives you a richer search feature, where you can narrow by dates, categories, authors, and more to refine your event search.

== Screenshots ==

1. Monthly Grid View
2. List View
3. Event management page
4. Category management page
5. Settings page
6. Location management
7. Style editing
8. Template editing

== Upgrade Notice ==

* 3.0.11 URGENT: Security fix - XSS scripting vulnerability resolved.