const { test, expect } = require( '../config/a11y-test' );

test.describe( 'My Calendar accessibility', () => {
	test( 'calendar front end has no serious or critical axe violations', async ( {
		admin,
		editor,
		makeAxeBuilder,
	} ) => {
		await admin.createNewPost( { title: 'My Calendar A11y Test' } );
		await editor.insertBlock( { name: 'core/shortcode', attributes: { text: '[my_calendar id="my-calendar"]' } } );
		await editor.publishPost();

		const previewPage = await editor.page.context().newPage();
		const permalink = await editor.page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getPermalink()
		);
		await previewPage.goto( permalink );
		await previewPage.locator( '#my-calendar' ).waitFor();

		const accessibilityScanResults = await makeAxeBuilder( { page: previewPage } )
			.include( '#my-calendar' )
			.analyze();

		const seriousOrCritical = accessibilityScanResults.violations.filter( ( violation ) =>
			[ 'serious', 'critical' ].includes( violation.impact )
		);

		expect( seriousOrCritical ).toEqual( [] );
		await previewPage.close();
	} );
} );
