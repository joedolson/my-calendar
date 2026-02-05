(() => {
	const { __, _x, _n, _nx } = wp.i18n;

	'use strict';
	mc_display_usertime();
	initjs();

	function initjs( ref = false ) {
		const calendar = document.querySelectorAll( '.mc-main, .mc-event-list' );
		if ( calendar ) {
			calendar.forEach( (el) => {
				let targetId = ( ref ) ? ref : el.getAttribute( 'id' );
				mc_build_toggles( targetId );
				el.classList.remove( 'mcjs' );
			});
		}
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

	my_calendar_external_links();
	mc_disclosures();
	if ( 'true' === my_calendar.ajax ) {
		//mc_render_buttons();
		mc_setup_handlers();
	} else {
		mc_render_links();
	}

	function mc_setup_handlers() {
		// Prevents spacebar from scrolling the page on links with button role.
		let buttonHandlers = document.querySelectorAll('.my-calendar-header a:not(.mc-print a, .mc-export a), .my-calendar-footer a:not(.mc-print a, .mc-export a), .my-calendar-header input[type=submit], .my-calendar-footer input[type=submit], .my-calendar-header button:not(.mc-export button), .my-calendar-footer button:not(.mc-export button)');
		buttonHandlers.forEach( (el) => {
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
			let targetId = el.getAttribute( 'id' ), ref = calendar.getAttribute('id'),
				month, day, year, mcat, loc, access, mcs, link, url, inputForm, searchParams;

			if ( 'INPUT' === el.nodeName || 'BUTTON' === el.nodeName ) {
				inputForm = el.closest( 'form' );
				if ( inputForm ) {
					if ( inputForm.classList.contains( 'mc-date-switcher' ) ) {
						month = inputForm.querySelector( 'select[name=month]' ).value;
						let day_input = inputForm.querySelector( 'select[name=dy]' );
						if ( day_input ) {
							day = day_input.value;
						}
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
					if ( inputForm.classList.contains( 'mc-search-form' ) ) {
						mcs = inputForm.querySelector( 'input[name=mcs]' ).value;
					}
					link = el.closest( 'form' ).getAttribute( 'action' );
				} else {
					link = el.getAttribute('data-href');
				}
			}
			try {
				url = new URL(link);
				url.searchParams.delete('embed');
				url.searchParams.delete('source');
				if ( 'INPUT' === el.nodeName || 'BUTTON' === el.nodeName ) {
					inputForm = el.closest( 'form' );
					if ( inputForm ) {
						url.searchParams.delete( 'month' );
						url.searchParams.delete( 'dy' );
						url.searchParams.delete( 'yr' );
						if ( '' !== month && 'undefined' !== typeof( month ) ) {
							url.searchParams.append( 'month', parseInt( month ) );
							if ( 'undefined' !== typeof( day ) ) {
								url.searchParams.append( 'dy', parseInt( day ) );
							}
							url.searchParams.append( 'yr', parseInt( year ) );
						}
						url.searchParams.delete( 'mcat' );
						if ( '' !== mcat && 'undefined' !== typeof( mcat ) ) {
							url.searchParams.append( 'mcat', mcat );
						}
						url.searchParams.delete( 'loc' );
						url.searchParams.delete( 'ltype' );
						if ( '' !== loc && 'undefined' !== typeof( loc ) ) {
							url.searchParams.append( 'ltype', 'id' );
							url.searchParams.append( 'loc', loc );
						}
						url.searchParams.delete( 'access' );
						if ( '' !== access && 'undefined' !== typeof( access ) ) {
							if ( 'all' !== access ) {
								url.searchParams.append( 'access', parseInt( access ) );
							}
						}
						url.searchParams.delete( 'mcs' );
						if ( '' !== mcs && 'undefined' !== typeof( mcs ) ) {
							url.searchParams.append( 'mcs', encodeURIComponent( mcs ) );
						}

						link = url.toString();
					}
				}
				searchParams = url.searchParams;
				searchParams.sort();

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
					calendar.insertAdjacentHTML( 'afterbegin', error );
				});
		}
	}

	function mc_build_calendar( targetId, ref ) {
		initjs( ref );
		// functions to execute when new view loads.
		// List view.
		if ( typeof( my_calendar ) !== "undefined" && my_calendar.list == 'true' ) {
			let listEvents;
			if ( 'false' === my_calendar.links ) {
				listEvents = document.querySelectorAll( 'li.mc-events' );
				listEvents.forEach( (el) => {
					let target = el.querySelector( '.mc-events' );
					if ( ! el.classList.contains( 'current-day' ) && target ) {
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
			let gridEvents = document.querySelectorAll('.mc-main.calendar .has-events > .calendar-event > *:not(header)');
			gridEvents.forEach( (el) => {
				el.style.display = 'none';
			});
		}
		// Mini view.
		if  ( typeof( my_calendar ) !== "undefined" && my_calendar.mini == 'true' ) {
			let miniEvents = document.querySelectorAll( '.mc-main.mini .has-events .calendar-events' );
			miniEvents.forEach( (el) => {
				el.style.display = 'none';
			});
		}
		my_calendar_external_links();
		mc_disclosures();
		let originalFocus = document.getElementById( targetId );
		originalFocus.focus();
		let refAnnounce = document.getElementById( 'mc_head_' + ref );
		wp.a11y.speak( refAnnounce.innerText );
		mc_display_usertime();
		if ( 'true' === my_calendar.ajax ) {
			mc_setup_handlers();
		} else {
			mc_render_links();
		}
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

	function mc_render_links() {
		const buttons = document.querySelectorAll( '.mc-main button.mc-navigation-button' );
		buttons.forEach( (el) => {
			let link = document.createElement( 'a' );
			link.setAttribute( 'id', el.getAttribute( 'id' ) );
			link.setAttribute( 'href', el.getAttribute( 'data-href' ) );
			classes = el.getAttribute( 'class' ) ?? '';
			link.setAttribute( 'class', classes );
			link.setAttribute( 'rel', 'nofollow' );
			link.classList.remove( 'mc-navigation-button' );
			link.classList.add( 'mc-navigation-link' );
			link.innerHTML = el.innerHTML;
			el.replaceWith( link );
		});
	}

	function mc_disclosures() {
		const hasPopup = document.querySelectorAll( 'button.has-popup' );
		if ( hasPopup ) {
			hasPopup.forEach( (el) => {
				let controlId = el.getAttribute( 'aria-controls' );
				let controlled = document.getElementById( controlId );
				//$( this ).append( '<span class="dashicons dashicons-plus" aria-hidden="true">' );
				controlled.style.display = 'none';
				el.addEventListener( 'click', function() {
					let visible = controlled.checkVisibility();
					let position = el.offsetWidth + 8;
					controlled.style.left = position + 'px';
					if ( visible ) {
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

	function mc_build_toggles( targetId ) {
		let exportButton, subscribeButton;
		if ( targetId ) {
			const subscribe = document.querySelector( '#' + targetId + ' .mc-subscribe' );
			const exports   = document.querySelector( '#' + targetId + ' .mc-download' );
			if ( null !== subscribe ) {
				subscribeButton = subscribe.querySelector( 'button' );
				if ( null === subscribeButton ) {
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
			}
			if ( null !== exports ) {
				exportButton = exports.querySelector( 'button' );
				if ( null === exportButton ) {
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
			}
			if ( null === exportButton || null === subscribeButton ) {
				const toggles = document.querySelectorAll( '#' + targetId + ' .mc-export button' );
				let icon;
				toggles.forEach( (el) => {
					el.addEventListener( 'click', function() {
						let controlled = el.getAttribute( 'aria-controls' );
						let target     = document.getElementById( controlled );
						if ( target ) {
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
						}
					});
				});
			}
		}
	}

	function my_calendar_external_links() {
		let external_links = document.querySelectorAll('.mc-main a[target=_blank]');
		external_links.forEach( (el) => {
			el.classList.add( 'mc-opens-in-new-tab' );
			el.insertAdjacentHTML( 'beforeend', ' <span class="dashicons dashicons-external" aria-hidden="true"></span><span class="screen-reader-text"> ' + my_calendar.newWindow + '</span>' );
		});
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

})();