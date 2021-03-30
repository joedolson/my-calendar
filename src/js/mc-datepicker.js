const pickers = Array.prototype.slice.apply( document.querySelectorAll( 'duet-date-picker' ) );

pickers.forEach((picker) => {
	picker.localization = duetLocalization;
});

const eventBegin = document.querySelector( 'duet-date-picker[identifier=mc_event_date]' );
const eventEnd = document.querySelector( 'duet-date-picker[identifier=mc_event_enddate]' );

var startDate = false;
var endDate = false;

eventBegin.addEventListener( 'duetChange', function(e) {
	startDate = e.detail.valueAsDate;
	endDate   = document.querySelector( 'input[name="event_end[]"]' ).value;
	console.log( 'start date', e.detail.value + ' ' + endDate );

	if ( new Date( endDate ) < startDate ) {
		console.log( 'Your end date is before your start date: ', endDate + ' ' + startDate );
	}
});

eventEnd.addEventListener( 'duetChange', function(e) {
	endDate   = e.detail.valueAsDate;
	startDate = document.querySelector( 'input[name="event_begin[]"]' ).value;

	console.log( 'end date', e.detail.value + ' ' + startDate );

	if ( new Date( startDate ) > endDate ) {
		console.log( 'Your end date is before your start date: ', endDate + ' ' + startDate );
	}
});