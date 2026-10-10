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
					'event_desc'         => '<p>Modal focus test content.</p>',
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

    await closeButton.press("Tab");
    await expect(closeButton).toBeFocused();
    await closeButton.press("Shift+Tab");
    await expect(closeButton).toBeFocused();

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
