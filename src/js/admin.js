var clipboard = new ClipboardJS('.mc-copy-to-clipboard');
clipboard.on( 'success', function(e) {
	var parent   = e.trigger.parentNode;
	var response = parent.querySelector( '.mc-notice-copied' );
	var text     = response.textContent;
	wp.a11y.speak( text );
	response.classList.add( 'visible' );
});

window.addEventListener( 'beforeunload', function(e) {
	var unsubmitted = document.getElementById( 'mc_unsubmitted' );
	var hold        = ( typeof( unsubmitted ) != 'undefined' && unsubmitted != null ) ? true : false;
	if ( hold ) {
		// following two lines will cause the browser to ask the user if they
		// want to leave. The text of this dialog is controlled by the browser.
		e.preventDefault(); //per the standard
		e.returnValue = ''; //required for Chrome
	}
	//else: user is allowed to leave without a warning dialog
});
