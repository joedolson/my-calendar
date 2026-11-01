const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { wpEval } = require("../config/wp-cli");
const { setEventDate, setDescription } = require("../config/admin-helpers");

const LOCATION_PREFIX = "E2E Managed Location";
const EVENT_PREFIX = "E2E Location Event";
const ADD_LOCATION_URL = "/wp-admin/admin.php?page=my-calendar-locations";
const ADD_EVENT_URL = "/wp-admin/admin.php?page=my-calendar";

const location = {
  label: `${LOCATION_PREFIX} Hall`,
  street: "742 Evergreen Terrace",
  street2: "Suite 200",
  city: "Springfield",
  state: "MN",
  postcode: "55123",
  region: "Upper Midwest",
  country: "United States",
  latitude: "44.5",
  longitude: "-93.25",
  zoom: "12",
  phone: "612-555-0101",
  phone2: "612-555-0102",
  url: "https://example.com/e2e-location",
};

test.describe("My Calendar admin location creation", () => {
  test.describe.configure({ mode: "serial" });

  let tomorrow;
  let locationPostUrl;

  test.beforeAll(() => {
    tomorrow = wpEval(
      "echo wp_date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );"
    );
  });

  test.afterAll(() => {
    wpEval(`
			global $wpdb;
			$ids = $wpdb->get_col( "SELECT event_id FROM " . my_calendar_table() . " WHERE event_title LIKE '${EVENT_PREFIX}%'" );
			foreach ( $ids as $id ) {
				mc_delete_event( (int) $id );
			}
			$locations = $wpdb->get_col( "SELECT location_id FROM " . my_calendar_locations_table() . " WHERE location_label LIKE '${LOCATION_PREFIX}%'" );
			foreach ( $locations as $location ) {
				mc_delete_location( (int) $location, 'integer' );
			}
		`);
  });

  test("creates a location with all fields and shows it on the location post", async ({
    page,
  }) => {
    await page.goto(ADD_LOCATION_URL);

    await page.locator("#e_label").fill(location.label);
    await page.locator("#e_street").fill(location.street);
    await page.locator("#e_street2").fill(location.street2);
    await page.locator("#e_city").fill(location.city);
    await page.locator("#e_state").fill(location.state);
    await page.locator("#e_postcode").fill(location.postcode);
    await page.locator("#e_region").fill(location.region);
    await page.locator("#e_country").fill(location.country);
    await page.locator("#e_zoom").selectOption(location.zoom);
    await page.locator("#e_latitude").fill(location.latitude);
    await page.locator("#e_longitude").fill(location.longitude);
    await page.locator("#e_phone").fill(location.phone);
    await page.locator("#e_phone2").fill(location.phone2);
    await page.locator("#e_url").fill(location.url);

    const accessTerm = page
      .locator('input[name="location_access[]"]')
      .first();
    const hasAccessTerm = (await accessTerm.count()) > 0;
    if (hasAccessTerm) {
      await accessTerm.check();
    }

    await page.getByRole("button", { name: "Add Location" }).first().click();

    const notice = page.locator(".notice", {
      hasText: "Location added successfully",
    });
    await expect(notice).toBeVisible();
    await notice.getByRole("link", { name: "Edit location" }).click();

    // The edit screen holds every saved value.
    await expect(page.locator("#e_label")).toHaveValue(location.label);
    await expect(page.locator("#e_street")).toHaveValue(location.street);
    await expect(page.locator("#e_street2")).toHaveValue(location.street2);
    await expect(page.locator("#e_city")).toHaveValue(location.city);
    await expect(page.locator("#e_state")).toHaveValue(location.state);
    await expect(page.locator("#e_postcode")).toHaveValue(location.postcode);
    await expect(page.locator("#e_region")).toHaveValue(location.region);
    await expect(page.locator("#e_country")).toHaveValue(location.country);
    await expect(page.locator("#e_zoom")).toHaveValue(location.zoom);
    expect(
      parseFloat(await page.locator("#e_latitude").inputValue())
    ).toBeCloseTo(parseFloat(location.latitude), 4);
    expect(
      parseFloat(await page.locator("#e_longitude").inputValue())
    ).toBeCloseTo(parseFloat(location.longitude), 4);
    await expect(page.locator("#e_phone")).toHaveValue(location.phone);
    await expect(page.locator("#e_phone2")).toHaveValue(location.phone2);
    await expect(page.locator("#e_url")).toHaveValue(location.url);
    if (hasAccessTerm) {
      await expect(
        page.locator('input[name="location_access[]"]').first()
      ).toBeChecked();
    }

    locationPostUrl = await page.locator("a.view").first().getAttribute("href");
    expect(locationPostUrl).toBeTruthy();

    // The location's own post shows the saved data.
    await page.goto(locationPostUrl);
    const details = page.locator(".mc-view-location");
    await expect(details).toBeVisible();
    await expect(page.locator("body")).toContainText(location.label);
    const hcard = details.locator(".mc-location-hcard");
    await expect(hcard).toContainText(location.label);
    await expect(hcard.locator(".p-street-address")).toHaveText(
      location.street
    );
    await expect(hcard.locator(".p-extended-address")).toHaveText(
      location.street2
    );
    await expect(hcard.locator(".p-locality")).toHaveText(location.city);
    await expect(hcard.locator(".p-region")).toHaveText(location.state);
    await expect(hcard.locator(".p-postal-code")).toHaveText(
      location.postcode
    );
    await expect(hcard.locator(".p-country-name")).toHaveText(
      location.country
    );
    await expect(hcard.locator(".p-tel")).toHaveText(location.phone);
    await expect(
      hcard.locator(`a.location-link[href^="${location.url}"]`)
    ).toBeVisible();
    await expect(
      details.locator(".mc-location-upcoming")
    ).toContainText("No events currently scheduled at this location.");
  });

  test("an event created at the location appears on the location post", async ({
    page,
  }) => {
    test.skip(!locationPostUrl, "Location was not created.");
    const title = `${EVENT_PREFIX} Upcoming`;

    await page.goto(ADD_EVENT_URL);
    await page.getByLabel("Event Title").fill(title);
    await setDescription(page, "<p>E2E event at a managed location.</p>");
    await setEventDate(page, tomorrow);
    await page.locator("#mc_event_time").fill("15:30");
    await page.locator("#mc_event_endtime").fill("16:45");
    await page.locator("#l_preset").selectOption({ label: location.label });

    await page.getByRole("button", { name: "Publish", exact: true }).last().click();

    const notice = page.locator(".notice", { hasText: "Event added" });
    await expect(notice).toBeVisible();
    await expect(
      notice.getByRole("link", { name: "View Event" })
    ).toBeVisible();

    await page.goto(locationPostUrl);
    const upcoming = page.locator(".mc-location-upcoming");
    await expect(upcoming).toContainText(title);
    await expect(upcoming).toContainText(/3:30\s*p\.?m/i);
    await expect(upcoming).not.toContainText(
      "No events currently scheduled at this location."
    );
  });
});
