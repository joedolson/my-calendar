jQuery(document).ready(function ($) {
	let r = document.querySelector('.mc-main');

	$('.mc-color-input').wpColorPicker({
		change:	function( event, ui ) {
			if ( null !== r ) {
				let color    = ui.color.toString();
				let variable = event.target.getAttribute( 'data-variable' );
				r.style.setProperty( variable, color );
				wp.a11y.speak( 'Preview updated' );
			}
		},
	});

	$('.mc-text-input').on( 'keyup', function(event) {
		let el = $( this );
		setTimeout( function() {
			let text = el.val();
			let variable = event.target.getAttribute( 'data-variable' );
			r.style.setProperty( variable, text );
			wp.a11y.speak( 'Preview updated' );
		}, 500, el, event );
	});

	var el = $(".my-calendar-style-preview");
	el.css("height", el.outerHeight()).find(".mc-main").stickOnScroll({
		footerElement: $(".mc-contrast-table"),
		bottomOffset: 20,
		topOffset: 34,
		setParentOnStick: true,
		stickClass: 'fix',
		setWidthOnStick: true,
	});

	let styleToggles = document.querySelectorAll( '.add-new-variable' );
	styleToggles.forEach( (el) => {
		let target  = el.closest( '.mc-new-variable' );
		let targets = target.querySelectorAll( 'p' );
		targets.forEach( (t) => { t.style.display = 'none'; } );

		el.addEventListener( 'click', function() {
			let expanded = el.getAttribute( 'aria-expanded' );
			if ( 'true' === expanded ) {
				targets.forEach( (t) => { console.log( t ); t.style.display = 'none'; } );
				el.setAttribute( 'aria-expanded', 'false' );
			} else {
				targets.forEach( (t) => { t.style.display = 'grid'; } );
				el.setAttribute( 'aria-expanded', 'true' );
			}
		});		
	});
	/**
	 * Map ARIA attributes to My Calendar table so responsive view doesn't break table relationships.
	 */
	function my_calendar_table_aria() {
		try {
			const allTables = document.querySelectorAll('.mc-responsive-table');
			const allRowGroups = document.querySelectorAll('.mc-responsive-table thead, .mc-responsive-table tbody, .mc-responsive-table tfoot');
			const allRows = document.querySelectorAll('.mc-responsive-table tr');
			const allCells = document.querySelectorAll('.mc-responsive-table td');
			const allHeaders = document.querySelectorAll('.mc-responsive-table th');
			const allRowHeaders = document.querySelectorAll('.mc-responsive-table th[scope=row]');

			for (let i = 0; i < allTables.length; i++) {
				allTables[i].setAttribute('role','table');
			}
			for (let i = 0; i < allRowGroups.length; i++) {
				allRowGroups[i].setAttribute('role','rowgroup');
			}
			for (let i = 0; i < allRows.length; i++) {
				allRows[i].setAttribute('role','row');
			}
			for (let i = 0; i < allCells.length; i++) {
				allCells[i].setAttribute('role','cell');
			}
			for (let i = 0; i < allHeaders.length; i++) {
				allHeaders[i].setAttribute('role','columnheader');
			}
			// this accounts for scoped row headers
			for (let i = 0; i < allRowHeaders.length; i++) {
				allRowHeaders[i].setAttribute('role','rowheader');
			}
			// caption role not needed as it is not a real role and
			// browsers do not dump their own role with display block
		} catch (e) {
			console.log( "my_calendar_table_aria(): " + e );
		}
	}
	my_calendar_table_aria();
});