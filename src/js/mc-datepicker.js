window.addEventListener('load', function() {
	mcLoadPickers();
});

window.customElements.whenDefined( 'duet-date-picker' ).then(() => {
	elem = document.querySelectorAll('.duet-fallback');
	elem.forEach((el) => {
		el.parentNode.removeChild(el);
	});
});

function mcLoadPickers() {
	const pickers = Array.prototype.slice.apply( document.querySelectorAll( 'duet-date-picker' ) );

	pickers.forEach((picker) => {
		const DATE_FORMAT_US = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/
		let fullMonth;
		picker.dateAdapter = {
			parse(value = "", createDate) {
			const matches = value.match(DATE_FORMAT_US)
				if (matches) {
					return createDate(matches[3], matches[1], matches[2])
				}
			},
			format(date) {
				switch ( duetFormats.date ) {
					case 'Y-m-d':
						return `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`
					case 'm/d/Y':
						return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`
					case 'd-m-Y':
						return `${date.getDate()}-${date.getMonth() + 1}-${date.getFullYear()}`
					case 'j F Y':
						fullMonth = Intl.DateTimeFormat( 'en-US', { month: 'long' } ).format( date );
						return `${date.getDate()} ${fullMonth} ${date.getFullYear()}`
					case 'M j, Y':
						fullMonth = Intl.DateTimeFormat( 'en-US', { month: 'short' } ).format( date );
						return `${date.getDate()} ${fullMonth}, ${date.getFullYear()}`
					default:
						return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
				}
			},
		}
		picker.localization = duetLocalization;
	});

	const eventBegin     = document.querySelector( 'duet-date-picker[identifier=mc_event_date]' );
	const eventRecur     = document.querySelector( 'duet-date-picker[identifier=r_begin]' );
	const eventEnd       = document.querySelector( 'duet-date-picker[identifier=mc_event_enddate]' );
	const eventEndEl     = document.querySelector( 'input[name="event_end[]"]' );

	if ( null !== eventBegin ) {
		if ( null !== eventRecur ) {
			eventRecur.addEventListener( 'duetChange', function(e) {
				let startDate = e.detail.value;
				let endDate    = eventEndEl.value;
				let recurValue = document.querySelector( 'input[name="recur_end[]"' ).value;
				let recurEnd   = document.querySelector( '[identifier="r_end"]' );
				/* Handle adding occurrences */
				if ( ( '' !== recurValue ) && startDate > recurValue ) {
					recurEnd.value = e.detail.value;
				}

				myCalendarTestDates( endDate, startDate );
			});
		}

		eventBegin.addEventListener( 'duetChange', duetBeginUpdate );
		eventEnd.addEventListener( 'duetChange', duetEndUpdate );

	}
}

function duetEndUpdate(e) {
	const eventBeginEl = document.querySelector( 'input[name="event_begin[]"]' );
	const recurrences  = document.querySelector( '.disable-recurrences' );

	let endDate   = e.detail.value;
	let startDate = eventBeginEl.value;

	if ( null !== recurrences ) {
		const fieldset = recurrences.querySelector( 'fieldset' );
		const icon     = recurrences.querySelector( '.dashicons' );
		const inputs   = recurrences.querySelectorAll( 'fieldset input, fieldset select, fieldset duet-date-picker' );

		fieldset.setAttribute( 'style', '' );
		recurrences.querySelector( '.enable-repetition' ).setAttribute( 'aria-expanded', 'true' );
		icon.classList.add( 'dashicons-arrow-down' );
		icon.classList.remove( 'dashicons-arrow-right' );
		inputs.forEach((input) => {
			input.disabled = false;
		});
	}

	myCalendarTestDates( endDate, startDate );
}

function duetBeginUpdate(e) {
	const eventEndEl  = document.querySelector( 'input[name="event_end[]"]' );
	const eventEnd    = document.querySelector( 'duet-date-picker[identifier=mc_event_enddate]' );
	const recurrences = document.querySelector( '.disable-recurrences' );

	let startDate = e.detail.value;
	let endDate  = eventEndEl.value;
	/* Handle main date picker. */
	if ( '' == endDate || startDate > endDate ) {
		eventEnd.value = e.detail.value;
		endDate        = e.detail.value;
	}

	if ( null !== recurrences ) {
		const fieldset = recurrences.querySelector( 'fieldset' );
		const icon     = recurrences.querySelector( '.dashicons' );
		const inputs   = recurrences.querySelectorAll( 'fieldset input, fieldset select, fieldset duet-date-picker' );

		fieldset.setAttribute( 'style', '' );
		recurrences.querySelector( '.enable-repetition' ).setAttribute( 'aria-expanded', 'true' );
		icon.classList.add( 'dashicons-arrow-down' );
		icon.classList.remove( 'dashicons-arrow-right' );
		inputs.forEach((input) => {
			input.disabled = false;
		});
	}

	myCalendarTestDates( endDate, startDate );
}

/**
 * Disable the submit button if the end date is before the start date. Disable recurring options if length of event is greater than the recur period.
 *
 * @param {string} endDate Date string from end date field.
 * @param {string} startDate Date string from start date field.
 */
function myCalendarTestDates( end, start ) {
	const eventDateError = document.querySelector( '#event_date_error' );
	const submitButton   = document.querySelector( '#my-calendar .button-primary' );
	const recurOptions   = document.querySelectorAll( '#e_recur option' );

	if ( ! ( end && start ) ) {
		return;
	}
	let endDate   = new Date( end );
	let startDate = new Date( start );
	let dateDiff  = ( endDate - startDate ) / 1000;
	if ( recurOptions ) {
		recurOptions.forEach((el) =>{
			let period = el.getAttribute( 'data-period' );
			if ( period < dateDiff ) {
				el.setAttribute( 'disabled', 'true' );
			} else {
				el.removeAttribute( 'disabled' );
			}
		});
	}
	if ( new Date( endDate ) < startDate ) {
		eventDateError.classList.add( 'visible' );
		submitButton.disabled = true;
	} else {
		eventDateError.classList.remove( 'visible' );
		submitButton.disabled = false;
	}
}