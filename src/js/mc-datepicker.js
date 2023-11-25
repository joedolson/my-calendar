window.customElements.whenDefined( 'duet-date-picker' ).then(() => {
	elem = document.querySelectorAll('.duet-fallback');
	elem.forEach((el) => {
		el.parentNode.removeChild(el);
	});
});

const pickers = Array.prototype.slice.apply( document.querySelectorAll( 'duet-date-picker' ) );

pickers.forEach((picker) => {
	const DATE_FORMAT_US = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/
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
					var fullMonth = Intl.DateTimeFormat( 'en-US', { month: 'long' } ).format( date );
					return `${date.getDate()} ${fullMonth} ${date.getFullYear()}`
				case 'M j, Y':
					var fullMonth = Intl.DateTimeFormat( 'en-US', { month: 'short' } ).format( date );
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
const eventDateError = document.querySelector( '#event_date_error' );
const submitButton   = document.querySelector( '#my-calendar .button-primary' );

var startDate   = false;
var endDate     = false;
var recurrences = document.querySelector( '.disable-recurrences' );

if ( null !== eventRecur ) {
	eventRecur.addEventListener( 'duetChange', function(e) {
		startDate = e.detail.value;
		recurValue = document.querySelector( 'input[name="recur_end[]"' ).value;
		recurEnd   = document.querySelector( '[identifier="r_end"]' );
		/* Handle adding occurrences */
		if ( ( '' !== recurValue ) && startDate > recurValue ) {
			recurEnd.value = e.detail.value;
		}

		myCalendarTestDates( endDate, startDate );
	});
}

eventBegin.addEventListener( 'duetChange', function(e) {
	startDate = e.detail.value;
	endValue  = document.querySelector( 'input[name="event_end[]"]' ).value;
	endDate   = document.querySelector( '[identifier="mc_event_enddate"]' );

	/* Handle main date picker. */
	if ( '' == endValue || startDate > endValue ) {
		endDate.value = e.detail.value;
	}

	if ( null !== recurrences ) {
		var fieldset = recurrences.querySelector( 'fieldset' );
		fieldset.setAttribute( 'style', '' );
		recurrences.querySelector( '.enable-repetition' ).setAttribute( 'aria-expanded', 'true' );
		var icon = recurrences.querySelector( '.dashicons' );
		icon.classList.add( 'dashicons-arrow-down' );
		icon.classList.remove( 'dashicons-arrow-right' );
		var inputs = recurrences.querySelectorAll( 'fieldset input, fieldset select, fieldset duet-date-picker' );
		inputs.forEach((input) => {
			input.disabled = false;
		});
	}

	myCalendarTestDates( endDate, startDate );
});

eventEnd.addEventListener( 'duetChange', function(e) {
	endDate   = e.detail.value;
	startDate = document.querySelector( 'input[name="event_begin[]"]' ).value;

	if ( null !== recurrences ) {
		var fieldset = recurrences.querySelector( 'fieldset' );
		fieldset.setAttribute( 'style', '' );
		recurrences.querySelector( '.enable-repetition' ).setAttribute( 'aria-expanded', 'true' );
		var icon = recurrences.querySelector( '.dashicons' );
		icon.classList.add( 'dashicons-arrow-down' );
		icon.classList.remove( 'dashicons-arrow-right' );
		var inputs = recurrences.querySelectorAll( 'fieldset input, fieldset select, fieldset duet-date-picker' );
		inputs.forEach((input) => {
			input.disabled = false;
		});
	}

	myCalendarTestDates( endDate, startDate );
});

/**
 * Disable the submit button if the end date is before the start date. Disable recurring options if length of event is greater than the recur period.
 *
 * @param {string} endDate Date string from end date field.
 * @param {string} startDate Date string from start date field.
 */
function myCalendarTestDates( endDate, startDate ) {
	if ( ! ( endDate && startDate ) ) {
		return;
	}
	endDate   = new Date( endDate );
	startDate = new Date( startDate );
	var dateDiff = ( endDate - startDate ) / 1000;
	var recurOptions = document.querySelectorAll( '#e_recur option' );
	if ( recurOptions ) {
		recurOptions.forEach((el) =>{
			var period = el.getAttribute( 'data-period' );
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
		console.log( 'Your end date is before your start date: ', endDate + ' ' + startDate );
	} else {
		eventDateError.classList.remove( 'visible' );
		submitButton.disabled = false;
	}
}
