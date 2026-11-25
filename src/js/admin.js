const clipboard = new ClipboardJS('.mc-copy-to-clipboard');
clipboard.on( 'success', function(e) {
	let parent   = e.trigger.parentNode;
	let response = parent.querySelector( '.mc-notice-copied' );
	let text     = response.textContent;
	wp.a11y.speak( text );
	response.classList.add( 'visible' );
});

if ( typeof window.mcTemplateConversion !== 'undefined' ) {
	const conversion = window.mcTemplateConversion;
	let conversionId = 0;

	document.addEventListener( 'click', function(e) {
		const button = e.target.closest( '.mc-convert-template' );
		if ( ! button || button.getAttribute( 'aria-disabled' ) === 'true' ) {
			return;
		}

		const description = button.closest( 'td' ).previousElementSibling;
		const previousError = description.querySelector( '.mc-template-conversion-error' );
		if ( previousError ) {
			previousError.remove();
		}
		button.setAttribute( 'aria-disabled', 'true' );
		const request = new URLSearchParams( {
			action: 'mc_convert_template',
			key: button.getAttribute( 'data-action' ),
			nonce: conversion.nonce
		} );

		fetch( conversion.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: request.toString()
		} )
			.then( function(response) {
				return response.json();
			} )
			.then( function(response) {
				if ( ! response.success ) {
					throw new Error( response.data && response.data.message ? response.data.message : conversion.failed );
				}
				const result = response.data;
				const existing = description.querySelector( '.mc-template-conversion' );
				if ( existing ) {
					existing.remove();
				}
				const container = document.createElement( 'div' );
				container.className = 'mc-template-conversion';
				const textarea = document.createElement( 'textarea' );
				textarea.id = 'mc-converted-template-' + ++conversionId;
				textarea.className = 'large-text readonly';
				textarea.readOnly = true;
				textarea.rows = 10;
				textarea.value = result.converted;
				const label = document.createElement( 'label' );
				label.htmlFor = textarea.id;
				label.textContent = result.label;
				container.append( label, textarea );

				const copyContainer = document.createElement( 'div' );
				copyContainer.className = 'mc-copy-button';
				const copyButton = document.createElement( 'button' );
				copyButton.type = 'button';
				copyButton.className = 'button button-primary mc-copy-to-clipboard';
				copyButton.setAttribute( 'data-clipboard-target', '#' + textarea.id );
				copyButton.textContent = conversion.copy;
				const copied = document.createElement( 'span' );
				copied.className = 'mc-notice-copied';
				copied.textContent = conversion.copied;
				copyContainer.append( copyButton, copied );
				container.append( copyContainer );

				if ( result.discarded.length ) {
					const heading = document.createElement( 'h3' );
					heading.textContent = conversion.discarded;
					const list = document.createElement( 'ul' );
					result.discarded.forEach( function(piece) {
						const item = document.createElement( 'li' );
						const code = document.createElement( 'code' );
						code.textContent = '{' + piece.tag + '}';
						item.appendChild( code );
						item.appendChild( document.createTextNode( ' ' + piece.attribute + '="' + piece.value + '"' ) );
						list.appendChild( item );
					} );
					container.append( heading, list );
				}
				description.appendChild( container );
				wp.a11y.speak( conversion.complete );
			} )
			.catch( function(error) {
				const message = document.createElement( 'p' );
				message.className = 'mc-template-conversion-error';
				message.setAttribute( 'role', 'alert' );
				message.textContent = error.message || conversion.failed;
				description.appendChild( message );
			} )
			.finally( function() {
				button.removeAttribute( 'aria-disabled' );
			} );
	} );
}

window.addEventListener( 'beforeunload', function(e) {
	let unsubmitted = document.getElementById( 'mc_unsubmitted' );
	let hold        = ( typeof( unsubmitted ) != 'undefined' && unsubmitted != null ) ? true : false;
	if ( hold ) {
		// Prompt to check whether user wants to leave.
		e.preventDefault();
	}
});

// Toggles which upcoming event input fields are visible in shortcode builder
// based on the selected type.
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