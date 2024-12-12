=== My Calendar - Accessible Event Manager ===
Contributors: joedolson
Donate link: https://www.joedolson.com/donate/
Tags: event manager, event calendar, venue, location, accessibility
Requires at least: 4.9
Tested up to: 6.7
Requires PHP: 7.4
Text domain: my-calendar
Stable tag: 3.5.21
License: GPLv2 or later

Accessible WordPress event calendar plugin. Manage single or recurring events, event venues, and display your calendar anywhere on your site.

== Description ==

[My Calendar](https://joedolson.com/my-calendar/) offers easy-to-use WordPress event management with rich options for custom displays. Display individual event calendars in WordPress Multisite, offer multiple views of calendars limited by event categories, locations or author, or show simple text-based lists of your upcoming events.

= Rich Event Calendar Features = 

You'll find enormous design flexibility fo your custom calendar. With recurring event support, design customization tools, custom templating, and category and venue support out of the box, My Calendar gives you a great feature set to get your calendar set up.

= Built with Accessibility in Mind =

My Calendar is an events calendar focused on holistic accessibility: providing a positive experience for site visitors and administrators who use assistive technology. It includes built-in settings where you can describe the ADA compliance features of your events and venues. Accessibility is a critical part of your website, so your audience can get equal access and experience to the events you list.

Learn about [accessible events](https://docs.joedolson.com/my-calendar/event-accessibility/) or [visit the My Calendar demo](https://demos.joedolson.com/my-calendar/)

= Premium Event Management =
Looking for more? [Buy My Calendar Pro](https://www.joedolson.com/my-calendar/pro/), the premium extension for My Calendar.

My Calendar Pro adds tons of great additional features:

* Support for user-submitted events,
* Custom field creation and management,
* integration between posting and event creation,
* Import events from outside sources, and
* support for sharing events between multiple sites.

= Sell Event Tickets =
Do you sell tickets for your events? [Use My Tickets](https://wordpress.org/plugins/my-tickets/) and sell tickets for My Calendar events. Set prices, ticket availability, and sell multiple events at the same time using My Tickets.

= Features: =

*	Calendar grid, card, and list views of events
*	Month, multi-month, week, or daily view.
*	Mini-calendar for compact displays (as widget or shortcode)
*	Widgets: today's events, upcoming events, mini calendar, event search
*	Customize templates for event output
*	Limit views by categories, location, author, or host
*	Extensive support for recurring events.
*	Edit or add single dates in recurring events
*	Rich permissions handling to restrict access to parts of My Calendar
*	Email notifications when events are scheduled or drafted
*	Post to X when events are created (using [XPoster](http://wordpress.org/plugins/wp-to-twitter/))
*	Event location management
*	Fetch events from a remote database. (Sharing events in a network of sites.)
*	Multisite-friendly
*	Integrated help page
*	Shortcode Generator to create customized views of My Calendar
*	SEO with JSON-LD structured data for events and venues.
*	Export or subscribe via iCal or Google Calendar.
*	Completely responsive events views
*	Extensive [public documentation](https://docs.joedolson.com/my-calendar/).
*	Hundreds of [actions and filters for custom development](https://joedolson.github.io/my-calendar/)

= Accessibility =

My Calendar is designed with accessibility in mind. All interfaces - both front and back end - are tested with various assistive technology. 

The plugin includes features for indicating the accessibility services available for events and at physical venues, as well as providing access to the content for users with disabilities.

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

   My Calendar -> Add Event
   My Calendar -> Events
   My Calendar -> Add New Location
   My Calendar -> Locations
   My Calendar -> Categories
   My Calendar -> Design
   My Calendar -> Settings
   My Calendar -> Shortcodes
   My Calendar -> Help

4. Visit My Calendar -> Help for assistance with shortcode options or widget configuration.

== Changelog ==

= 3.5.21 =

* Bug fix: iCal feed link incorrect.
* Bug fix: Pass time frame into navigation calculation to help prevent calendars from switching formats when multiple calendars appear on a page.
* Code docs improvements.

= 3.5.20 =

* Bug fix: Undefined foreground color in some stylesheets.
* Bug fix: Missing close `span` in SVG wrapper.
* Bug fix: Check whether `$event` is an object before accessing properties in event template check.
* Bug fix: Add missing stylesheet class in some calendar themes.
* Bug fix: Handle uncommon case where a location ID is assigned but location no longer exists.
* Bug fix: Fix incorrect parentheses that could unset recurring event instances unintentionally.
* Bug fix: Allow help links to wrap.
* Bug fix: Root event link should point to source event, not self.
* Bug fix: When splitting an event, execute the 'add' action instead of the 'edit' action to ensure post meta fields are saved correctly.
* Update social media links.
* Add class for locations to event class list. 

= 3.5.19 =

* Remove textdomain loader (obsolete since WP 4.6).
* Hide icon selector if icons are disabled.
* Hide color selector & column if category colors are disabled.
* Minor back-end CSS changes.

= 3.5.18 =

* Bug fix: PHP template for location single misnamed with incorrectly called variables.
* Docs: Improved function doc for `mc_ts()`.

= 3.5.17 =

* Accessibility: Move icon rendering to an `aria-hidden` element instead of directly generated content.
* Change: Misc. design changes to improve icon alignments and sizing.
* Change: Misc. changes to standardize rendering of icons between different stylesheets.
* Change: Add `rel="nofollow"` to filtering and navigation links to reduce crawling on duplicate views.
* Change: Add CSS prefix to stylesheets so additional stylesheets override reset.css.
* Change: Query location upcoming events by ID, not name.
* Docs: Add `timerange` to in-plugin docs list.
* Bug fix: Don't perform geolocation calls if passed fields have no values.
* Bug fix: Missing CSS variables & script localization in admin grid view.
* Bug fix: Style modal edit links in admin.

= 3.5.16 =

* Bug fix: Restore event and location pagination broken in 3.5.13.
* Bug fix: Pass valid `$type` parameters to admin notice function.
* Bug fix: Able to delete the custom all day time label for an event.
* Bug fix: Checkboxes not uncheckable for event date handling parameters.
* Accessibility: Wrap pagination and filters in `nav` elements.
* Accessibility: Consistent ordering of pagination and filters.
* Feature: Add event count column to locations screen.

= 3.5.15 =

* Bug fix: Execute `the_content` filters on output broke some displays.
* Bug fix: Use full event date & time in the stored _mc_event_date meta.
* Add: filter `mc_execute_the_content` to enable execution of `the_content` filters.

= 3.5.14 =

* Bug fix: Typo in template tag documentation. Props @robnicholson.
* Bug fix: Incorrectly escaped double quotes in CSV output broke importing.
* Bug fix: Verify whether 'event_location' property exists when processing submission errors.
* Bug fix: Autosetting end date failed due to incorrect logic & timezone offsetting.
* Bug fix: Don't offset timezone when calculating whether an event should be displayed based on options.
* Change: Execute `the_content` filters on output to support oEmbed and block output in events.

= 3.5.13 =

* Bug fix: Missing remote DB reference for event occurrence lists.
* Bug fix: Don't attempt to copy location relationships when fetching data remotely.
* Bug fix: Clear fragment cache when remote DB is acctivated.
* Feature: Add filter to change remote DB prefix when using remote DB.
* Feature: Setting to flush fragment cache.
* Change: Remove usages of deprecated 'SQL_CALC_FOUND_ROWS'.

= 3.5.12 =

* Add: Function that converts from a My Calendar approval status to the equivalent post status.
* Bug fix: If location name controls in place, do not show on location edit screen. Props @jacquebert.
* Change: Simplify My Calendar's admin notice function and support all standard notice types.

= 3.5.11 =

* Bug fix: Default image alt attribute incorrectly fetched.
* Bug fix: Remove stray quote in filter button text.
* Bug fix: Prevent PHP warning if category styles transient contains invalid content.
* Bug fix: PHP warning on Help screen.
* Bug fix: Increase z-index on modals.
* Change: Add filters for locations and events post type arguments.
* Change: Add 'autoexcerpt' as a template tag in addition to the `excerpt` and `short_desc` tags.
* Change: Add editor support for location post types.
* Change: Add link to location post editor in location manager.
* Change: Add `permalink` as alias for `details_link` for clearer tag & function usage.
* Remove override for deprecated filter `tmp_grunion_allow_editor_view`.

= 3.5.10 =

* Bug fix: Don't create custom icon cache when debugging.
* Bug fix: Only execute style vars foreach if is an array.
* Change: Add 'my-calendar' as body class on primary calendar page.
* Change: Noindex calendar archive pages.

= 3.5.9 =

* Bug fix: Fix CSV event download PHP error.
* Bug fix: Fix misc. HTML validation errors.
* Bug fix: Force text wrapping in button reset.
* Bug fix: Fix data checks for event author when no user is logged in. (Pro).

= 3.5.8 =

* Bug fix: Use `mc_exit_early()` to determine classes as well as to determine display.
* Bug fix: Editing a single date updated descriptions for all events.
* Bug fix: Only parse URL arguments for specific calendar ID.
* Bug fix: No content fallback text not passed to output function in main shortcode.
* Bug fix: Don't duplicate registration fields in front-end submissions (Pro).
* Bug fix: Handle the possibility that front-end location presets may be 0 instead of empty (Pro).

= 3.5.7 =

* Bug fix: PHP 8.2 deprecated error in `mc_ordinal`.
* Bug fix: Category dropdown navigation element should limit categories shown.
* Bug fix: Export dropdown button broken.
* Bug fix: Export/Subscribe dropdowns duplicated if multiple calendars present. Props @mikeknappe.
* Bug fix: Incorrect argument passed to mini widget ignored months setting.
* Bug fix: Reset styles didn't handle Mini calendar widget disclosure properly.
* Feature: Add setting to change subscribe and export button text. Props @mikeknappe.
* Docs: Fix incorrect docs on `mc_category_key`.
* I18n: Missing translator function. Props @DAnn2012.

= 3.5.6 =

* Bug fix: JS error breaking add new location in event editor.
* Bug fix: Imported settings should not strip HTML from templates.
* Revert: InnoDB change in version 3.5.5 created unanticipated problems.

= 3.5.5 =

* Bug fix: Templates rendered incorrectly when search-friendly permalinks disabled.
* Bug fix: Clear PHP warning if no future dates in export generation.
* Bug fix: Only show Pro promo inside My Calendar pages.
* Bug fix: Remove support for InnoDB search on pre 5.5 MySQL; add support for Fulltext search on InnoDB otherwise.
* Bug fix: Event title not rendered unless 'read more' enabled.
* Bug fix: Category color styles cache deleted in wrong place.
* Change: Move sqlite check to existing db engine check function.

= 3.5.4 =

* Bug fix: One further case where legacy templating could return invalid value in event details.

= 3.5.3 =

* Bug fix: Adjust CSS for themes that set inputs to a fixed height.
* Bug fix: JS in ajax navigation needs to only select first child of cells.
* Bug fix: Omit the current day border on single day views.
* Bug fix: Execute mc_get_details() regardless of whether there are already details.
* Try: Sqlite fix for Playground in upcoming events widget.

= 3.5.2 =

* Bug fix: If setting to link to event page enabled, event wrapper was not added.
* Bug fix: Incorrect kses on Mini calendar widget removed too much information.
* Bug fix: Category filters did not remove existing filter when adding categories.
* Bug fix: PHP templating rendered event details when linking to external URL.
* Bug fix: Fix handling of custom templates with legacy templating when passed via settings.
* Addition: class 'today-event' for events occurring on the current day.

= 3.5.1 =

* Bug fix: Mini calendar widget stripped out `select`, `option`, and `input`.
* Bug fix: Upcoming Events list showed out-of-query events.
* Update: Support sqlite to enable demo support in Playground.

= 3.5.0 =

* Major change: Remove CSS style editor and style migration functions.
* Major change: Remove CSS and Icon backup functions.
* Major change: Upcoming events list now returns fixed numbers of events by default.
* Major change: Location data only fetched from location table, no longer saved to event table.
* Major change: Implement responsive table CSS & JS.
* Major change: Introduce PHP templating.
* Major change: Rewrite all additional stylesheet skins.
* Change: Selecting a stylesheet is now optional.
* Change: Add autocompletion for country selection.
* Change: Make modal view the default pop-up view.
* Change: Improve HTML semantics in event display.
* Change: Switch popup triggers to buttons.
* Change: Navigation controls are always controls; don't switch to span when active.
* Change: Replace ical generation with spatie/icalendar-generator.
* Feature: Add card view.
* Feature: Style variable previews.
* Feature: Add importer to import events from The Events Calendar.
* Feature: Add support for featured image on locations.
* Breaking change: `mc_event_classes()` now echos classes. Use `mc_get_event_classes()` to return.
* Bug fix: Prevent My Calendar post types from showing up in export tool. Props @carstenbach.
* Performance: Use fragment caching to reduce duplicate db queries.
* Performance: Prevent infinite date navigation in calendar navigation tools.
* Performance/SEO: Only output Schema on main event views, not in widgets.
* Support Yoast schema engine (props @joostdevalk)
* Update to WPCS 3 (props @joostdevalk)
* Many, many minor visual changes to improve consistency.

= 3.4.24 =

* Security: Fix XSS scripting issue (shortcode parsing) reported by Patchstack.
* Security: Fix XSS scripting issue (event creation and output) reported by WPScan.

= 3.4.23 =

* Bug fix: Date validation function applied a timezone offset by mistake, causing functions detecting the current day to sometimes return the wrong day.

= 3.4.22 =

* Security: Fix unauthorized SQL injection vulnerability. Update as soon as possible. Props Tenable. 

= 3.4.21 =

* Bug fix: Settings that allow HTML were aggressively sanitized, stripping HTML.
* Bug fix: Location field should not be disabled in shortcode generator when has value.
* Bug fix: Provide easier access to calendar page in shortcode generator.
* Bug fix: (Design) Fix change of month heading in mini calendar.
* Bug fix: Fix month number enumeration in date classes.
* Bug fix: Improve counting of events in upcoming events lists.
* Docs: Documentation on `mc_get_uri` filter incorrect.
* Change: Pass $event object to `mc_return_uri` filter.

= 3.4.20 =

* Feature: Add {edit_link} template tag for admin email notifications.
* Feature: Support filtering locations by ID when available.
* I18n: Make security information texts more consistent.
* Bug fix: Fix minor layout issues in admin grid view.
* Bug fix: Fix script load in admin grid view. Support modals.
* Bug fix: Update location configuration logic. Props @joergpe.

= 3.4.19 =

* Bug fix: missing space between attributes caused entire attribute string to fail `wp_kses` test.
* Bug fix: Force variable types in a couple of function returns.
* Bug fix: Use a persistent MySQL connection when accessing a remote DB. Props @joergpe.
* Bug fix: Add new categories to primary selector on creation.

= 3.4.18 =

* Bug fix: Fix issue with AJAX search within calendar navigation elements.
* Bug fix: Extra EOL possible in iCal output.
* Bug fix: Categories were passed with icons to iCal, leaving duplicated category names after HTML was stripped.
* Bug fix: Remove quoted-printable from iCal encoding.
* Bug fix: Deprecated argument error in calendar settings.

= 3.4.17 =

* Bug fix: Only render event title filters when processing the event. 
* Bug fix: `mc_inner_content` filter should run whenever description content is displayed.
* Bug fix: `mc_inner_content` filter should not override disclosure close buttons.
* Bug fix: If a theme uses H1 for widgets, replace with H2 instead of removing.
* Bug fix: Hidden event redirect could fire on non-event pages.
* Bug fix: PHP 8.1 compatibility fix.
* Bug fix: allow {color} and {inverse} template tags in style attributes.
* Bug fix: Cache busting on reset.css broken.
* Docs: Some docs fixes.
* Docs: Move some additional template tags to the uncommon tags array.
* Change: Update microformat classes to remove '.fn' (collision with block editor footnotes.)
* Change: Updates to search mechanisms for advanced search (Pro).
* Change: Add baseline CSS for search results in reset.css

= 3.4.16 =

* Bug fix: Redirect single page for calendar to canonical location based on permalink settings.
* Bug fix: Remove .mcjs class for every calendar on page.
* Change: Pass event count to search results filter.

= 3.4.15 =

* Bug fix: Restore missing 'gmap' option.
* Bug fix: Set width/height on category SVGs in event manager.
* Bug fix: Accessibility data inconsistently passed from front-end submissions.
* Bug fix: Use excerpts by default in iCal. props @masonwolf
* Bug fix: Use details link by default in iCal. props @masonwolf
* Bug fix: Single ical exports need to use correct date, not series start date. props @masonwolf
* Bug fix: Series ical exports need to use root URL, not first instance URL. props @masonwolf

= 3.4.14 =

* Bug fix: Discrepancy in query arguments for event status counts and event lists.
* Bug fix: When publishing an event, remove any spam flag in place without requiring extra step.
* Bug fix: Use front-end links for delete and edit if viewed from front-end in Pro.
* Change: Add filter to enable RSS feeds for My Calendar post types.
* Security hardening: Move sanitizing earlier in numerous locations.

= 3.4.13 =

* Bug fix: Don't send admin edit link to public submitters.
* Bug fix: Unset 'current' keyword when replaced with user ID in MySQL query.
* Change: Hide adminbar when embedding calendar in iframe.

= 3.4.12 =

* Change: when mc_id not passed, display next event if there is one, rather than always the nearest event.
* Change: minify admin JS.
* Change: combine admin event ajax and category ajax into one file.
* Bug fix: Use user-supplied alt attributes from Pro when image provided via URL.
* Bug fix: Invalid canonical value when mc_id not passed.
* Bug fix: Get event data earlier when processing post responses. Fixes undeclared variable error.
* Bug fix: Pass event ID into submission data when editing an event. [Pro]
* Bug fix: Fix final week recurring patterns in iCal exports. Props @masonwolf.

= 3.4.11 =

* Bug fix: Exit without attempting to call category icons if database value is empty.
* Bug fix: Set canonical URLs when mc_id parameter is not set.
* Bug fix: Don't apply timezone offsets when creating recur rules.
* Bug fix: Saved custom templates were overwritten when updating settings that include templates.
* Bug fix: Event element allow lists mismatched on initial check and final check.

= 3.4.10 =

* Bug fix: Duplicate ID attributes on category SVG icons.
* Bug fix: Weekday recurring events used wrong basis for date addition.

= 3.4.9 =

* Bug fix: List view with grouped lists and modal JS enabled did not trigger modal.
* Bug fix: Add Event adminbar link should not depend on the My Calendar ID being set.
* Bug fix: If no calendar is located, automatically create a new calendar page.
* Bug fix: Location relationships were not created between locations and location posts.
* Feature: Add support for map links using alternate mapping services. Embedded maps still only available via Google.

= 3.4.8 =

* Bug fix: Don't show edit event link to users who don't have permission to edit events.
* Bug fix: Send front-end editing link when submitting from the front end. [Pro].
* Bug fix: Fix AJAX notices in event importer [Pro].
* Bug fix: Comment corrections & removal of unused variables.

= 3.4.7 =

* Bug fix: Two missed get_option references migrated to mc_get_option; fixes ability to skip events the occur on holidays.
* Bug fix: Add setting & filter for hiding past dates on list view on initial load.
* Docs: Update docs referencing 'mc_is_url' now that function is removed.
* Add 'check' action in mc_check_data() to handle data verification without taking action.

= 3.4.6 =

* Bug fix: Category classes on multibyte category names not rendered. (Props @sutefu23)
* Bug fix: Current category classes failed for multiple selected classes. (Props @sutefu23)
* Bug fix: Fix path references when using custom icon directories.
* Bug fix: Don't use icon transients when WP_DEBUG is true.
* Bug fix: Fix sorting direction on secondary sorts in events list.
* Bug fix: Fix aria-sort rendering in sortable tables.

= 3.4.5 =

* Bug fix: Group event editor stripped HTML from content.
* Bug fix: Clear PHP warning on 'event_approved'
* Bug fix: selected attribute stripped from select inputs in mc_kses_elements
* Bug fix: Change wide field inputs to prevent exceeding size of containers
* Change: Allow mc_admin_category_list() to work on front end for Pro. 

= 3.4.4 =

* Bug fix: Improve modal CSS: better support for multiline titles, adminbar, and avoiding collision with close button.
* Bug fix: Modal should use single title, not current context title.
* Bug fix: Intermediary headings when viewing multiple months in grid were not translated.
* Bug fix: Add user notification if required function `mime_content_type` not available.
* Bug fix: Add eventattendancemode parameter to JSON schema.
* Change: Use ordinals for recurring events by days, for improved textual clarity.
* Docs: Document a couple undocumented filters.
* Security: Resolve four CSRF vulnerabilities in the admin. Props thiennv through Patchstack.

= 3.4.3 =

* Bug fix: Overly general no-scroll selector on modal behaviors caused. Added prefix & specificity.
* Bug fix: Wrong variable called for custom navigation items.
* Bug fix: Verify that callable functions exist before calling them.
* Bug fix: CSS Migration moved files into the wrong directory: move to correct directory.
* Bug fix: Perform integrity checks on imported settings files.
* Feature: Locate CSS migrated into wrong directory and offer to move them.

= 3.4.2 =

* Bug fix: Revert fix that supported custom title formats in Full Site Editor, as it caused problems in some classic themes.
* Bug fix: default_settings should only ever call add_option, not update_option. Resolves bug that could reset user's settings if they had previously uninstalled.
* Bug fix: Accidentally used sanitize_textarea_field on event content instead of wp_kses_post, which stripped HTML.

= 3.4.1 =

* Bug fix: Fatal error in installation routine on multisite.
* Bug fix: Broken popups in AJAX navigation on compact calendar.
* Bug fix: Error in child descendant if multi-language events called.
* Bug fix: Mis-called setting for default location.

= 3.4.0 =

* Feature: import and export calendar settings.
* Feature: Migrate CSS to custom file locations.
* Feature: Add modal option for all popup views.
* Feature: Copy to clipboard for help & shortcodes.
* Feature: REST API support for outputting events.
* Feature: New default stylesheet.
* Feature: Support 'current' as an argument in a comma-separated list of users.
* Bug fix: User category limits need to be configurable for all users who can add events.
* Bug fix: User category limits should limit the categories selectable by users, not just those editable.
* Bug fix: Default events screen says 'All' but only showed 'Published'
* Bug fix: Quick publish of a draft should keep user in draft events list.
* Bug fix: Don't repeat geolocation queries if a location does not have valid data.
* Bug fix: Should only require 'Add Events' permissions to set user categories.
* Bug fix: Category list did not filter out unavailable categories.
* Bug fix: Location slug callback had bad typos.
* Bug fix: Fix PHP notice if $templates not an array.
* Bug fix: Fix JS for list view to handle if an event has a language change.
* Bug fix: Mini URL used incorrectly.
* Bug fix: If primary category is already private, no need to check whether a private category is asigned.
* Bug fix: Dynamically manage color in SVG icons.
* Bug fix: Delete post meta when dropping database on uninstall.
* Bug fix: PHP 8.1. updates.
* Bug fix: All day events not rendered correctly in iCal clients. Props @drjoeward.
* Change: Manage settings in a single database option rather than individual options.
* Change: Improved design on print view.
* Change: Refresh undeprecated stylesheets.
* Change: Deprecate older stylesheets.
* Change: show category icons in event manager.
* Change: Render CSS variables using `wp_add_inline_style()`.
* Change: Render My Calendar main output without shortcode on defined home location.
* Change: Remove a couple very long deprecated functions.
* Change: Don't save default text settings; use coded fallbacks when empty.
* Planning: Prep for removal of core CSS editing.
* Planning: Prep work for new templating framework.
* Planning: Updates to support future changes in My Calendar Pro.
* Performance: caching of icon list data.
* Accessibility: Improve aria-label patterns so user settings override.
* Accessibility: Omit aria-label if link text is already unique.
* Accessibility: Improve calendar navigation using AJAX.

== Frequently Asked Questions ==

= Where can I find plugin documentation? =

Take a look at my [documentation website for My Calendar](https://docs.joedolson.com/my-calendar/) or the [developer hook documentation](https://joedolson.github.io/my-calendar/). If you're using the free version, please consider [making a donation](https://www.joedolson.com/donate/) or [buying My Calendar Pro](https://www.joedolson.com/my-calendar/pro/) before requesting support.

= How can my visitors or members submit events? =

My Calendar Pro supports a richly featured interface for getting events from the public. You can customize the form to collect exactly the fields you need, and review the events before publishing. [Buy it today](https://www.joedolson.com/my-calendar/pro/)!

= Is there an advanced search feature? =

[Buying My Calendar Pro](https://www.joedolson.com/my-calendar/pro/) gets you a richly featured advanced event search. You can narrow by dates, categories, authors, and more to refine your event search.

= Where can I report issues or request features? =

My Calendar is developed on Github, and I welcome contribution. [Vist the My Calendar repository](https://github.com/joedolson/my-calendar) to make requests.

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
