const clipboard = new ClipboardJS('.mc-copy-to-clipboard');
clipboard.on( 'success', function(e) {
	let parent   = e.trigger.parentNode;
	let response = parent.querySelector( '.mc-notice-copied' );
	let text     = response.textContent;
	wp.a11y.speak( text );
	response.classList.add( 'visible' );
});

window.addEventListener( 'beforeunload', function(e) {
	let unsubmitted = document.getElementById( 'mc_unsubmitted' );
	let hold        = ( typeof( unsubmitted ) != 'undefined' && unsubmitted != null ) ? true : false;
	if ( hold ) {
		// Prompt to check whether user wants to leave.
		e.preventDefault();
	}
});

let typeSelector = document.getElementById( 'typeupcoming' );
let labels;
if ( typeSelector ) {
	let dayLabels = document.querySelectorAll( 'label.days' );
	let eventLabels = document.querySelectorAll( 'label.events' );
	let inputs = document.querySelectorAll( '.before-input, .after-input' );
	dayLabels.forEach( (el) => { el.style.display = 'none' } );
	typeSelector.addEventListener( 'change', function(e) {
		let value = typeSelector.value;
		if ( value === 'event' ) {
			dayLabels.forEach( (el) => { el.style.display = 'none' } );
			eventLabels.forEach( (el) => { el.style.display = 'block' } );
		}
		if ( value === 'days' ) {
			eventLabels.forEach( (el) => { el.style.display = 'none' } );
			dayLabels.forEach( (el) => { el.style.display = 'block' } );
		}
		if ( 'days' !== value && 'event' !== value ) {
			inputs.forEach( (el) => { el.style.display = 'none' } );
		} else {
			inputs.forEach( (el) => { el.style.display = 'block' } );
		}
	});
}