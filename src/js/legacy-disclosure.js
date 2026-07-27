(function ($) {
	if ( 'true' === my_calendar.list ) {
		if ( 'false' === my_calendar.links ) {
			$('li .list-event' ).hide();
			$('li.current-day .list-event').show();
			$('li.current-day .event-date .mc-text-button' ).attr( 'aria-expanded', true );
			$(document).on( 'click', '.event-date button', function (e) {
				e.preventDefault();
				$( this ).closest( '.mc-events' ).find( '.mc-event' ).toggle();
				let visible = $(this).closest( '.mc-events' ).find( '.mc-event' ).is(':visible');
				if ( visible ) {
					$(this).attr('aria-expanded', 'true');
				} else {
					$(this).attr('aria-expanded', 'false');
				}
				e.stopImmediatePropagation();
				return false;
			});
		}
	}

	if ( 'true' === my_calendar.list && 'true' === my_calendar.links ) {
		let container = '.list-event';
		$( container + ' .single-details' ).hide();
		$(document).on('click', container + ' .event-title .open',
			function (e) {
				let visible      = $(this).parents( '.mc-event' ).children( '.details' ).is(':visible');
				let controls     = $( this ).attr( 'aria-controls' );
				const controlled = $( '#' + controls );
				if ( visible ) {
					$(this).attr( 'aria-expanded', 'false' );
				} else {
					$(this).attr( 'aria-expanded', 'true' );
				}
				e.preventDefault();
				let current_date = $(this).parents( '.mc-event' ).children();

				$(this).closest( '.mc-main' ).toggleClass( 'grid-open' );
				controlled.toggle();

				const focusable  = current_date.find( 'a, button:not(.event-title > button), object, :input, iframe, [tabindex]' );
				const lastFocus  = focusable.last();
				const firstFocus = focusable.first();
				firstFocus.attr( 'data-action', 'shiftforward' );
				lastFocus.attr( 'data-action', 'shiftback' );

				$( container ).children( '.single-details' ).not( current_date ).hide();
				e.stopImmediatePropagation();
				return false;
			}
		);

		$(document).on('click', '.calendar-event .close',
			function (e) {
				e.preventDefault();
				$(this).parents( '.mc-event' ).find( 'a.open' ).attr( 'aria-expanded', 'false' );
				$(this).closest( '.mc-main' ).removeClass( 'grid-open' );
				$(this).closest('.mc-event').find('.event-title a').trigger( 'focus' );
				$(this).closest('div.single-details').toggle();
				e.stopImmediatePropagation();
			});

		$(document).on( 'keydown', function(e) {
			let keycode = ( e.keyCode ? e.keyCode : e.which );
			if ( keycode == 27 ) {
				$( '.mc-main ').removeClass( 'grid-open' );
				$( '.calendar-event div.single-details' ).hide();
			}
		});

		$(document).on(
			'keydown', '.mc-event a, .mc-event object, .mc-event :input, .mc-event iframe, .mc-event [tabindex]',
			function(e) {
				let keycode = ( e.keyCode ? e.keyCode : e.which );
				let action  = $( ':focus' ).attr( 'data-action' );
				if ( ( !e.shiftKey && keycode == 9 ) && action == 'shiftback' ) {
					e.preventDefault();
					$( '[data-action=shiftforward]' ).trigger( 'focus' );
				}
				if ( ( e.shiftKey && keycode == 9 ) && action == 'shiftforward' ) {
					e.preventDefault();
					$( '[data-action=shiftback]' ).trigger( 'focus' );
				}
			}
		);
	}
}(jQuery));