const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { wpEval } = require("../config/wp-cli");

const PREFIX = "E2E Category";
const CATEGORIES_URL = "/wp-admin/admin.php?page=my-calendar-categories";

/**
 * Read the category display settings.
 *
 * @return {{hide_icons: string, apply_color: string}} Current settings.
 */
const getSettings = () =>
  JSON.parse(
    wpEval(
      "echo wp_json_encode( array( 'hide_icons' => mc_get_option( 'hide_icons' ), 'apply_color' => mc_get_option( 'apply_color' ) ) );"
    )
  );

/**
 * Set category display settings directly in the database.
 *
 * @param {string} hideIcons 'true' or 'false'.
 * @param {string} applyColor 'default', 'font' or 'background'.
 */
const setSettings = (hideIcons, applyColor) => {
  wpEval(
    `$o = get_option( 'my_calendar_options' ); $o['hide_icons'] = '${hideIcons}'; $o['apply_color'] = '${applyColor}'; update_option( 'my_calendar_options', $o ); delete_transient( 'mc_generated_category_styles' );`
  );
};

/**
 * Create a category directly in the database.
 *
 * @param {string} name Category name.
 * @param {string} color Hex color.
 * @param {string} icon Icon file name.
 * @return {number} Category ID.
 */
const createCategory = (name, color, icon) =>
  parseInt(
    wpEval(
      `echo mc_create_category( array( 'category_name' => '${name}', 'category_color' => '${color}', 'category_icon' => '${icon}' ) );`
    ),
    10
  );

/**
 * Fetch a category row by name.
 *
 * @param {string} name Category name.
 * @return {object|null} Category row.
 */
const getCategory = (name) =>
  JSON.parse(
    wpEval(
      `global $wpdb; echo wp_json_encode( $wpdb->get_row( $wpdb->prepare( 'SELECT category_id, category_name, category_color, category_icon FROM ' . my_calendar_categories_table() . ' WHERE category_name = %s', '${name}' ) ) );`
    )
  );

/**
 * Set the color picker's input value.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {string} color Hex color.
 */
const setColor = async (page, color) => {
  await page.locator("#cat_color").evaluate((el, value) => {
    el.value = value;
    el.dispatchEvent(new Event("input", { bubbles: true }));
    el.dispatchEvent(new Event("change", { bubbles: true }));
  }, color);
};

/**
 * Search for an icon and choose it from the autocomplete results.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {string} search Search term.
 * @param {string} filename Icon file name to choose.
 */
const chooseIcon = async (page, search, filename) => {
  await page.locator("#cat_icon").fill("");
  await page.locator("#cat_icon").pressSequentially(search);
  await page
    .locator("#mc-icons-autocomplete [role='option']", { hasText: filename })
    .first()
    .click();
  await expect(page.locator("#mc_category_icon")).toHaveValue(filename);
};

/**
 * Submit the category settings form.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {{hideIcons: boolean, applyColor: 'default'|'font'|'background'}} settings Settings to apply.
 */
const saveSettings = async (page, { hideIcons, applyColor }) => {
  await page.goto(CATEGORIES_URL);
  await page
    .getByRole("checkbox", { name: "Hide Category icons" })
    .setChecked(hideIcons);
  const radioNames = {
    default: "Hide category colors",
    font: /use category color for text/,
    background: /use category color as a background color/,
  };
  await page.getByRole("radio", { name: radioNames[applyColor] }).check();
  await page.getByRole("button", { name: "Save Settings" }).click();
  await expect(
    page.locator(".notice", {
      hasText: "My Calendar Category Configuration Updated",
    })
  ).toBeVisible();
  // The save response renders the form before options update; reload for fresh state.
  await page.goto(CATEGORIES_URL);
};

const categoryRow = (page, name) =>
  page.locator("table.mc-categories tr").filter({ hasText: name });

