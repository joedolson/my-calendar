const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { wpEval } = require("../config/wp-cli");

test.describe("Upcoming events shortcode navigation", () => {
  test.describe.configure({ mode: "serial" });

  // Events in the included category, in chronological order.
  const PAST = ["E2E Up Past 1", "E2E Up Past 2", "E2E Up Past 3"];
  const UPCOMING = [
    "E2E Up A01",
    "E2E Up A02",
    "E2E Up A03",
    "E2E Up A04",
    "E2E Up A05",
    "E2E Up A06",
    "E2E Up A07",
    "E2E Up A08",
    "E2E Up A09",
    "E2E Up A10",
  ];
  const ALL = [...PAST, ...UPCOMING];
  const EXCLUDED = /E2E Up (Other|Draft)/;

  let data;

  const list = (page) => page.locator(".mc-event-list.upcoming-events").first();
  const items = (page) =>
    list(page).locator("li:not(.mc-load-events-controls)");
  const prevButton = (page) =>
    list(page).locator(".mc-load-prev-upcoming-events");
  const nextButton = (page) =>
    list(page).locator(".mc-load-next-upcoming-events");

  const titles = async (page) =>
    (await items(page).allTextContents()).map((text) => text.trim());

  /**
   * Click a navigation button and return the titles of the list it loads.
   */
  const load = async (page, button) => {
    const before = await titles(page);
    await button.click();
    await expect
      .poll(() => titles(page), {
        message: "Expected navigation to load a different set of events.",
      })
      .not.toEqual(before);

    return titles(page);
  };

  const isToday = async (button) =>
    (await button.getAttribute("class")).includes("mc-today");

  /**
   * Follow the next (or previous) button until it turns into the "Today" button.
   *
   * @return {Promise<string[][]>} Each loaded page of titles, starting with the first page.
   */
  const walk = async (page, direction) => {
    const button = direction === "next" ? nextButton : prevButton;
    const pages = [await titles(page)];
    for (let i = 0; i < 15 && !(await isToday(button(page))); i++) {
      pages.push(await load(page, button(page)));
    }

    return pages;
  };

  test.beforeAll(() => {
    data = JSON.parse(
      wpEval(`
				$included = mc_create_category( array(
					'category_name'  => 'E2E Upcoming Included',
					'category_color' => '#336699',
				) );
				$other = mc_create_category( array(
					'category_name'  => 'E2E Upcoming Other',
					'category_color' => '#993366',
				) );

				$today = strtotime( current_time( 'Y-m-d' ) );
				$make  = function ( $title, $offset, $category, $time = '12:00:00', $approved = 1 ) use ( $today ) {
					$date  = gmdate( 'Y-m-d', strtotime( ( $offset >= 0 ? '+' : '' ) . $offset . ' days', $today ) );
					$event = array(
						'event_begin'        => $date,
						'event_end'          => $date,
						'event_title'        => $title,
						'event_desc'         => '',
						'event_short'        => '',
						'event_registration' => '',
						'event_tickets'      => '',
						'event_time'         => $time,
						'event_endtime'      => gmdate( 'H:i:s', strtotime( $time ) + 3600 ),
						'event_recur'        => 'S',
						'event_repeats'      => 1,
						'event_author'       => get_current_user_id(),
						'event_host'         => get_current_user_id(),
						'event_category'     => $category,
						'event_link'         => '',
						'event_link_expires' => 0,
						'event_approved'     => $approved,
						'event_flagged'      => 0,
						'event_group_id'     => 0,
						'event_span'         => 0,
						'event_hide_end'     => 0,
						'event_holiday'      => 0,
						'event_categories'   => array( $category ),
					);
					$response = my_calendar_save( 'add', array( true, false, $event, false, array() ) );

					return $response['event_id'];
				};

				$events = array(
					// Included category: three past events and ten upcoming events, two of them on the same day.
					$make( 'E2E Up Past 1', -5, $included ),
					$make( 'E2E Up Past 2', -4, $included ),
					$make( 'E2E Up Past 3', -3, $included ),
					$make( 'E2E Up A01', 2, $included ),
					$make( 'E2E Up A02', 3, $included, '10:00:00' ),
					$make( 'E2E Up A03', 4, $included ),
					$make( 'E2E Up A04', 5, $included ),
					$make( 'E2E Up A05', 6, $included, '09:00:00' ),
					$make( 'E2E Up A06', 6, $included, '15:00:00' ),
					$make( 'E2E Up A07', 8, $included ),
					$make( 'E2E Up A08', 9, $included ),
					$make( 'E2E Up A09', 10, $included ),
					$make( 'E2E Up A10', 11, $included ),
					// Events that do not match the shortcode filters, interleaved with and following the included events.
					$make( 'E2E Up Other Before', -6, $other ),
					$make( 'E2E Up Other Early', 1, $other ),
					$make( 'E2E Up Other Middle', 3, $other, '18:00:00' ),
					$make( 'E2E Up Other Same Day', 6, $other, '12:00:00' ),
					$make( 'E2E Up Other Late', 20, $other ),
					$make( 'E2E Up Draft', 7, $included, '12:00:00', 0 ),
				);

				$page_id = wp_insert_post( array(
					'post_title'   => 'My Calendar Upcoming Navigation E2E Test',
					'post_content' => '[my_calendar_upcoming type="event" before="0" after="3" category="' . $included . '" navigation="true" template="{title}"]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				) );
				echo wp_json_encode( array(
					'events'    => $events,
					'page_id'   => $page_id,
					'permalink' => get_permalink( $page_id ),
					'included'  => $included,
					'other'     => $other,
				) );
			`)
    );

    // Fail with a clear message if events were not created.
    expect(
      data.events.every((id) => id),
      `Upcoming events test data was not created: ${JSON.stringify(data)}`
    ).toBe(true);
  });

  test.afterAll(() => {
    if (!data) {
      return;
    }
    wpEval(`
			foreach ( ${JSON.stringify(data.events)} as $event_id ) {
				if ( $event_id ) {
					mc_delete_event( $event_id );
				}
			}
			wp_delete_post( ${data.page_id}, true );
			global $wpdb;
			foreach ( array( ${data.included}, ${data.other} ) as $category_id ) {
				$category = mc_get_category( $category_id );
				if ( $category ) {
					wp_delete_term( $category->category_term, 'mc-event-category' );
				}
				$wpdb->delete( my_calendar_categories_table(), array( 'category_id' => $category_id ), array( '%d' ) );
			}
		`);
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(data.permalink);
    await expect(list(page)).toBeVisible();
  });

  test("initial list starts with the nearest upcoming events", async ({
    page,
  }) => {
    const first = await titles(page);

    expect(first.length).toBeGreaterThan(0);
    expect(first).toEqual(UPCOMING.slice(0, first.length));
    await expect(prevButton(page)).toHaveClass(/mc-previous/);
    await expect(nextButton(page)).toHaveClass(/mc-next/);
  });

  test("next navigation shows every upcoming event once, in order", async ({
    page,
  }) => {
    const pages = await walk(page, "next");
    const flat = pages.flat();

    expect(pages.length, "Expected several pages of events.").toBeGreaterThan(
      1
    );
    for (const current of pages) {
      expect(current.length).toBeGreaterThan(0);
    }

    const repeated = flat.filter((title, i) => flat.indexOf(title) !== i);
    expect(repeated, "Events repeated while navigating.").toEqual([]);

    const skipped = UPCOMING.filter((title) => !flat.includes(title));
    expect(skipped, "Events skipped while navigating.").toEqual([]);

    expect(flat).toEqual(UPCOMING);
  });

  test("previous navigation reaches every earlier event once, in order", async ({
    page,
  }) => {
    const pages = await walk(page, "previous");
    // Pages are collected from the most recent backwards.
    const flat = pages.reverse().flat();

    expect(pages.length, "Expected previous pages of events.").toBeGreaterThan(
      1
    );

    const repeated = flat.filter((title, i) => flat.indexOf(title) !== i);
    expect(repeated, "Events repeated while navigating.").toEqual([]);

    const skipped = ALL.filter(
      (title) => !flat.includes(title) && !UPCOMING.includes(title)
    );
    expect(skipped, "Past events skipped while navigating.").toEqual([]);

    // Pages are contiguous: the result must be a run of the full list ending at the initial page.
    const start = ALL.indexOf(flat[0]);
    expect(start).toBeGreaterThanOrEqual(0);
    expect(flat).toEqual(ALL.slice(start, start + flat.length));
  });

  test("previous after next returns to the events immediately before", async ({
    page,
  }) => {
    const first = await titles(page);
    const second = await load(page, nextButton(page));
    const back = await load(page, prevButton(page));

    expect(second[0]).toBe(UPCOMING[first.length]);
    for (const title of second) {
      expect(back, "Previous page repeats events.").not.toContain(title);
    }
    // The last event shown must be the one directly before the page we left.
    expect(back[back.length - 1]).toBe(ALL[ALL.indexOf(second[0]) - 1]);
  });

  test("events outside the shortcode filters never appear", async ({
    page,
  }) => {
    const seen = [
      ...(await walk(page, "next")).flat(),
      ...(await walk(page, "previous")).flat(),
    ];

    expect(seen.length).toBeGreaterThan(0);
    for (const title of seen) {
      expect(title).not.toMatch(EXCLUDED);
      expect(ALL).toContain(title);
    }
  });

  test("navigation ignores excluded events when finding the end of the list", async ({
    page,
  }) => {
    // An excluded event follows the last included event; it must not keep "Future Events" alive.
    const pages = await walk(page, "next");
    const last = pages[pages.length - 1];

    expect(last[last.length - 1]).toBe(UPCOMING[UPCOMING.length - 1]);
    await expect(nextButton(page)).toHaveClass(/mc-today/);
    await expect(nextButton(page)).toContainText("Today");
  });

  test("today button after reaching the last event returns to the current day", async ({
    page,
  }) => {
    const initial = await titles(page);
    await walk(page, "next");

    const next = nextButton(page);
    await expect(next).toHaveClass(/mc-today/);
    expect(await titles(page)).not.toEqual(initial);

    await next.click();
    await expect.poll(() => titles(page)).toEqual(initial);
    await expect(nextButton(page)).toHaveClass(/mc-next/);
  });

  test("today button after reaching the first event returns to the current day", async ({
    page,
  }) => {
    const initial = await titles(page);
    await walk(page, "previous");

    const prev = prevButton(page);
    await expect(prev).toHaveClass(/mc-today/);
    await expect(prev).toContainText("Today");
    expect(await titles(page)).not.toEqual(initial);

    await prev.click();
    await expect.poll(() => titles(page)).toEqual(initial);
    await expect(prevButton(page)).toHaveClass(/mc-previous/);
  });
});
