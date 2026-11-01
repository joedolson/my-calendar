/**
 * Set the event date in the Duet date picker and its fallback input.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {string} date Date in Y-m-d format.
 */
const setEventDate = async (page, date) => {
  await page.evaluate((value) => {
    const picker = document.querySelector(
      'duet-date-picker[identifier="mc_event_date"]'
    );
    if (picker) {
      picker.value = value;
    }
    const fallback = document.querySelector("input#mc_event_date");
    if (fallback) {
      fallback.value = value;
    }
  }, date);
};

/**
 * Set the event description using the plain text editor.
 *
 * @param {import('@playwright/test').Page} page Page object.
 * @param {string} content Description HTML.
 */
const setDescription = async (page, content) => {
  const textTab = page.locator("#content-html");
  if (await textTab.count()) {
    await textTab.click();
  }
  await page.locator("textarea#content").fill(content);
};

module.exports = { setEventDate, setDescription };