test.describe("My Calendar category management", () => {
  test.describe.configure({ mode: "serial" });

  let originalOptions;

  test.beforeAll(() => {
    originalOptions = wpEval(
      "echo base64_encode( serialize( get_option( 'my_calendar_options', array() ) ) );"
    );
  });

  test.beforeEach(() => {
    setSettings("false", "background");
  });

  test.afterAll(() => {
    wpEval(`
			global $wpdb;
			$rows = $wpdb->get_results( "SELECT category_id, category_term FROM " . my_calendar_categories_table() . " WHERE category_name LIKE '${PREFIX}%'" );
			foreach ( $rows as $row ) {
				$wpdb->delete( my_calendar_categories_table(), array( 'category_id' => $row->category_id ), array( '%d' ) );
				if ( $row->category_term ) {
					wp_delete_term( (int) $row->category_term, 'mc-event-category' );
				}
				delete_option( 'mc_category_icon_category_' . $row->category_id );
				delete_transient( 'mc_cat_' . $row->category_id );
			}
			update_option( 'my_calendar_options', unserialize( base64_decode( '${originalOptions}' ) ) );
		`);
  });

  test("adds a category with a name, color and icon without changing settings", async ({
    page,
  }) => {
    const name = `${PREFIX} Added`;
    const color = "#1a2b3c";
    const settingsBefore = getSettings();

    await page.goto(CATEGORIES_URL);
    await page.getByLabel("Category Name").fill(name);
    await setColor(page, color);
    await chooseIcon(page, "democrat", "democrat.svg");
    await page.getByRole("button", { name: "Add Category" }).click();

    await expect(
      page.locator(".notice", { hasText: "Category added successfully" })
    ).toBeVisible();

    const row = categoryRow(page, name);
    await expect(row).toBeVisible();
    await expect(row.locator("svg.category-icon")).toBeVisible();
    await expect(row.locator("td", { hasText: /#1a2b3c/i })).toBeVisible();

    const saved = getCategory(name);
    expect(saved.category_name).toBe(name);
    expect(saved.category_color.toLowerCase()).toBe(color);
    expect(saved.category_icon).toBe("democrat.svg");

    expect(getSettings()).toEqual(settingsBefore);
  });

  test("updates a category without changing settings", async ({ page }) => {
    const name = `${PREFIX} Original`;
    const newName = `${PREFIX} Updated`;
    const newColor = "#aa5500";
    const id = createCategory(name, "#112233", "democrat.svg");
    const settingsBefore = getSettings();

    await page.goto(`${CATEGORIES_URL}&mode=edit&category_id=${id}`);
    await expect(page.getByLabel("Category Name")).toHaveValue(name);
    await page.getByLabel("Category Name").fill(newName);
    await setColor(page, newColor);
    await chooseIcon(page, "event", "event.svg");
    await page.getByRole("button", { name: "Save Changes" }).click();

    await expect(
      page.locator(".notice", { hasText: "Category edited successfully" })
    ).toBeVisible();

    const row = categoryRow(page, newName);
    await expect(row).toBeVisible();
    await expect(row.locator("td", { hasText: /#aa5500/i })).toBeVisible();

    const saved = getCategory(newName);
    expect(saved.category_id).toBe(String(id));
    expect(saved.category_color.toLowerCase()).toBe(newColor);
    expect(saved.category_icon).toBe("event.svg");

    expect(getSettings()).toEqual(settingsBefore);
  });

  test("updating settings does not change categories", async ({ page }) => {
    const name = `${PREFIX} Stable`;
    createCategory(name, "#334455", "democrat.svg");
    const categoryBefore = getCategory(name);

    await saveSettings(page, { hideIcons: true, applyColor: "font" });

    expect(getSettings()).toEqual({ hide_icons: "true", apply_color: "font" });
    expect(getCategory(name)).toEqual(categoryBefore);

    await saveSettings(page, { hideIcons: false, applyColor: "background" });

    expect(getSettings()).toEqual({
      hide_icons: "false",
      apply_color: "background",
    });
    expect(getCategory(name)).toEqual(categoryBefore);
  });

  test("category list and form show icons only when icons are enabled", async ({
    page,
  }) => {
    const name = `${PREFIX} Icons`;
    createCategory(name, "#445566", "democrat.svg");

    await saveSettings(page, { hideIcons: false, applyColor: "background" });
    await expect(page.locator("th.mc-icon")).toBeVisible();
    await expect(categoryRow(page, name).locator("svg.category-icon")).toBeVisible();
    await expect(page.locator("#cat_icon")).toBeVisible();

    await saveSettings(page, { hideIcons: true, applyColor: "background" });
    await expect(page.locator("th.mc-icon")).toHaveCount(0);
    await expect(categoryRow(page, name)).toBeVisible();
    await expect(categoryRow(page, name).locator("svg")).toHaveCount(0);
    await expect(page.locator("#cat_icon")).toHaveCount(0);
  });

  test("category list and form show colors only when colors are enabled", async ({
    page,
  }) => {
    const name = `${PREFIX} Colors`;
    createCategory(name, "#665544", "democrat.svg");

    await saveSettings(page, { hideIcons: false, applyColor: "font" });
    await expect(page.locator("th.mc-color")).toBeVisible();
    await expect(
      categoryRow(page, name).locator("td", { hasText: /#665544/i })
    ).toBeVisible();
    await expect(page.locator("#cat_color")).toHaveCount(1);

    await saveSettings(page, { hideIcons: false, applyColor: "background" });
    await expect(page.locator("th.mc-color")).toBeVisible();
    await expect(
      categoryRow(page, name).locator("td", { hasText: /#665544/i })
    ).toBeVisible();

    await saveSettings(page, { hideIcons: false, applyColor: "default" });
    await expect(page.locator("th.mc-color")).toHaveCount(0);
    await expect(categoryRow(page, name)).toBeVisible();
    await expect(
      categoryRow(page, name).locator("td", { hasText: /#665544/i })
    ).toHaveCount(0);
    await expect(page.locator("#cat_color")).toHaveCount(0);
  });
});
