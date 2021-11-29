(function ($) {
	'use strict';
	$(function () {
		$('li.mc-events').children().not('.event-date').hide();
		$('li.current-day').children().show();
		$('li.current-day .event-date .mc-text-button' ).attr( 'aria-expanded', true );
		$(document).on( 'click', '.event-date button',
			function (e) {
				e.preventDefault();
				var mcEvent = $( this ).closest( '.mc-events' ).find( '.mc-event:first' );
				$( this ).closest( '.mc-events' ).find( '.mc-event' ).toggle();
				mcEvent.attr('tabindex', '-1').trigger( 'focus' );
				var visible = $(this).closest( '.mc-events' ).find('.mc-event').is(':visible');
				if ( visible ) {
					$(this).attr('aria-expanded', 'true');
				} else {
					$(this).attr('aria-expanded', 'false');
				}
			});
	});
}(jQuery));