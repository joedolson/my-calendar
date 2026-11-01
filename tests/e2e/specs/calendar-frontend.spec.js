const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { wpEval } = require("../config/wp-cli");

test.describe("My Calendar front end", () => {
  test.describe.configure({ mode: "serial" });

  let pageId;
  let eventId;
  let permalink;
  let originalOptions;

  test.beforeAll(() => {
    const result = JSON.parse(
      wpEval(`
				$original_options = get_option( 'my_calendar_options', array() );
				$options = $original_options;
				$options['calendar_javascript'] = 'modal';
				$options['open_uri'] = 'false';
				update_option( 'my_calendar_options', $options );

				$event = array(
					'event_begin'        => current_time( 'Y-m-d' ),
					'event_end'          => current_time( 'Y-m-d' ),
					'event_title'        => 'E2E Modal Focus Test Event',
					'event_desc'         => '<p><a href="https://example.com">Modal focus test link.</a></p>',
					'event_short'        => '',
					'event_registration' => '',
					'event_tickets'      => '',
					'event_time'         => '12:00:00',
					'event_endtime'      => '13:00:00',
					'event_recur'        => '',
					'event_repeats'      => '',
					'event_author'       => get_current_user_id(),
					'event_host'         => get_current_user_id(),
					'event_category'     => 1,
					'event_link'         => '',
					'event_link_expires' => 0,
					'event_approved'     => 1,
					'event_flagged'      => 0,
					'event_group_id'     => 0,
					'event_span'         => 0,
					'event_hide_end'     => 0,
					'event_holiday'      => 0,
					'event_categories'   => array( 1 ),
				);
				$response = my_calendar_save( 'add', array( true, false, $event, false, array() ) );
				$page_id = wp_insert_post( array(
					'post_title'   => 'My Calendar Modal E2E Test',
					'post_content' => '[my_calendar id="my-calendar"]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				) );
				echo wp_json_encode( array(
					'event_id'        => $response['event_id'],
					'page_id'         => $page_id,
					'permalink'       => get_permalink( $page_id ),
					'original_options' => base64_encode( serialize( $original_options ) ),
				) );
			`)
    );

    eventId = result.event_id;
    pageId = result.page_id;
    permalink = result.permalink;
    originalOptions = result.original_options;
  });

  test.afterAll(() => {
    if (eventId) {
      wpEval(`mc_delete_event( ${eventId} );`);
    }
    if (pageId) {
      wpEval(`wp_delete_post( ${pageId}, true );`);
    }
    if (originalOptions) {
      wpEval(
        `update_option( 'my_calendar_options', unserialize( base64_decode( '${originalOptions}' ) ) );`
      );
    }
  });

  test("calendar shortcode renders on the front end", async ({
    admin,
    editor,
  }) => {
    await admin.createNewPost({ title: "My Calendar E2E Test" });
    await editor.insertBlock({
      name: "core/shortcode",
      attributes: { text: '[my_calendar id="my-calendar"]' },
    });
    await editor.publishPost();

    const previewPage = await editor.page.context().newPage();
    const permalink = await editor.page.evaluate(() =>
      window.wp.data.select("core/editor").getPermalink()
    );
    await previewPage.goto(permalink);

    await expect(previewPage.locator("#my-calendar")).toBeVisible();
    await previewPage.close();
  });

  test("event modal is keyboard accessible and returns focus after closing", async ({
    page,
  }) => {
    await page.goto(permalink);
    await page.bringToFront();

    const trigger = page.locator('#my-calendar [id^="modal-button-"]').first();

    await expect(trigger).toBeVisible();
    await trigger.focus();
    await expect(trigger).toBeFocused();
    await trigger.press("Enter");

    const modal = page.locator("#mc-modal");
    await expect(modal).toBeVisible();
    const closeButton = page.locator("#mc-modal-close");
    const overlay = page.locator("#mc-modal-overlay");
    await expect(closeButton).toBeFocused();

    let focusReturnedToCloseButton = false;
    for (let tabPresses = 0; tabPresses < 10; tabPresses++) {
      await page.keyboard.press("Tab");
      await expect
        .poll(() =>
          page.evaluate(() =>
            Boolean(
              document.activeElement &&
                document.activeElement.closest("#mc-modal")
            )
          )
        )
        .toBe(true);

      if (
        await closeButton.evaluate(
          (button) => document.activeElement === button
        )
      ) {
        focusReturnedToCloseButton = true;
        break;
      }
    }
    expect(focusReturnedToCloseButton).toBe(true);

    await closeButton.click();
    await expect(modal).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.press("Enter");
    await expect(closeButton).toBeFocused();
    await overlay.click({ position: { x: 5, y: 5 } });
    await expect(modal).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.press("Enter");
    await expect(closeButton).toBeFocused();
    await closeButton.press("Enter");
    await expect(modal).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.press("Enter");
    await expect(closeButton).toBeFocused();
    await page.keyboard.press("Escape");
    await expect(modal).toBeHidden();
    await expect(trigger).toBeFocused();
  });
});

