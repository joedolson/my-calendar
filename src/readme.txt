=== My Calendar ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: calendar, dates, times, event, events, scheduling, schedule, event manager, event calendar, class, concert, venue, location, box office, tickets, registration
Requires at least: 4.4
Tested up to: 5.9
Requires PHP: 7.0
Text domain: my-calendar
Stable tag: 3.3.9
License: GPLv2 or later

Accessible WordPress event calendar plugin. Show events from multiple calendars on pages, in posts, or in widgets.

== Description ==

My Calendar does WordPress event management with richly customizable ways to display events. The plugin supports individual event calendars within WordPress Multisite, multiple calendars displayed by categories, locations or author, or simple lists of upcoming events.

Easy to use for anybody, My Calendar provides enormous flexibility for designers and developers needing a custom calendar. My Calendar is built with accessibility in mind, so all your users can get equal access and experience in your calendar.

= Premium Event Management =
Looking for more? [Buy My Calendar Pro](https://www.joedolson.com/my-calendar/pro/), the premium extension for My Calendar to add support for user-submitted events, integration between posting and event creation, and import events from outside sources.

= Selling event tickets? =
Do you sell tickets for your events? [Use My Tickets](https://wordpress.org/plugins/my-tickets/) and sell tickets for My Calendar events. Set prices, ticket availability, and sell multiple events at the same time using My Tickets.

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

* Let your site visitors submit events to your site (pay to post or free!).
* Let logged-in users edit their events from the front-end.
* Create events when you publish a blog post
* Publish a blog post when you create an event
* Advanced search features
* Responsive mode
* Import events from .ics or .csv formats via file or URL.
* REST API support for sharing events between multiple sites.

= Translations =

Visit [Wordpress Translations](https://translate.wordpress.org/projects/wp-plugins/my-calendar) to check progress or contribute to your language.

Translating my plugins is always appreciated. Visit <a href="https://translate.wordpress.org/projects/wp-plugins/my-calendar">WordPress translations</a> to help get your language to 100%!

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

= 3.3.10 =

* Bug fix: aria-current not rendered on event manager filters.
* Bug fix: published filter not marked as current by default.
* Bug fix: Cache allowed sites for CORS headers on multisite networks.
* Bug fix: fread error if no stylesheet defined.
* Bug fix: Extra closing div in single-day view.

= 3.3.9 =

* Feature: Ability to merge duplicate locations.
* Bug fix: New locations created with events were not properly saved with the event, leading to possible location duplications.
* Bug fix: Add location to table should not be checked when copying an event.
* Bug fix: Possible fix to meta permissions.
* Bug fix: Fall back to non-fulltext queries if search term below length limit.
* Bug fix: 'search' nav item not rendering.

= 3.3.8 =

* Bug fix: Generated a duplicate location if event with location unselected location.
* Bug fix: Setting an event's all day label text to blank should not be overridden by defaults.
* Bug fix: Delete single event from front-end pointed to wrong destination.
* Bug fix: Missing help text for copying events.
* Change: Minor text change to empty location value.
* Change: Clear list items in list view (CSS)

= 3.3.7 =

* Bug fix: Fixes location admin verification error in manage locations list.

= 3.3.6 =

* Bug fix: Event template previews should only show to users who can use them.
* Bug fix: Category key icons should show background colors when configured.

= 3.3.5 =

* Bug fix: Default values for screen options were not called.
* Bug fix: Event count dots should not show in print view.
* Bug fix: PHP notice if mc_id not set on single event views.
* Bug fix: Documentation link led to removed page.
* Bug fix: Modal help links should open in parent window, not within modal.
* Bug fix: Search query sent to docs site should not be translatable.
* Bug fix: JPG or GIF custom icons should be accepted.
* Bug fix: Template attributes containing HTML stripped attributes in template manager.
* Bug fix: PHP Warning when checking for private category property and object not defined.
* Bug fix: Don't show admin grid view location dropdown if more than 200 locations.
* Bug fix: Prevent large icons from overflowing custom icon list.
* Bug fix: Fix display of custom icons in icons modal.
* Performance: only run mc_create_tags() once per event.
* Performance: cache whether icons are custom rather than inspecting directory for every icon load.
* New filter: `mc_display_location_events` on upcoming event arguments for location screen.
* Change: label My Calendar page in pages list.

= 3.3.4 =

* Bug fix: is_single() shouldn't be called in admin
* Bug fix: Prevent invalid events from breaking year dropdown.
* Bug fix: Make sure category colors are important.
* Bug fix: Set margins to 0 on input/button in nav.
* Bug fix: Decreasing font sizes in nav caused too many problems in older themes.
* Bug fix: Don't insert locations if no data passed to inserter.
* Bug fix: Delete location argument was not used.
* Bug fix: don't output empty locations.
* Bug fix: 'span' is not an attribute on 'span'.
* Bug fix: Verify validity of category relationships when parsing admin lists.
* Bug fix: $templates was undefined and broke saving templates.
* Bug fix: missing quote in 'delete template' button.
* Bug fix: custom templates sanitized incorrectly.
* Bug fix: translations link went to old translations site.
* Bug fix: Handle what happens if default category is deleted.
* Bug fix: Invalid class not reset in admin lists.
* Bug fix: date displayed in wrong timezone in admin recurring events list.
* Change: If location without any unique data is listed in admin, auto delete.
* Change: changes to add dates UI to clarify usage.

= 3.3.3 =

* Bug fix: Timezone omits positive/negative signifier in JSON LD in UTC+ timezones.
* Bug fix: Widen location autocomplete field.
* Bug fix: Fix show location shortcode templating.
* Bug fix: Recur daily by weekday did not produce valid times.
* Bug fix: Skip holidays default state filter missing.
* Bug fix: Only apply default state on special case recurrence fields on new events.
* Bug fix: Category relationships not updated correctly if category deleted.
* Bug fix: File path incorrectly referenced when finding custom icon directories.

= 3.3.2 =

* Change: Add classes representing start time and event length.
* Bug fix: Remove unneeded generic class declarations.
* Bug fix: Show stored location, not event location, in events list.
* Bug fix: Add missing elements to KSES filters for widgets.
* Bug fix: Incorrect logic to hide read more link.
* Feature: Add field to set calendar month heading. 

= 3.3.1 =

* Bug fix: Bulk removal of locations broken.
* Bug fix: SVG category icons should not be queried remotely; use filesystem.
* Layout: wider max-width by default, center calendar in container.
* Bug fix: Display more information link had inverted logic & wrong label.
* Bug fix: Don't show location link if location is not post type mc-locations.
* Bug fix: Week view could end up offset incorrectly in some views due to dates getting double timezone offsets.
* Bug fix: Provide back-compatibility for tabs in older versions of My Calendar Pro

= 3.3.0 =

Backend Changes:

* Replaced date picker with the <a href="https://github.com/duetds/date-picker">Duet Design Systems accessible date picker</a>.
* Accessibility & usability improvements to adding additional occurrences to an event. (DB change)
* Add support for custom fields on locations. <a href="https://github.com/joedolson/plugin-extensions/blob/master/my-calendar/mc-custom-location-fields.php">See demo at Github</a>
* Extensive back-end user experience changes.
* Link location title to edit screen in location manager
* Improve checkbox labeling in event manager.
* Improve button labeling in nav ordering.
* Add row actions to Location manager.
* Add support for custom columns in location manager.
* Bug fix: use aria tab panels properly in settings.
* Removed upgrade cycles & associated code for upgrading from version 2.3.x (last release in 2015.)
* Support aria-sort on sortable tables.
* Locations support both descending & ascending sort.
* Bug fix: pagination when sorting in event manager.
* Update settings configuration for default calendar URL.
* New setting to control whether plugin settings are removed on uninstall.
* Text changes for clarity & simplification
* Change 'Short Description' to 'Excerpt' for clarity
* Collapse 'Event Groups' and 'Events List' into a single screen.
* Inline help pop-ups
* Show event count for category links.
* Add settings manager to My Calendar primary view page.
* Updated recurring events input method.
* Add category during event creation.
* Make event bulk actions a dropdown.
* With Google Maps API, auto query lat/lon data for locations.
* Add calendar view for navigating events in admin.
* Simplify featured image support.
* Use checkboxes to select categories in widgets & shortcode generator.
* Show warning if screen has unsaved changes
* Template tag & event template previews.

Bug fixes:

* Bug fix: Deleting a location from the location manager should not send user to the location editor.
* Bug fix: row action links not properly labeled.
* Bug fix: row action links not becoming visible on focus.
* Bug fix: PHP warning on installations without saved locations.
* Bug fix: Screen options weren't able to retrieve user settings correctly.
* Bug fix: Event manager displayed recurring event options on single event editing screens.
* Bug fix: Form overflows in responsive views.
* Bug fix: Need breaking container in map bubble after location name.
* Bug fix [a11y]: Ensure focus isn't loss in sortable lists; announce change via wp.a11y.speak.
* Bug fix: If no previous or next event, generated numerous PHP errors.
* Stylesheet previewer in Design manager.
* Only show "special scheduling options" when relevant.
* Add Help tab to explain statuses.
* Add color picker to CSS variable UI

Frontend changes:

* Add front-end location view.
* Update default custom templates.
* Support filtering by multiple locations in calendar shortcodes or by filter.
* Change: use a stateful heading for all calendar views.
* Change: Support AJAX navigation on date select form.
* Bug fix: Override custom select styles from Twenty Twenty One
* New: recurring-event classes in event lists.
* Rewrote Google Maps scripting
* New SVG category icons, sourced from Font Awesome (https://fontawesome.com/license)
* New default stylesheet: twentytwentyone.css
* Individual display settings for different calendar views
* Creates demo content on initial installation.
* Enable pretty permalinks by default on new installations
* Add accessibility fields as a default event output.
* Removed RSS feeds.
* Always show event title in pop-up.
* Update default date/time formatting.
* Support search in calendar navigation.
* Support category dropdown in calendar navigation.
* Support location dropdown in calendar navigation.
* Support accessibility feature dropdown in calendar navigation.
* Support ld+json schema.org data for events and locations.
* Changed heading structure for main calendar view.
* Add event number to list view and event number hint in mini view.
* 'Show recurring' flag in upcoming events list.
* Upcoming events list should not wrap empty value in `ul`
* New default stylesheet

Developer Changes:

* New actions: 'mc_event_happening', 'mc_event_future', 'mc_event_over' executed whenever an event is compared to the current time, usable for automatic transitions and notifications.
* Filter: 'mc_output_is_visible' to determine whether a given display feature should be shown on calendar.
* Disable sending email notifications for Spam events. Add action to optionally handle spam notifications.
* Remove the process shortcodes option. Shortcodes can be disabled using 'mc_process_shortcodes' filter.
* Published documentation to https://docs.joedolson.com/my-calendar/
* New filter to add custom permissions. 'mc_capabilities'
* New filter for event details `mc_event_detail_{value}`
* Started work on documenting filters and actions.
* Code reorganization.
* PHP 8.0 compatibility.

= 3.2.19 =

* Resolve svn problem causing missing files.

= 3.2.18 =

* Security: Fixes reflected XSS flaw in admin. Props to @erwinr and WPScan.

= 3.2.17 =

* Bug fix: Add parameter for required fields handling to ignore during imports.
* Add filter handling calendar URLs when using Polylang or WPML.

= 3.2.16 =

* Bug fix: Check for undefined objects in localization, not for undefined object props.
* Change: Set parameter for location autocomplete switchover to 50 instead of 25 locations.
* Change: Tweak directory removal process slightly.

= 3.2.15 =

* Bug fix: Hide event details section if no fields are visible for section.
* Bug fix: Update localization to correct usage of l10n parameter.
* Bug fix: Location AJAX query executed function that only existed in Pro.

= 3.2.14 =

* Bug fixes: Misc. type casting issues.
* Add filters `mc_filter_events` to filter results of main event queries.
* Add $args array to `mc_searched_events` filter parameters.
* Avoid running My Calendar core functionality through My Calendar's own hooks.
* When using REST API, variables are not submitted in a POST query.
* [Performance] Move custom location query into object creation to reduce DB calls.
* Use try/catch in mc-ajax.js to handle case where href does not contain a full URL.
* Autocomplete support for locations in admin.
* Reset select elements in My Calendar nav to inline.
* Minor refactoring in settings pages.

= 3.2.13 =

* Bug fix: Using embed targets is more complicated than expected; disable by default. Enable with 'mc_use_embed_targets' filter.
* Bug fix: Strip embed from parameters when building links (when embed target enabled.)

= 3.2.12 =

* Bug fix: Don't use embed target link when AJAX disabled.
* Improvement: Add AJAX navigation into browser history & address bar.

= 3.2.11 =

* Bug fix: switching to week view display broken.
* Bug fix: links to template help pointed to old location for help.
* Bug fix: AJAX nav pulled height from first rendered calendar, not current navigating calendar.
* Change: filter to pass custom notices for front end submissions and editing.
* Remove fallback function for is_ssl()
* Improve conflicting event errors when the conflicting event is still unpublished.
* Add custom template to pass a calendar that's embeddable via iframe.
* Bug fix: Multisite environments need to use navigation on current site, not from remote site.

= 3.2.10 =

* Change: Fallback text should have a stylable wrapper.
* Bug fix: Missing translatable string.
* Bug fix: When multiple categories selected, events in more than one category would appear multiple times.
* Bug fix: Missing space in MySQL filters in event manager.
* Bug fix: PHP Notice thrown in location manager.
* Bug fix: Add note to open events link field if no URI configured.
* Layout fix: Ensure there's always a space between date & time.

= 3.2.9 =

* Bug fix: Additional of required fields testing erased error messages generated prior to required fields testing.
* Bug fix: If an individual occurrence title is edited, event permalinks show the single change on all events.
* Bug fix: Prev/next event links don't include unique event IDs.
* Bug fix: Remove irrelevant arguments from prev/next event link generation.
* Bug fix: Ignore templates if no data passed.

= 3.2.8 =

* Bug fix: Extraneous screen-reader-text summary generated in event views.
* Bug fix: Fixes to missing parameters in Schema.org microdata.
* Bug fix: Incorrect type comparison caused custom templates not to render in single event view.
* New feature: Default location.

= 3.2.7 =

* Bug fix: Prevent events from being created without categories.
* Bug fix: Ensure category relationships are deleted when related events are deleted.
* Add handling for seeing & managing events that are invalid.
* Add styles for invalid rows.

= 3.2.6 =

* Added filter to change date format on calendar grid.
* New filter for modifying user selection output.
* Bug fix: only check for get_magic_quotes_gpc() if below PHP 7.4
* Bug fix: invalid query in mc_get_locations() if arguments passed as array.

= 3.2.5 =

* Bug fix: CSV exported text fields contained newline characters.

= 3.2.4 =

* Bug fix: Permissions issue caused by variable type mismatch.

= 3.2.3 =

* Bug fix: 3.2.2 created multiple post types with the same slug, triggering 404 errors.
* Bug fix: Templates could return the name of the template if template empty/missing.

= 3.2.2 =

* Bug fix: Curly brace offset access deprecated
* Bug fix: Make next/prev post link arguments optional.
* Bug fix: Template queries could return an empty template.
* Change: Remove trashed events from default events list.

= 3.2.1 =

* PHP Notice: undefined variable.
* Bug fix: screen options not saving.
* Bug fix: Accidental auto-assigning of first category to events when editing.

= 3.2.0 =

* Auto-toggle admin time format if display time format set to European format.
* Show API endpoint when API enabled.
* Add alternate alias for API endpoint.
* Add style variables with category colors to style output.
* Add color output icon with CSS variables in style editor.
* Add new default stylesheet: Twentytwenty.css
* Move permalink setting to general settings panel.
* Change event timestamps to use a real UTC timestamp for reference.
* Switch from using date() to gmdate().
* Update Pickadate to 3.6.4. Resolves some bugs, but introduces an accessibility issue.
    * Customizations to Pickadate 3.6.4 to repair accessibility
    * don't move focus to picker
    * add 'close' button to time picker.
    * Switch Pickadate to classic theme (modified).
* Improvements to output code layout.
* Eliminate empty HTML wrappers in content.
* New filter: mc_get_users. Use custom arguments to get users.
* New filters: mc_header_navigation, mc_footer_navigation
* New template tags: {userstart}, {userend} - date/time in local users timezone.
* Bug fix: Misc. ARIA/id relationships broken.
* Bug fix: remote locations sometimes pulled from local database.
* Bug fix: Long-standing issues in user input settings.
* Bug fix: Don't duplicate .summary values.
* Bug fix: Only render one close button in mini calendar.
* Collapse 'View Calendar' and 'Add Event' adminbar menus into a single menu.
* Remove upgrade path from 2.2.10.
* Remove .mc-event-visible from style output. Unused since 2011.
* Remove numerous deprecated functions.
* Conformance with latest WordPress PHPCS ruleset.

= Future Changes =

* Refactor options storage

== Frequently Asked Questions ==

= Hey! Why don't you have any Frequently Asked Questions here! =

Because the majority of users end up on my web site asking for help anyway -- and it's simply more work to maintain two copies. Please visit [my web site FAQ](http://www.joedolson.com/my-calendar/faq/) to read my Frequently Asked Questions!

= This plugin is complicated. Why won't you help me figure out how to use it? =

I will! But not in person. Take a look at my [documentation website for My Calendar](http://docs.joedolson.com/my-calendar/) before making your request, and consider [making a donation](https://www.joedolson.com/donate/) or [buying My Calendar Pro](https://www.joedolson.com/my-calendar/pro/)!

= Can my visitors or members submit events? =

I've written a premium plugin that adds this feature: My Calendar Pro. [Buy it today](https://www.joedolson.com/my-calendar/pro/)!

= Is there an advanced search feature? =

The search feature in My Calendar is pretty basic; but [buying My Calendar Pro](https://www.joedolson.com/my-calendar/pro/) gives you a richer search feature, where you can narrow by dates, categories, authors, and more to refine your event search.

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

* 3.3.0 Major release: 