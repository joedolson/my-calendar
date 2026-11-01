const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { wpEval } = require("../config/wp-cli");
const { setEventDate, setDescription } = require("../config/admin-helpers");

const EVENT_PREFIX = "E2E Admin Event";
const ADD_EVENT_URL = "/wp-admin/admin.php?page=my-calendar";
const START_TIME = "15:30";
const END_TIME = "16:45";

test.describe("My Calendar admin event creation", () => {
  test.describe.configure({ mode: "serial" });

  let pageId;
  let calendarUrl;
  let today;
  let originalOptions;
  let demoLocationId;

  test.beforeAll(() => {
    const result = JSON.parse(
      wpEval(`
				$original_options = get_option( 'my_calendar_options', array() );
				$options          = $original_options;
				$options['display_single'] = array( 'author', 'host', 'ical', 'gcal', 'gmap', 'gmap_link', 'address', 'excerpt', 'description', 'image', 'tickets', 'link', 'more', 'access' );
				$views = isset( $options['views'] ) && is_array( $options['views'] ) ? $options['views'] : array();
				if ( ! in_array( 'single', $views, true ) ) {
					$views[] = 'single';
				}
				$options['views'] = $views;
				update_option( 'my_calendar_options', $options );

				global $wpdb;
				$location_id = $wpdb->get_var( "SELECT location_id FROM " . my_calendar_locations_table() . " WHERE location_label LIKE 'Demo:%' ORDER BY location_id ASC LIMIT 1" );

				$page_id = wp_insert_post( array(
					'post_title'   => 'My Calendar Admin Event E2E',
					'post_content' => '[my_calendar id="my-calendar"]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				) );
				echo wp_json_encode( array(
					'page_id'          => $page_id,
					'permalink'        => get_permalink( $page_id ),
					'today'            => current_time( 'Y-m-d' ),
					'location_id'      => (int) $location_id,
					'original_options' => base64_encode( serialize( $original_options ) ),
				) );
			`)
    );

    pageId = result.page_id;
    calendarUrl = result.permalink;
    today = result.today;
    demoLocationId = result.location_id;
    originalOptions = result.original_options;
  });

  test.afterAll(() => {
    wpEval(`
			global $wpdb;
			$ids = $wpdb->get_col( "SELECT event_id FROM " . my_calendar_table() . " WHERE event_title LIKE '${EVENT_PREFIX}%'" );
			foreach ( $ids as $id ) {
				mc_delete_event( (int) $id );
			}
			$locations = $wpdb->get_col( "SELECT location_id FROM " . my_calendar_locations_table() . " WHERE location_label LIKE 'E2E Location%'" );
			foreach ( $locations as $location ) {
				mc_delete_location( (int) $location, 'integer' );
			}
		`);
    if (pageId) {
      wpEval(`wp_delete_post( ${pageId}, true );`);
    }
    if (originalOptions) {
      wpEval(
        `update_option( 'my_calendar_options', unserialize( base64_decode( '${originalOptions}' ) ) );`
      );
    }
  });

  test("creates an event with all fields and a saved location", async ({
    page,
  }) => {
    const title = `${EVENT_PREFIX} Full`;
    const excerpt = "E2E full event excerpt text.";
    const description = "E2E full event description paragraph.";
    const registration = "E2E registration details: bring a ticket.";
    const moreInfo = "https://example.com/e2e-more-info";
    const ticketsUrl = "https://example.com/e2e-tickets";
    const accessNotes = "E2E accessibility notes.";

    test.skip(!demoLocationId, "Demo location is not available.");

    await page.goto(ADD_EVENT_URL);

    await page.getByLabel("Event Title").fill(title);
    await setDescription(page, `<p>${description}</p>`);
    await page.locator("#e_short").fill(excerpt);

    await setEventDate(page, today);
    await page.locator("#mc_event_time").fill(START_TIME);
    await page.locator("#mc_event_endtime").fill(END_TIME);

    await page.locator("#e_link").fill(moreInfo);
    await page.locator("#event_tickets").fill(ticketsUrl);
    await page.locator("#event_registration").fill(registration);
    await page.locator("#events_access_notes").fill(accessNotes);

    const accessTerm = page.locator('input[name="events_access[]"]').first();
    let accessLabel = "";
    if (await accessTerm.count()) {
      await accessTerm.check();
      accessLabel = (
        await page.locator(`label[for="${await accessTerm.getAttribute("id")}"]`).innerText()
      ).trim();
    }

    await page.locator("#l_preset").selectOption(String(demoLocationId));

    await page.getByRole("button", { name: "Publish", exact: true }).last().click();

    const notice = page.locator(".notice", { hasText: "Event added" });
    await expect(notice).toBeVisible();
    const viewLink = notice.getByRole("link", { name: "View Event" });
    const editLink = notice.getByRole("link", { name: "Edit Event" });
    await expect(viewLink).toBeVisible();
    await expect(editLink).toBeVisible();
    const singleUrl = await viewLink.getAttribute("href");
    const editUrl = await editLink.getAttribute("href");

    // The edit screen holds the saved values.
    await page.goto(editUrl);
    await expect(page.locator("#e_title")).toHaveValue(title);
    await expect(page.locator("#e_short")).toHaveValue(excerpt);
    await expect(page.locator("#e_link")).toHaveValue(moreInfo);
    await expect(page.locator("#event_tickets")).toHaveValue(ticketsUrl);
    await expect(page.locator("#mc_event_time")).toHaveValue(START_TIME);
    await expect(page.locator("#mc_event_endtime")).toHaveValue(END_TIME);
    await expect(page.locator("#mc-current-location")).toContainText(
      "Minnesota Orchestra"
    );

    // Front end calendar: event is on the expected day with the expected time.
    await page.goto(calendarUrl);
    const dayCell = page.locator(`#calendar-${today}`);
    await expect(dayCell).toContainText(title);
    await expect(dayCell).toContainText(/3:30\s*p\.?m/i);

    // Single event view.
    await page.goto(singleUrl);
    const body = page.locator("body");
    await expect(body).toContainText(title);
    await expect(body).toContainText(excerpt);
    await expect(body).toContainText(description);
    await expect(body).toContainText(registration);
    await expect(body).toContainText(/3:30\s*p\.?m/i);
    await expect(body).toContainText(/4:45\s*p\.?m/i);
    await expect(body).toContainText("Minnesota Orchestra");
    await expect(body).toContainText("1111 Nicollet Mall");
    await expect(body).toContainText("Minneapolis");
    await expect(page.locator(`a[href="${moreInfo}"]`).first()).toBeVisible();
    await expect(page.locator(`a[href="${ticketsUrl}"]`).first()).toBeVisible();
    await expect(page.locator('a[href*="google.com/calendar"]').first()).toBeVisible();
    await expect(page.locator('a.ical[href*="vcal="]').first()).toBeVisible();
    await expect(body).toContainText(accessNotes);
    if (accessLabel) {
      await expect(body).toContainText(accessLabel);
    }
  });

  test("creates an event with title, description and a new location", async ({
    page,
  }) => {
    const title = `${EVENT_PREFIX} Minimal`;
    const description = "E2E minimal event description.";
    const location = {
      label: "E2E Location Hall",
      street: "123 Test Street",
      city: "Testville",
      state: "MN",
      postcode: "55401",
    };

    await page.goto(ADD_EVENT_URL);

    await page.getByLabel("Event Title").fill(title);
    await setDescription(page, `<p>${description}</p>`);
    await setEventDate(page, today);
    await page.locator("#mc_event_time").fill(START_TIME);
    await page.locator("#mc_event_endtime").fill(END_TIME);

    await page.getByRole("button", { name: "Add a new location" }).click();
    await page.locator("#e_label").fill(location.label);
    await page.locator("#e_street").fill(location.street);
    await page.locator("#e_city").fill(location.city);
    await page.locator("#e_state").fill(location.state);
    await page.locator("#e_postcode").fill(location.postcode);

    await page.getByRole("button", { name: "Publish", exact: true }).last().click();

    const notice = page.locator(".notice", { hasText: "Event added" });
    await expect(notice).toBeVisible();
    const viewLink = notice.getByRole("link", { name: "View Event" });
    const editLink = notice.getByRole("link", { name: "Edit Event" });
    await expect(viewLink).toBeVisible();
    await expect(editLink).toBeVisible();
    const singleUrl = await viewLink.getAttribute("href");
    const editUrl = await editLink.getAttribute("href");

    await page.goto(editUrl);
    await expect(page.locator("#e_title")).toHaveValue(title);
    await expect(page.locator("#mc-current-location")).toContainText(
      location.label
    );

    await page.goto(calendarUrl);
    const dayCell = page.locator(`#calendar-${today}`);
    await expect(dayCell).toContainText(title);
    await expect(dayCell).toContainText(/3:30\s*p\.?m/i);

    await page.goto(singleUrl);
    const body = page.locator("body");
    await expect(body).toContainText(title);
    await expect(body).toContainText(description);
    await expect(body).toContainText(location.label);
    await expect(body).toContainText(location.street);
    await expect(body).toContainText(location.city);
    await expect(body).toContainText(/3:30\s*p\.?m/i);
    await expect(body).toContainText(/4:45\s*p\.?m/i);
  });

  test("saving a draft opens the edit screen and hides the event", async ({
    page,
  }) => {
    const title = `${EVENT_PREFIX} Draft`;

    await page.goto(ADD_EVENT_URL);

    await page.getByLabel("Event Title").fill(title);
    await setDescription(page, "<p>E2E draft event description.</p>");
    await setEventDate(page, today);
    await page.locator("#mc_event_time").fill(START_TIME);
    await page.locator("#mc_event_endtime").fill(END_TIME);

    await page.getByRole("button", { name: "Save Draft" }).first().click();

    await expect(page).toHaveURL(/page=my-calendar&mode=edit&event_id=\d+/);
    await expect(page.locator("#e_title")).toHaveValue(title);
    await expect(
      page.locator(".notice", { hasText: "Event draft saved" })
    ).toBeVisible();

    await page.goto(calendarUrl);
    await expect(page.locator("#my-calendar")).toBeVisible();
    await expect(page.locator("#my-calendar")).not.toContainText(title);
  });
});
