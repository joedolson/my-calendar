(function ($) {
	'use strict';
	$(function () {
		mc_display_usertime();
		mc_build_toggles();
		const calendar = document.querySelectorAll( '.mc-main' );
		if ( calendar ) {
			calendar.forEach( (el) => {
				el.classList.remove( 'mcjs' );
			});
		}
	});

	if ( 'true' === my_calendar.mini ) {
		$( ".mini .calendar-events" ).hide();
		$( document ).on( "click", ".mini .has-events .trigger", function (e) {
			e.preventDefault();
			const current_date = $(this).parents( '.has-events' ).children( '.calendar-events' );
			current_date.toggle();
			$( '.mini .has-events' ).children( '.trigger' ).removeClass( 'active-toggle' );
			$( '.mini .has-events' ).children().not( '.mc-date-container' ).not( current_date ).hide();
			$( this ).addClass( 'active-toggle' );
			e.stopImmediatePropagation();
		});
		$( document ).on( "click", ".calendar-events .close", function (e) {
			e.preventDefault();
			$(this).closest( '.mini .has-events' ).children( '.trigger' ).removeClass( 'active-toggle' );
			$(this).closest( 'div.calendar-events' ).toggle();
			e.stopImmediatePropagation();
		});
	}

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

	if ( 'true' === my_calendar.grid || ( 'true' === my_calendar.list && 'true' === my_calendar.links ) ) {
		let container = ( 'true' === my_calendar.grid ) ? '.calendar-event' : '.list-event';
		let wrapper = ( 'true' === my_calendar.links && 'true' === my_calendar.grid ) ? '.mc-events' : container;
		$( wrapper + ' .single-details' ).hide();
		$(document).on('click', wrapper + ' .event-title .open',
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

				const focusable = current_date.find( 'a, object, :input, iframe, [tabindex]' );
				const lastFocus  = focusable.last();
				const firstFocus = focusable.first();
				firstFocus.attr( 'data-action', 'shiftforward' );
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
			let keycode = ( e.keyCode ? e.keyCode : e.which );
			if ( keycode == 27 ) {
				$( '.mc-main ').removeClass( 'grid-open' );
				$( '.calendar-event div.single-details' ).hide();
				$( ".mini .calendar-events" ).hide();
			}
		});

		$(document).on( 'keydown', '.mc-event a, .mc-event object, .mc-event :input, .mc-event iframe, .mc-event [tabindex]',
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
			});
	}

	if ( 'true' === my_calendar.ajax ) {
		mc_render_buttons();
		// Prevents spacebar from scrolling the page on links with button role.
		$(document).on( 'keydown', '.my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a)', function(e) {
			if ( 32 === e.which ) {
				e.preventDefault();
			}
		});
		$(document).on('click keyup', ".my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a), .my-calendar-header input[type=submit], .my-calendar-footer input[type=submit], .my-calendar-header button:not(.mc-subscribe button), .my-calendar-footer button:not(.mc-subscribe button)", function (e) {
			e.preventDefault();
			if ( 'click' === e.type || ( 'keyup' === e.type && 32 === e.which ) ) {
				let targetId   = $( this ).attr( 'id' );
				const calendar = $( this ).closest( '.mc-main' );
				let ref        = calendar.attr('id');
				let month      = '';
				let day        = '';
				let year       = '';
				let mcat       = '';
				let loc        = '';
				let access     = '';
				let mcs        = '';
				let link       = '';
				let url;
				if ( 'INPUT' === this.nodeName || 'BUTTON' === this.nodeName ) {
					const inputForm = $( this ).parents( 'form' );
					if ( inputForm.hasClass( 'mc-date-switcher' ) ) {
						month = inputForm.find( 'select[name=month]' ).val();
						day   = inputForm.find( 'select[name=dy]' ).val();
						year  = inputForm.find( 'select[name=yr]' ).val();
					}
					if ( inputForm.hasClass( 'mc-categories-switcher' ) ) {
						mcat = inputForm.find( 'select[name=mcat]' ).val();
					}
					if ( inputForm.hasClass( 'mc-locations-switcher' ) ) {
						loc = inputForm.find( 'select[name=loc]' ).val();
					}
					if ( inputForm.hasClass( 'mc-access-switcher' ) ) {
						access = inputForm.find( 'select[name=access]' ).val();
					}
					mcs  = inputForm.find( 'input[name=mcs]' ).val();
					link = $( this ).attr( 'data-href' );
				} else {
					link = $(this).attr('href');
				}
				try {
					url = new URL(link);
					url.searchParams.delete('embed');
					url.searchParams.delete('source');
					if ( 'INPUT' === this.nodeName || 'BUTTON' === this.nodeName ) {
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
							url.searchParams.append( 'ltype', 'id' );
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

				let height = calendar.height();
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
						$('.calendar-event').children().not('header').hide();
					}
					// Mini view.
					if  ( typeof( my_calendar ) !== "undefined" && my_calendar.mini == 'true' ) {
						$('.mini .has-events').children().not('.mc-date-container').hide();
					}
					mc_render_buttons();
					// All views.
					$( '#' + targetId ).trigger( 'focus' );
					let refText = $( '#mc_head_' + ref ).text();
					wp.a11y.speak( refText );
					mc_display_usertime();
					mc_build_toggles();
					my_calendar_table_aria();
				});
			}
		});
	}

	function mc_display_usertime() {
		const usertime = $( '.mc-user-time' );
		let label = Intl.DateTimeFormat().resolvedOptions().timeZone,
			udate = '',
			utime = '';
		usertime.each(function() {
			let time  = $( this ).text();
			// Handle Internet Explorer's lack of timezone info.
			if (undefined === label) {
				label = $( this ).attr( 'data-label' );
			}
			if ( time.replace( 'Z', '.000Z' ) === new Date( time ).toISOString() ) {
				$( this ).css( {'display' : 'none'} );
			}
			utime = '<span class="mc-local-time-time">' + new Date( time ).toLocaleTimeString().replace( ':00 ', ' ' ) + '</span>';
			udate = '<span class="mc-local-time-date">' + new Date( time ).toLocaleDateString() + '</span>';
			$( this ).html( '<span class="mc-local-time-label">' + label + ':</span>' + ' ' + udate + '<span class="sep">, </span>' + utime ).attr( 'data-time', time );
		});
	}

	function mc_render_buttons() {
		const links = $( '.my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a)' );
		links.each( function() {
			$( this ).attr( 'role', 'button' );
		});
	}

	function mc_build_toggles() {
		const subscribe   = $( '.mc-subscribe' );
		const exports     = $( '.mc-download' );
		if ( subscribe.length > 0 ) {
			let controls_id = 'mc_control_' + Math.floor(Math.random() * 1000 ).toString();
			const toggle = document.createElement( 'button' );
			toggle.setAttribute( 'type', 'button' );
			toggle.setAttribute( 'aria-controls', controls_id );
			toggle.setAttribute( 'aria-expanded', false );
			toggle.innerHTML = my_calendar.subscribe + ' <span class="dashicons dashicons-arrow-right" aria-hidden="true"></span>';
			subscribe.find( 'ul' ).attr( 'id', controls_id );
			subscribe.find( 'ul' ).css( { 'display' : 'none' } );
			subscribe.prepend( toggle );
		}
		if ( exports.length > 0 ) {
			let controls_id = 'mc_control_' + Math.floor(Math.random() * 1000 ).toString();
			const toggle = document.createElement( 'button' );
			toggle.setAttribute( 'type', 'button' );
			toggle.setAttribute( 'aria-controls', controls_id );
			toggle.setAttribute( 'aria-expanded', false );
			toggle.innerText = my_calendar.export;
			exports.find( 'ul' ).attr( 'id', controls_id );
			exports.find( 'ul' ).css( { 'display' : 'none' } );
			exports.prepend( toggle );
		}
		const toggles = $( '.mc-export button' );
		toggles.each( function() {
			$( this ).on( 'click', function(e) {
				let controlled = $( this ).attr( 'aria-controls' );
				let target     = $( '#' + controlled );
				if ( target.is( ':visible' ) ) {
					target.css( { 'display' : 'none' } );
					$( this ).attr( 'aria-expanded', 'false' );
					$( this ).find( '.dashicons' ).removeClass( 'dashicons-arrow-down' ).addClass( 'dashicons-arrow-right' );
				} else {
					target.css( { 'display' : 'block' } );
					$( this ).attr( 'aria-expanded', 'true' );
					$( this ).find( '.dashicons' ).removeClass( 'dashicons-arrow-right' ).addClass( 'dashicons-arrow-down' );
				}
			});
		});
	}

	$('.mc-main a[target=_blank]').append( ' <span class="dashicons dashicons-external" aria-hidden="true"></span><span class="screen-reader-text"> ' + my_calendar.newWindow + '</span>' );

	/**
	 * Map ARIA attributes to My Calendar table so responsive view doesn't break table relationships.
	 */
		function my_calendar_table_aria() {
			try {
				const allTables = document.querySelectorAll('.mc-main.calendar table.my-calendar-table');
				const allRowGroups = document.querySelectorAll('.mc-main.calendar table.my-calendar-table thead, .mc-main.calendar table.my-calendar-table tbody, .mc-main.calendar table.my-calendar-table tfoot');
				const allRows = document.querySelectorAll('.mc-main.calendar table.my-calendar-table tr');
				const allCells = document.querySelectorAll('.mc-main.calendar table.my-calendar-table td');
				const allHeaders = document.querySelectorAll('.mc-main.calendar table.my-calendar-table th');
				const allRowHeaders = document.querySelectorAll('.mc-main.calendar table.my-calendar-table th[scope=row]');

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
	
}(jQuery));