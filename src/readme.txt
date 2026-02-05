=== My Calendar - Accessible Event Manager ===
Contributors: joedolson
Donate link: https://www.joedolson.com/donate/
Tags: event manager, event calendar, venue, location, accessibility
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.4
Text domain: my-calendar
Stable tag: 3.7.3
License: GPL-2.0+

Accessible WordPress event calendar plugin. Manage single or recurring events, event venues, and display your calendar anywhere on your site.

== Description ==

[My Calendar](https://joedolson.com/my-calendar/) offers easy-to-use WordPress event management with rich options for custom displays. Display individual event calendars in WordPress Multisite, offer multiple views of calendars limited by event categories, locations or author, or show simple text-based lists of your upcoming events.

= Rich Event Calendar Features =

You'll find enormous design flexibility fo your custom calendar. With recurring event support, design customization tools, custom templating, and category and venue support out of the box, My Calendar gives you a great feature set to get your calendar set up.

= Built with Accessibility in Mind =

My Calendar is an events calendar focused on holistic accessibility: providing a positive experience for site visitors and administrators who use assistive technology. It includes built-in settings where you can describe the ADA compliance features of your events and venues. Accessibility is a critical part of your website, so your audience can get equal access and experience to the events you list.

Learn about [accessible events](https://docs.joedolson.com/my-calendar/event-accessibility/) or [visit the My Calendar demo](https://demos.joedolson.com/my-calendar/)

= Accessibility-first Software =

While My Calendar has a strong focus on backwards compatibility, it is officially built with an accessibility first mindset. That means that if a choice has to be made between improving accessibility and breaking backwards compatibility, the more accessible choice will always come first.

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

The plugin includes features for showing the accessibility services available for events and at physical venues, as well as providing access to your event information for users with disabilities.

= What's in My Calendar Pro? =

* Let your site visitors submit events to your site (pay to post or free!).
* Let logged-in users edit their events from the front-end.
* Custom field creator
* Create events when you publish a blog post
* Publish a blog post when you create an event
* Advanced search features
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

= 3.7.3 =

* Bug fix: Incorrect order of operations broke exporting events as CSV.
* Bug fix: Unverified manipulation of element in navigation JS failed if upcoming events list present.
* Bug fix: Missing ID on date switcher submit broke JS navigation if used.

= 3.7.2 =

* Bug fix: Broken navigation in mini calendar widgets & admin grid view.
* Bug fix: PHP warnings thrown when deleting single events.

= 3.7.1 =

* Bug fix: Ability to disable specific time views accidentally disable `month+n` views.
* Bug fix: Misnamed variable in location controller.
* Bug fix: Accidentally restored a behavior in fetching permalinks that hasn't been used since before 3.0.0, now removed fully.

= 3.7.0 =

Accessibility-first breaking change: The default navigation has changed from using `a` elements to `button` elements. Custom styles may need to be updated.

* Feature: Migrate accessibility characteristics for locations and events to taxonomies and make editable.
* Feature: Add API authentication key to allow export of private events.
* Feature: Ability to disable time formats.
* Feature: Support custom fields in API & remote database access.
* Feature: New event type: private to author.
* Feature: New template tag: `{recurring}` to show a list of recurring event dates. Max limit 50.
* Change: Simplify event insertion with `mc_insert_event()` function.
* Change: Make calendar sharing a popup list.
* Change: Remove mini calendar link targets settings & feature.
* Change: Sort variables to limit URL permutations generated in navigation.
* Change: Change calendar navigation from links operated as buttons to use buttons.
* Change: Added style variables for main background, color, padding, and margin.
* Change: Default category color from dark blue to light gray.
* Change: Load Google Maps asynchronously.
* Change: Update action scheduler and increment required WP version.
* Change: Rewrite all front-end JS to remove jQuery dependency.
* Change: If a new event is saved as draft, redirect to the edit screen for that event.
* Change: Update deprecated Google Maps.marker.
* Change: Add access terms as classes on event wrapper.
* Change: Adjust RSS subscription window from 7 days to 90 days.
* Change: New filter `mc_style_variables` to filter CSS variables.
* Change: Event editor indicates in heading and title if the event is recurring.
* Change: Add filters to customize location controls from code.
* Change: Omit 'description' from template previews.
* Change: Misc. minor tweaks to default styles.
* Change: Display notification for admins if location filters use invalid values.
* Bug fix: iCal subscription link pointed to export URL.
* Bug fix: Disabling event links on grid/list views should not also disable single event view.
* Bug fix: Filtering calendars by accessibility characteristics was broken.
* Bug fix: Don't do preg_match URL check if value is already false.
* Bug fix: Don't display map requirements if mapping service disabled.
* Bug fix: Prevent empty notices from showing in responses.
* Bug fix: If an image URL 404s, delete the internal reference.
* Bug fix: add srcset and sizes to kses array.
* Bug fix: Verify that events exists before checking their publication status.
* Bug fix: Modified dates were not tracked along with deleted and customized dates.
* Bug fix: Fix migration from Calendar plugin.
* Bug fix: Handle null values in stored images.
* Bug fix: Details link didn't return external links.
* Bug fix: Upcoming events did not consider current time zone when querying.
* Bug fix: Author & host values not saved correctly in widgets.
* Bug fix: 'current' keyword ignored if it's the only value passed.
* Bug fix: prevent rewriting subscribe and export toggles.
* Bug fix: Misnamed variable in location saving.
* Bug fix: Only find `[my_calendar **]` when checking for existing calendar page.
* Bug fix: Check publication status of pages found in calendar page checks.
* Bug fix: Delete version when deleting plugin.
* Bug fix: Incorrect data type passed to `mc_category_icon()` in templating.
* Performance: Don't parse the instance array multiple times in event editing.
* Performance: Throw 404 error is date queries go outside valid event boundaries.
* Removed: SVG files, unused since 3.5.
* Accessibility: Heading hierarchy incorrect in template management.
* Accessibility: fixes to admin tabs.
* Accessibility: fix when event date selection triggers errors.
* Accessibility: Use aria-pressed to indicate currently selected categories.

= 3.6.17 =

* Security: Broken Access Control in unused mc_dismiss_notice() function. Props @patchstack and Doan Dinh Van.


= 3.6.16 =

* Bug fix: Omit `mc_id` parameter on permalinks if event is singular.
* Bug fix: Hidden focusable elements inside the dialog broke focus management. Props @alh0319.
* Change: Switch non-modal content from using `aria-hidden` to using `inert`.

= 3.6.15 =

* Bug fix: Add `METHOD:PUBLISH` to iCal exports.
* Bug fix: Strip tags from iCal title.

= 3.6.14 =

* Bug fix: Made a dumb mistake in the date badge, and I localized a string instead of a date.

= 3.6.13 =

* Bug fix: Month in date badge was not localized.
* Bug fix: Default maptype was not inherited from settings correctly.
* Change: Minor style and text changes on PHP templates screen.

= 3.6.12 =

* Bug fix: Update screen reader text classes to remove `clip`.
* Bug fix: Fix PHP warning from unverified array keys.
* Bug fix: Navigation broken when using auto-generated ID keys.
* Bug fix: Switch `webcals` URLs back to `webcal` due to lack of Google support.
* Bug fix: Missing variable declaration in Today's events widget.
* Bug fix: Inspect event object for location_post property before accessing.
* Change: Modify list preset 4 template so group 3 is not a child of group 2.
* Change: Remove Chrome hack for `windowunload` events.

= 3.6.11 =

* Bug fix: Omitted new argument in `mc_after_details` filter in legacy custom template path caused fatal error on single events.

= 3.6.10 =

* Bug fix: Typo in upcoming events arguments broke sort order by attempting to assign the template key as an order.
* Bug fix: Trigger for editing events did not work for single events that ran multiple days because of non-unique ID.
* Bug fix: Add HTML for icons to iCal link for design parity.
* Bug fix: Omit aria-described when there is no calendar ID.
* Change: Exit edit panel generation before creating if not in context that generates it.

= 3.6.9 =

* Bug fix: Incorrect handling of non-array function results triggered fatal error.
* Bug fix: For event titles with no `time` string, modify the template, not the title of the event.

= 3.6.8 =

* Feature: Add new conditional function `mc_has_category( $event )`.
* Change: Add text arguments to core template functions that return template text. Allows template logic to override settings.
* Bug fix: Stringify array data before sending to Akismet.
* Bug fix: Allow `svg` in print view.
* Bug fix: Move `pre_get_document_title` filter to later priority to apply after SEO plugins.
* Bug fix: Add site name to page title.
* Bug fix: Section headers were not selectable.
* Bug fix: Single-day view returned all dates of long-running single events.
* Bug fix: In legacy templates, data passed to get event images was the full data object instead of the tags array.

= 3.6.7 =

* Bug fix: Properly fetch icons for secondary categories in admin events list.
* Bug fix: Improve argument passing in upcoming & today's events handler.
* Bug fix: List preset wrapper templates missing in legacy templating on today's events lists.
* Bug fix: Don't output edit and delete links if the link is not valid. (Pro)
* Change & bug fix: Change `js-modal` prefix to `mc-modal` to reduce conflicts with other uses of this modal library.
* Change: UI changes to shortcode builder & widget interfaces to clarify usage.

= 3.6.6 =

* Bug fix: Fix two PHP 8+ fatal errors in My Calendar legacy widget admin.
* Bug fix: Fix undefined array key in widget output.
* Bug fix: Add CID attribute to time format navigation, allow time format switching in mini calendar.
* Bug fix: CSS fix for time frame and calendar format navigation margins.

= 3.6.5 =

* Bug fix: Network site check referenced obsolete option.
* Bug fix: Front-end edit controls didn't override theme margins.
* Bug fix: Control rendering for edit & subscribe panels dependent on AJAX being enabled.
* Bug fix: Open day URI setting had no default value.
* Bug fix: Card view closing div in wrong location.

= 3.6.4 =

* Bug fix: Follow up fix to incorrect headings in 5-day view when week starts on Monday.
* Bug fix: `mc_event_over` action should only fire after event ends, not after it starts.
* Bug fix: Don't output `aria-labelledby` on event details container in single event view. Only relevant for dialog rendering.

= 3.6.3 =

* Bug fix: Don't reset the start of the week when hiding weekends after weekend-heading fix in 3.6.2.
* Bug fix: Missed spaces between attributes broke registration information field.

= 3.6.2 =

* Bug fix: 'weekend-heading' class applied on rightmost two cells instead of Saturday/Sunday.
* Bug fix: Upcoming Events navigation layout broken in card preset.
* Bug fix: Add autorefresh parameter to CodeMirror to fix layout in template editor.
* Bug fix: Restore default z-index on close button in legacy disclosure.
* Bug fix: Set max-width on close button.
* Bug fix: Remove position:relative from twentyfifteen.css to prevent overriding position:absolute.
* Change: Set 'is-main-view' class on initial load, removed on navigation.

= 3.6.1 =

* Bug fix: Fix logic that set details to show in card view when event links pointed to individual pages, but caused details to render in non-card views.
* Remove upgrade cycle intended for 3.6.0. There were no settings or database updates required.

= 3.6.0 =

* Feature: Added 'Cancelled' state as an event status option. Cancelled events are public, but marked as cancelled.
* Feature: Added text setting to customize 'Cancelled' text.
* Feature: Added 'Private' state as an event status option. Private events are public, but only to logged-in users.
* Feature: Added default template designs for upcoming events and other list outputs.
* Feature: Made default template designs selectable globally and individually by shortcode/widget.
* Feature: Add main shortcode parameter to define custom classes.
* Feature: Improve AJAX editing of single instances to support dynamic editing as well as deletion.
* Feature: Add option to automatically copy PHP templates into your theme.
* Feature: Add "edit in theme" link in PHP template browser when custom template exists.
* Feature: Add `mc_date_badge()` to generate a formatted date badge on events.
* Feature: Add CSS variables targeting list presets.
* Feature: Add previous and next events navigation in Upcoming Events list.
* Change: Improved UX when copying events.
* Change: Improve design of category selector for widgets and user permissions.
* Change: Change table responsiveness to be driven by container width rather than viewport width.
* Change: Added wrapper around upcoming lists and other list outputs.
* Change: Added support for My Calendar CSS variables inside upcoming lists and other list outputs.
* Change: Added variables for weekend grid headers.
* Change: Added class to disable responsive styles.
* Change: Extensive fixes to responsive behavior in the admin.
* Change: Support usertime JS updates in Upcoming Events lists.
* Change: Minimum WordPress version to 6.4.
* Change: Update autocomplete to version 3.0.3.
* Change: Don't render single events in main shortcode if pretty permalinks enabled.
* Change: Polish themes.
* Filters: Added filter to modify categories shown in category key `mc_category_key_array`.
* Bug fix: Fix deprecated jQuery methods.
* Bug fix: Hide admin notices inside the Help modal.
* Bug fix: Prevent invalid event IDs in canonical link generator.
* Bug fix: Change `webcal` protocols to `webcals`.
* Bug fix: Change instance key to use full date and time.
* Bug fix: Don't display search results if the page content contains the main shortcode.
* Bug fix: Enqueue front-end admin styles in back-end admin.
* Bug fix: Add missing attributes to kses handler.
* Bug fix: Misc. design tweaks to handle additional theme design cases.
* Bug fix: Start time didn't display if event started at midnight.
* Bug fix: 24 hour time template used saved template instead of forcing 24 hour time.
* Bug fix: Prevent double padding or missing padding on event titles.
* Bug fix: When disabling event title links, don't disable event details in card view.
* Bug fix: Don't show week numbers in card view.
* Bug fix: Validate occurrence IDs before attempting redirect.
* Bug fix: Default admin events view is not 'all', and 'all' should not be marked active.
* Security: Misc. hardening.
* Structure: Move action scheduler into vendor directory.
* Accessibility: Reduce animations when prefers-reduced-motion applied.
* Accessibility: Announce when main calendar is loading changes.
* Performance: Move SVG resources to code, to avoid excess file lookups.

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
