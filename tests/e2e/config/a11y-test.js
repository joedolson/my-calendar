const base = require( '@wordpress/e2e-test-utils-playwright' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

/**
 * Extends the WordPress Playwright test base with an axe-core builder fixture,
 * pre-configured against WCAG 2.1 A/AA rules for accessibility assertions.
 */
const test = base.test.extend( {
	makeAxeBuilder: async ( { page }, use ) => {
		const makeAxeBuilder = () =>
			new AxeBuilder( { page } ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa' ] );

		await use( makeAxeBuilder );
	},
} );

const { expect } = base;

module.exports = { test, expect };
