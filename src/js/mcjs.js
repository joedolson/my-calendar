(function ($) {
	const { __, _x, _n, _nx } = wp.i18n;

	'use strict';
	mc_display_usertime();
	const calendar = document.querySelectorAll( '.mc-main, .mc-event-list' );
	if ( calendar ) {
		calendar.forEach( (el) => {
			let targetId = el.getAttribute( 'id' );
			mc_build_toggles( targetId );
			el.classList.remove( 'mcjs' );
		});
	}

	const loadmore = document.querySelectorAll( '.mc-loader' );
	if ( loadmore ) {
		loadmore.forEach( (el) => {
			const parent = el.closest( 'ul' );
			parent.setAttribute( 'tabindex', '-1' );
			parent.addEventListener( 'click', function( e ) {
				let targetParent = e.target.closest( 'button' );
				if ( targetParent && targetParent.classList.contains( 'mc-loader' ) ) {
					loadUpcoming( targetParent, parent );
				}
			});
		});
	}

	function loadUpcoming( el, parent ) {
		let request = new XMLHttpRequest();

		request.open( 'POST', my_calendar.ajaxurl, true );
		request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded;' );
		request.onload = function () {
			if ( this.status >= 200 && this.status < 400) {
				let results = JSON.parse( this.response );
				// Remove the list items.
				parent.querySelectorAll( 'li' ).forEach( e => e.remove() );
				// Append the response.
				parent.innerHTML += results.response;
				// Set focus to parent list.
				parent.focus();
				wp.a11y.speak( __( 'Upcoming events loaded', 'my-calendar' ) );
			} else {
				// Request failed.
				parent.innerHTML += '<li>' + __( 'Upcoming Events failed to load', 'my-calendar' ) + '</li>';
				wp.a11y.speak( __( 'Upcoming Events failed to load', 'my-calendar' ) );
			}
		};
		request.onerror = function() {
			// Connection error
		};
		let time = el.value;
		let args = el.getAttribute( 'data-value' );
		type = ( 'dates' === time ) ? 'dates' : 'time';

		request.send('action=' + my_calendar.action + '&behavior=loadupcoming&' + type + '=' + time + '&args=' + args );
	}

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
	my_calendar_external_links();
	my_calendar_edit_toggles();
	mc_render_buttons();
	if ( 'true' === my_calendar.ajax ) {
		mc_setup_handlers();
	}

	function mc_setup_handlers() {
		// Prevents spacebar from scrolling the page on links with button role.
		let buttonHandlers = document.querySelectorAll('.my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a)');
		buttonHandlers.forEach( (el) => {
			el.addEventListener( 'keydown', function(e) {
				if ( ' ' === e.key ) {
					e.preventDefault();
				}
			});
		});
		let navActions = document.querySelectorAll('.my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a), .my-calendar-header input[type=submit], .my-calendar-footer input[type=submit], .my-calendar-header button:not(.mc-export button), .my-calendar-footer button:not(.mc-export button)');
		navActions.forEach( (el) => {
			el.addEventListener( 'click', function(e) {
				mc_handle_navigation(e, el);
			});
			el.addEventListener( 'keyup', function(e) {
				mc_handle_navigation(e, el);
			});
		});
	}

	function mc_handle_navigation(e, el) {
		e.preventDefault();
		if ( 'click' === e.type || ( 'keyup' === e.type && ' ' === e.key ) ) {
			const calendar = el.closest( '.mc-main' );
			calendar.classList.remove( 'is-main-view' );
			let targetId   = el.getAttribute( 'id' ), ref = calendar.getAttribute('id'),
							month, day, year, mcat, loc, access, mcs, link, url;
			console.log( targetId );
			if ( 'INPUT' === el.nodeName || 'BUTTON' === el.nodeName ) {
				const inputForm = el.closest( 'form' );
				if ( inputForm.classList.contains( 'mc-date-switcher' ) ) {
					month = inputForm.querySelector( 'select[name=month]' ).value;
					day   = inputForm.querySelector( 'select[name=dy]' ).value;
					year  = inputForm.querySelector( 'select[name=yr]' ).value;
				}
				if ( inputForm.classList.contains( 'mc-categories-switcher' ) ) {
					mcat = inputForm.querySelector( 'select[name=mcat]' ).value;
				}
				if ( inputForm.classList.contains( 'mc-locations-switcher' ) ) {
					loc = inputForm.querySelector( 'select[name=loc]' ).value;
				}
				if ( inputForm.classList.contains( 'mc-access-switcher' ) ) {
					access = inputForm.querySelector( 'select[name=access]' ).value;
				}
				mcs  = inputForm.querySelector( 'input[name=mcs]' ).value;
				link = el.getAttribute( 'data-href' );
			} else {
				link = el.getAttribute('href');
			}
			try {
				url = new URL(link);
				url.searchParams.delete('embed');
				url.searchParams.delete('source');
				if ( 'INPUT' === el.nodeName || 'BUTTON' === el.nodeName ) {
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

			calendar.insertAdjacentHTML( 'afterbegin', '<div class="mc-loading"></div><div class="loading"><span class="screen-reader-text">Loading...</span></div>');
			wp.a11y.speak( __( 'Loading', 'my-calendar' ) );

			fetch( link )
				.then( response => {
					if ( response.ok ) {
						return response.text();
					}
				}).then (html => {
					const parser = new DOMParser();
					const doc = parser.parseFromString( html, "text/html" );
					calendar.replaceWith( doc.querySelector( '#' + ref ) );
					mc_build_calendar( targetId, ref );
				}).catch( error => {
					calendar.insertAdjacentHTML( 'beforebegin', error );
				});
		}
	}

	function mc_build_calendar( targetId, ref ) {
		// functions to execute when new view loads.
		// List view.
		if ( typeof( my_calendar ) !== "undefined" && my_calendar.list == 'true' ) {
			let listEvents;
			if ( 'false' === my_calendar.links ) {
				listEvents = document.querySelectorAll( 'li.mc-events' );
				listEvents.forEach( (el) => {
					let target = el.querySelector( '.mc-events' );
					if ( ! el.classList.contains( 'current-day' ) ) {
						target.style.display = 'none';
					}
				});
			} else {
				listEvents =  document.querySelectorAll( 'li.mc-events .single-details' );
				listEvents.forEach( (el) => {
					el.style.display = 'none';
				});
			}
		}
		// Grid view.
		if ( typeof( my_calendar ) !== "undefined" && my_calendar.grid == 'true' ) {
			let gridEvents = document.querySelectorAll('.has-events > .calendar-event > *:not(header)');
			gridEvents.forEach( (el) => {
				el.style.display = 'none';
			});
		}
		// Mini view.
		if  ( typeof( my_calendar ) !== "undefined" && my_calendar.mini == 'true' ) {
			let miniEvents = document.querySelectorAll( '.min .has-events > *:not(.mc-date-container)' );
			miniEvents.forEach( (el) => {
				el.style.display = 'none';
			});
		}
		mc_render_buttons();
		my_calendar_external_links();
		my_calendar_edit_toggles();
		let originalFocus = document.getElementById( targetId );
		originalFocus.focus();
		let refAnnounce = document.getElementById( 'mc_head_' + ref );
		wp.a11y.speak( refAnnounce.innerText );
		mc_display_usertime();
		mc_build_toggles( ref );
		mc_setup_handlers();
		my_calendar_table_aria();
	}

	function mc_display_usertime() {
		const usertime = document.querySelectorAll( '.mc-user-time' );
		let label = new Intl.DateTimeFormat().resolvedOptions().timeZone, udate, utime;
		usertime.forEach( (el) => {
			let time  = el.innerText;
			let type  = el.getAttribute( 'data-type' );
			// Handle Internet Explorer's lack of timezone info.
			if (undefined === label) {
				label = el.getAttribute( 'data-label' );
			}
			if ( 'datetime' === type ) {
				utime = '<span class="mc-local-time-time">' + new Date( time ).toLocaleTimeString().replace( ':00 ', ' ' ) + '</span>';
				udate = '<span class="mc-local-time-date">' + new Date( time ).toLocaleDateString() + '</span>';
				el.innerHTML = '<span class="mc-local-time-label">' + label + ':</span> ' + udate + '<span class="sep">, </span>' + utime;
			}
			if ( 'date' === type ) {
				udate = '<span class="mc-local-time-date">' + new Date( time ).toLocaleDateString() + '</span>';
				el.innerHTML = '<span class="mc-local-time-label">' + label + ':</span> ' + udate;

			}
			if ( 'time' === type ) {
				utime = '<span class="mc-local-time-time">' + new Date( time ).toLocaleTimeString().replace( ':00 ', ' ' ) + '</span>';
				el.innerHTML = '<span class="mc-local-time-label">' + label + ':</span> ' + utime;

			}
			el.setAttribute( 'data-time', time );
		});
	}

	function mc_render_buttons() {
		const links = document.querySelectorAll( '.my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a)' );
		links.forEach( (el) => {
			el.setAttribute( 'role', 'button' );
		});
	}

	function mc_build_toggles( targetId ) {
		if ( targetId ) {
			const subscribe   = document.querySelector( '#' + targetId + ' .mc-subscribe' );
			const exports     = document.querySelector( '#' + targetId + ' .mc-download' );
			if ( null !== subscribe ) {
				let controls_id = 'mc_control_' + Math.floor(Math.random() * 1000 ).toString();
				const toggle = document.createElement( 'button' );
				toggle.setAttribute( 'type', 'button' );
				toggle.setAttribute( 'aria-controls', controls_id );
				toggle.setAttribute( 'aria-expanded', false );
				toggle.innerHTML = my_calendar.subscribe + ' <span class="dashicons dashicons-arrow-right" aria-hidden="true"></span>';
				subscribe.querySelector( 'ul' ).setAttribute( 'id', controls_id );
				subscribe.querySelector( 'ul' ).style.display = 'none';
				subscribe.insertAdjacentElement( 'afterbegin', toggle );
			}
			if ( null !== exports ) {
				let controls_id = 'mc_control_' + Math.floor(Math.random() * 1000 ).toString();
				const toggle = document.createElement( 'button' );
				toggle.setAttribute( 'type', 'button' );
				toggle.setAttribute( 'aria-controls', controls_id );
				toggle.setAttribute( 'aria-expanded', false );
				toggle.innerHTML = my_calendar.export + ' <span class="dashicons dashicons-arrow-right" aria-hidden="true"></span>';
				exports.querySelector( 'ul' ).setAttribute( 'id', controls_id );
				exports.querySelector( 'ul' ).style.display = 'none';
				exports.insertAdjacentElement( 'afterbegin', toggle );
			}
			const toggles = document.querySelectorAll( '#' + targetId + ' .mc-export button' );
			let icon;
			toggles.forEach( (el) => {
				el.addEventListener( 'click', function() {
					let controlled = el.getAttribute( 'aria-controls' );
					let target     = document.getElementById( controlled );
					if ( target.checkVisibility() ) {
						target.style.display = 'none';
						el.setAttribute( 'aria-expanded', 'false' );
						icon = el.querySelector( '.dashicons' );
						icon.classList.remove( 'dashicons-arrow-down' );
						icon.classList.add( 'dashicons-arrow-right' );
					} else {
						target.style.display = 'block';
						el.setAttribute( 'aria-expanded', 'true' );
						icon = el.querySelector( '.dashicons' );
						icon.classList.remove( 'dashicons-arrow-right' )
						icon.classList.add( 'dashicons-arrow-down' );
					}
				});
			});
		}
	}

	function my_calendar_external_links() {
		let external_links = document.querySelectorAll('.mc-main a[target=_blank]');
		external_links.forEach( (el) => {
			el.classList.add( 'mc-opens-in-new-tab' );
			el.insertAdjacentHTML( 'beforeend', ' <span class="dashicons dashicons-external" aria-hidden="true"></span><span class="screen-reader-text"> ' + my_calendar.newWindow + '</span>' );
		});
	}

	function my_calendar_edit_toggles() {
		const adminToggles = document.querySelectorAll( '.mc-toggle-edit' );
		if ( adminToggles ) {
			adminToggles.forEach( (el) => {
				let controls = el.getAttribute( 'aria-controls' );
				let controlled = document.querySelector( '#' + controls );
				el.addEventListener( 'click', function(e) {
					let position = el.offsetWidth + 8;
					controlled.style.left = position + 'px';
					let expanded = el.getAttribute( 'aria-expanded' );
					if ( 'true' === expanded ) {
						controlled.style.display = 'none';
						el.setAttribute( 'aria-expanded', 'false' );
					} else {
						controlled.style.display = 'block';
						el.setAttribute( 'aria-expanded', 'true' );
					}
				});
			});
		}
	}
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