test.describe("My Calendar front end navigation", () => {
  test.describe.configure({ mode: "serial" });

  const TITLES = {
    today: "E2E Nav Today General",
    todayOther: "E2E Nav Today Other",
    previous: "E2E Nav Previous Month",
    next: "E2E Nav Next Month",
  };

  let data;

  /**
   * Read a query parameter from the current page URL.
   */
  const param = (page, name) => new URL(page.url()).searchParams.get(name);

  const calendar = (page) => page.locator("#my-calendar");

  /**
   * Locate a navigation control by ID prefix. Controls are buttons when the calendar
   * uses AJAX navigation, and links otherwise, but the IDs are the same.
   */
  const control = (page, prefix) =>
    calendar(page).locator(`[id^="${prefix}"]`).first();

  /**
   * Month grids include leading and trailing days from adjacent months, marked
   * with the 'nextmonth' class. This matches events only in the displayed month.
   */
  const inDisplayedMonth = (page, title) =>
    calendar(page)
      .locator(".day-with-date:not(.nextmonth)")
      .filter({ hasText: title });

  const showsMonth = async (page, month, year) => {
    await expect.poll(() => param(page, "month")).toBe(String(month));
    await expect.poll(() => param(page, "yr")).toBe(String(year));
  };

  test.beforeAll(() => {
    data = JSON.parse(
      wpEval(`
				$original_options = get_option( 'my_calendar_options', array() );
				$options = $original_options;
				$options['calendar_javascript'] = 'modal';
				$options['open_uri']            = 'false';
				$options['topnav']              = 'toggle,timeframe,jump,nav';
				$options['bottomnav']           = 'key,print';
				$options['time_views']          = array( 'month', 'week', 'day' );
				$options['views']               = array( 'calendar', 'list', 'mini', 'single' );
				update_option( 'my_calendar_options', $options );

				$other_category = mc_create_category( array(
					'category_name'  => 'E2E Nav Category',
					'category_color' => '#336699',
				) );

				$today    = current_time( 'Y-m-d' );
				$previous = strtotime( 'first day of last month', strtotime( $today ) );
				$next     = strtotime( 'first day of next month', strtotime( $today ) );

				$make = function ( $title, $date, $category ) {
					$event = array(
						'event_begin'        => $date,
						'event_end'          => $date,
						'event_title'        => $title,
						'event_desc'         => '',
						'event_short'        => '',
						'event_registration' => '',
						'event_tickets'      => '',
						'event_time'         => '12:00:00',
						'event_endtime'      => '13:00:00',
						'event_recur'        => 'S',
						'event_repeats'      => 1,
						'event_author'       => get_current_user_id(),
						'event_host'         => get_current_user_id(),
						'event_category'     => $category,
						'event_link'         => '',
						'event_link_expires' => 0,
						'event_approved'     => 1,
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
					$make( '${TITLES.today}', $today, 1 ),
					$make( '${TITLES.todayOther}', $today, $other_category ),
					$make( '${TITLES.previous}', gmdate( 'Y-m-15', $previous ), $other_category ),
					$make( '${TITLES.next}', gmdate( 'Y-m-15', $next ), 1 ),
				);

				global $wpdb;
				$occurrences = array();
				foreach ( $events as $created_id ) {
					$occurrences[] = $created_id ? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . my_calendar_event_table() . ' WHERE occur_event_id = %d', $created_id ) ) : 0;
				}

				$page_id = wp_insert_post( array(
					'post_title'   => 'My Calendar Navigation E2E Test',
					'post_content' => '[my_calendar id="my-calendar"]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				) );
				echo wp_json_encode( array(
					'events'           => $events,
					'occurrences'      => $occurrences,
					'page_id'          => $page_id,
					'permalink'        => get_permalink( $page_id ),
					'category'         => $other_category,
					'previous_month'   => (int) gmdate( 'n', $previous ),
					'previous_year'    => (int) gmdate( 'Y', $previous ),
					'next_month'       => (int) gmdate( 'n', $next ),
					'next_year'        => (int) gmdate( 'Y', $next ),
					'original_options' => base64_encode( serialize( $original_options ) ),
				) );
			`)
    );

    // Fail with a clear message if events were not created.
    expect(
      data.events.every((id) => id) && data.occurrences.every((n) => n > 0),
      `Navigation test events were not created: ${JSON.stringify(data)}`
    ).toBe(true);
  });

  test.afterAll(() => {
    if (!data) {
      return;
    }
    wpEval(`
			foreach ( ${JSON.stringify(data.events)} as $event_id ) {
				mc_delete_event( $event_id );
			}
			wp_delete_post( ${data.page_id}, true );
			global $wpdb;
			$category = mc_get_category( ${data.category} );
			if ( $category ) {
				wp_delete_term( $category->category_term, 'mc-event-category' );
			}
			$wpdb->delete( my_calendar_categories_table(), array( 'category_id' => ${data.category} ), array( '%d' ) );
			update_option( 'my_calendar_options', unserialize( base64_decode( '${data.original_options}' ) ) );
		`);
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(data.permalink);
    await expect(calendar(page)).toBeVisible();
  });

  test("shows only the current month's events by default", async ({ page }) => {
    await expect(calendar(page)).toContainText(TITLES.today);
    await expect(calendar(page)).toContainText(TITLES.todayOther);
    await expect(calendar(page)).not.toContainText(TITLES.previous);
    await expect(calendar(page)).not.toContainText(TITLES.next);
    await expect(control(page, "mc_today_")).toHaveClass(/mc-active/);
    await expect(control(page, "mc_month-")).toHaveClass(/mc-active/);
  });

  test("next and previous move between months", async ({ page }) => {
    await control(page, "mc_next_").click();
    await showsMonth(page, data.next_month, data.next_year);
    await expect(calendar(page)).toContainText(TITLES.next);
    await expect(inDisplayedMonth(page, TITLES.today)).toHaveCount(0);
    await expect(calendar(page)).not.toContainText(TITLES.previous);
    await expect(control(page, "mc_today_")).not.toHaveClass(/mc-active/);

    // Back to the current month, then one more step back.
    await control(page, "mc_previous_").click();
    await expect(calendar(page)).toContainText(TITLES.today);
    await expect(calendar(page)).not.toContainText(TITLES.next);

    await control(page, "mc_previous_").click();
    await showsMonth(page, data.previous_month, data.previous_year);
    await expect(calendar(page)).toContainText(TITLES.previous);
    await expect(inDisplayedMonth(page, TITLES.today)).toHaveCount(0);
    await expect(calendar(page)).not.toContainText(TITLES.next);
  });

  test("today returns to the current month", async ({ page }) => {
    await control(page, "mc_next_").click();
    await expect(calendar(page)).toContainText(TITLES.next);

    await control(page, "mc_today_").click();
    await expect(calendar(page)).toContainText(TITLES.today);
    await expect(calendar(page)).not.toContainText(TITLES.next);
    await expect.poll(() => param(page, "month")).toBeNull();
    await expect(control(page, "mc_today_")).toHaveClass(/mc-active/);
  });

  test("time toggles switch between month, week, and day views", async ({
    page,
  }) => {
    await control(page, "mc_week-").click();
    await expect.poll(() => param(page, "time")).toBe("week");
    await expect(control(page, "mc_week-")).toHaveClass(/mc-active/);
    await expect(control(page, "mc_month-")).not.toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);
    await expect(calendar(page)).not.toContainText(TITLES.next);

    await control(page, "mc_day-").click();
    await expect.poll(() => param(page, "time")).toBe("day");
    await expect(control(page, "mc_day-")).toHaveClass(/mc-active/);
    await expect(control(page, "mc_week-")).not.toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);

    await control(page, "mc_month-").click();
    await expect.poll(() => param(page, "time")).toBe("month");
    await expect(control(page, "mc_month-")).toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);
  });

  test("next and previous move by a week in week view", async ({ page }) => {
    await control(page, "mc_week-").click();
    await expect(control(page, "mc_week-")).toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);

    await control(page, "mc_next_").click();
    await expect.poll(() => param(page, "time")).toBe("week");
    await expect(calendar(page)).not.toContainText(TITLES.today);

    await control(page, "mc_today_").click();
    await expect(calendar(page)).toContainText(TITLES.today);

    await control(page, "mc_previous_").click();
    await expect.poll(() => param(page, "time")).toBe("week");
    await expect(calendar(page)).not.toContainText(TITLES.today);
  });

  test("next and previous move by a day in day view", async ({ page }) => {
    await control(page, "mc_day-").click();
    await expect(control(page, "mc_day-")).toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);

    await control(page, "mc_next_").click();
    await expect.poll(() => param(page, "time")).toBe("day");
    await expect(calendar(page)).not.toContainText(TITLES.today);

    await control(page, "mc_today_").click();
    await expect(calendar(page)).toContainText(TITLES.today);

    await control(page, "mc_previous_").click();
    await expect(calendar(page)).not.toContainText(TITLES.today);
  });

  test("format toggle switches between grid and list", async ({ page }) => {
    await expect(control(page, "mc_grid-")).toHaveClass(/mc-active/);

    await control(page, "mc_list-").click();
    await expect.poll(() => param(page, "format")).toBe("list");
    await expect(control(page, "mc_list-")).toHaveClass(/mc-active/);
    await expect(control(page, "mc_grid-")).not.toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);

    await control(page, "mc_grid-").click();
    await expect(control(page, "mc_grid-")).toHaveClass(/mc-active/);
    await expect(calendar(page)).toContainText(TITLES.today);
  });

  test("category key filters events and can be cleared", async ({ page }) => {
    const catButton = control(page, `mc_cat_${data.category}-`);

    await catButton.click();
    await expect.poll(() => param(page, "mcat")).toBe(String(data.category));
    await expect(calendar(page)).toContainText(TITLES.todayOther);
    await expect(calendar(page)).not.toContainText(TITLES.today);
    await expect(
      control(page, `mc_cat_${data.category}-`)
    ).toHaveAttribute("aria-pressed", "true");

    await control(page, "mc_cat_all-").click();
    await expect.poll(() => param(page, "mcat")).toBeNull();
    await expect(calendar(page)).toContainText(TITLES.today);
    await expect(calendar(page)).toContainText(TITLES.todayOther);
  });

  test("category filter persists while navigating between months", async ({
    page,
  }) => {
    await control(page, `mc_cat_${data.category}-`).click();
    await expect.poll(() => param(page, "mcat")).toBe(String(data.category));
    // With AJAX navigation the URL changes before the calendar is replaced; wait for the filtered view.
    await expect(
      control(page, `mc_cat_${data.category}-`)
    ).toHaveAttribute("aria-pressed", "true");

    // The next month only has a General event, so nothing matches the filter.
    await control(page, "mc_next_").click();
    await showsMonth(page, data.next_month, data.next_year);
    await expect(calendar(page)).not.toContainText(TITLES.next);
    await expect.poll(() => param(page, "mcat")).toBe(String(data.category));

    await control(page, "mc_today_").click();
    await expect.poll(() => param(page, "month")).toBeNull();
    await expect(calendar(page)).toContainText(TITLES.todayOther);
    await control(page, "mc_previous_").click();
    await showsMonth(page, data.previous_month, data.previous_year);
    await expect(calendar(page)).toContainText(TITLES.previous);
    await expect.poll(() => param(page, "mcat")).toBe(String(data.category));
  });

  test("the General category shows only its own events", async ({ page }) => {
    await control(page, "mc_cat_1-").click();
    await expect.poll(() => param(page, "mcat")).toBe("1");
    await expect(calendar(page)).toContainText(TITLES.today);
    await expect(calendar(page)).not.toContainText(TITLES.todayOther);
  });
});
