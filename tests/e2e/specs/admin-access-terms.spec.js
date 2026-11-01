const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { wpEval } = require("../config/wp-cli");

const PREFIX = "E2E Access";
const EVENT_TERMS_URL = "/wp-admin/admin.php?page=my-calendar-access-terms";
const LOCATION_TERMS_URL = `${EVENT_TERMS_URL}&terms=locations`;
const EVENT_FORM_URL = "/wp-admin/admin.php?page=my-calendar";
const LOCATION_FORM_URL = "/wp-admin/admin.php?page=my-calendar-locations";

const EVENT_TAXONOMY = "mc-event-access";
const LOCATION_TAXONOMY = "mc-location-access";

let originalDefaults;

/**
 * Read the default term sets.
 *
 * @return {{events: number[], locations: number[]}} Sorted term IDs.
 */
const getDefaults = () =>
  JSON.parse(
    wpEval(
      "$e = array_map( 'intval', (array) mc_get_option( 'default_access_terms' ) ); $l = array_map( 'intval', (array) mc_get_option( 'default_location_terms' ) ); sort( $e ); sort( $l ); echo wp_json_encode( array( 'events' => $e, 'locations' => $l ) );"
    )
  );

/**
 * Overwrite the default term sets.
 *
 * @param {number[]} events Event term IDs.
 * @param {number[]} locations Location term IDs.
 */
const setDefaults = (events, locations) => {
  wpEval(
    `mc_update_option( 'default_access_terms', array( ${events.join(",")} ) ); mc_update_option( 'default_location_terms', array( ${locations.join(",")} ) );`
  );
};

/**
 * Create a term directly in the database.
 *
 * @param {string} name Term name.
 * @param {string} taxonomy Taxonomy slug.
 * @return {number} Term ID.
 */
const createTerm = (name, taxonomy) =>
  parseInt(
    wpEval(
      `$t = wp_insert_term( '${name}', '${taxonomy}' ); echo is_wp_error( $t ) ? 0 : $t['term_id'];`
    ),
    10
  );

/**
 * Find a term by name.
 *
 * @param {string} name Term name.
 * @param {string} taxonomy Taxonomy slug.
 * @return {number} Term ID or 0.
 */
const findTerm = (name, taxonomy) =>
  parseInt(
    wpEval(
      `$t = get_term_by( 'name', '${name}', '${taxonomy}' ); echo $t ? $t->term_id : 0;`
    ),
    10
  );

const termRow = (page, name) =>
  page.locator("table.mc-access-terms tr").filter({ hasText: name });

/**
 * Add a term through the admin form.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {string} name Term name.
 * @param {{isDefault?: boolean, locations?: boolean}} options Options.
 * @return {number} Term ID.
 */
const addTerm = async (page, name, { isDefault = false, locations = false } = {}) => {
  await page.goto(locations ? LOCATION_TERMS_URL : EVENT_TERMS_URL);
  await page.locator("#cat_name").fill(name);
  await page.locator("#mc_default_access_term").setChecked(isDefault);
  await page.getByRole("button", { name: "Add Term" }).click();
  await expect(
    page.locator(".notice", { hasText: "Access term added successfully" })
  ).toBeVisible();

  return findTerm(name, locations ? LOCATION_TAXONOMY : EVENT_TAXONOMY);
};

/**
 * Edit a term through the admin form.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {number} id Term ID.
 * @param {string} name New name.
 * @param {boolean} isDefault Whether the term is a default.
 * @param {boolean} locations Whether this is a location term.
 */
const editTerm = async (page, id, name, isDefault, locations = false) => {
  await page.goto(
    `${locations ? LOCATION_TERMS_URL : EVENT_TERMS_URL}&mode=edit&access_term_id=${id}`
  );
  await page.locator("#cat_name").fill(name);
  await page.locator("#mc_default_access_term").setChecked(isDefault);
  await page.getByRole("button", { name: "Save Changes" }).click();
  await expect(
    page.locator(".notice", { hasText: "Access term edited successfully" })
  ).toBeVisible();
};

