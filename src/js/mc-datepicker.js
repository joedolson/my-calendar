window.customElements.whenDefined( 'duet-date-picker' ).then(() => {
	elem = document.querySelectorAll('.duet-fallback');
	elem.forEach((el) => {
		el.parentNode.removeChild(el);
	});
});

const pickers = Array.prototype.slice.apply( document.querySelectorAll( 'duet-date-picker' ) );

pickers.forEach((picker) => {
	picker.localization = duetLocalization;
});

const eventBegin     = document.querySelector( 'duet-date-picker[identifier=mc_event_date]' );
const eventRecur     = document.querySelector( 'duet-date-picker[identifier=r_begin]' );
const eventEnd       = document.querySelector( 'duet-date-picker[identifier=mc_event_enddate]' );
const eventDateError = document.querySelector( '#event_date_error' );
const submitButton   = document.querySelector( '#my-calendar .button-primary' );

var startDate = false;
var endDate   = false;

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


eventBegin.addEventListener( 'duetChange', function(e) {
	startDate = e.detail.value;
	endValue  = document.querySelector( 'input[name="event_end[]"]' ).value;
	endDate   = document.querySelector( '[identifier="mc_event_enddate"]' );

	/* Handle main date picker. */
	if ( '' == endValue || startDate > endValue ) {
		endDate.value = e.detail.value;
	}

	myCalendarTestDates( endDate, startDate );
});

eventEnd.addEventListener( 'duetChange', function(e) {
	endDate   = e.detail.value;
	startDate = document.querySelector( 'input[name="event_begin[]"]' ).value;

	myCalendarTestDates( endDate, startDate );
});

function myCalendarTestDates( endDate, startDate ) {
	if ( ! ( endDate && startDate ) ) {
		return;
	}
	endDate = new Date( endDate );
	startDate = new Date( startDate );
	if ( new Date( endDate ) < startDate ) {
		eventDateError.classList.add( 'visible' );
		submitButton.disabled = true;
		console.log( 'Your end date is before your start date: ', endDate + ' ' + startDate );
	} else {
		eventDateError.classList.remove( 'visible' );
		submitButton.disabled = false;
	}
}
