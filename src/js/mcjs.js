(function ($) {
	'use strict';
	$(function () {
		mc_display_usertime();
		var calendar = document.querySelector( '.mc-main' );
		if ( calendar ) {
			calendar.classList.remove( 'mcjs' );
		}
	});

	if ( 'true' === my_calendar.mini ) {
		$( ".mini .calendar-events" ).hide();
		$( document ).on( "click", ".mini .has-events .trigger", function (e) {
			e.preventDefault();
			var current_date = $(this).parents( '.has-events' ).children( '.calendar-events' );
			current_date.toggle();
			$( '.mini .has-events' ).children( '.trigger' ).removeClass( 'active-toggle' );
			$( '.mini .has-events' ).children().not( '.mc-date-container' ).not( current_date ).hide();
			$( this ).addClass( 'active-toggle' );
			e.stopImmediatePropagation();
		} );
		$( document ).on( "click", ".calendar-events .close", function (e) {
			e.preventDefault();
			$(this).closest( '.mini .has-events' ).children( '.trigger' ).removeClass( 'active-toggle' );
			$(this).closest( 'div.calendar-events' ).toggle();
			e.stopImmediatePropagation();
		} );
	}

	if ( 'true' === my_calendar.list ) {
		if ( 'false' === my_calendar.links ) {
			$('li .list-event' ).hide();
			$('li.current-day .list-event').show();
			$('li.current-day .event-date .mc-text-button' ).attr( 'aria-expanded', true );
			$(document).on( 'click', '.event-date button',
				function (e) {
					e.preventDefault();
					console.log($( this ).closest( '.mc-events' ).find( '.mc-event' ) );
					$( this ).closest( '.mc-events' ).find( '.mc-event' ).toggle();
					var visible = $(this).closest( '.mc-events' ).find( '.mc-event' ).is(':visible');
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

	if ( 'true' === my_calendar.grid || ( 'true' === my_calendar.list && 'true' === my_calendar.links ) ) {
		var container = ( 'true' === my_calendar.grid ) ? '.calendar-event' : '.list-event';
		var wrapper = ( 'true' === my_calendar.links && 'true' === my_calendar.grid ) ? '.mc-events' : container;
		$( wrapper + ' .single-details' ).hide();
		$(document).on('click', wrapper + ' .event-title .open',
			function (e) {
				var visible    = $(this).parents( '.mc-event' ).children( '.details' ).is(':visible');
				var controls   = $( this ).attr( 'aria-controls' );
				var controlled = $( '#' + controls );
				if ( visible ) {
					$(this).attr( 'aria-expanded', 'false' );
				} else {
					$(this).attr( 'aria-expanded', 'true' );
				}
				e.preventDefault();
				var current_date = $(this).parents( '.mc-event' ).children();

				$(this).closest( '.mc-main' ).toggleClass( 'grid-open' );
				controlled.toggle();

				var focusable = current_date.find( 'a, object, :input, iframe, [tabindex]' );
				var lastFocus  = focusable.last();
				lastFocus.attr( 'data-action', 'shiftback' );

				$( wrapper ).children( '.single-details' ).not( current_date ).hide();
				e.stopImmediatePropagation();
				return false;
			});

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
			var keycode = ( e.keyCode ? e.keyCode : e.which );
			if ( keycode == 27 ) {
				$( '.mc-main ').removeClass( 'grid-open' );
				$( '.calendar-event div.single-details' ).hide();
				$( ".mini .calendar-events" ).hide();
			}
		});

		$(document).on( 'keydown', '.mc-event a, .mc-event object, .mc-event :input, .mc-event iframe, .mc-event [tabindex]',
			function(e) {
				var keycode = ( e.keyCode ? e.keyCode : e.which );
				var action  = $( ':focus' ).attr( 'data-action' );
				if ( ( !e.shiftKey && keycode == 9 ) && action == 'shiftback' ) {
					e.preventDefault();
					$( '.mc-toggle.close' ).trigger( 'focus' );
				}
				if ( ( e.shiftKey && keycode == 9 ) && action == 'shiftforward' ) {
					e.preventDefault();
					$( '[data-action=shiftback]' ).trigger( 'focus' );
				}
			});
	}

	if ( 'true' === my_calendar.ajax ) {
		$(document).on('click', ".my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a), .my-calendar-header input[type=submit], .my-calendar-footer input[type=submit]", function (e) {
			e.preventDefault();
			var targetId = $( this ).attr( 'id' );
			var calendar = $( this ).closest( '.mc-main' );
			var ref      = calendar.attr('id');
			var month    = '';
			var day      = '';
			var year     = '';
			var mcat     = '';
			var loc      = '';
			var access   = '';
			var mcs      = '';
			if ( 'INPUT' === this.nodeName ) {
				var inputForm = $( this ).parents( 'form' );
				if ( inputForm.hasClass( 'mc-date-switcher' ) ) {
					var month = inputForm.find( 'select[name=month]' ).val();
					var day   = inputForm.find( 'select[name=dy]' ).val();
					var year  = inputForm.find( 'select[name=yr]' ).val();
				}
				if ( inputForm.hasClass( 'mc-categories-switcher' ) ) {
					var mcat = inputForm.find( 'select[name=mcat]' ).val();
				}
				if ( inputForm.hasClass( 'mc-locations-switcher' ) ) {
					var loc = inputForm.find( 'select[name=loc]' ).val();
				}
				if ( inputForm.hasClass( 'mc-access-switcher' ) ) {
					var access = inputForm.find( 'select[name=access]' ).val();
				}
				var mcs   = calendar.find( '#mcs' ).val();
				var link  = $( this ).attr( 'data-href' );
			} else {
				var link = $(this).attr('href');
			}
			let url;
			try {
				url = new URL(link);
				url.searchParams.delete('embed');
				if ( 'INPUT' === this.nodeName ) {
					if ( '' !== month ) {
						url.searchParams.delete( 'month' );
						url.searchParams.delete( 'dy' );
						url.searchParams.delete( 'yr' );

						url.searchParams.append( 'month', parseInt( month ) );
						if ( 'undefined' !== typeof( day ) ) {
							url.searchParams.append( 'dy', parseInt( day ) );
						}
						url.searchParams.append( 'yr', parseInt( year ) );
					}
					if ( '' !== mcat ) {
						url.searchParams.delete( 'mcat' );
						url.searchParams.append( 'mcat', mcat );
					}
					if ( '' !== loc ) {
						url.searchParams.delete( 'loc' );
						url.searchParams.delete( 'ltype' );
						url.searchParams.append( 'ltype', 'name' );
						url.searchParams.append( 'loc', loc );
					}
					if ( '' !== access ) {
						url.searchParams.delete( 'access' );
						url.searchParams.append( 'access', parseInt( access ) );
					}
					url.searchParams.delete( 'mcs' );
					if ( '' !== mcs && 'undefined' !== typeof( mcs ) ) {
						url.searchParams.append( 'mcs', encodeURIComponent( mcs ) );
					}

					link = url.toString();
				}

				window.history.pushState({}, '', url );
			} catch(_) {
				url = false;
			}

			var height = calendar.height();
			$('#' + ref ).html('<div class=\"mc-loading\"></div><div class=\"loading\" style=\"height:' + height + 'px\"><span class="screen-reader-text">Loading...</span></div>');
			$( '#' + ref ).load( link + ' #' + ref + ' > *', function ( response, status, xhr ) {

				if ( status == 'error' ) {
					$( '#' + ref ).html( xhr.status + " " + xhr.statusText );
				}
				// functions to execute when new view loads.
				// List view.
				if ( typeof( my_calendar ) !== "undefined" && my_calendar.list == 'true' ) {
					if ( 'false' === my_calendar.links ) {
						$('li.mc-events').find( '.mc-events' ).hide();
						$('li.current-day').children().show();
					} else {
						$('li.mc-events .single-details' ).hide();
					}
				}
				// Grid view.
				if ( typeof( my_calendar ) !== "undefined" && my_calendar.grid == 'true' ) {
					$('.calendar-event').children().not('.event-title').hide();
				}
				// Mini view.
				if  ( typeof( my_calendar ) !== "undefined" && my_calendar.mini == 'true' ) {
					$('.mini .has-events').children().not('.mc-date-container').hide();
				}
				// All views.
				$( '#' + targetId ).trigger( 'focus' );
				var refText = $( '#mc_head_' + ref ).text();
				wp.a11y.speak( refText );
				mc_display_usertime();
			});
		});
	}

	function mc_display_usertime() {
		var utime = $( '.mc-user-time' );
		utime.each(function() {
			var time  = $( this ).text();
			var label = $( this ).attr( 'data-label' );
			var utime = '<span class="mc-local-time-time">' + new Date( time ).toLocaleTimeString().replace( ':00 ', ' ' ) + '</span>';
			var udate = '<span class="mc-local-time-date">' + new Date( time ).toLocaleDateString() + '</span>';
			$( this ).html( '<span class="mc-local-time-label">' + label + '</span>' + ' ' + udate + '<span class="sep">, </span>' + utime ).attr( 'data-time', time );
		});
	}

	$('.mc-main a[target=_blank]').append( ' <span class="dashicons dashicons-external" aria-hidden="true"></span><span class="screen-reader-text"> ' + my_calendar.newWindow + '</span>' );
}(jQuery));