test.describe("My Calendar access terms", () => {
  test.describe.configure({ mode: "serial" });

  test.beforeAll(() => {
    originalDefaults = wpEval(
      "echo wp_json_encode( array( 'events' => mc_get_option( 'default_access_terms' ), 'locations' => mc_get_option( 'default_location_terms' ) ) );"
    );
  });

  test.beforeEach(() => {
    setDefaults([], []);
  });

  test.afterAll(() => {
    wpEval(
      `foreach ( array( '${EVENT_TAXONOMY}', '${LOCATION_TAXONOMY}' ) as $tax ) { $terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'name__like' => '${PREFIX}' ) ); if ( is_array( $terms ) ) { foreach ( $terms as $t ) { wp_delete_term( $t->term_id, $tax ); } } }`
    );
    const original = JSON.parse(originalDefaults);
    setDefaults(
      (original.events || []).map(Number),
      (original.locations || []).map(Number)
    );
  });

  test("adds an event access term that is not a default", async ({ page }) => {
    const name = `${PREFIX} Plain`;
    const id = await addTerm(page, name);

    expect(id).toBeGreaterThan(0);
    await expect(termRow(page, name)).toBeVisible();
    await expect(termRow(page, name).getByText("(Default)")).toHaveCount(0);
    expect(getDefaults()).toEqual({ events: [], locations: [] });
  });

  test("adds multiple event terms to the default set", async ({ page }) => {
    const first = await addTerm(page, `${PREFIX} First`, { isDefault: true });
    await expect(
      page.locator(".notice", {
        hasText: "Term Added to Default Event Access Terms",
      })
    ).toBeVisible();
    const second = await addTerm(page, `${PREFIX} Second`, { isDefault: true });
    const third = await addTerm(page, `${PREFIX} Third`);

    expect(getDefaults().events).toEqual([first, second].sort((a, b) => a - b));

    await page.goto(EVENT_TERMS_URL);
    await expect(termRow(page, `${PREFIX} First`).getByText("(Default)")).toBeVisible();
    await expect(termRow(page, `${PREFIX} Second`).getByText("(Default)")).toBeVisible();
    await expect(termRow(page, `${PREFIX} Third`).getByText("(Default)")).toHaveCount(0);

    // Add the third term to the set from the list.
    await termRow(page, `${PREFIX} Third`)
      .getByRole("link", { name: /to defaults/ })
      .click();
    await expect(
      page.locator(".notice", {
        hasText: "Term Added to Default Event Access Terms",
      })
    ).toBeVisible();

    expect(getDefaults().events).toEqual(
      [first, second, third].sort((a, b) => a - b)
    );
    expect(getDefaults().locations).toEqual([]);
  });

  test("edits an event term and its default status without changing other defaults", async ({
    page,
  }) => {
    const keep = await addTerm(page, `${PREFIX} Keep`, { isDefault: true });
    const change = await addTerm(page, `${PREFIX} Change`, { isDefault: true });

    // Rename and remove from defaults.
    await editTerm(page, change, `${PREFIX} Changed`, false);
    expect(findTerm(`${PREFIX} Change`, EVENT_TAXONOMY)).toBe(0);
    expect(findTerm(`${PREFIX} Changed`, EVENT_TAXONOMY)).toBe(change);
    expect(getDefaults().events).toEqual([keep]);

    await page.goto(EVENT_TERMS_URL);
    await expect(termRow(page, `${PREFIX} Changed`)).toBeVisible();
    await expect(termRow(page, `${PREFIX} Changed`).getByText("(Default)")).toHaveCount(0);
    await expect(termRow(page, `${PREFIX} Keep`).getByText("(Default)")).toBeVisible();

    // Add back to defaults.
    await editTerm(page, change, `${PREFIX} Changed`, true);
    expect(getDefaults().events).toEqual([keep, change].sort((a, b) => a - b));
  });

  test("new event form selects default access terms", async ({ page }) => {
    const defaultA = createTerm(`${PREFIX} Event Default A`, EVENT_TAXONOMY);
    const defaultB = createTerm(`${PREFIX} Event Default B`, EVENT_TAXONOMY);
    const other = createTerm(`${PREFIX} Event Other`, EVENT_TAXONOMY);
    const locationDefault = createTerm(
      `${PREFIX} Location Only`,
      LOCATION_TAXONOMY
    );
    setDefaults([defaultA, defaultB], [locationDefault]);

    await page.goto(EVENT_FORM_URL);
    await expect(page.locator(`#access_term_${defaultA}`)).toBeChecked();
    await expect(page.locator(`#access_term_${defaultB}`)).toBeChecked();
    await expect(page.locator(`#access_term_${other}`)).not.toBeChecked();
    await expect(page.locator(`#access_term_${other}`)).toHaveCount(1);

    // Location defaults do not leak into the event access list.
    await expect(
      page.locator(`input[name="events_access[]"][value="${locationDefault}"]`)
    ).toHaveCount(0);
  });

  test("adds and edits location terms with their own default set", async ({
    page,
  }) => {
    const first = await addTerm(page, `${PREFIX} Loc First`, {
      isDefault: true,
      locations: true,
    });
    const second = await addTerm(page, `${PREFIX} Loc Second`, {
      isDefault: true,
      locations: true,
    });
    const third = await addTerm(page, `${PREFIX} Loc Third`, {
      locations: true,
    });

    expect(first).toBeGreaterThan(0);
    expect(findTerm(`${PREFIX} Loc First`, EVENT_TAXONOMY)).toBe(0);
    expect(getDefaults()).toEqual({
      events: [],
      locations: [first, second].sort((a, b) => a - b),
    });

    await page.goto(LOCATION_TERMS_URL);
    await expect(termRow(page, `${PREFIX} Loc First`).getByText("(Default)")).toBeVisible();
    await expect(termRow(page, `${PREFIX} Loc Third`).getByText("(Default)")).toHaveCount(0);

    await editTerm(page, third, `${PREFIX} Loc Third Edited`, true, true);
    await editTerm(page, first, `${PREFIX} Loc First`, false, true);

    expect(findTerm(`${PREFIX} Loc Third Edited`, LOCATION_TAXONOMY)).toBe(third);
    expect(getDefaults()).toEqual({
      events: [],
      locations: [second, third].sort((a, b) => a - b),
    });
  });

  test("new location form selects default location terms", async ({ page }) => {
    const defaultA = createTerm(`${PREFIX} Loc Default A`, LOCATION_TAXONOMY);
    const defaultB = createTerm(`${PREFIX} Loc Default B`, LOCATION_TAXONOMY);
    const other = createTerm(`${PREFIX} Loc Other`, LOCATION_TAXONOMY);
    const eventDefault = createTerm(`${PREFIX} Event Only`, EVENT_TAXONOMY);
    setDefaults([eventDefault], [defaultA, defaultB]);

    await page.goto(LOCATION_FORM_URL);
    await expect(page.locator(`#access_term_${defaultA}`)).toBeChecked();
    await expect(page.locator(`#access_term_${defaultB}`)).toBeChecked();
    await expect(page.locator(`#access_term_${other}`)).not.toBeChecked();
    await expect(page.locator(`#access_term_${other}`)).toHaveCount(1);

    // Event defaults do not leak into the location access list.
    await expect(
      page.locator(`input[name="location_access[]"][value="${eventDefault}"]`)
    ).toHaveCount(0);
  });
});
