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

const eventBegin = document.querySelector( 'duet-date-picker[identifier=mc_event_date]' );
const eventEnd = document.querySelector( 'duet-date-picker[identifier=mc_event_enddate]' );
const eventDateError = document.querySelector( '#event_date_error' );
const submitButton   = document.querySelector( '#my-calendar .button-primary' );

var startDate = false;
var endDate = false;

eventBegin.addEventListener( 'duetChange', function(e) {
	startDate = e.detail.value;
	endDate   = document.querySelector( 'input[name="event_end[]"]' ).value;

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
