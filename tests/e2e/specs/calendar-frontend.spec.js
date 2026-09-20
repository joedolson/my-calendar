const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'My Calendar front end', () => {
	test( 'calendar shortcode renders on the front end', async ( { page, admin, editor } ) => {
		await admin.createNewPost( { title: 'My Calendar E2E Test' } );
		await editor.insertBlock( { name: 'core/shortcode', attributes: { text: '[my_calendar id="my-calendar"]' } } );
		await editor.publishPost();

		const previewPage = await editor.page.context().newPage();
		const permalink = await editor.page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getPermalink()
		);
		await previewPage.goto( permalink );

		await expect( previewPage.locator( '#my-calendar' ) ).toBeVisible();
		await previewPage.close();
	} );
} );
