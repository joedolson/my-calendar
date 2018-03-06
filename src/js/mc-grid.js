(function ($) {
    'use strict';
    $(function () {
        $(".calendar-event").children().not(".event-title").hide();
		
        $(document).on("click", ".calendar-event .event-title",
            function (e) {
                e.preventDefault();
				var current_date = $(this).parent().children();	

				$(this).closest( '.mc-main' ).toggleClass( 'grid-open' );				
                $(this).parent().children().not(".event-title").toggle().attr("tabindex", "-1");
				$(this).parent().focus();
				
				var focusable = current_date.find( 'a, object, :input, iframe, [tabindex]' );
				var lastFocus  = focusable.last();
				var firstFocus = focusable.first();
				lastFocus.attr( 'data-action', 'shiftback' );
				
				$(".calendar-event").children().not(".event-title").not( current_date ).hide();
            });
			
        $(document).on("click", ".calendar-event .close",
            function (e) {
                e.preventDefault();
				$(this).closest( '.mc-main' ).removeClass( 'grid-open' );
                $(this).closest(".vevent").find(".event-title a").focus();
                $(this).closest("div.details").toggle();
            });
			
		$(document).on( 'keydown', function(e) {
			var keycode   = ( e.keyCode ? e.keyCode : e.which );
			if ( keycode == 27 ) {
				$( '.mc-main ').removeClass( 'grid-open' );
				$( '.calendar-event div.details' ).hide();
			}
		});
			
		$(document).on( 'keydown', '.details a, .details object, .details :input, .details iframe, .details [tabindex]', 
			function(e) {
				var keycode   = ( e.keyCode ? e.keyCode : e.which );
				var action = $( ':focus' ).attr( 'data-action' );
				if ( ( !e.shiftKey && keycode == 9 ) && action == 'shiftback' ) {
					e.preventDefault();
					$( '.mc-toggle.close' ).focus();
				}
				if ( ( e.shiftKey && keycode == 9 ) && action == 'shiftforward' ) {
					e.preventDefault();
					$( '[data-action=shiftback]' ).focus();
				}				
			});
    });
}(jQuery));	