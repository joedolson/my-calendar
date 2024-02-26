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
		// following two lines will cause the browser to ask the user if they
		// want to leave. The text of this dialog is controlled by the browser.
		e.preventDefault(); //per the standard
		e.returnValue = ''; //required for Chrome
	}
	//else: user is allowed to leave without a warning dialog
});
