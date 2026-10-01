const { test, expect } = require("@playwright/test");
const { wpEval } = require("../config/wp-cli");

/**
 * Regression test for the Google Maps tile-loading bug: mc_map() in src/js/gmaps.js
 * must set a zoom level (or call fitBounds) or the Maps JS API builds the map
 * container but never requests tiles. Requires a real, billing-enabled Google
 * Maps API key since it exercises the live Maps JavaScript API.
 */
test.describe("My Calendar location map rendering", () => {
  test.skip(
    !process.env.MC_GMAP_API_KEY,
    "Set MC_GMAP_API_KEY to a valid Google Maps API key to run map rendering tests."
  );

  let locationId;
  let permalink;

  test.beforeAll(() => {
    const apiKey = process.env.MC_GMAP_API_KEY;
    wpEval(
      "update_option( 'my_calendar_options', array_merge( get_option( 'my_calendar_options', array() ), array( 'gmap_api_key' => '" +
        apiKey +
        "', 'gmap' => 'true', 'map_service' => 'google' ) ) );"
    );

    const json = wpEval(`
			$result = mc_insert_location( array(
				'location_label'     => 'E2E Map Test Location',
				'location_street'    => '1134 Poplar Street',
				'location_street2'   => '',
				'location_city'      => 'Missoula',
				'location_state'     => 'MT',
				'location_postcode'  => '59802',
				'location_region'    => '',
				'location_country'   => 'US',
				'location_url'       => '',
				'location_longitude' => '-113.985603',
				'location_latitude'  => '46.878399',
				'location_zoom'      => '14',
				'location_phone'     => '',
				'location_phone2'    => '',
			) );
			echo wp_json_encode( array(
				'location_id' => $result['location_id'],
				'permalink'   => get_permalink( $result['location_post'] ),
			) );
		`);
    const data = JSON.parse(json);
    locationId = data.location_id;
    permalink = data.permalink;
  });

  test.afterAll(() => {
    if (locationId) {
      wpEval(`mc_delete_location( ${locationId} );`);
    }
  });

  test("location map loads Google Maps tiles", async ({ page }) => {
    await page.goto(permalink);

    const mapContainer = page.locator(".mc-gmap-markers");
    await expect(mapContainer).toBeVisible();

    // Tiles are only requested once the map has a usable zoom level (see mc_map() in gmaps.js).
    await expect
      .poll(async () => mapContainer.locator("img").count(), {
        message:
          "Expected Google Maps tile images to load inside the map container",
        timeout: 15000,
      })
      .toBeGreaterThan(0);
  });
});
